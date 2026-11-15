@extends('layouts.app')

@section('title', 'Dashboard admin - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
<section class="section dashboard-shell">
    <div class="container">
        <div class="page-heading">
            <p class="eyebrow">Espace administration</p>
            <h1 class="section-title">Vue d’ensemble</h1>
            <p class="muted">Indicateurs agrégés depuis les services de la plateforme.</p>
        </div>

        <div class="stats-grid">
            <article class="stat-box"><span>Clients actifs</span><strong>{{ $stats['utilisateurs_inscrits'] === null ? 'Indisponible' : number_format($stats['utilisateurs_inscrits'], 0, ',', ' ') }}</strong></article>
            <article class="stat-box"><span>Produits au catalogue</span><strong>{{ $stats['medicaments_catalogue'] === null ? 'Indisponible' : number_format($stats['medicaments_catalogue'], 0, ',', ' ') }}</strong></article>
            <article class="stat-box"><span>Commandes livrées</span><strong>{{ $stats['commandes_livrees_ou_retirees'] === null ? 'Indisponible' : number_format($stats['commandes_livrees_ou_retirees'], 0, ',', ' ') }}</strong></article>
            <article class="stat-box"><span>Ordonnances validées</span><strong>{{ $stats['ordonnances_validees'] === null ? 'Indisponible' : number_format($stats['ordonnances_validees'], 0, ',', ' ') }}</strong></article>
        </div>

        <div class="chart-box">
            <h2>Accès rapide aux 5 espaces</h2>
            <div class="staff-shortcuts admin-shortcuts">
                <a href="{{ route('admin.dashboard') }}"><span class="material-symbols-rounded" aria-hidden="true">dashboard</span> Tableau de bord</a>
                <a href="{{ route('admin.catalogue') }}"><span class="material-symbols-rounded" aria-hidden="true">medication</span> Produits</a>
                <a href="{{ route('admin.utilisateurs') }}"><span class="material-symbols-rounded" aria-hidden="true">group</span> Gestion des utilisateurs</a>
                <a href="{{ route('admin.commandes') }}"><span class="material-symbols-rounded" aria-hidden="true">receipt_long</span> Gestion des commandes</a>
                <a href="{{ route('compte') }}"><span class="material-symbols-rounded" aria-hidden="true">manage_accounts</span> Mon compte administrateur</a>
            </div>
        </div>
    </div>
</section>
@endsection
