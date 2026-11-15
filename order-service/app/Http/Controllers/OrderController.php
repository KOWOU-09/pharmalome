<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Notification;
use App\Models\Ordonnance;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Commande::query();
        $userId = $request->user()?->id ?? $request->integer('user_id');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $commandes = $query->latest()->paginate(10);

        return response()->json([
            'data' => $commandes,
            'message' => 'Commandes récupérées.',
        ]);
    }

    public function show(Commande $commande)
    {
        return response()->json([
            'data' => $commande->load(['lignes', 'ordonnance', 'paiement']),
        ]);
    }

    public function store(Request $request)
    {
        if (is_string($request->input('items'))) {
            $items = json_decode($request->input('items'), true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($items)) {
                return response()->json(['message' => 'Le format des articles est invalide.'], 422);
            }
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'mode_reception' => ['required', 'in:retrait,livraison'],
            'adresse_livraison' => ['required_if:mode_reception,livraison', 'nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.nom' => ['required', 'string', 'max:255'],
            'items.*.prix' => ['required', 'integer', 'min:1'],
            'items.*.quantite' => ['required', 'integer', 'min:1', 'max:99'],
            'ordonnance' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $commande = Commande::create([
            'user_id' => $validated['user_id'],
            'statut' => 'en_attente',
            'mode_reception' => $validated['mode_reception'],
            'adresse_livraison' => $validated['adresse_livraison'] ?? null,
            'sous_total' => 0,
            'frais_livraison' => $validated['mode_reception'] === 'livraison' ? 1000 : 0,
            'total' => 0,
        ]);

        $sousTotal = 0;

        foreach ($validated['items'] as $item) {
            $commande->lignes()->create([
                'medicament_id' => $item['id'],
                'nom_medicament' => $item['nom'] ?? 'Produit',
                'prix_unitaire' => (int) ($item['prix'] ?? 0),
                'quantite' => (int) ($item['quantite'] ?? 1),
            ]);

            $sousTotal += ((int) ($item['prix'] ?? 0)) * ((int) ($item['quantite'] ?? 1));
        }

        $total = $sousTotal + $commande->frais_livraison;
        $commande->update([
            'sous_total' => $sousTotal,
            'total' => $total,
            'statut' => count($validated['items']) > 0 && $validated['ordonnance'] ? 'ordonnance_a_valider' : 'validee',
        ]);

        if ($validated['ordonnance'] ?? null) {
            $path = $validated['ordonnance']->store('ordonnances', 'public');

            $commande->ordonnance()->create([
                'fichier' => $path,
                'statut' => 'en_attente',
            ]);
        }

        Notification::create([
            'user_id' => $commande->user_id,
            'message' => 'Votre commande a bien été enregistrée.',
            'lu' => false,
        ]);

        return response()->json([
            'message' => 'Commande enregistrée.',
            'data' => $commande->load(['lignes', 'ordonnance']),
        ], 201);
    }

    public function updateStatus(Request $request, Commande $commande)
    {
        $validated = $request->validate([
            'statut' => ['required', 'string'],
        ]);

        $commande->update(['statut' => $validated['statut']]);

        Notification::create([
            'user_id' => $commande->user_id,
            'message' => 'Le statut de votre commande a évolué : '.$validated['statut'],
            'lu' => false,
        ]);

        return response()->json([
            'message' => 'Statut mis à jour.',
            'data' => $commande->fresh(),
        ]);
    }

    public function assignCourier(Request $request, Commande $commande)
    {
        $validated = $request->validate([
            'livreur_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($commande->mode_reception !== 'livraison' || $commande->statut !== 'en_livraison') {
            return response()->json([
                'message' => 'Seules les commandes en cours de livraison peuvent être affectées.',
            ], 422);
        }

        $commande->update(['livreur_id' => $validated['livreur_id'] ?? null]);

        if ($commande->livreur_id !== null) {
            Notification::create([
                'user_id' => $commande->livreur_id,
                'message' => 'Une nouvelle mission de livraison vous a été attribuée.',
                'lu' => false,
            ]);
        }

        return response()->json([
            'message' => $commande->livreur_id === null
                ? 'Le livreur a été retiré de la commande.'
                : 'La mission de livraison a été attribuée.',
            'data' => $commande->fresh(),
        ]);
    }

    public function courierMissions(Request $request)
    {
        $courier = $this->authenticatedCourier($request);
        if ($courier instanceof JsonResponse) {
            return $courier;
        }

        $missions = Commande::query()
            ->where('livreur_id', $courier['id'])
            ->where('mode_reception', 'livraison')
            ->whereIn('statut', ['en_livraison', 'livree'])
            ->with('lignes')
            ->latest()
            ->get();

        return response()->json(['data' => $missions]);
    }

    public function completeMission(Request $request, Commande $commande)
    {
        $courier = $this->authenticatedCourier($request);
        if ($courier instanceof JsonResponse) {
            return $courier;
        }

        if ($commande->livreur_id !== (int) $courier['id']
            || $commande->mode_reception !== 'livraison'
            || $commande->statut !== 'en_livraison') {
            return response()->json([
                'message' => 'Cette mission ne vous est pas attribuée ou n’est plus en cours.',
            ], 403);
        }

        $commande->update(['statut' => 'livree']);

        Notification::create([
            'user_id' => $commande->user_id,
            'message' => 'Votre commande a été livrée.',
            'lu' => false,
        ]);

        return response()->json([
            'message' => 'La livraison a été marquée comme terminée.',
            'data' => $commande->fresh(),
        ]);
    }

    private function authenticatedCourier(Request $request): array|JsonResponse
    {
        $token = $request->bearerToken();
        if (! is_string($token) || $token === '') {
            return response()->json(['message' => 'Authentification requise.'], 401);
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(2)
                ->timeout(5)
                ->get(rtrim((string) config('services.user_service.url'), '/').'/api/me');
        } catch (ConnectionException $exception) {
            Log::error('Le service utilisateurs est injoignable lors de la validation d’un livreur.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Le service de compte est momentanément indisponible.'], 503);
        }

        $user = $response->json();
        if (! $response->successful() || ! is_array($user)) {
            return response()->json(['message' => 'Jeton utilisateur invalide ou expiré.'], 401);
        }

        if (($user['role'] ?? null) !== 'livreur' || ! ($user['actif'] ?? false)) {
            return response()->json(['message' => 'Accès réservé à un compte livreur actif.'], 403);
        }

        return ['id' => (int) $user['id']];
    }

    public function cancel(Commande $commande)
    {
        if (! in_array($commande->statut, ['en_attente', 'validee'])) {
            return response()->json(['message' => 'Cette commande ne peut pas être annulée.'], 422);
        }

        $commande->update(['statut' => 'annulee']);

        return response()->json([
            'message' => 'Commande annulée.',
            'data' => $commande->fresh(),
        ]);
    }

    public function ordonnances()
    {
        return response()->json([
            'data' => Ordonnance::with('commande')->get(),
            'message' => 'Ordonnances enregistrées.',
        ]);
    }

    public function ordonnanceShow(Ordonnance $ordonnance)
    {
        return response()->json([
            'data' => $ordonnance->load('commande'),
        ]);
    }

    public function showFile(Ordonnance $ordonnance)
    {
        if (! $ordonnance->fichier || ! Storage::disk('public')->exists($ordonnance->fichier)) {
            return response()->json(['message' => 'Fichier non trouvé.'], 404);
        }

        return Storage::disk('public')->response($ordonnance->fichier);
    }

    public function valider(Request $request, Ordonnance $ordonnance)
    {
        $ordonnance->update([
            'statut' => 'validee',
            'pharmacien_id' => $request->input('pharmacien_id', 1),
            'validee_le' => now(),
        ]);

        $ordonnance->commande->update(['statut' => 'validee']);

        return response()->json([
            'message' => 'Ordonnance validée.',
            'data' => $ordonnance->fresh(),
        ]);
    }

    public function refuser(Request $request, Ordonnance $ordonnance)
    {
        $validated = $request->validate([
            'motif_refus' => ['required', 'string', 'min:5'],
        ]);

        $ordonnance->update([
            'statut' => 'refusee',
            'motif_refus' => $validated['motif_refus'],
            'pharmacien_id' => $request->input('pharmacien_id', 1),
        ]);

        $ordonnance->commande->update(['statut' => 'refusee']);

        return response()->json([
            'message' => 'Ordonnance refusée.',
            'data' => $ordonnance->fresh(),
        ]);
    }

    public function paiement(Request $request, Commande $commande)
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:mobile_money,a_la_reception'],
            'montant' => ['required', 'integer', 'min:1'],
        ]);

        $paiement = $commande->paiement()->create([
            'mode' => $validated['mode'],
            'montant' => $validated['montant'],
            'statut' => 'payee',
            'reference' => 'PM-'.strtoupper(str()->random(8)),
        ]);

        return response()->json([
            'message' => 'Paiement enregistré.',
            'data' => $paiement,
        ], 201);
    }

    public function stats()
    {
        return response()->json([
            'data' => [
                'total_commandes' => Commande::count(),
                'commandes_en_attente' => Commande::where('statut', 'en_attente')->count(),
                'commandes_validees' => Commande::where('statut', 'validee')->count(),
                'commandes_livrees' => Commande::where('statut', 'livree')->count(),
            ],
        ]);
    }

    public function publicStats()
    {
        return response()->json([
            'commandes_livrees_ou_retirees' => Commande::query()
                ->whereIn('statut', ['livree', 'retiree'])
                ->count(),
            'ordonnances_validees' => Ordonnance::query()
                ->where('statut', 'validee')
                ->count(),
        ]);
    }

    public function notifications(Request $request)
    {
        $userId = $request->input('user_id', 1);

        return response()->json([
            'data' => Notification::where('user_id', $userId)->latest()->get(),
            'message' => 'Notifications utilisateur.',
        ]);
    }

    public function markNotificationsAsRead(Request $request)
    {
        $userId = $request->input('user_id', 1);
        Notification::where('user_id', $userId)->update(['lu' => true]);

        return response()->json(['message' => 'Notifications marquées comme lues.']);
    }
}
