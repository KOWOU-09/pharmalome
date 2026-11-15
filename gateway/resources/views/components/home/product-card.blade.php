@props(['product'])

<article class="home-product-card">
    <div class="product-image">
        <a href="{{ route('medicament.show', ['id' => $product['id']]) }}" aria-label="Voir {{ $product['nom'] }}">
            <span class="material-symbols-rounded" aria-hidden="true">medical_services</span>
        </a>
        <button class="favorite-button" type="button" aria-label="Ajouter {{ $product['nom'] }} aux favoris" aria-pressed="false" data-favorite>
            <span class="material-symbols-rounded" aria-hidden="true">favorite</span>
        </button>
    </div>
    <div class="home-product-info">
        <a class="home-product-name" href="{{ route('medicament.show', ['id' => $product['id']]) }}">{{ $product['nom'] }}</a>
        @if($product['dosage'] !== '')
            <small>{{ $product['dosage'] }}</small>
        @endif
        @if($product['promotion'])
            <small class="badge-pill">Promotion</small>
        @endif
        <strong class="home-product-price">
            {{ number_format($product['prix'], 0, ',', ' ') }} FCFA
            @if($product['promotion'])
                <del class="muted">{{ number_format($product['prix_initial'], 0, ',', ' ') }} FCFA</del>
            @endif
        </strong>
        <button
            class="home-add-button"
            type="button"
            data-add-to-cart
            data-id="{{ $product['id'] }}"
            data-name="{{ $product['nom'] }}"
            data-price="{{ $product['prix'] }}"
            data-stock="{{ $product['stock'] }}"
            data-prescription="{{ $product['ordonnance'] ? '1' : '0' }}"
            @disabled($product['stock'] < 1)
        >
            <span class="material-symbols-rounded" aria-hidden="true">add_shopping_cart</span>
            {{ $product['stock'] > 0 ? 'Ajouter au panier' : 'Indisponible' }}
        </button>
    </div>
</article>
