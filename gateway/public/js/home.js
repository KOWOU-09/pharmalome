document.addEventListener('DOMContentLoaded', () => {
    const storageKey = 'medexpress-favorites';
    const readFavorites = () => {
        try {
            const value = JSON.parse(localStorage.getItem(storageKey) || '[]');
            return Array.isArray(value) ? value.map(Number).filter(Number.isInteger) : [];
        } catch (error) {
            console.warn('Impossible de lire la liste des favoris.', error);
            return [];
        }
    };

    const updateButtons = () => {
        const favorites = readFavorites();
        document.querySelectorAll('[data-favorite]').forEach((button) => {
            const productId = Number(button.closest('.home-product-card')?.querySelector('[data-id]')?.dataset.id);
            const active = favorites.includes(productId);
            button.setAttribute('aria-pressed', String(active));
            button.setAttribute('aria-label', `${active ? 'Retirer' : 'Ajouter'} des favoris`);
        });
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-favorite]');
        if (!button) return;

        const card = button.closest('.home-product-card');
        const productId = Number(card?.querySelector('[data-add-to-cart]')?.dataset.id);
        if (!Number.isInteger(productId)) return;

        const favorites = readFavorites();
        const updated = favorites.includes(productId)
            ? favorites.filter((id) => id !== productId)
            : [...favorites, productId];

        try {
            localStorage.setItem(storageKey, JSON.stringify(updated));
            updateButtons();
        } catch (error) {
            console.error('Impossible de sauvegarder les favoris dans ce navigateur.', error);
            button.setAttribute('aria-pressed', String(!favorites.includes(productId)));
        }
    });

    updateButtons();
});
