document.addEventListener('DOMContentLoaded', () => {
    const storageKey = 'pharmalome-cart';

    const readCart = () => {
        try {
            const cart = JSON.parse(localStorage.getItem(storageKey) || '[]');
            return Array.isArray(cart)
                ? cart.filter((item) => item && Number.isInteger(Number(item.id)) && Number(item.quantity) > 0)
                : [];
        } catch (error) {
            console.warn('Le panier enregistré est invalide et a été réinitialisé.', error);
            localStorage.removeItem(storageKey);
            return [];
        }
    };

    const saveCart = (cart) => {
        localStorage.setItem(storageKey, JSON.stringify(cart));
        window.dispatchEvent(new CustomEvent('pharmalome:cart-updated'));
    };

    const updateBadge = () => {
        const total = readCart().reduce((count, item) => count + Number(item.quantity), 0);
        document.querySelectorAll('[data-cart-count]').forEach((badge) => {
            badge.textContent = String(total);
        });
    };

    window.PharmalomeCart = {
        getItems: readCart,
        saveItems: saveCart,
    };

    if (document.querySelector('[data-clear-cart]')) {
        saveCart([]);
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-add-to-cart]');
        if (!button || button.disabled) return;

        const id = Number(button.dataset.id);
        const stock = Number(button.dataset.stock);
        const cart = readCart();
        const existing = cart.find((item) => Number(item.id) === id);

        if (existing && Number(existing.quantity) >= stock) {
            const message = document.querySelector('[data-cart-message]');
            if (message) message.textContent = 'La quantité maximale disponible est déjà dans votre panier.';
            return;
        }

        if (existing) {
            existing.quantity = Number(existing.quantity) + 1;
        } else {
            cart.push({
                id,
                name: button.dataset.name,
                price: Number(button.dataset.price),
                stock,
                prescription: button.dataset.prescription === '1',
                quantity: 1,
            });
        }

        saveCart(cart);
        const message = document.querySelector('[data-cart-message]');
        if (message) message.textContent = 'Produit ajouté au panier.';
        const originalText = button.textContent;
        button.textContent = 'Ajouté ✓';
        window.setTimeout(() => {
            if (button.isConnected) button.textContent = originalText;
        }, 1200);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-confirm]');
        if (form && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-toggle-password]');
        if (!button) return;

        const input = button.parentElement.querySelector('input');
        if (!input) return;

        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        button.querySelector('.material-symbols-rounded').textContent = visible ? 'visibility_off' : 'visibility';
    });

    updateBadge();
    window.addEventListener('storage', updateBadge);
    window.addEventListener('pharmalome:cart-updated', updateBadge);
});
