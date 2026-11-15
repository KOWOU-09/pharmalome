@extends('layouts.app')

@section('title', 'Mon compte - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/compte.css') }}">
@endpush

@section('content')
<section class="section">
    <div class="container">
        <div class="page-heading">
            <p class="eyebrow">Espace personnel</p>
            <h1 class="section-title">Bonjour {{ $user['name'] ?? 'et bienvenue' }}</h1>
            <p class="muted">Retrouvez ici les informations de votre compte et les accès liés à votre profil.</p>
        </div>

        <div class="grid-2 account-grid">
            <section class="account-card">
                <span class="material-symbols-rounded account-icon" aria-hidden="true">account_circle</span>
                <h2>Mes informations</h2>
                @if($errors->has('profil'))
                    <div class="form-errors" role="alert">{{ $errors->first('profil') }}</div>
                @endif
                <form class="form-grid" method="POST" action="{{ route('compte.update') }}">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="prenom">Prénom</label>
                        <input class="input" id="prenom" name="prenom" value="{{ old('prenom', $user['prenom'] ?? '') }}" autocomplete="given-name" required>
                    </div>
                    <div>
                        <label for="nom">Nom</label>
                        <input class="input" id="nom" name="nom" value="{{ old('nom', $user['nom'] ?? '') }}" autocomplete="family-name" required>
                    </div>
                    <div>
                        <label for="email">Adresse e-mail</label>
                        <input class="input" id="email" name="email" type="email" value="{{ old('email', $user['email'] ?? '') }}" autocomplete="email" required>
                    </div>
                    <div>
                        <label for="telephone">Téléphone</label>
                        <input class="input" id="telephone" name="telephone" type="tel" value="{{ old('telephone', $user['telephone'] ?? '') }}" autocomplete="tel" required>
                    </div>
                    <div>
                        <label for="adresse">Adresse</label>
                        <input class="input" id="adresse" name="adresse" value="{{ old('adresse', $user['adresse'] ?? '') }}" autocomplete="street-address">
                    </div>
                    @if(($user['role'] ?? '') === 'pharmacien')
                        <div>
                            <label for="qualification">Diplôme / qualification professionnelle</label>
                            <input class="input" id="qualification" name="qualification" value="{{ old('qualification', $user['qualification'] ?? '') }}" required>
                        </div>
                        <div>
                            <label for="numero_ordre">Numéro d’inscription à l’ordre</label>
                            <input class="input" id="numero_ordre" name="numero_ordre" value="{{ old('numero_ordre', $user['numero_ordre'] ?? '') }}" required>
                        </div>
                        <p class="auth-help muted">
                            Vérification : {{ ($user['pharmacien_verifie'] ?? false) ? 'validée' : 'en attente de validation par l’administration' }}.
                            Toute modification de ces informations relance la vérification.
                        </p>
                    @endif
                    <button class="btn btn-primary" type="submit">Enregistrer mes informations</button>
                </form>
                <dl class="account-details">
                    <div><dt>Type de compte</dt><dd>{{ ucfirst($user['role'] ?? 'client') }}</dd></div>
                </dl>
            </section>

            <section class="account-card">
                <span class="material-symbols-rounded account-icon" aria-hidden="true">lock</span>
                <h2>Modifier mon mot de passe</h2>
                @if($errors->has('mot_de_passe'))
                    <div class="form-errors" role="alert">{{ $errors->first('mot_de_passe') }}</div>
                @endif
                <form class="form-grid" method="POST" action="{{ route('compte.password.update') }}">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="current_password">Mot de passe actuel</label>
                        <div class="password-field">
                            <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                            <button class="password-toggle" type="button" data-toggle-password aria-label="Afficher le mot de passe" aria-pressed="false">
                                <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="new_password">Nouveau mot de passe</label>
                        <div class="password-field">
                            <input class="input" id="new_password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                            <button class="password-toggle" type="button" data-toggle-password aria-label="Afficher le mot de passe" aria-pressed="false">
                                <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="password_confirmation">Confirmer le nouveau mot de passe</label>
                        <div class="password-field">
                            <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                            <button class="password-toggle" type="button" data-toggle-password aria-label="Afficher le mot de passe" aria-pressed="false">
                                <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
                            </button>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Modifier mon mot de passe</button>
                </form>
            </section>

            <section class="account-card">
                <span class="material-symbols-rounded account-icon" aria-hidden="true">assignment</span>
                <h2>Mes activités</h2>
                <p class="muted">Consultez vos achats ou poursuivez votre sélection de produits.</p>
                <div class="hero-actions account-actions">
                    <a class="btn btn-primary" href="{{ route('commandes') }}">Voir mes commandes</a>
                    <a class="btn btn-outline" href="{{ route('catalogue') }}">Parcourir le catalogue</a>
                </div>
            </section>
        </div>

        @if(($user['role'] ?? '') === 'admin' || (($user['role'] ?? '') === 'pharmacien' && ($user['pharmacien_verifie'] ?? false)))
            <section class="account-card staff-shortcuts">
                <h2>Accès professionnel</h2>
                <div class="hero-actions account-actions">
                    <a class="btn btn-outline" href="{{ route('pharmacien.dashboard') }}">Espace pharmacien</a>
                    @if(($user['role'] ?? '') === 'admin')
                        <a class="btn btn-outline" href="{{ route('admin.dashboard') }}">Administration</a>
                    @endif
                </div>
            </section>
        @elseif(($user['role'] ?? '') === 'pharmacien')
            <section class="account-card staff-shortcuts">
                <h2>Vérification professionnelle en attente</h2>
                <p class="muted">Les fonctions réservées aux pharmaciens seront accessibles après validation de vos informations par l’administration.</p>
            </section>
        @endif
    </div>
</section>
@endsection
