@extends('layouts.app')

@section('title', 'À propos - MedExpress TOGO')
@section('meta_description', 'Découvrez MedExpress TOGO, son service de commande de produits de santé et son accompagnement par les pharmacies partenaires.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/info-pages.css') }}">
@endpush

@section('content')
<section class="section info-page">
    <div class="container">
        <p class="section-kicker">MedExpress TOGO</p>
        <h1 class="section-title">À propos de nous</h1>
        <div class="info-intro">
            <h2>Un accès plus simple aux produits de santé</h2>
            <p>MedExpress TOGO est une plateforme qui facilite la recherche et la commande de produits de santé auprès de pharmacies partenaires. Nous voulons rendre le parcours plus pratique, avec des informations claires et un accompagnement humain.</p>
        </div>

        <div class="info-card-grid">
            <article class="info-card">
                <span class="material-symbols-rounded" aria-hidden="true">health_and_safety</span>
                <h2>Un accompagnement professionnel</h2>
                <p>Lorsqu’une ordonnance est nécessaire, la commande doit être examinée par un pharmacien avant sa délivrance. Les informations du site ne remplacent pas un avis médical ou pharmaceutique.</p>
            </article>
            <article class="info-card">
                <span class="material-symbols-rounded" aria-hidden="true">shopping_bag</span>
                <h2>Un parcours pratique</h2>
                <p>Consultez le catalogue, passez votre commande et retrouvez son suivi dans votre espace client. Les options de retrait ou de livraison sont indiquées pendant la commande.</p>
            </article>
            <article class="info-card">
                <span class="material-symbols-rounded" aria-hidden="true">support_agent</span>
                <h2>Une équipe à votre écoute</h2>
                <p>Pour une question sur le fonctionnement du service ou une commande, notre équipe est joignable par téléphone ou via le formulaire de contact.</p>
            </article>
        </div>

        <div class="info-cta">
            <div>
                <h2>Une question ?</h2>
                <p>Écrivez-nous, nous vous répondrons dès que possible.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('contact') }}">Contacter l’équipe</a>
        </div>
    </div>
</section>
@endsection
