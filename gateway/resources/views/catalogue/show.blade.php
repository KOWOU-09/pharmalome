@extends('layouts.app')

@section('title', $product['nom'] . ' - MedExpress TOGO')

@section('content')
<section class="section">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Fil d’Ariane">
            <a href="{{ route('home') }}">Accueil</a><span aria-hidden="true">/</span>
            <a href="{{ route('catalogue') }}">Nos produits</a><span aria-hidden="true">/</span>
            <span aria-current="page">{{ $product['nom'] }}</span>
        </nav>
        <div class="grid-2">
            <div class="product-card">
                <div class="detail-product-image" aria-hidden="true">
                    <span class="material-symbols-rounded">medical_services</span>
                </div>
                <div class="product-body">
                    <div class="product-meta">
                        <span class="badge-pill">{{ $product['badge'] }}</span>
                        <span class="price">
                            {{ number_format($product['prix'], 0, ',', ' ') }} FCFA
                            @if($product['promotion'])
                                <del class="muted">{{ number_format($product['prix_initial'], 0, ',', ' ') }} FCFA</del>
                            @endif
                        </span>
                    </div>
                    <h2>{{ $product['nom'] }}</h2>
                    <p class="muted">{{ $product['description'] }}</p>
                    <p class="muted">Les informations affichées ne remplacent pas l’avis d’un professionnel de santé.</p>
                </div>
            </div>

            <div class="product-card">
                <div class="product-body">
                    <h3>Informations utiles</h3>
                    <ul class="card-list">
                        @if($product['dosage'] !== '')<li><strong>Dosage :</strong> {{ $product['dosage'] }}</li>@endif
                        @if($product['laboratoire'] !== '')<li><strong>Laboratoire :</strong> {{ $product['laboratoire'] }}</li>@endif
                        <li><strong>Ordonnance :</strong> {{ $product['ordonnance'] ? 'Oui' : 'Non' }}</li>
                        <li><strong>Disponibilité :</strong> {{ $product['stock'] > 0 ? 'En stock' : 'Rupture' }}</li>
                    </ul>
                    <div class="hero-actions" style="margin-top: 1.5rem;">
                        <button
                            class="btn btn-primary"
                            type="button"
                            data-add-to-cart
                            data-id="{{ $product['id'] }}"
                            data-name="{{ $product['nom'] }}"
                            data-price="{{ $product['prix'] }}"
                            data-stock="{{ $product['stock'] }}"
                            data-prescription="{{ $product['ordonnance'] ? '1' : '0' }}"
                            @disabled($product['stock'] < 1)
                        >{{ $product['stock'] > 0 ? 'Ajouter au panier' : 'Indisponible' }}</button>
                        <a href="{{ route('catalogue') }}" class="btn btn-outline">Retour au catalogue</a>
                    </div>
                    <p class="muted" data-cart-message aria-live="polite"></p>
                </div>
            </div>
        </div>

        @if($similar)
        <div class="section">
            <h2 class="section-title">Produits similaires</h2>
            <div class="grid-3">
                @foreach($similar as $item)
                    <div class="product-card">
                        <a class="detail-product-image" href="{{ route('medicament.show', ['id' => $item['id']]) }}" aria-label="Voir {{ $item['nom'] }}">
                            <span class="material-symbols-rounded" aria-hidden="true">medical_services</span>
                        </a>
                        <div class="product-body">
                            <h3>{{ $item['nom'] }}</h3>
                            <p class="muted">{{ $item['description'] }}</p>
                            <a href="{{ route('medicament.show', ['id' => $item['id']]) }}" class="btn btn-primary">Voir</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/catalogue.css') }}">
@endpush
