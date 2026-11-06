/* Display-only catalog preview. Compatibility never authorizes a business action. */
(function (global) {
    'use strict';
    const failureMessage = 'Unable to load catalog configuration.';

    function catalogIds(value) {
        const ids = value ?? [];
        if (!Array.isArray(ids) || ids.length > 200) {
            throw new Error('Select at most 200 valid catalog IDs.');
        }
        return [...new Set(Array.from(ids, id => {
            const number = typeof id === 'number' ? id
                : (typeof id === 'string' && /^\d+$/.test(id) ? Number(id) : NaN);
            if (!Number.isSafeInteger(number) || number <= 0) {
                throw new Error('Catalog IDs must be positive safe integers.');
            }
            return number;
        }))];
    }

    function compatibleItems(data) {
        const object = value => value !== null && typeof value === 'object' && !Array.isArray(value);
        const messages = value => Array.isArray(value) && Array.from(value).every(message => typeof message === 'string');
        if (!object(data) || !Array.isArray(data.items) || !Array.from(data.items).every(item => object(item)
            && ['SERVICE', 'PRODUCT'].includes(item.type)
            && typeof item.name === 'string' && item.name.trim() !== ''
            && (item.scope_of_work == null || typeof item.scope_of_work === 'string'))) {
            throw new Error('Malformed catalog configuration; review the selected items.');
        }
        if (!messages(data.conflicts) || !messages(data.diagnostics)) {
            throw new Error('Malformed catalog review metadata; review the selected items.');
        }
        const itemReasons = [];
        for (const item of data.items) {
            if ((Object.hasOwn(item, 'compatible') && typeof item.compatible !== 'boolean')
                || ['diagnostics', 'conflicts'].some(key => Object.hasOwn(item, key) && !messages(item[key]))) {
                throw new Error('Malformed catalog item review metadata; review the selected items.');
            }
            if (item.compatible === false) itemReasons.push('A selected catalog item requires review.');
            itemReasons.push(...(item.diagnostics || []), ...(item.conflicts || []));
        }
        if (typeof data.compatible !== 'boolean') {
            throw new Error('Catalog compatibility is unverified; review the selected items.');
        }
        if (!data.compatible || data.conflicts.length || data.diagnostics.length || itemReasons.length) {
            const reasons = [...data.diagnostics, ...data.conflicts, ...itemReasons].filter(message => message.trim() !== '');
            throw new Error(reasons.join(' ') || 'Catalog configuration requires review.');
        }
        // Keep order and repeated commercial instances exactly as returned.
        return data.items;
    }

    function responseError(data) {
        if (!data || typeof data !== 'object' || Array.isArray(data)) return failureMessage;
        const errors = data.errors && typeof data.errors === 'object' && !Array.isArray(data.errors)
            ? Object.values(data.errors).flat().filter(message => typeof message === 'string' && message.trim() !== '') : [];
        return errors.join(' ') || (typeof data.message === 'string' && data.message.trim() !== '' ? data.message : failureMessage);
    }

    global.ordoDealCatalogSelection = function (options) {
        return {
            selectedProductIds: Array.isArray(options.productIds)
                ? options.productIds.map(id => ['number', 'string'].includes(typeof id) ? String(id) : id)
                : (options.productIds ?? []),
            catalogItems: [], catalogError: '', catalogLoading: false,
            catalogRequestSequence: 0,
            catalogScopeOwned: !options.dealId && (!(options.initialScope || '').trim() || (options.generatedScope && options.generatedScope === options.initialScope)),
            catalogLastScope: options.initialScope || '',
            initCatalogSelection() {
                this.$watch('selectedServiceIds', () => this.refreshCatalogConfiguration());
                this.$watch('selectedProductIds', () => this.refreshCatalogConfiguration());
                this.$watch('customerType', () => this.refreshCatalogConfiguration());
                this.refreshCatalogConfiguration();
            },
            catalogScopeEdited() { this.catalogScopeOwned = false; this.catalogLastScope = ''; },
            applyCatalogScope(items) {
                if (!this.catalogScopeOwned) return;
                const element = this.$root.querySelector('[name="scope_of_work"]');
                if (!element) return;
                // Also protect edits whose input callback has not fired yet.
                if (element.value !== this.catalogLastScope) {
                    this.catalogScopeEdited();
                    return;
                }
                this.catalogLastScope = items.filter(item => (item.scope_of_work || '').trim())
                    .map(item => `${item.type === 'PRODUCT' ? 'Product' : 'Service'}: ${item.name}\n${item.scope_of_work}`)
                    .join('\n\n');
                element.value = this.catalogLastScope;
            },
            async refreshCatalogConfiguration() {
                const sequence = ++this.catalogRequestSequence;
                // Discard unsaved display data immediately, including on removal or validation failure.
                this.catalogItems = []; this.catalogError = '';
                this.applyCatalogScope([]);
                try {
                    const services = catalogIds(this.selectedServiceIds);
                    const products = catalogIds(this.selectedProductIds);
                    if (!services.length && !products.length) return;
                    this.catalogLoading = true;
                    const token = this.$root.querySelector('[name="_token"]')?.value || '';
                    const response = await fetch(options.url, {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify({ catalog_selection_present: 1, service_ids: services, product_ids: products,
                            customer_type: this.customerType, deal_id: options.dealId || null }),
                    });
                    if (sequence !== this.catalogRequestSequence) return;
                    if (!response || typeof response.ok !== 'boolean' || typeof response.json !== 'function') {
                        throw new Error('Malformed catalog response; review the selected items.');
                    }
                    const data = await response.json();
                    if (sequence !== this.catalogRequestSequence) return;
                    if (!response.ok) throw new Error(responseError(data));
                    this.catalogItems = compatibleItems(data);
                    this.applyCatalogScope(this.catalogItems);
                } catch (error) {
                    if (sequence === this.catalogRequestSequence) {
                        this.catalogItems = [];
                        this.catalogError = typeof error?.message === 'string' && error.message.trim() !== '' ? error.message : failureMessage;
                    }
                } finally {
                    if (sequence === this.catalogRequestSequence) this.catalogLoading = false;
                }
            },
        };
    };
})(typeof window !== 'undefined' ? window : globalThis);
