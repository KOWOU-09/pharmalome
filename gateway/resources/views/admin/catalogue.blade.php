@extends('layouts.app')

@section('title', 'Gestion du catalogue - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<section class="section">
    <div class="container">
        <div class="page-heading">
            <p class="eyebrow">Administration</p>
            <h1 class="section-title">Produits et catégories</h1>
            <p class="muted">Les modifications sont enregistrées dans le service catalogue.</p>
        </div>
        @if($serviceUnavailable)
            <div class="flash-message flash-error" role="status">Le service catalogue est indisponible ou certaines données n’ont pas pu être chargées.</div>
        @endif
        <div class="admin-form-grid">
            <section class="admin-form-card">
                <h2>Ajouter une catégorie</h2>
                <form class="form-grid" method="POST" action="{{ route('admin.catalogue.categories.store') }}">
                    @csrf
                    <label for="category-name">Nom de la catégorie</label>
                    <input class="input" id="category-name" name="libelle" maxlength="255" value="{{ old('libelle') }}" required>
                    <button class="btn btn-primary" type="submit">Ajouter la catégorie</button>
                </form>
            </section>
            @if($categories)
                <section class="admin-form-card">
                    <h2>Ajouter un produit</h2>
                    <form class="form-grid admin-product-create" method="POST" action="{{ route('admin.catalogue.produits.store') }}">
                        @csrf
                        <div><label for="product-name">Nom</label><input class="input" id="product-name" name="nom" maxlength="255" required></div>
                        <div><label for="product-category">Catégorie</label>
                            <select class="select" id="product-category" name="categorie_id" required>
                                @foreach($categories as $category)<option value="{{ $category['id'] }}">{{ $category['libelle'] }}</option>@endforeach
                            </select>
                        </div>
                        <div class="admin-field-row">
                            <div><label for="product-price">Prix (FCFA)</label><input class="input" id="product-price" name="prix" type="number" min="1" required></div>
                            <div><label for="product-stock">Stock</label><input class="input" id="product-stock" name="quantite_stock" type="number" min="0" required></div>
                        </div>
                        <label class="user-active"><input type="checkbox" name="promotion" value="1"> Mettre en promotion</label>
                        <div>
                            <label for="product-sale-price">Prix promotionnel (FCFA)</label>
                            <input class="input" id="product-sale-price" name="prix_promotion" type="number" min="1" aria-describedby="promotion-help">
                            <small id="promotion-help" class="muted">Laissez vide hors promotion. Le prix promotionnel doit être inférieur au prix normal.</small>
                        </div>
                        <div class="admin-field-row">
                            <div><label for="product-threshold">Seuil d’alerte</label><input class="input" id="product-threshold" name="seuil_alerte" type="number" min="0" value="5"></div>
                            <div><label for="product-expiry">Péremption</label><input class="input" id="product-expiry" name="date_peremption" type="date"></div>
                        </div>
                        <div class="admin-field-row">
                            <div><label for="product-dosage">Dosage / format</label><input class="input" id="product-dosage" name="dosage" maxlength="100"></div>
                            <div><label for="product-lab">Laboratoire</label><input class="input" id="product-lab" name="laboratoire" maxlength="255"></div>
                        </div>
                        <div><label for="product-description">Description</label><textarea id="product-description" name="description" maxlength="2000"></textarea></div>
                        <label class="user-active"><input type="checkbox" name="ordonnance_requise" value="1"> Ordonnance requise</label>
                        <button class="btn btn-primary" type="submit">Créer le produit</button>
                    </form>
                </section>
            </div>
            <section class="admin-category-list">
                <h2>Catégories enregistrées</h2>
                @foreach($categories as $category)
                    <form class="category-edit-form" method="POST" action="{{ route('admin.catalogue.categories.update', ['id' => $category['id']]) }}">
                        @csrf
                        @method('PUT')
                        <label for="edit-category-{{ $category['id'] }}">Catégorie #{{ $category['id'] }}</label>
                        <input class="input" id="edit-category-{{ $category['id'] }}" name="libelle" maxlength="255" value="{{ $category['libelle'] }}" required>
                        <span class="muted">{{ $category['medicaments_count'] ?? 0 }} produit(s)</span>
                        <button class="btn btn-outline" type="submit">Renommer</button>
                    </form>
                @endforeach
            </section>
            @else
            <div class="admin-form-card">
                <p class="muted">Ajoutez d’abord une catégorie pour pouvoir créer des produits.</p>
            </div>
            @endif
        </div>

        <div class="catalog-admin-heading">
            <h2>Produits enregistrés</h2>
            <a class="btn btn-outline" href="{{ route('catalogue') }}">Voir la boutique</a>
        </div>
        @if($products)
            <div class="admin-product-list">
                @foreach($products as $product)
                    <article class="admin-product-card">
                        <div class="admin-product-title">
                            <span class="material-symbols-rounded" aria-hidden="true">medication</span>
                            <div><h3>{{ $product['nom'] }}</h3><small>Produit #{{ $product['id'] }}</small></div>
                        </div>
                        <form class="admin-product-edit" method="POST" action="{{ route('admin.catalogue.produits.update', ['id' => $product['id']]) }}">
                            @csrf
                            @method('PUT')
                            <div><label for="edit-category-{{ $product['id'] }}">Catégorie</label>
                                <select class="select" id="edit-category-{{ $product['id'] }}" name="categorie_id" required>
                                    @foreach($categories as $category)<option value="{{ $category['id'] }}" @selected(($product['categorie_id'] ?? null) == $category['id'])>{{ $category['libelle'] }}</option>@endforeach
                                </select>
                            </div>
                            <div><label for="edit-name-{{ $product['id'] }}">Nom</label><input class="input" id="edit-name-{{ $product['id'] }}" name="nom" value="{{ $product['nom'] }}" maxlength="255" required></div>
                            <div class="admin-field-row">
                                <div><label for="edit-price-{{ $product['id'] }}">Prix (FCFA)</label><input class="input" id="edit-price-{{ $product['id'] }}" name="prix" type="number" min="1" value="{{ $product['prix'] }}" required></div>
                                <div><label for="edit-stock-{{ $product['id'] }}">Stock</label><input class="input" id="edit-stock-{{ $product['id'] }}" name="quantite_stock" type="number" min="0" value="{{ $product['quantite_stock'] }}" required></div>
                            </div>
                            <label class="user-active">
                                <input type="hidden" name="promotion" value="0">
                                <input type="checkbox" name="promotion" value="1" @checked($product['promotion'] ?? false)> En promotion
                            </label>
                            <div><label for="edit-sale-price-{{ $product['id'] }}">Prix promotionnel (FCFA)</label><input class="input" id="edit-sale-price-{{ $product['id'] }}" name="prix_promotion" type="number" min="1" value="{{ $product['prix_promotion'] ?? '' }}"></div>
                            <div class="admin-field-row">
                                <div><label for="edit-threshold-{{ $product['id'] }}">Seuil d’alerte</label><input class="input" id="edit-threshold-{{ $product['id'] }}" name="seuil_alerte" type="number" min="0" value="{{ $product['seuil_alerte'] ?? 0 }}"></div>
                                <label class="user-active"><input type="checkbox" name="ordonnance_requise" value="1" @checked($product['ordonnance_requise'] ?? false)> Ordonnance</label>
                            </div>
                            <div class="admin-field-row">
                                <div><label for="edit-dosage-{{ $product['id'] }}">Dosage / format</label><input class="input" id="edit-dosage-{{ $product['id'] }}" name="dosage" maxlength="100" value="{{ $product['dosage'] ?? '' }}"></div>
                                <div><label for="edit-lab-{{ $product['id'] }}">Laboratoire</label><input class="input" id="edit-lab-{{ $product['id'] }}" name="laboratoire" maxlength="255" value="{{ $product['laboratoire'] ?? '' }}"></div>
                            </div>
                            <div><label for="edit-description-{{ $product['id'] }}">Description</label><textarea id="edit-description-{{ $product['id'] }}" name="description" maxlength="2000">{{ $product['description'] ?? '' }}</textarea></div>
                            <div><label for="edit-expiry-{{ $product['id'] }}">Date de péremption</label><input class="input" id="edit-expiry-{{ $product['id'] }}" name="date_peremption" type="date" value="{{ isset($product['date_peremption']) ? \Illuminate\Support\Carbon::parse($product['date_peremption'])->format('Y-m-d') : '' }}"></div>
                            <div class="admin-product-actions">
                                <button class="btn btn-primary" type="submit">Enregistrer</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.catalogue.produits.destroy', ['id' => $product['id']]) }}" data-confirm="Retirer ce produit du catalogue ?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger-outline" type="submit">Retirer le produit</button>
                        </form>
                    </article>
                @endforeach
            </div>
        @elseif(! $serviceUnavailable)
            <div class="empty-state"><span class="material-symbols-rounded" aria-hidden="true">inventory_2</span><h2>Aucun produit dans le catalogue</h2></div>
        @endif
        @if(($pagination['last_page'] ?? 1) > 1)
            <nav class="admin-pagination" aria-label="Pagination des produits administrateur">
                @if($pagination['current_page'] > 1)
                    <a class="btn btn-outline" href="{{ route('admin.catalogue', ['page' => $pagination['current_page'] - 1]) }}">Précédent</a>
                @endif
                <span class="muted">Page {{ $pagination['current_page'] }} sur {{ $pagination['last_page'] }}</span>
                @if($pagination['current_page'] < $pagination['last_page'])
                    <a class="btn btn-outline" href="{{ route('admin.catalogue', ['page' => $pagination['current_page'] + 1]) }}">Suivant</a>
                @endif
            </nav>
        @endif
    </div>
</section>
@endsection
