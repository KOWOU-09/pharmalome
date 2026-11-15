document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-checkout-form]');
    const itemsInput = document.querySelector('[data-checkout-items]');
    const summary = document.querySelector('[data-checkout-summary]');
    const total = document.querySelector('[data-checkout-total]');
    const delivery = document.querySelector('[data-checkout-delivery]');
    const submit = document.querySelector('[data-checkout-submit]');
    const empty = document.querySelector('[data-checkout-empty]');

    if (!form || !window.PharmalomeCart) return;

    const money = (amount) => `${Number(amount).toLocaleString('fr-FR')} FCFA`;
    const render = () => {
        const cart = window.PharmalomeCart.getItems();
        const isDelivery = form.querySelector('[name="mode_reception"]').value === 'livraison';
        itemsInput.value = JSON.stringify(cart.map((item) => ({
            id: Number(item.id),
            quantity: Number(item.quantity),
        })));

        summary.replaceChildren();
        for (const item of cart) {
            const row = document.createElement('div');
            row.className = 'summary-line';
            const name = document.createElement('span');
            name.textContent = `${item.name} × ${item.quantity}`;
            const price = document.createElement('strong');
            price.textContent = money(Number(item.price) * Number(item.quantity));
            row.append(name, price);
            summary.append(row);
        }

        const subtotal = cart.reduce((amount, item) => amount + Number(item.price) * Number(item.quantity), 0);
        const shipping = cart.length && isDelivery ? 1000 : 0;
        delivery.textContent = money(shipping);
        total.textContent = money(subtotal + shipping);
        empty.hidden = cart.length > 0;
        submit.disabled = cart.length === 0;
    };

    form.addEventListener('change', render);
    form.addEventListener('submit', (event) => {
        render();
        if (window.PharmalomeCart.getItems().length === 0) {
            event.preventDefault();
        }
    });
    window.addEventListener('pharmalome:cart-updated', render);
    render();
});
