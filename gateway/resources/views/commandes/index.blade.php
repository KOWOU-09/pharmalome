@extends('layouts.app')

@section('title', 'Mes commandes - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/commandes.css') }}">
@endpush

@section('content')
<section class="section">
    <div class="container">
        @if(session('clear_cart'))
            <span data-clear-cart hidden></span>
        @endif
        <div class="page-heading">
            <p class="eyebrow">Espace client</p>
            <h1 class="section-title">Mes commandes</h1>
            <p class="muted">Retrouvez l’historique et l’état de vos achats.</p>
        </div>
        @if($ordersUnavailable)
            <div class="flash-message flash-error" role="status">Le service de commandes est momentanément indisponible. Réessayez plus tard.</div>
        @elseif($orders)
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Commande</th><th>Date</th><th>Total</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            @php
                                $statusLabels = [
                                    'en_attente' => 'En attente',
                                    'ordonnance_a_valider' => 'Ordonnance à valider',
                                    'validee' => 'Validée',
                                    'livree' => 'Livrée',
                                    'retiree' => 'Retirée',
                                    'refusee' => 'Refusée',
                                    'annulee' => 'Annulée',
                                ];
                                $status = $order['statut'] ?? 'inconnue';
                            @endphp
                            <tr>
                                <td>#{{ $order['id'] }}</td>
                                <td>{{ isset($order['created_at']) ? \Illuminate\Support\Carbon::parse($order['created_at'])->format('d/m/Y à H:i') : '—' }}</td>
                                <td>{{ number_format((int) ($order['total'] ?? 0), 0, ',', ' ') }} FCFA</td>
                                <td><span class="status {{ in_array($status, ['validee', 'livree', 'retiree'], true) ? 'active' : ($status === 'refusee' || $status === 'annulee' ? 'alert' : 'waiting') }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
                <h2>Aucune commande pour le moment</h2>
                <p class="muted">Vos commandes confirmées apparaîtront ici.</p>
                <a class="btn btn-primary" href="{{ route('catalogue') }}">Découvrir les produits</a>
            </div>
        @endif
    </div>
</section>
@endsection
