@php($isAdmin = session('pharmalome_user.role') === 'admin')
<header @class(['topbar', 'topbar-admin' => $isAdmin])>
    <div class="container nav-wrap">
        <a href="{{ $isAdmin ? route('admin.dashboard') : route('home') }}" class="brand">
            <img class="brand-logo" src="{{ asset('images/medexpress-logo.svg') }}" width="160" height="36" alt="MedExpress TOGO">
        </a>

        <nav class="main-nav" aria-label="Navigation principale">
            @if($isAdmin)
                <a href="{{ route('admin.dashboard') }}" @class(['is-active' => request()->routeIs('admin.dashboard')]) @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>Tableau de bord</a>
                <a href="{{ route('admin.catalogue') }}" @class(['is-active' => request()->routeIs('admin.catalogue*')]) @if(request()->routeIs('admin.catalogue*')) aria-current="page" @endif>Produits</a>
                <a href="{{ route('admin.utilisateurs') }}" @class(['is-active' => request()->routeIs('admin.utilisateurs*')]) @if(request()->routeIs('admin.utilisateurs*')) aria-current="page" @endif>Utilisateurs</a>
                <a href="{{ route('admin.commandes') }}" @class(['is-active' => request()->routeIs('admin.commandes*')]) @if(request()->routeIs('admin.commandes*')) aria-current="page" @endif>Commandes</a>
                <a href="{{ route('compte') }}" @class(['is-active' => request()->routeIs('compte')]) @if(request()->routeIs('compte')) aria-current="page" @endif>Mon compte</a>
            @else
                <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home')]) @if(request()->routeIs('home')) aria-current="page" @endif>Accueil</a>
                <a href="{{ route('catalogue') }}" @class(['is-active' => (request()->routeIs('catalogue', 'medicament.show') && ! request()->boolean('promotion') && ! request()->boolean('ordonnance'))]) @if(request()->routeIs('catalogue', 'medicament.show') && ! request()->boolean('promotion') && ! request()->boolean('ordonnance')) aria-current="page" @endif>Nos produits</a>
                <a href="{{ route('catalogue', ['promotion' => 1]) }}" @class(['is-active' => request()->boolean('promotion')]) @if(request()->boolean('promotion')) aria-current="page" @endif>Promotions</a>
                <a href="{{ route('catalogue', ['ordonnance' => 1]) }}" @class(['is-active' => request()->boolean('ordonnance')]) @if(request()->boolean('ordonnance')) aria-current="page" @endif>Sur ordonnance</a>
                @if(session('pharmalome_user'))
                    <a href="{{ route('commandes') }}" @class(['is-active' => request()->routeIs('commandes', 'checkout')]) @if(request()->routeIs('commandes', 'checkout')) aria-current="page" @endif>Commandes</a>
                    <a href="{{ route('compte') }}" @class(['is-active' => request()->routeIs('compte')]) @if(request()->routeIs('compte')) aria-current="page" @endif>Mon compte</a>
                    @if(session('pharmalome_user.role') === 'livreur')
                        <a href="{{ route('livreur.dashboard') }}" @class(['is-active' => request()->routeIs('livreur.*')]) @if(request()->routeIs('livreur.*')) aria-current="page" @endif>Mes missions</a>
                    @endif
                    @if(session('pharmalome_user.role') === 'pharmacien' && session('pharmalome_user.pharmacien_verifie'))
                        <a href="{{ route('pharmacien.dashboard') }}" @class(['is-active' => request()->routeIs('pharmacien.*')]) @if(request()->routeIs('pharmacien.*')) aria-current="page" @endif>Espace pharmacien</a>
                    @endif
                @endif
            @endif
        </nav>

        <div class="nav-actions">
            @if($isAdmin)
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline" type="submit"><span class="material-symbols-rounded" aria-hidden="true">logout</span> Déconnexion</button>
                </form>
            @else
                <a href="{{ route('panier') }}" @class(['cart-link', 'is-active' => request()->routeIs('panier')]) @if(request()->routeIs('panier')) aria-current="page" @endif><span class="material-symbols-rounded" aria-hidden="true">shopping_bag</span> Panier <span class="badge" data-cart-count>0</span></a>
                @if(session('pharmalome_user'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline" type="submit"><span class="material-symbols-rounded" aria-hidden="true">logout</span> Déconnexion</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" @class(['btn', 'btn-outline', 'is-active' => request()->routeIs('login')]) @if(request()->routeIs('login')) aria-current="page" @endif>Connexion</a>
                    <a href="{{ route('register') }}" @class(['btn', 'btn-primary', 'is-active' => request()->routeIs('register')]) @if(request()->routeIs('register')) aria-current="page" @endif>S'inscrire</a>
                @endif
            @endif
        </div>
    </div>
</header>
