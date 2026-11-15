@extends('layouts.app')

@section('title', 'Mot de passe oublié - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<section class="section auth-panel">
    <div class="auth-form">
        <div class="auth-header">
            <h1>Réinitialiser mon mot de passe</h1>
            <p class="muted">Un lien sécurisé sera envoyé à l’adresse associée au compte.</p>
        </div>

        @if($errors->any())
            <div class="form-errors" role="alert">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form class="form-grid" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div>
                <label for="email">Adresse e-mail</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            </div>
            <button class="btn btn-primary" type="submit">Envoyer le lien</button>
            <p class="muted"><a href="{{ route('login') }}" class="inline-link">Retour à la connexion</a></p>
        </form>
    </div>
</section>
@endsection
