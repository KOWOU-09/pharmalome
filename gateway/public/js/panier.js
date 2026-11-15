document.addEventListener('DOMContentLoaded', () => {
    const list = document.querySelector('[data-cart-items]');
    if (!list || !window.PharmalomeCart) return;

    const money = (amount) => `${Number(amount).toLocaleString('fr-FR')} FCFA`;

    const render = () => {
        const cart = window.PharmalomeCart.getItems();
        const subtotal = cart.reduce((total, item) => total + Number(item.price) * Number(item.quantity), 0);
        const delivery = cart.length ? 1000 : 0;

        list.replaceChildren();
        cart.forEach((item) => {
            const row = document.createElement('article');
            row.className = 'cart-item';
            row.dataset.cartItem = item.id;

            const details = document.createElement('div');
            const title = document.createElement('h3');
            title.textContent = item.name;
            const description = document.createElement('p');
            description.className = 'muted';
            description.textContent = `${item.quantity} boîte(s) · ${money(Number(item.price) * Number(item.quantity))}${item.prescription ? ' · Ordonnance requise' : ''}`;
            details.append(title, description);

            const controls = document.createElement('div');
            controls.className = 'qty-control';
            const decrease = document.createElement('button');
            decrease.type = 'button';
            decrease.dataset.cartAction = 'decrease';
            decrease.dataset.id = item.id;
            decrease.textContent = '−';
            decrease.setAttribute('aria-label', `Retirer une unité de ${item.name}`);
            const quantity = document.createElement('span');
            quantity.textContent = String(item.quantity);
            const increase = document.createElement('button');
            increase.type = 'button';
            increase.dataset.cartAction = 'increase';
            increase.dataset.id = item.id;
            increase.textContent = '+';
            increase.setAttribute('aria-label', `Ajouter une unité de ${item.name}`);
            increase.disabled = Number(item.quantity) >= Number(item.stock);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'cart-remove';
            remove.dataset.cartAction = 'remove';
            remove.dataset.id = item.id;
            remove.textContent = 'Supprimer';
            controls.append(decrease, quantity, increase, remove);
            row.append(details, controls);
            list.append(row);
        });

        document.querySelector('[data-cart-subtotal]').textContent = money(subtotal);
        document.querySelector('[data-cart-delivery]').textContent = money(delivery);
        document.querySelector('[data-cart-total]').textContent = money(subtotal + delivery);
        document.querySelector('[data-cart-empty]').hidden = cart.length > 0;
        const checkout = document.querySelector('[data-checkout-link]');
        checkout.setAttribute('aria-disabled', String(cart.length === 0));
        checkout.classList.toggle('is-disabled', cart.length === 0);
    };

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-cart-action]');
        if (!button) return;

        const cart = window.PharmalomeCart.getItems();
        const item = cart.find((entry) => Number(entry.id) === Number(button.dataset.id));
        if (!item) return;

        if (button.dataset.cartAction === 'remove') {
            window.PharmalomeCart.saveItems(cart.filter((entry) => Number(entry.id) !== Number(item.id)));
        } else {
            const delta = button.dataset.cartAction === 'increase' ? 1 : -1;
            item.quantity = Number(item.quantity) + delta;
            window.PharmalomeCart.saveItems(cart.filter((entry) => Number(entry.quantity) > 0));
        }
    });

    document.querySelector('[data-checkout-link]').addEventListener('click', (event) => {
        if (event.currentTarget.getAttribute('aria-disabled') === 'true') event.preventDefault();
    });

    window.addEventListener('pharmalome:cart-updated', render);
    render();
});
