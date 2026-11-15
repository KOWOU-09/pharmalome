<header class="store-header">
    <div class="store-header-inner">
        <a class="store-brand" href="{{ route('home') }}" aria-label="MedExpress TOGO, accueil">
            <img src="{{ asset('images/medexpress-logo.svg') }}" width="180" height="41" alt="MedExpress TOGO">
        </a>

        <form class="store-search" action="{{ route('catalogue') }}" method="GET" role="search">
            <span class="material-symbols-rounded" aria-hidden="true">search</span>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Rechercher un médicament..." aria-label="Rechercher un médicament">
            <button type="submit">Rechercher</button>
        </form>

        <div class="store-header-actions">
            <a class="store-support" href="{{ route('contact') }}" aria-label="Contacter l’assistance">
                <span class="material-symbols-rounded" aria-hidden="true">support_agent</span>
                <span><strong>Assistance</strong><small>+228 93 12 34 56</small></span>
            </a>
            <a class="store-account" href="{{ session('pharmalome_user') ? route('compte') : route('login') }}">
                <span class="material-symbols-rounded" aria-hidden="true">account_circle</span>
                <span>{{ session('pharmalome_user.name', 'Mon compte') }}</span>
            </a>
            <a class="store-cart" href="{{ route('panier') }}" aria-label="Ouvrir le panier, {{ request()->routeIs('panier') ? 'page actuelle' : '' }}">
                <span class="material-symbols-rounded" aria-hidden="true">shopping_bag</span><span data-cart-count>0</span>
            </a>
        </div>
    </div>
</header>
