/* DOM bridge only. Never changes submitted catalog fields or business permissions. */
(function (global) {
    'use strict';
    const selectionSelector = '[name="service_options[]"], [name="product_options[]"]';
    const reviewMessage = 'Review required: custom, ambiguous or unavailable catalog selections cannot generate scope.';
    function attach(form) {
        if (form.ordoCatalogPreview) return form.ordoCatalogPreview;
        const doc = form.ownerDocument;
        const status = form.querySelector('[data-catalog-status]');
        const list = form.querySelector('[data-catalog-items]');
        const scope = form.querySelector('[name="scope_of_work"]');
        let state = null, ready = false, epoch = 0, scheduled = false, signature = '', missingLegacy = false;
        function display(kind, message, items = []) {
            if (!status || !list) return;
            status.dataset.state = kind;
            status.textContent = message;
            list.replaceChildren();
            for (const item of items) {
                const row = doc.createElement('li');
                row.textContent = `${item.type === 'PRODUCT' ? 'Product' : 'Service'}: ${item.name}`;
                list.appendChild(row); // Never deduplicate commercial instances or interpret HTML.
            }
        }
        function invalidate() {
            ++epoch;
            if (state) ++state.catalogRequestSequence;
        }
        function stop() {
            invalidate();
            if (state) state.catalogScopeEdited();
            state = null;
            ready = false;
            signature = '';
            display('unavailable', 'Catalog preview unavailable until the form is ready.');
        }
        function selection() {
            const services = [], products = [], entries = [];
            let review = missingLegacy || Boolean(form.querySelector('#services-other-toggle:checked'));
            for (const input of form.querySelectorAll(selectionSelector)) {
                if (!input.checked) continue;
                const id = input.dataset.catalogId || '';
                entries.push([input.name, input.value, id]);
                if (input.value === 'Others' || !/^\d+$/.test(id)
                    || !Number.isSafeInteger(Number(id)) || Number(id) <= 0) review = true;
                else (input.name === 'service_options[]' ? services : products).push(Number(id));
            }
            for (const input of form.querySelectorAll('[name="services_other[]"], [name="products_other_entries[]"], [name="products_other"], [name="service_identification_custom[]"], #services-other-input, #products-other-input')) {
                if ((input.value || '').trim()) { review = true; entries.push(['custom', input.value]); }
            }
            const customer = form.querySelector('[name="customer_type"]:checked')?.value;
            const customerType = customer === 'business' ? 'Business' : customer === 'individual' ? 'Individual' : '';
            return { services, products, review, customerType, signature: JSON.stringify([entries, customerType, review]) };
        }
        function covers(items, selected) {
            const expected = new Set([...selected.services.map(id => `SERVICE:${id}`), ...selected.products.map(id => `PRODUCT:${id}`)]);
            const seen = new Set();
            for (const item of items) {
                const id = item.type === 'SERVICE' ? item.service_id : item.product_id;
                const key = `${item.type}:${id}`;
                if (!expected.has(key)) return false;
                seen.add(key);
            }
            return expected.size === seen.size;
        }
        async function refresh() {
            if (!ready || !state) return;
            const selected = selection();
            if (selected.signature === signature) return;
            signature = selected.signature;
            invalidate();
            const current = state, generation = epoch;
            current.selectedServiceIds = selected.services;
            current.selectedProductIds = selected.products;
            current.customerType = selected.customerType;
            current.catalogItems = [];
            if (selected.review) {
                current.catalogScopeEdited();
                display('review', reviewMessage);
                return;
            }
            if (!selected.customerType && (selected.services.length || selected.products.length)) {
                display('unavailable', 'Select a customer type to preview catalog configuration.');
                return;
            }
            display(selected.services.length || selected.products.length ? 'loading' : 'no-selection',
                selected.services.length || selected.products.length ? 'Loading catalog preview…' : 'No catalog selection.');
            await current.refreshCatalogConfiguration();
            if (!ready || state !== current || epoch !== generation) return;
            if (selection().signature !== signature) { signature = ''; return refresh(); }
            if (current.catalogError) display('review', `Review required: ${current.catalogError}`);
            else if (selected.services.length || selected.products.length) display('verified-compatible',
                'Catalog preview verified compatible for this selection. Informational only.', current.catalogItems);
        }
        function schedule() {
            // Synchronous invalidation precedes deferred reads after existing filtering/custom-row handlers.
            invalidate();
            if (scheduled) return;
            scheduled = true;
            queueMicrotask(() => { scheduled = false; signature = ''; void refresh(); });
        }
        function contextReady(event) {
            stop();
            const detail = event.detail || {}, id = detail.dealId;
            if (id != null && (!/^\d+$/.test(String(id)) || !Number.isSafeInteger(Number(id)) || Number(id) <= 0)) {
                display('review', 'Review required: the Deal context is unavailable.');
                return;
            }
            if (typeof global.ordoDealCatalogSelection !== 'function' || !scope || !form.dataset.catalogPreviewUrl) return;
            missingLegacy = Boolean(detail.unresolved);
            for (const [field, names] of Object.entries(detail.legacyNames || {})) {
                const checked = Array.from(form.querySelectorAll(selectionSelector)).filter(input => input.name === field && input.checked);
                if (!Array.isArray(names) || names.some(name => typeof name !== 'string' || !checked.some(input => input.value === name))) missingLegacy = true;
            }
            state = global.ordoDealCatalogSelection({url: form.dataset.catalogPreviewUrl,
                dealId: id == null ? null : Number(id), initialScope: scope.value});
            state.$root = form;
            const current = state, apply = current.applyCatalogScope;
            current.applyCatalogScope = function (items) {
                if (!ready || state !== current || selection().signature !== signature) return;
                if (items === current.catalogItems && !covers(items, selection())) {
                    throw new Error('Catalog response does not represent the complete selection.');
                }
                apply.call(current, items);
            };
            ready = true;
            void refresh();
        }
        form.addEventListener('deal-catalog:context-ready', contextReady);
        form.addEventListener('deal-catalog:context-pending', stop);
        form.addEventListener('reset', stop);
        doc.addEventListener('deal-drawer:closed', stop);
        function changed(event) {
            if (event.target === scope) { state?.catalogScopeEdited(); return; }
            const name = event.target.name || '', id = event.target.id || '';
            if (['customer_type', 'service_options[]', 'product_options[]', 'service_area_options[]', 'services_other[]',
                'products_other_entries[]', 'products_other', 'service_identification_custom[]'].includes(name)
                || ['services-other-toggle', 'services-other-input', 'products-other-input'].includes(id)) schedule();
        }
        form.addEventListener('change', changed);
        form.addEventListener('input', changed);
        // Custom rows are inserted/removed without guaranteed change events. Ignore preview rendering itself.
        const observer = typeof global.MutationObserver === 'function' ? new global.MutationObserver(() => {
            if (ready && selection().signature !== signature) schedule();
        }) : null;
        observer?.observe(form, {childList: true, subtree: true});
        form.ordoCatalogPreview = {refresh, stop};
        return form.ordoCatalogPreview;
    }
    global.ordoDealCatalogPreview = attach;
    if (global.document) {
        const initialize = () => {
            const form = global.document.getElementById('createDealForm');
            if (form) attach(form);
        };
        if (global.document.readyState === 'loading') global.document.addEventListener('DOMContentLoaded', initialize, {once: true});
        else initialize();
    }
})(typeof window !== 'undefined' ? window : globalThis);
