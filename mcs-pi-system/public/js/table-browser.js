(() => {
    document.querySelectorAll('main table.data').forEach((table, tableIndex) => {
        if (table.dataset.tableBrowser === 'off') return;
        const headers = Array.from(table.querySelectorAll('thead th'));
        const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => !row.classList.contains('order-summary') && row.children.length === headers.length && !row.querySelector('td[colspan]'));
        if (!rows.length) return;
        const columns = headers.map((el, index) => ({name: el.textContent.trim(), index})).filter(el => el.name && !/^actions?$/i.test(el.name));
        const toolbar = document.createElement('div');
        toolbar.className = 'filters';
        const control = (title, tag) => {
            const group = document.createElement('div'); group.className = 'form-group';
            const label = document.createElement('label'); const input = document.createElement(tag);
            input.id = `table-${tableIndex}-${toolbar.children.length}`; label.htmlFor = input.id; label.textContent = title;
            group.append(label, input); toolbar.append(group); return input;
        };
        const search = control('Search this table', 'input'); search.type = 'search'; search.placeholder = 'Search names, IDs and details';
        const column = control('Filter column', 'select'); column.add(new Option('Choose a column', ''));
        columns.forEach(el => column.add(new Option(el.name, el.index)));
        const value = control('Filter value', 'select'); value.disabled = true; value.add(new Option('All values', ''));
        const reset = document.createElement('button'); reset.type = 'button'; reset.className = 'btn btn-secondary'; reset.textContent = 'Reset'; toolbar.append(reset);
        const result = document.createElement('p'); result.className = 'text-muted mb-1'; result.setAttribute('role', 'status'); result.setAttribute('aria-live', 'polite');
        table.parentElement.before(toolbar, result);
        const text = (row, index) => row.children[index]?.textContent.trim() || '';
        const searchable = row => columns.map(el => text(row, el.index)).join(' ').toLocaleLowerCase();
        const grouped = rows.some(row => row.dataset.group);
        const groups = new Map(); rows.forEach(row => { const key = row.dataset.group || row; if (!groups.has(key)) groups.set(key, []); groups.get(key).push(row); });
        function apply() {
            const query = search.value.trim().toLocaleLowerCase();
            let count = 0;
            groups.forEach(group => {
                // Keep a complete GE and its original total together when any line matches.
                const matches = group.some(row => (!query || searchable(row).includes(query)) && (!value.value || text(row, Number(column.value)) === value.value));
                group.forEach(row => { row.hidden = !matches; if (matches) count++; });
                if (group[0].dataset.group) {
                    table.querySelectorAll('tbody .order-summary').forEach(summary => { if (summary.dataset.group === group[0].dataset.group) summary.hidden = !matches; });
                }
            });
            result.textContent = `${count} of ${rows.length} rows shown.${grouped ? ' Matches show the complete GE and its total.' : ''}`;
        }
        column.addEventListener('change', () => {
            value.replaceChildren(new Option('All values', '')); value.disabled = column.value === '';
            if (!value.disabled) Array.from(new Set(rows.map(row => text(row, Number(column.value))).filter(Boolean))).sort((a,b) => a.localeCompare(b, undefined, {numeric:true})).forEach(text => value.add(new Option(text, text)));
            apply();
        });
        search.addEventListener('input', apply); value.addEventListener('change', apply);
        reset.addEventListener('click', () => { search.value = ''; column.value = ''; value.replaceChildren(new Option('All values', '')); value.disabled = true; apply(); });
        apply();
    });
})();
