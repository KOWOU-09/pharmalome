@extends('layouts.app')

@section('title', 'Gestion des commandes - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<section class="section">
    <div class="container">
        <div class="page-heading">
            <p class="eyebrow">Administration</p>
            <h1 class="section-title">Gestion des commandes</h1>
            <p class="muted">Consultez les commandes, mettez à jour leur progression et affectez les missions de livraison.</p>
        </div>
        @if($couriersUnavailable)
            <div class="flash-message flash-error" role="status">La liste des livreurs actifs est indisponible. Les affectations ne peuvent pas être modifiées.</div>
        @endif
        @if($serviceUnavailable)
            <div class="flash-message flash-error" role="status">Le service commandes est indisponible. Les commandes n’ont pas pu être chargées.</div>
        @elseif($orders)
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Commande</th><th>Client</th><th>Total</th><th>Statut</th><th>Mise à jour</th><th>Livreur</th></tr></thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>#{{ $order['id'] }}<small class="table-subtext">{{ isset($order['created_at']) ? \Illuminate\Support\Carbon::parse($order['created_at'])->format('d/m/Y H:i') : '—' }}</small></td>
                                <td>#{{ $order['user_id'] }}</td>
                                <td>{{ number_format((int) ($order['total'] ?? 0), 0, ',', ' ') }} FCFA</td>
                                <td><span class="status {{ in_array($order['statut'] ?? '', ['livree', 'retiree'], true) ? 'active' : 'waiting' }}">{{ ucfirst(str_replace('_', ' ', $order['statut'] ?? '')) }}</span></td>
                                <td>
                                    <form class="order-status-form" method="POST" action="{{ route('admin.commandes.statut', ['id' => $order['id']]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="statut-{{ $order['id'] }}">Statut de la commande {{ $order['id'] }}</label>
                                        <select class="select" id="statut-{{ $order['id'] }}" name="statut" required>
                                            @foreach(['en_attente' => 'En attente', 'ordonnance_a_valider' => 'Ordonnance à valider', 'validee' => 'Validée', 'en_preparation' => 'En préparation', 'prete' => 'Prête', 'en_livraison' => 'En livraison', 'livree' => 'Livrée', 'retiree' => 'Retirée', 'refusee' => 'Refusée', 'annulee' => 'Annulée'] as $value => $label)
                                                <option value="{{ $value }}" @selected(($order['statut'] ?? '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-outline" type="submit">Enregistrer</button>
                                    </form>
                                </td>
                                <td>
                                    @if(($order['mode_reception'] ?? '') === 'livraison' && ($order['statut'] ?? '') === 'en_livraison')
                                        <form method="POST" action="{{ route('admin.commandes.livreur', ['id' => $order['id']]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <label class="sr-only" for="livreur-{{ $order['id'] }}">Livreur de la commande {{ $order['id'] }}</label>
                                            <select class="select" id="livreur-{{ $order['id'] }}" name="livreur_id" @disabled($couriersUnavailable) required>
                                                <option value="">Choisir un livreur</option>
                                                @foreach($couriers as $courier)
                                                    <option value="{{ $courier['id'] }}" @selected(($order['livreur_id'] ?? null) == $courier['id'])>{{ $courier['name'] }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-outline" type="submit" @disabled($couriersUnavailable)>Affecter</button>
                                        </form>
                                    @elseif($order['livreur_id'] ?? null)
                                        Livreur #{{ $order['livreur_id'] }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state"><span class="material-symbols-rounded" aria-hidden="true">receipt_long</span><h2>Aucune commande à afficher</h2></div>
        @endif
    </div>
</section>
@endsection
