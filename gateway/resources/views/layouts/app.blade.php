<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="@yield('meta_description', 'MedExpress TOGO : votre pharmacie en ligne, avec des produits de santé certifiés et livraison rapide au Togo.')">
        <meta http-equiv="x-dns-prefetch-control" content="on">
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/medexpress-logo.svg') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
        <title>@yield('title', 'MedExpress TOGO')</title>
        <link rel="stylesheet" href="{{ asset('css/app.css') }}" fetchpriority="high">
        @stack('styles')
    </head>
    <body>
        @if(request()->routeIs('home'))
            @include('partials.home-header')
        @else
            @include('partials.navbar')
        @endif

        <main>
            @if(session('status'))
                <div class="container flash-message" role="status">{{ session('status') }}</div>
            @endif
            @if(session('warning'))
                <div class="container flash-message flash-error" role="alert">{{ session('warning') }}</div>
            @endif
            @if($errors->has('access'))
                <div class="container flash-message flash-error" role="alert">{{ $errors->first('access') }}</div>
            @endif
            @yield('content')
        </main>

        @include('partials.footer')

        <script src="{{ asset('js/app.js') }}" defer></script>
        @stack('scripts')
    </body>
</html>
