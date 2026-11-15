@extends('layouts.app')

@section('title', 'Inscription - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<section class="section auth-panel">
    <div class="auth-form">
        <div class="auth-header">
            <h1>Créer un compte</h1>
            <p class="muted">Rejoignez MedExpress TOGO dès maintenant</p>
        </div>

        @if($errors->any())
            <div class="form-errors" role="alert">
                <p>Vérifiez les informations suivantes :</p>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="form-grid" method="POST" action="{{ route('register.store') }}">
            @csrf
            <div>
                <label for="prenom">Prénom</label>
                <input class="input" id="prenom" name="prenom" type="text" value="{{ old('prenom') }}" placeholder="Votre prénom" autocomplete="given-name" required>
            </div>
            <div>
                <label for="nom">Nom</label>
                <input class="input" id="nom" name="nom" type="text" value="{{ old('nom') }}" placeholder="Votre nom" autocomplete="family-name" required>
            </div>
            <div>
                <label for="email">Adresse e-mail</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nom@exemple.com" autocomplete="email" required>
            </div>
            <div>
                <label for="phone">Téléphone</label>
                <input class="input" id="phone" name="telephone" type="tel" value="{{ old('telephone') }}" placeholder="+228 99 00 00 00" autocomplete="tel" required>
            </div>
            <div>
                <label for="password">Mot de passe</label>
                <div class="password-field">
                    <input class="input" id="password" name="password" type="password" placeholder="8 caractères minimum" autocomplete="new-password" minlength="8" required>
                    <button class="password-toggle" type="button" data-toggle-password aria-label="Afficher le mot de passe" aria-pressed="false">
                        <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
                    </button>
                </div>
            </div>
            <div>
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <div class="password-field">
                    <input class="input" id="password_confirmation" name="password_confirmation" type="password" placeholder="Retapez votre mot de passe" autocomplete="new-password" minlength="8" required>
                    <button class="password-toggle" type="button" data-toggle-password aria-label="Afficher le mot de passe" aria-pressed="false">
                        <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Créer mon compte</button>
            <p class="auth-help muted">Cette inscription crée un compte client et vous connecte automatiquement. Les comptes pharmacien, livreur et administrateur sont créés par l’administration.</p>
            <p class="muted">Déjà inscrit ? <a href="{{ route('login') }}" class="inline-link">Se connecter</a></p>
        </form>
    </div>
</section>
@endsection
