@extends('layouts.app')

@section('title', 'Validation d’ordonnance - MedExpress TOGO')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
<section class="section">
    <div class="container">
        <div class="page-heading">
            <p class="eyebrow">Espace pharmacien</p>
            <h1 class="section-title">Validation des ordonnances</h1>
            <p class="muted">Examinez les documents transmis avec les commandes.</p>
        </div>
        @if($serviceUnavailable)
            <div class="flash-message flash-error" role="status">Le service de commandes est indisponible. Les ordonnances ne peuvent pas être chargées.</div>
        @elseif($prescriptions)
            <div class="prescription-list">
                @foreach($prescriptions as $prescription)
                    @php($status = $prescription['statut'] ?? 'inconnue')
                    <article class="prescription-card">
                        <div class="prescription-info">
                            <span class="material-symbols-rounded prescription-icon" aria-hidden="true">description</span>
                            <div>
                                <h2>Ordonnance #{{ $prescription['id'] }}</h2>
                                <p class="muted">Commande #{{ $prescription['commande_id'] ?? ($prescription['commande']['id'] ?? '—') }}</p>
                                <span class="status {{ $status === 'validee' ? 'active' : ($status === 'refusee' ? 'alert' : 'waiting') }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                            </div>
                        </div>
                        <div class="prescription-actions">
                            @if(!empty($prescription['fichier']))
                                <a class="btn btn-outline" href="{{ route('pharmacien.ordonnances.fichier', ['id' => $prescription['id']]) }}" target="_blank" rel="noopener">
                                    <span class="material-symbols-rounded" aria-hidden="true">open_in_new</span> Ouvrir le fichier
                                </a>
                            @endif
                            @if($status === 'en_attente')
                                <form method="POST" action="{{ route('pharmacien.ordonnances.valider', ['id' => $prescription['id']]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-primary" type="submit">Valider</button>
                                </form>
                                <form class="reject-form" method="POST" action="{{ route('pharmacien.ordonnances.refuser', ['id' => $prescription['id']]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label class="sr-only" for="motif-{{ $prescription['id'] }}">Motif du refus</label>
                                    <input class="input" id="motif-{{ $prescription['id'] }}" name="motif_refus" minlength="5" maxlength="1000" placeholder="Motif du refus" required>
                                    <button class="btn btn-outline" type="submit">Refuser</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-state"><span class="material-symbols-rounded" aria-hidden="true">task_alt</span><h2>Aucune ordonnance en attente</h2><p class="muted">Les ordonnances reçues apparaîtront ici.</p></div>
        @endif
    </div>
</section>
@endsection
