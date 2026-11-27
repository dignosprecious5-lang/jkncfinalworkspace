/* Existing Alpine Deal form: read-only catalog preview and managed default scope. */
(function (global) {
    'use strict';
    global.ordoDealCatalogSelection = function (options) {
        return {
            selectedProductIds: (options.productIds || []).map(String),
            catalogItems: [], catalogError: '', catalogLoading: false,
            catalogRequestSequence: 0,
            catalogScopeOwned: !options.dealId && (!(options.initialScope || '').trim() || (options.generatedScope && options.generatedScope === options.initialScope)),
            catalogLastScope: options.generatedScope || '',
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
                const services = [...new Set((this.selectedServiceIds || []).map(Number))];
                const products = [...new Set((this.selectedProductIds || []).map(Number))];
                if (!services.length && !products.length) { this.catalogLoading = false; return; }
                this.catalogLoading = true;
                try {
                    const token = this.$root.querySelector('[name="_token"]')?.value || '';
                    const response = await fetch(options.url, {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify({ catalog_selection_present: 1, service_ids: services, product_ids: products,
                            customer_type: this.customerType, deal_id: options.dealId || null }),
                    });
                    const data = await response.json();
                    if (sequence !== this.catalogRequestSequence) return;
                    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Unable to load catalog configuration.');
                    this.catalogItems = data.items || [];
                    this.catalogError = (data.conflicts || []).length ? 'Resolve conflicting product term headings before issuing: ' + data.conflicts.join(', ') : '';
                    this.applyCatalogScope(this.catalogItems);
                } catch (error) {
                    if (sequence === this.catalogRequestSequence) this.catalogError = error.message || 'Unable to load catalog configuration.';
                } finally {
                    if (sequence === this.catalogRequestSequence) this.catalogLoading = false;
                }
            },
        };
    };
})(typeof window !== 'undefined' ? window : globalThis);
