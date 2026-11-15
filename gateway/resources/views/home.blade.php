@extends('layouts.app')

@section('title', 'MedExpress TOGO | Votre santé, notre priorité')
@section('meta_description', 'Achetez vos produits de santé auprès de pharmacies partenaires avec MedExpress TOGO, et faites-vous accompagner par notre équipe.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/accueil.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/accueil-stats.js') }}" defer></script>
    <script src="{{ asset('js/home.js') }}" defer></script>
@endpush

@section('content')
<div class="store-home">
    <aside class="home-sidebar" aria-label="Menu de la boutique">
        <nav class="home-side-nav">
            <a href="{{ route('home') }}" class="side-link is-active" aria-current="page">
                <span class="material-symbols-rounded" aria-hidden="true">home</span>
                <span>Accueil</span>
            </a>
            <a href="{{ route('catalogue') }}" class="side-link">
                <span class="material-symbols-rounded" aria-hidden="true">medication</span><span>Nos produits</span>
                <span class="side-chevron" aria-hidden="true">›</span>
            </a>
            <a href="{{ route('catalogue', ['ordonnance' => 1]) }}" class="side-link">
                <span class="material-symbols-rounded" aria-hidden="true">prescriptions</span><span>Ordonnances</span>
            </a>
            <a href="{{ route('catalogue', ['promotion' => 1]) }}" class="side-link">
                <span class="material-symbols-rounded" aria-hidden="true">sell</span>
                <span>Promotions</span>
            </a>
            <a href="{{ route('about') }}" class="side-link">
                <span class="material-symbols-rounded" aria-hidden="true">info</span><span>À propos</span>
            </a>
            <a href="{{ route('contact') }}" class="side-link">
                <span class="material-symbols-rounded" aria-hidden="true">call</span><span>Contact</span>
            </a>
        </nav>

        <section class="sidebar-help">
            <span class="help-icon"><span class="material-symbols-rounded" aria-hidden="true">support_agent</span></span>
            <h2>Besoin d’aide ?</h2>
            <p>Notre équipe est disponible pour vous accompagner.</p>
            <a class="help-button" href="{{ route('contact') }}">Nous contacter</a>
            <div class="lome-skyline" aria-hidden="true"><span>TOGO</span></div>
            <div class="sidebar-promise">
                <strong>MedExpress TOGO</strong>
                <span>Ensemble pour une vie plus saine</span>
            </div>
        </section>
    </aside>

    <div class="home-main">
        <section class="home-hero" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="hero-eyebrow">Votre pharmacie en ligne</p>
                <h1 id="hero-title"><span>MedExpress</span> TOGO</h1>
                <p class="hero-heading">Achetez vos médicaments<br class="desktop-break"> en toute confiance !</p>
                <p class="hero-points">Qualité <i></i> Prix abordables <i></i> Livraison rapide</p>
                <a class="hero-cta" href="{{ route('catalogue') }}">
                    <span class="material-symbols-rounded" aria-hidden="true">shopping_bag</span>
                    Commander maintenant <span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
                </a>
            </div>
            <div class="hero-promise">
                <p>Votre santé,<br>notre priorité</p>
            </div>
            <span class="hero-leaf hero-leaf-one" aria-hidden="true"></span>
            <span class="hero-leaf hero-leaf-two" aria-hidden="true"></span>
        </section>

        <section class="benefits-bar" aria-label="Nos engagements">
            <article class="benefit-item">
                <span class="material-symbols-rounded" aria-hidden="true">verified_user</span>
                <span><strong>Validation accompagnée</strong><small>Par nos pharmaciens</small></span>
            </article>
            <article class="benefit-item">
                <span class="material-symbols-rounded" aria-hidden="true">local_shipping</span>
                <span><strong>Livraison rapide</strong><small>Partout au Togo</small></span>
            </article>
            <article class="benefit-item">
                <span class="material-symbols-rounded" aria-hidden="true">verified</span>
                <span><strong>Produits certifiés</strong><small>Et de qualité</small></span>
            </article>
            <article class="benefit-item">
                <span class="material-symbols-rounded" aria-hidden="true">support_agent</span>
                <span><strong>Assistance 24h/24</strong><small>Et 7j/7</small></span>
            </article>
        </section>

        <section class="home-section categories-section" aria-labelledby="categories-title">
            <div class="home-section-heading">
                <h2 id="categories-title">Catégories populaires</h2>
                <a href="{{ route('catalogue') }}">Voir toutes les catégories <span aria-hidden="true">→</span></a>
            </div>
            <div class="home-category-grid">
                @foreach($categories as $category)
                    <x-home.category-card :category="$category" />
                @endforeach
            </div>
            @if($catalogUnavailable)
                <p class="flash-message flash-error" role="status">Le catalogue est momentanément indisponible.</p>
            @elseif(! $categories)
                <p class="muted">Les catégories seront bientôt disponibles.</p>
            @endif
        </section>

        <section class="home-section popular-section" aria-labelledby="popular-title">
            <div class="home-section-heading">
                <h2 id="popular-title">Produits populaires</h2>
                <a class="mobile-only-link" href="{{ route('catalogue') }}">Tout voir <span aria-hidden="true">→</span></a>
            </div>
            <div class="home-products-layout">
                <div class="home-product-grid">
                    @foreach($products as $product)
                        <x-home.product-card :product="$product" />
                    @endforeach
                </div>
                @if($promotionProducts)
                <article class="home-promo" id="promotions">
                    <div class="promo-copy">
                        <span class="promo-kicker">Offres en cours</span>
                        <p>{{ count($promotionProducts) }} produit(s)</p>
                        <strong>Prix<br>réduits</strong>
                        <span>sur les produits<br>en promotion</span>
                        <a href="{{ route('catalogue', ['promotion' => 1]) }}">Profiter maintenant <span aria-hidden="true">→</span></a>
                    </div>
                </article>
                @else
                <article class="home-promo" id="promotions">
                    <div class="promo-copy">
                        <span class="promo-kicker">Promotions</span>
                        <p>Pas d’offre</p>
                        <strong>en cours</strong>
                        <span>Revenez bientôt<br>pour découvrir nos offres.</span>
                        @if(session('pharmalome_user.role') === 'admin')
                            <a href="{{ route('admin.catalogue') }}">Créer une promotion <span aria-hidden="true">→</span></a>
                        @endif
                    </div>
                </article>
                @endif
            </div>
        </section>

        <section class="home-section about-section" id="about">
            <div>
                <p class="section-kicker">MedExpress TOGO</p>
                <h2>Votre santé, notre priorité</h2>
                <p>Nous vous aidons à trouver des produits de santé auprès de pharmacies partenaires, avec des informations claires, un parcours pratique et un accompagnement humain.</p>
                <a class="text-link" href="{{ route('about') }}">En savoir plus <span aria-hidden="true">→</span></a>
            </div>
            <div class="about-stats">
                <h2>MedExpress TOGO en chiffres</h2>
                <div class="about-stats-grid">
                    <article><strong data-stat="utilisateurs_inscrits" data-value="{{ $stats['utilisateurs_inscrits']['value'] ?? '' }}" aria-live="polite">{{ $stats['utilisateurs_inscrits']['display'] ?? 'Indisponible' }}</strong><span>Clients actifs</span></article>
                    <article><strong data-stat="medicaments_catalogue" data-value="{{ $stats['medicaments_catalogue']['value'] ?? '' }}" aria-live="polite">{{ $stats['medicaments_catalogue']['display'] ?? 'Indisponible' }}</strong><span>Produits au catalogue</span></article>
                    <article><strong data-stat="commandes_livrees_ou_retirees" data-value="{{ $stats['commandes_livrees_ou_retirees']['value'] ?? '' }}" aria-live="polite">{{ $stats['commandes_livrees_ou_retirees']['display'] ?? 'Indisponible' }}</strong><span>Commandes livrées</span></article>
                    <article><strong data-stat="ordonnances_validees" data-value="{{ $stats['ordonnances_validees']['value'] ?? '' }}" aria-live="polite">{{ $stats['ordonnances_validees']['display'] ?? 'Indisponible' }}</strong><span>Ordonnances traitées</span></article>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
