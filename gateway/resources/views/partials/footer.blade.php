<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="{{ route('home') }}" aria-label="MedExpress TOGO, accueil">
                <img src="{{ asset('images/medexpress-logo.svg') }}" width="180" height="41" alt="MedExpress TOGO">
            </a>
            <p>Votre pharmacie en ligne au Togo, avec des produits de santé certifiés et une livraison rapide.</p>
        </div>
        <div>
            <h5>Navigation</h5>
            <ul>
                <li><a href="{{ route('home') }}">Accueil</a></li>
                <li><a href="{{ route('catalogue') }}">Nos produits</a></li>
                <li><a href="{{ route('catalogue', ['promotion' => 1]) }}">Promotions</a></li>
                <li><a href="{{ route('catalogue', ['ordonnance' => 1]) }}">Médicaments sur ordonnance</a></li>
                <li><a href="{{ route('about') }}">À propos</a></li>
                @if(session('pharmalome_user'))
                    <li><a href="{{ route('commandes') }}">Mes commandes</a></li>
                @endif
            </ul>
        </div>
        <div>
            <h5>Contact</h5>
            <ul>
                <li><a href="{{ route('contact') }}">Nous écrire</a></li>
                <li><a href="tel:+22893123456">Lomé, Togo · +228 93 12 34 56</a></li>
                <li><a href="mailto:{{ config('services.contact.email') }}">{{ config('services.contact.email') }}</a></li>
                @if(! session('pharmalome_user'))
                    <li><a href="{{ route('register') }}">Créer un compte</a></li>
                @endif
            </ul>
        </div>
    </div>
    <div class="footer-bottom">© {{ now()->year }} MedExpress TOGO — Tous droits réservés.</div>
</footer>
