@extends('layouts.app')

@section('title', 'Connexion - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<section class="section auth-panel">
    <div class="auth-form">
        <div class="auth-header">
            <h1>Connexion</h1>
            <p class="muted">Bienvenue sur MedExpress TOGO</p>
        </div>

        @if($errors->any())
            <div class="form-errors" role="alert">
                <p>La connexion n’a pas abouti :</p>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="form-grid" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div>
                <label for="email">Adresse e-mail</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nom@exemple.com" autocomplete="email" required>
            </div>
            <div>
                <label for="password">Mot de passe</label>
                <div class="password-field">
                    <input class="input" id="password" name="password" type="password" placeholder="Votre mot de passe" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" data-toggle-password aria-label="Afficher le mot de passe" aria-pressed="false">
                        <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
                    </button>
                </div>
            </div>
            <p class="auth-help muted"><a href="{{ route('password.request') }}" class="inline-link">Mot de passe oublié ?</a></p>
            <button type="submit" class="btn btn-primary">Se connecter</button>
            <p class="auth-help muted">Connectez-vous avec l’adresse e-mail et le mot de passe de votre compte client. <a href="{{ route('register') }}" class="inline-link">Créer un compte client</a>.</p>
        </form>
    </div>
</section>
@endsection
