@extends('layouts.app')

@section('title', 'Gestion du stock - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
<section class="section">
    <div class="container">
        <div class="page-heading">
            <p class="eyebrow">Espace pharmacien</p>
            <h1 class="section-title">Gestion du stock</h1>
            <p class="muted">Les quantités et seuils affichés proviennent du service catalogue.</p>
        </div>
        @if($serviceUnavailable)
            <div class="flash-message flash-error" role="status">Le service catalogue est indisponible. Le stock n’a pas pu être chargé.</div>
        @elseif($items)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Stock</th>
                        <th>Seuil</th>
                        <th>Péremption</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td><strong>{{ $item['nom'] }}</strong></td>
                            <td><span class="status {{ ($item['quantite_stock'] ?? 0) <= ($item['seuil_alerte'] ?? 0) ? 'alert' : 'active' }}">{{ $item['quantite_stock'] ?? 0 }}</span></td>
                            <td>{{ $item['seuil_alerte'] ?? '—' }}</td>
                            <td>{{ $item['date_peremption'] ?? '—' }}</td>
                            <td>
                                <form class="restock-form" method="POST" action="{{ route('pharmacien.stock.reapprovisionner', ['id' => $item['id']]) }}">
                                    @csrf
                                    <label class="sr-only" for="quantite-{{ $item['id'] }}">Quantité à ajouter pour {{ $item['nom'] }}</label>
                                    <input class="input" id="quantite-{{ $item['id'] }}" name="quantite" type="number" value="1" min="1" max="100000" required>
                                    <button class="btn btn-primary" type="submit">Réapprovisionner</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="empty-state"><span class="material-symbols-rounded" aria-hidden="true">inventory_2</span><h2>Aucun produit au catalogue</h2><p class="muted">Les produits disponibles apparaîtront ici.</p></div>
        @endif
    </div>
</section>
@endsection
