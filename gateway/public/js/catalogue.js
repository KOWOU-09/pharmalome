document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-catalog-filters]');
    const grid = document.querySelector('[data-product-grid]');
    if (!form || !grid) return;

    const cards = [...grid.querySelectorAll('[data-product-card]')];
    const search = form.querySelector('[data-filter-search]');
    const category = form.querySelector('[data-filter-category]');
    const prescription = form.querySelector('[data-filter-prescription]');
    const price = form.querySelector('[data-filter-price]');
    const sort = document.querySelector('[data-filter-sort]');
    const count = document.querySelector('[data-product-count]');
    const empty = document.querySelector('[data-no-products]');
    let filterTimer;

    const applyFilters = () => {
        const query = search.value.trim().toLocaleLowerCase();
        const selectedCategory = category.value.toLocaleLowerCase();
        const selectedPrescription = prescription.value;
        const maxPrice = price.value === '' ? Number.POSITIVE_INFINITY : Number(price.value);
        const visibleCards = cards.filter((card) => {
            const matches = (card.dataset.name.toLocaleLowerCase().includes(query)
                || card.dataset.description.toLocaleLowerCase().includes(query))
                && (!selectedCategory || card.dataset.category.toLocaleLowerCase() === selectedCategory)
                && (!selectedPrescription || card.dataset.prescription === selectedPrescription)
                && (!new URLSearchParams(window.location.search).has('promotion') || card.dataset.promotion === '1')
                && Number(card.dataset.price) <= maxPrice;
            card.hidden = !matches;
            return matches;
        });

        if (sort.value === 'price-ascending') {
            visibleCards.sort((a, b) => Number(a.dataset.price) - Number(b.dataset.price));
        } else if (sort.value === 'price-descending') {
            visibleCards.sort((a, b) => Number(b.dataset.price) - Number(a.dataset.price));
        }

        visibleCards.forEach((card) => grid.append(card));
        count.textContent = `${visibleCards.length} produit${visibleCards.length === 1 ? '' : 's'} affiché${visibleCards.length === 1 ? '' : 's'}`;
        empty.hidden = visibleCards.length > 0;
    };

    form.addEventListener('submit', (event) => event.preventDefault());
    const submitFilters = () => {
        const params = new URLSearchParams();
        for (const [key, value] of new FormData(form)) {
            if (value) params.append(key, value);
        }
        params.delete('page');
        window.location.assign(`${form.dataset.resetUrl}?${params.toString()}`);
    };

    search.addEventListener('input', () => {
        window.clearTimeout(filterTimer);
        filterTimer = window.setTimeout(submitFilters, 300);
    });
    price.addEventListener('input', () => {
        window.clearTimeout(filterTimer);
        filterTimer = window.setTimeout(submitFilters, 300);
    });
    category.addEventListener('change', submitFilters);
    prescription.addEventListener('change', submitFilters);
    form.addEventListener('reset', (event) => {
        event.preventDefault();
        window.location.assign(form.dataset.resetUrl);
    });
    sort.addEventListener('change', applyFilters);
    applyFilters();
});
