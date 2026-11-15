@extends('layouts.app')

@section('title', 'Panier - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/panier.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/panier.js') }}" defer></script>
@endpush

@section('content')
<section class="section">
    <div class="container cart-layout">
        <div>
            <h1 class="section-title">Votre panier</h1>
            <div data-cart-items aria-live="polite"></div>
            <p class="muted" data-cart-empty hidden>Votre panier est vide. <a href="{{ route('catalogue') }}" class="inline-link">Parcourir le catalogue</a></p>
        </div>

        <aside class="summary-box">
            <h3>Résumé</h3>
            <div class="summary-line"><span>Sous-total</span><strong data-cart-subtotal>0 FCFA</strong></div>
            <div class="summary-line"><span>Frais de livraison</span><strong data-cart-delivery>0 FCFA</strong></div>
            <div class="summary-line"><span>Total</span><strong data-cart-total>0 FCFA</strong></div>
            <a href="{{ route('checkout') }}" class="btn btn-primary" data-checkout-link aria-disabled="true" style="width: 100%; margin-top: 1rem;">Valider la commande</a>
        </aside>
    </div>
</section>
@endsection
