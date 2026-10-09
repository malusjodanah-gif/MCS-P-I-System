(() => {
    const form = document.getElementById('ge-order-form');
    if (!form) return;
    const flag = document.getElementById('inventory-flag');
    const lines = document.getElementById('ge-lines');
    const supplier = document.getElementById('ge-supplier');
    const add = document.getElementById('add-ge-lines');
    const draft = new WeakMap();
    let previousStock = flag.value === 'STOCK';
    const money = (value) => `${supplier.selectedOptions[0]?.dataset.currency || 'PGK'} ${value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    function update() {
        const stock = flag.value === 'STOCK';
        let total = 0;
        lines.querySelectorAll('.ge-line').forEach(row => {
            const item = row.querySelector('.line-item');
            const desc = row.querySelector('.line-description');
            const qty = row.querySelector('.line-quantity');
            const cost = row.querySelector('.line-cost');
            item.setCustomValidity('');
            desc.setCustomValidity('');
            if (!draft.has(row)) draft.set(row, stock ? '' : desc.value);
            if (!previousStock) draft.set(row, desc.value);
            row.querySelectorAll('.stock-fields').forEach(el => { el.hidden = !stock; });
            item.disabled = !stock;
            desc.readOnly = stock;
            desc.value = stock ? (item.selectedOptions[0]?.dataset.description || '') : draft.get(row);
            row.querySelector('.line-uom').value = stock ? (item.selectedOptions[0]?.dataset.uom || '') : '';
            row.querySelector('.line-help').textContent = stock ? 'Description and UOM come from the selected item.' : 'Enter the service or one-off purchase description.';
            const active = (stock ? item.value !== '' : desc.value.trim() !== '') || qty.value !== '' || cost.value !== '';
            item.required = stock && active;
            desc.required = !stock && active;
            qty.required = active;
            cost.required = active;
            const price = Math.round(Number(cost.value) * 10000) / 10000;
            const amount = Math.max(0, Math.round((Number(qty.value) || 0) * (price || 0) * 100) / 100);
            total += amount;
            row.querySelector('.line-total').textContent = money(amount);
            row.querySelector('.line-summary').textContent = active ? `— ${desc.value || 'Incomplete line'} · ${money(amount)}` : '— optional';
        });
        previousStock = stock;
        document.getElementById('ge-grand-total').textContent = money(total);
        add.disabled = lines.children.length >= 100;
        document.getElementById('ge-line-limit').hidden = !add.disabled;
    }
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    lines.addEventListener('click', event => {
        if (!event.target.closest('.clear-line')) return;
        const row = event.target.closest('.ge-line');
        row.querySelectorAll('input,select').forEach(el => { el.value = ''; });
        draft.set(row, '');
        update();
    });
    add.addEventListener('click', () => {
        const start = lines.children.length;
        for (let i = start; i < Math.min(start + 10, 100); i++) {
            const template = document.getElementById('ge-line-template').innerHTML.replaceAll('__INDEX__', String(i));
            lines.insertAdjacentHTML('beforeend', template);
            lines.lastElementChild.querySelector('.line-number').textContent = i + 1;
        }
        lines.children[start].open = true;
        update();
        lines.children[start].querySelector(flag.value === 'STOCK' ? '.line-item' : '.line-description').focus();
    });
    form.addEventListener('invalid', event => {
        const row = event.target.closest('.ge-line');
        if (row) row.open = true;
    }, true);
    form.addEventListener('submit', event => {
        if (!Array.from(lines.querySelectorAll('.line-item,.line-description,.line-quantity,.line-cost')).some(el => !el.disabled && el.value.trim() !== '')) {
            event.preventDefault();
            lines.firstElementChild.open = true;
            const target = lines.firstElementChild.querySelector(flag.value === 'STOCK' ? '.line-item' : '.line-description');
            target.setCustomValidity('Enter at least one complete order line.');
            target.reportValidity();
            target.addEventListener('input', () => target.setCustomValidity(''), {once: true});
        }
    });
    update();
})();
