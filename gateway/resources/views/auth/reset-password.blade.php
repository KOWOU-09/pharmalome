@extends('layouts.app')

@section('title', 'Choisir un nouveau mot de passe - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<section class="section auth-panel">
    <div class="auth-form">
        <div class="auth-header">
            <h1>Choisir un nouveau mot de passe</h1>
            <p class="muted">Le lien d’invitation ou de réinitialisation ne peut être utilisé qu’une seule fois.</p>
        </div>

        @if($errors->any())
            <div class="form-errors" role="alert">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form class="form-grid" method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ old('token', $token) }}">
            <div>
                <label for="email">Adresse e-mail</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>
            </div>
            <div>
                <label for="password">Nouveau mot de passe</label>
                <div class="password-field">
                    <input class="input" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    <button class="password-toggle" type="button" data-toggle-password aria-label="Afficher le mot de passe" aria-pressed="false">
                        <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
                    </button>
                </div>
            </div>
            <div>
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <div class="password-field">
                    <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                    <button class="password-toggle" type="button" data-toggle-password aria-label="Afficher le mot de passe" aria-pressed="false">
                        <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
                    </button>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Enregistrer le mot de passe</button>
        </form>
    </div>
</section>
@endsection
