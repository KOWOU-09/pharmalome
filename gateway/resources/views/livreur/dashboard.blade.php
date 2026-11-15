@extends('layouts.app')

@section('title', 'Mes missions - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
<section class="section dashboard-shell">
    <div class="container">
        <div class="page-heading">
            <p class="eyebrow">Espace livreur</p>
            <h1 class="section-title">Mes missions de livraison</h1>
            <p class="muted">Seules les commandes qui vous sont attribuées apparaissent dans cet espace.</p>
        </div>

        @if($serviceUnavailable)
            <div class="flash-message flash-error" role="status">Le service de commandes est momentanément indisponible.</div>
        @elseif($missions)
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Mission</th><th>Adresse de livraison</th><th>Articles</th><th>Total</th><th>État</th><th>Action</th></tr></thead>
                    <tbody>
                        @foreach($missions as $mission)
                            <tr>
                                <td>#{{ $mission['id'] }}</td>
                                <td>{{ $mission['adresse_livraison'] ?? 'Adresse non renseignée' }}</td>
                                <td>
                                    @foreach($mission['lignes'] ?? [] as $line)
                                        <div>{{ $line['nom_medicament'] }} × {{ $line['quantite'] }}</div>
                                    @endforeach
                                </td>
                                <td>{{ number_format((int) ($mission['total'] ?? 0), 0, ',', ' ') }} FCFA</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $mission['statut'] ?? '')) }}</td>
                                <td>
                                    @if(($mission['statut'] ?? '') === 'en_livraison')
                                        <form method="POST" action="{{ route('livreur.missions.complete', ['id' => $mission['id']]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-primary" type="submit">Marquer livrée</button>
                                        </form>
                                    @else
                                        Livraison terminée
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <span class="material-symbols-rounded" aria-hidden="true">local_shipping</span>
                <h2>Aucune mission assignée</h2>
                <p class="muted">Les nouvelles missions apparaîtront ici après leur affectation par l’administration.</p>
            </div>
        @endif
    </div>
</section>
@endsection
