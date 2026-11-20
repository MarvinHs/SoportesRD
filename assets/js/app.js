// SoportesRD - scripts del sitio y del panel
document.addEventListener('DOMContentLoaded', () => {
    // Menú móvil
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-nav]');
    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open);
        });
    }

    // Zona de arrastrar y soltar archivos
    document.querySelectorAll('[data-dropzone]').forEach(zone => {
        const input = zone.querySelector('input[type=file]');
        const list = zone.querySelector('[data-file-list]');
        const render = () => {
            list.innerHTML = '';
            [...input.files].forEach(f => {
                const li = document.createElement('li');
                li.textContent = '📎 ' + f.name + ' (' + (f.size / 1024 / 1024).toFixed(2) + ' MB)';
                list.appendChild(li);
            });
        };
        input.addEventListener('change', render);
        ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, () => zone.classList.add('drag')));
        ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, () => zone.classList.remove('drag')));
    });

    // Panel: barra lateral móvil
    const sideToggle = document.querySelector('[data-side-toggle]');
    if (sideToggle) sideToggle.addEventListener('click', () => document.body.classList.toggle('side-open'));

    // Confirmaciones
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('submit', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
    });

    // Cotizaciones: líneas dinámicas
    const items = document.querySelector('[data-items]');
    if (items) {
        const tpl = document.querySelector('#item-tpl');
        const itbisRate = parseFloat(items.dataset.itbis || '18') / 100;
        const fmt = n => 'RD$ ' + n.toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const calc = () => {
            let sub = 0;
            items.querySelectorAll('tr').forEach(tr => {
                const c = parseFloat(tr.querySelector('[name="cant[]"]').value) || 0;
                const p = parseFloat(tr.querySelector('[name="precio[]"]').value) || 0;
                tr.querySelector('[data-imp]').textContent = fmt(c * p);
                sub += c * p;
            });
            const applyItbis = document.querySelector('[name=aplica_itbis]')?.checked;
            const it = applyItbis ? sub * itbisRate : 0;
            document.querySelector('[data-sub]').textContent = fmt(sub);
            document.querySelector('[data-itbis-val]').textContent = fmt(it);
            document.querySelector('[data-total]').textContent = fmt(sub + it);
        };
        document.querySelector('[data-add-item]').addEventListener('click', () => { items.appendChild(tpl.content.cloneNode(true)); calc(); });
        items.addEventListener('input', calc);
        items.addEventListener('click', e => { if (e.target.closest('[data-del]')) { e.target.closest('tr').remove(); calc(); } });
        document.querySelector('[name=aplica_itbis]')?.addEventListener('change', calc);
        calc();
    }
});
