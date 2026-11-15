@extends('layouts.app')

@section('title', 'Contact - MedExpress TOGO')
@section('meta_description', 'Contactez l’équipe MedExpress TOGO par téléphone, e-mail ou via notre formulaire.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/info-pages.css') }}">
@endpush

@section('content')
<section class="section info-page">
    <div class="container">
        <p class="section-kicker">Nous sommes à votre écoute</p>
        <h1 class="section-title">Contactez-nous</h1>
        <p class="muted info-lead">Une question sur le site ou votre commande ? Envoyez-nous un message ou contactez directement notre équipe.</p>

        <div class="contact-layout">
            <aside class="contact-details">
                <h2>Nos coordonnées</h2>
                <p>Notre équipe vous accompagne pour vos questions liées au service et à vos commandes.</p>
                <a href="tel:+22893123456">
                    <span class="material-symbols-rounded" aria-hidden="true">call</span>
                    <span><strong>Téléphone</strong><br>+228 93 12 34 56</span>
                </a>
                <a href="mailto:{{ config('services.contact.email') }}">
                    <span class="material-symbols-rounded" aria-hidden="true">mail</span>
                    <span><strong>E-mail</strong><br>{{ config('services.contact.email') }}</span>
                </a>
                <p class="contact-location"><span class="material-symbols-rounded" aria-hidden="true">location_on</span> Lomé, Togo</p>
                <p class="contact-note">Pour une urgence médicale, contactez les services d’urgence ou un professionnel de santé.</p>
            </aside>

            <form class="contact-form" method="POST" action="{{ route('contact.send') }}">
                @csrf
                <h2>Écrivez-nous</h2>

                <div>
                    <label for="contact-name">Nom complet</label>
                    <input class="input" id="contact-name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="120" required>
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact-email">Adresse e-mail</label>
                    <input class="input" id="contact-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required>
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact-subject">Sujet</label>
                    <input class="input" id="contact-subject" name="subject" value="{{ old('subject') }}" maxlength="120" required>
                    @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact-message">Votre message</label>
                    <textarea class="input" id="contact-message" name="message" rows="6" minlength="10" maxlength="5000" required>{{ old('message') }}</textarea>
                    @error('message') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <button class="btn btn-primary" type="submit">Envoyer le message</button>
            </form>
        </div>
    </div>
</section>
@endsection
