(() => {
    const order = document.getElementById('receipt-order-line');
    const quantity = document.getElementById('receipt-quantity');
    const note = document.getElementById('receipt-quantity-note');
    if (!order || !quantity || !note) return;
    function update() {
        const selected = order.selectedOptions[0];
        quantity.setCustomValidity('');
        if (!selected) { note.textContent = 'No order line has an outstanding quantity available for receipt.'; return; }
        const ordered = Number(selected.dataset.ordered);
        const received = Number(selected.dataset.received);
        const remaining = Math.max(0, ordered - received);
        const units = selected.dataset.uom ? ` ${selected.dataset.uom}` : ' units';
        quantity.max = remaining;
        let text = `Quantity ordered: ${ordered}${units}. Already received: ${received}${units}. Outstanding: ${remaining}${units}.`;
        if (quantity.value !== '') {
            const entered = Number(quantity.value);
            if (!Number.isInteger(entered) || entered < 1) {
                quantity.setCustomValidity('Enter a positive whole quantity.');
                text += ' Enter a positive whole quantity.';
            } else if (entered > remaining) {
                quantity.setCustomValidity(`Only ${remaining}${units} remain on this order line.`);
                text += ` This receipt exceeds the outstanding quantity by ${entered - remaining}${units}.`;
            } else {
                text += ` After this receipt: ${remaining - entered}${units} outstanding.`;
            }
        }
        note.textContent = text;
    }
    order.addEventListener('change', update);
    quantity.addEventListener('input', update);
    update();
})();
