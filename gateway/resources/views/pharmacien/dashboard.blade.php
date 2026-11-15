@extends('layouts.app')

@section('title', 'Tableau de bord pharmacien - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
<section class="section dashboard-shell">
    <div class="container">
        <div class="page-heading">
            <p class="eyebrow">Espace professionnel</p>
            <h1 class="section-title">Tableau de bord pharmacien</h1>
            <p class="muted">Suivez l’activité et accédez rapidement à vos tâches.</p>
        </div>

        @if($serviceUnavailable)
            <div class="flash-message flash-error" role="status">Une ou plusieurs données ne sont pas disponibles. Vérifiez que les services catalogue et commandes sont démarrés.</div>
        @endif
        <div class="stats-grid">
            <article class="stat-box"><span>Commandes enregistrées</span><strong>{{ $stats['commandes'] ?? '—' }}</strong></article>
            <article class="stat-box"><span>Ordonnances à traiter</span><strong>{{ $stats['ordonnances'] ?? '—' }}</strong></article>
            <article class="stat-box"><span>Alertes catalogue</span><strong>{{ $stats['alertes'] ?? '—' }}</strong></article>
            <article class="stat-box"><span>Commandes en attente</span><strong>{{ $stats['en_attente'] ?? '—' }}</strong></article>
        </div>

        <div class="staff-dashboard-grid">
            <section class="chart-box">
                <div class="panel-heading">
                    <div><h2>Commandes récentes</h2><p class="muted">Données du service de commandes</p></div>
                    <a class="btn btn-outline" href="{{ route('commandes') }}">Voir mes commandes</a>
                </div>
                @if($recentOrders)
                    <div class="recent-order-list">
                    @foreach($recentOrders as $order)
                        <article>
                            <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
                            <div><strong>Commande #{{ $order['id'] }}</strong><small>Client #{{ $order['user_id'] ?? '—' }} · {{ ucfirst(str_replace('_', ' ', $order['statut'] ?? '')) }}</small></div>
                            <strong>{{ number_format((int) ($order['total'] ?? 0), 0, ',', ' ') }} FCFA</strong>
                        </article>
                    @endforeach
                    </div>
                @else
                    <p class="muted">Aucune commande récente à afficher.</p>
                @endif
            </section>

            <section class="chart-box">
                <h2>Accès rapides</h2>
                <div class="staff-shortcuts">
                    <a href="{{ route('pharmacien.ordonnances') }}"><span class="material-symbols-rounded" aria-hidden="true">description</span> Traiter les ordonnances</a>
                    <a href="{{ route('pharmacien.stock') }}"><span class="material-symbols-rounded" aria-hidden="true">inventory_2</span> Gérer le stock</a>
                    <a href="{{ route('catalogue') }}"><span class="material-symbols-rounded" aria-hidden="true">medication</span> Consulter le catalogue</a>
                </div>
            </section>
        </div>
    </div>
</section>
@endsection
