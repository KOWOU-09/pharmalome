@extends('layouts.app')

@section('title', 'Gestion des utilisateurs - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<section class="section">
    <div class="container">
        <div class="page-heading">
            <p class="eyebrow">Administration</p>
            <h1 class="section-title">Gestion des utilisateurs</h1>
            <p class="muted">Consultez les comptes, créez les accès professionnels et vérifiez les qualifications des pharmaciens.</p>
        </div>

        @if($errors->has('utilisateur'))
            <div class="form-errors" role="alert">{{ $errors->first('utilisateur') }}</div>
        @endif

        <section class="account-card">
            <h2>Créer un compte professionnel</h2>
            <p class="muted">Le titulaire recevra un lien d’invitation à usage unique pour définir son mot de passe.</p>
            <p class="muted">Ce formulaire sert uniquement aux pharmaciens, livreurs et administrateurs. Pour créer un compte client classique, utilisez <a class="inline-link" href="{{ route('register') }}">la page d’inscription client</a>.</p>
            <form class="form-grid" method="POST" action="{{ route('admin.utilisateurs.store') }}">
                @csrf
                <div>
                    <label for="prenom">Prénom</label>
                    <input class="input" id="prenom" name="prenom" value="{{ old('prenom') }}" required>
                </div>
                <div>
                    <label for="nom">Nom</label>
                    <input class="input" id="nom" name="nom" value="{{ old('nom') }}" required>
                </div>
                <div>
                    <label for="email">Adresse e-mail</label>
                    <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label for="telephone">Téléphone</label>
                    <input class="input" id="telephone" name="telephone" type="tel" value="{{ old('telephone') }}" required>
                </div>
                <div>
                    <label for="role">Rôle</label>
                    <select class="select" id="role" name="role" required>
                        <option value="pharmacien" @selected(old('role') === 'pharmacien')>Pharmacien</option>
                        <option value="livreur" @selected(old('role') === 'livreur')>Livreur</option>
                        <option value="admin" @selected(old('role') === 'admin')>Administrateur</option>
                    </select>
                </div>
                <div>
                    <label for="qualification">Diplôme / qualification (pharmacien)</label>
                    <input class="input" id="qualification" name="qualification" value="{{ old('qualification') }}">
                </div>
                <div>
                    <label for="numero_ordre">Numéro d’ordre (pharmacien)</label>
                    <input class="input" id="numero_ordre" name="numero_ordre" value="{{ old('numero_ordre') }}">
                </div>
                <button class="btn btn-primary" type="submit">Créer et envoyer l’invitation</button>
            </form>
        </section>

        <form class="user-filter-form" method="GET" action="{{ route('admin.utilisateurs') }}">
            <div>
                <label for="search">Rechercher un compte</label>
                <input class="input" id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, e-mail ou téléphone">
            </div>
            <div>
                <label for="filter-role">Filtrer par rôle</label>
                <select class="select" id="filter-role" name="role">
                    <option value="">Tous les rôles</option>
                    @foreach(['client' => 'Client', 'pharmacien' => 'Pharmacien', 'livreur' => 'Livreur', 'admin' => 'Administrateur'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="user-filter-actions">
                <button class="btn btn-primary" type="submit">Appliquer les filtres</button>
                @if(($filters['search'] ?? '') !== '' || ($filters['role'] ?? '') !== '')
                    <a class="btn btn-outline" href="{{ route('admin.utilisateurs') }}">Tout afficher</a>
                @endif
            </div>
        </form>

        @if($serviceUnavailable)
            <div class="flash-message flash-error" role="status">Le service utilisateurs est indisponible. Les comptes n’ont pas pu être chargés.</div>
        @elseif($users)
            <p class="muted user-results-count">{{ count($users) }} compte(s) affiché(s)</p>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Utilisateur</th><th>Contact et qualification</th><th>Rôle</th><th>État / vérification</th><th>Actions</th></tr></thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td><strong>{{ $user['name'] }}</strong><small class="table-subtext">Compte #{{ $user['id'] }}</small></td>
                                <td>
                                    {{ $user['email'] }}
                                    <small class="table-subtext">{{ $user['telephone'] ?? 'Téléphone non renseigné' }}</small>
                                    @if(($user['role'] ?? '') === 'pharmacien')
                                        <small class="table-subtext">{{ $user['qualification'] ?? 'Qualification non renseignée' }} · Ordre {{ $user['numero_ordre'] ?? 'non renseigné' }}</small>
                                    @endif
                                </td>
                                <td>
                                    <label class="sr-only" for="role-{{ $user['id'] }}">Rôle de {{ $user['name'] }}</label>
                                    <select class="select" id="role-{{ $user['id'] }}" name="role" form="user-form-{{ $user['id'] }}" required>
                                        @foreach(['client' => 'Client', 'pharmacien' => 'Pharmacien', 'livreur' => 'Livreur', 'admin' => 'Administrateur'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($user['role'] ?? 'client') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                        <label class="user-active">
                                            <input type="hidden" name="actif" value="0" form="user-form-{{ $user['id'] }}">
                                            <input type="checkbox" name="actif" value="1" form="user-form-{{ $user['id'] }}" @checked($user['actif'] ?? false)>
                                            Compte actif
                                        </label>
                                        @if(($user['role'] ?? '') === 'pharmacien')
                                            <label class="user-active">
                                                <input type="hidden" name="pharmacien_verifie" value="0" form="user-form-{{ $user['id'] }}">
                                                <input type="checkbox" name="pharmacien_verifie" value="1" form="user-form-{{ $user['id'] }}" @checked($user['pharmacien_verifie'] ?? false)>
                                                Qualification vérifiée
                                            </label>
                                        @endif
                                </td>
                                <td>
                                    <form class="user-edit-form" id="user-form-{{ $user['id'] }}" method="POST" action="{{ route('admin.utilisateurs.update', ['id' => $user['id']]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-primary" type="submit">Enregistrer</button>
                                    </form>
                                    @if(in_array($user['role'] ?? '', ['admin', 'pharmacien', 'livreur'], true))
                                        <form method="POST" action="{{ route('admin.utilisateurs.invitation', ['id' => $user['id']]) }}">
                                            @csrf
                                            <button class="btn btn-outline" type="submit">Renvoyer l’invitation</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <span class="material-symbols-rounded" aria-hidden="true">group</span>
                <h2>{{ ($filters['search'] ?? '') !== '' || ($filters['role'] ?? '') !== '' ? 'Aucun compte ne correspond à ces filtres' : 'Aucun utilisateur disponible' }}</h2>
                @if(($filters['search'] ?? '') !== '' || ($filters['role'] ?? '') !== '')
                    <a class="btn btn-outline" href="{{ route('admin.utilisateurs') }}">Effacer les filtres</a>
                @endif
            </div>
        @endif
    </div>
</section>
@endsection
