@props(['category'])

<a class="home-category-card" href="{{ route('catalogue', ['categorie' => $category['nom']]) }}">
    <span class="category-icon">
        <span class="material-symbols-rounded" aria-hidden="true">{{ $category['icone'] }}</span>
    </span>
    <span>{{ $category['nom'] }}</span>
</a>
