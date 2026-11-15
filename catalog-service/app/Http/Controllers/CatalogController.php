<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Medicament;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function publicStats()
    {
        return response()->json([
            'medicaments_catalogue' => Medicament::query()->count(),
        ]);
    }

    public function categories()
    {
        return response()->json([
            'data' => Category::withCount('medicaments')->get(),
            'message' => 'Catégories disponibles.',
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'libelle' => ['required', 'string', 'max:255', 'unique:categories,libelle'],
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Catégorie ajoutée.',
            'data' => $category,
        ], 201);
    }

    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'libelle' => ['required', 'string', 'max:255', 'unique:categories,libelle,'.$category->id],
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Catégorie mise à jour.',
            'data' => $category->fresh(),
        ]);
    }

    public function deleteCategory(Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'Catégorie supprimée.']);
    }

    public function index(Request $request)
    {
        $query = Medicament::query()->with('categorie');

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        if ($request->filled('categorie')) {
            $query->whereHas('categorie', fn ($category) => $category->where('libelle', $request->string('categorie')->trim()->value()));
        }

        if ($request->filled('ordonnance')) {
            $query->where('ordonnance_requise', (bool) $request->ordonnance);
        }

        if ($request->boolean('promotion')) {
            $query->where('promotion', true)->whereNotNull('prix_promotion');
        }

        if ($request->filled('prix_max') && is_numeric($request->query('prix_max'))) {
            $maxPrice = max(0, (int) $request->query('prix_max'));
            $query->where(function ($products) use ($maxPrice): void {
                $products->where(fn ($regular) => $regular->where('promotion', false)->where('prix', '<=', $maxPrice))
                    ->orWhere(fn ($sale) => $sale->where('promotion', true)->where('prix_promotion', '<=', $maxPrice));
            });
        }

        if ($request->filled('disponible')) {
            $query->where('quantite_stock', '>', 0);
        }

        $perPage = min(max($request->integer('per_page', 12), 1), 100);
        $medicaments = $query->orderBy('nom')->paginate($perPage);

        return response()->json([
            'data' => $medicaments,
        ]);
    }

    public function show(Medicament $medicament)
    {
        return response()->json(['data' => $medicament->load('categorie')]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'categorie_id' => ['required', 'exists:categories,id'],
            'nom' => ['required', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'laboratoire' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'prix' => ['required', 'integer', 'min:1'],
            'quantite_stock' => ['required', 'integer', 'min:0'],
            'seuil_alerte' => ['nullable', 'integer', 'min:0'],
            'date_peremption' => ['nullable', 'date'],
            'ordonnance_requise' => ['nullable', 'boolean'],
            'promotion' => ['nullable', 'boolean'],
            'prix_promotion' => ['nullable', 'required_if:promotion,1', 'integer', 'min:1', 'lt:prix'],
            'photo' => ['nullable', 'string'],
        ]);
        $validated['promotion'] = $request->boolean('promotion');
        if (! $validated['promotion']) {
            $validated['prix_promotion'] = null;
        }

        $medicament = Medicament::create($validated);

        return response()->json([
            'message' => 'Médicament ajouté.',
            'data' => $medicament,
        ], 201);
    }

    public function update(Request $request, Medicament $medicament)
    {
        $validated = $request->validate([
            'categorie_id' => ['nullable', 'exists:categories,id'],
            'nom' => ['nullable', 'string', 'max:255'],
            'prix' => ['nullable', 'integer', 'min:1'],
            'quantite_stock' => ['nullable', 'integer', 'min:0'],
            'seuil_alerte' => ['nullable', 'integer', 'min:0'],
            'ordonnance_requise' => ['nullable', 'boolean'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'laboratoire' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date_peremption' => ['nullable', 'date'],
            'promotion' => ['nullable', 'boolean'],
            'prix_promotion' => ['nullable', 'required_if:promotion,1', 'integer', 'min:1', 'lt:prix'],
        ]);
        $validated['promotion'] = $request->boolean('promotion');
        if (! $validated['promotion']) {
            $validated['prix_promotion'] = null;
        }

        $medicament->update($validated);

        return response()->json([
            'message' => 'Médicament mis à jour.',
            'data' => $medicament->fresh(),
        ]);
    }

    public function destroy(Medicament $medicament)
    {
        $medicament->delete();

        return response()->json(['message' => 'Médicament supprimé.']);
    }

    public function verifier(Request $request)
    {
        $items = $request->input('items', []);

        if (empty($items)) {
            return response()->json(['message' => 'Aucun article à vérifier.'], 422);
        }

        $result = [];

        foreach ($items as $item) {
            $medicament = Medicament::find($item['id'] ?? null);

            if (! $medicament) {
                $result[] = ['id' => $item['id'] ?? null, 'ok' => false, 'message' => 'Médicament introuvable.'];

                continue;
            }

            $quantite = (int) ($item['quantite'] ?? 0);
            $ok = $medicament->quantite_stock >= $quantite;

            $result[] = [
                'id' => $medicament->id,
                'nom' => $medicament->nom,
                'prix' => $medicament->promotion && $medicament->prix_promotion
                    ? $medicament->prix_promotion
                    : $medicament->prix,
                'ordonnance_requise' => $medicament->ordonnance_requise,
                'ok' => $ok,
                'message' => $ok ? 'Disponible.' : 'Stock insuffisant pour la quantité demandée.',
            ];
        }

        $hasError = collect($result)->contains(fn ($item) => ! $item['ok']);

        return response()->json([
            'ok' => ! $hasError,
            'data' => $result,
        ], $hasError ? 422 : 200);
    }

    public function diminuerStock(Request $request, Medicament $medicament)
    {
        $quantite = (int) $request->input('quantite', 1);

        if ($quantite < 1) {
            return response()->json(['message' => 'Quantité invalide.'], 422);
        }

        if ($medicament->quantite_stock < $quantite) {
            return response()->json(['message' => 'Stock insuffisant pour cette commande.'], 422);
        }

        $medicament->decrement('quantite_stock', $quantite);

        return response()->json([
            'message' => 'Stock diminué avec succès.',
            'data' => $medicament->fresh(),
        ]);
    }

    public function reapprovisionnerStock(Request $request, Medicament $medicament)
    {
        $quantite = (int) $request->input('quantite', 0);

        if ($quantite <= 0) {
            return response()->json(['message' => 'La quantité doit être positive.'], 422);
        }

        $medicament->increment('quantite_stock', $quantite);

        return response()->json([
            'message' => 'Stock réapprovisionné.',
            'data' => $medicament->fresh(),
        ]);
    }

    public function alertes()
    {
        $alertes = Medicament::whereColumn('quantite_stock', '<=', 'seuil_alerte')
            ->orWhere('date_peremption', '<=', now()->addDays(60))
            ->get();

        return response()->json(['data' => $alertes, 'message' => 'Alertes de stock et péremption.']);
    }
}
