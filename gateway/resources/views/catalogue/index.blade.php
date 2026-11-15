@extends('layouts.app')

@section('title', 'Catalogue - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/catalogue.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/catalogue.js') }}" defer></script>
@endpush

@section('content')
<section class="section">
    <div class="container catalog-layout">
        <aside class="filter-panel">
            <h3>Filtres</h3>
            <form method="GET" action="{{ route('catalogue') }}" data-catalog-filters data-reset-url="{{ route('catalogue') }}">
                @if(request()->boolean('promotion'))
                    <input type="hidden" name="promotion" value="1">
                @endif
                <div class="filter-group">
                    <label for="recherche">Rechercher</label>
                    <input class="input" id="recherche" name="search" type="search" value="{{ request('search') }}" placeholder="Nom du médicament" data-filter-search>
                </div>
                <div class="filter-group">
                    <label for="categorie">Catégorie</label>
                    <select class="select" id="categorie" name="categorie" data-filter-category>
                        <option value="">Toutes</option>
                        @foreach($categories as $category)
                            <option value="{{ $category['nom'] }}" @selected(request('categorie') === $category['nom'])>{{ $category['nom'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label for="ordonnance">Ordonnance</label>
                    <select class="select" id="ordonnance" name="ordonnance" data-filter-prescription>
                        <option value="">Tout</option>
                        <option value="1" @selected(request('ordonnance') === '1')>Sur ordonnance</option>
                        <option value="0" @selected(in_array(request('ordonnance'), ['0', 'false', 'no'], true))>Sans ordonnance</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="prix">Prix max</label>
                    <input class="input" id="prix" name="prix_max" type="number" value="{{ request('prix_max') }}" min="0" placeholder="Sans limite" data-filter-price>
                </div>
                <div class="filter-group">
                    <button type="reset" class="btn btn-outline">Effacer les filtres</button>
                </div>
            </form>
        </aside>

        <div>
            <div class="row catalog-heading">
                <div>
                    <h1 class="section-title" style="margin-bottom: 0;">Catalogue</h1>
                    <p class="muted">Des soins pour toute la famille</p>
                </div>
                <div>
                    <select class="select" aria-label="Trier les produits" data-filter-sort>
                        <option value="recommended">Tri : recommandé</option>
                        <option value="price-ascending">Prix croissant</option>
                        <option value="price-descending">Prix décroissant</option>
                    </select>
                </div>
            </div>

            @if($serviceUnavailable)
                <div class="flash-message flash-error" role="status">Le catalogue est momentanément indisponible. Réessayez dans quelques instants.</div>
            @endif
            <p class="muted" data-product-count aria-live="polite"></p>
            <div class="product-grid" data-product-grid>
                @foreach($products as $product)
                    <article
                        class="product-card catalog-card"
                        data-product-card
                        data-name="{{ $product['nom'] }}"
                        data-description="{{ $product['description'] }}"
                        data-category="{{ $product['categorie'] }}"
                        data-prescription="{{ $product['ordonnance'] ? '1' : '0' }}"
                        data-price="{{ $product['prix'] }}"
                        data-promotion="{{ $product['promotion'] ? '1' : '0' }}"
                        data-stock="{{ $product['stock'] }}"
                        data-id="{{ $product['id'] }}"
                    >
                        <div class="thumb" aria-hidden="true">
                            <span class="material-symbols-rounded">medical_services</span>
                        </div>
                        <div class="product-body">
                            <div class="meta-line">
                                <span class="badge-pill">{{ $product['badge'] }}</span>
                                <span class="price">
                                    {{ number_format($product['prix'], 0, ',', ' ') }} FCFA
                                    @if($product['promotion'])
                                        <del class="muted">{{ number_format($product['prix_initial'], 0, ',', ' ') }} FCFA</del>
                                    @endif
                                </span>
                            </div>
                            <h3>{{ $product['nom'] }}</h3>
                            <p class="muted">{{ $product['description'] }}</p>
                            <div class="catalog-card-actions">
                                <a class="btn btn-outline" href="{{ route('medicament.show', ['id' => $product['id']]) }}">Détails</a>
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
                                >{{ $product['stock'] > 0 ? 'Ajouter' : 'Indisponible' }}</button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <p class="muted" data-no-products hidden>Aucun produit ne correspond à ces filtres.</p>
            @if(($pagination['last_page'] ?? 1) > 1)
                <nav class="catalog-pagination" aria-label="Pagination du catalogue">
                    @for($page = 1; $page <= $pagination['last_page']; $page++)
                        <a class="btn {{ $page === $pagination['current_page'] ? 'btn-primary' : 'btn-outline' }}"
                            href="{{ route('catalogue', array_merge(request()->query(), ['page' => $page])) }}"
                            @if($page === $pagination['current_page']) aria-current="page" @endif>{{ $page }}</a>
                    @endfor
                </nav>
            @endif
        </div>
    </div>
</section>
@endsection

@push('styles')
    <style>
        .catalog-heading { margin-bottom: 1.5rem; }
        .catalog-heading > div:first-child p { margin: .35rem 0 0; }
        .catalog-heading > div:last-child { min-width: 190px; }
        .catalog-card .thumb { height: 190px; }
        .filter-panel { position: sticky; top: 96px; }
        @media (max-width: 900px) { .filter-panel { position: static; } }
        @media (max-width: 560px) { .catalog-heading { align-items: flex-start; flex-direction: column; } .catalog-heading > div:last-child { width: 100%; } }
    </style>
@endpush
