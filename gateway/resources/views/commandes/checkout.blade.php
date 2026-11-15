@extends('layouts.app')

@section('title', 'Validation de commande - MedExpress TOGO')

@section('content')
<section class="section">
    <div class="container cart-layout">
        <div>
            <h1 class="section-title">Validation de commande</h1>
            <p class="muted">Vérifiez votre panier et choisissez le mode de réception.</p>
            @if($errors->any())
                <div class="form-errors" role="alert">
                    <p>La commande n’a pas pu être confirmée :</p>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="form-grid checkout-form" method="POST" action="{{ route('checkout.store') }}" enctype="multipart/form-data" data-checkout-form>
                @csrf
                <input type="hidden" name="items" value="[]" data-checkout-items>
                <div>
                    <label for="mode_reception">Mode de réception</label>
                    <select class="select" id="mode_reception" name="mode_reception" required>
                        <option value="retrait" @selected(old('mode_reception') === 'retrait')>Retrait à la pharmacie</option>
                        <option value="livraison" @selected(old('mode_reception', 'livraison') === 'livraison')>Livraison à domicile</option>
                    </select>
                </div>
                <div>
                    <label for="adresse_livraison">Adresse de livraison</label>
                    <textarea id="adresse_livraison" name="adresse_livraison" placeholder="Ville, quartier, repère" maxlength="1000">{{ old('adresse_livraison') }}</textarea>
                </div>
                <div>
                    <label for="ordonnance">Ordonnance (PDF, JPG ou PNG)</label>
                    <input class="input" id="ordonnance" name="ordonnance" type="file" accept=".jpg,.jpeg,.png,.pdf,application/pdf,image/jpeg,image/png">
                    <small class="muted">Maximum 4 Mo. Obligatoire si un produit de votre panier l’exige.</small>
                </div>
                <p class="payment-note"><span class="material-symbols-rounded" aria-hidden="true">info</span> Le paiement en ligne (Mobile Money / carte) sera activé après le branchement du prestataire. Pour le moment, la commande est enregistrée avec paiement à la réception.</p>
                <button type="submit" class="btn btn-primary" data-checkout-submit>Confirmer la commande</button>
                <p class="muted" data-checkout-empty hidden>Votre panier est vide. <a class="inline-link" href="{{ route('catalogue') }}">Retourner au catalogue</a></p>
            </form>
        </div>

        <aside class="summary-box">
            <h3>Votre commande</h3>
            <div data-checkout-summary aria-live="polite"></div>
            <div class="summary-line"><span>Frais de livraison</span><strong data-checkout-delivery>0 FCFA</strong></div>
            <div class="summary-line summary-total"><span>Total estimé</span><strong data-checkout-total>0 FCFA</strong></div>
        </aside>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('js/checkout.js') }}" defer></script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/checkout.css') }}">
@endpush
