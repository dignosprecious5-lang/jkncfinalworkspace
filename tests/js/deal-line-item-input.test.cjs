const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/js/deals-engine.js', 'utf8');
function setup() {
    const fields = new Map();
    const field = id => {
        if (!fields.has(id)) fields.set(id, {value: '', style: {}, innerHTML: '', attributes: {}, focus() {}, select() {},
            setAttribute(name,value) {this.attributes[name]=value;}, removeAttribute(name) {delete this.attributes[name];},
            querySelectorAll() {
                if (this.parsedHtml !== this.innerHTML) {
                    this.parsedHtml=this.innerHTML;
                    this.options=[...this.innerHTML.matchAll(/<button[^>]*data-catalog-choice[^>]*>/g)].map(match=>{
                        const attr=name=>(match[0].match(new RegExp(name+'="([^"]*)"'))||[])[1];
                        return {id:attr('id'),dataset:{type:attr('data-type'),id:attr('data-id'),serviceId:attr('data-service-id')},
                            classList:{toggle() {}},scrollIntoView() {}};
                    });
                }
                return this.options || [];
            }});
        return fields.get(id);
    };
    field('lineItemType').value = 'SERVICE';
    field('lineItemQty').value = '1';
    const catalog = [
        {id: 21, type: 'SERVICE', name: 'Audit Support / Coordination', service_identifications: [{id:1,name:'Accounting & Compliance Advisory'}], price: 300, desc: 'Service scope', selectable: true},
        {id: 42, type: 'PRODUCT', name: 'Office Supplies', price: 125, desc: 'Product scope', selectable: true},
        {id: 43, type: 'PRODUCT', name: 'Inactive', selectable: false},
    ];
    const context = vm.createContext({
        window: {DEAL_BOOTSTRAP: {lineItemCatalog: catalog}},
        document: {getElementById: field, querySelector() {return null;}},
        dealLineItems: [], dealAvailedItems: [], dealDefaultRoute: 'Regular', dealDefaultBilling: 'Full Payment Before Service', dealScopeOfWork: '',
        setLineItemBilling() {}, getLineItemBillingValue() {return 'Full Payment Before Service';},
        escapeHtml: value => String(value).replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;'),
        syncDealPaymentTerms() {}, renderDealLineItems() {}, console,
        alert(message) {throw new Error(message);},
        async fetch(url, options) {
            context.payload = JSON.parse(options.body);
            return {ok: true, async json() {return {success: true, items: [context.payload]};}};
        },
    });
    const state = source.slice(source.indexOf('    let currentSelectedLineItemId ='), source.indexOf('    function formatCurrency('));
    const input = source.slice(source.indexOf('    const lineItemCatalog ='), source.indexOf('    function openLineItemViewModal('));
    vm.runInContext(state + input, context);
    return {field, context, catalog, run: code => vm.runInContext(code, context)};
}
test('searchable picker lists both item types by actual names and excludes inactive choices', () => {
    const {field, run} = setup();
    run('populateLineItemDropdown()');
    assert.match(field('lineItemCatalogChoices').innerHTML, /Audit Support \/ Coordination/);
    assert.match(field('lineItemCatalogChoices').innerHTML, /Office Supplies/);
    assert.doesNotMatch(field('lineItemCatalogChoices').innerHTML, /Inactive|Service:|Product:/);
    run('handleLineItemSelectChange("Office")');
    assert.match(field('lineItemCatalogChoices').innerHTML,/Office Supplies/);
    assert.doesNotMatch(field('lineItemCatalogChoices').innerHTML,/Audit Support/);
});
test('catalog selection populates scope and price and sends correct typed database ID', async () => {
    for (const [type, name, id, price] of [['SERVICE','Audit Support / Coordination',21,'300.00'],['PRODUCT','Office Supplies',42,'125.00']]) {
        const {field, context, run} = setup();
        field('lineItemType').value = type;
        field('lineItemNameSelect').value = name;
        run('handleLineItemSelectChange(document.getElementById("lineItemNameSelect").value)');
        assert.equal(field('lineItemUnitPrice').value, price);
        assert.match(field('lineItemDescription').value, /scope/);
        await run('handleSaveLineItem({preventDefault(){}})');
        assert.equal(context.payload[type.toLowerCase() + '_id'], id);
        assert.equal(context.payload.custom, false);
        assert.equal(context.payload.name, name);
    }
});
test('direct custom typing clears old defaults and sends no catalog identity for both types', async () => {
    for (const type of ['SERVICE','PRODUCT']) {
        const {field, context, run} = setup();
        field('lineItemType').value = type;
        run(`handleLineItemSelectChange(${JSON.stringify(type === 'SERVICE' ? 'Audit Support / Coordination' : 'Office Supplies')})`);
        field('lineItemNameSelect').value = 'Custom ' + type;
        run('handleLineItemSelectChange(document.getElementById("lineItemNameSelect").value)');
        assert.equal(field('lineItemDescription').value, '');
        assert.equal(field('lineItemUnitPrice').value, '0.00');
        assert.equal(field('lineItemUnitPrice').readOnly, false);
        field('lineItemUnitPrice').value = '75';
        await run('handleSaveLineItem({preventDefault(){}})');
        assert.equal(context.payload.custom, true);
        assert.equal(context.payload.product_id, null);
        assert.equal(context.payload.service_id, null);
        assert.equal(context.payload.name, 'Custom ' + type);
    }
});
test('editing an existing custom item with a catalog name keeps its custom identity', async () => {
    const {context, run} = setup();
    context.dealLineItems.push({id:'legacy-1',type:'PRODUCT',name:'Office Supplies',custom:true,unitPrice:80});
    run('openEditLineItemModal("legacy-1")');
    await run('handleSaveLineItem({preventDefault(){}})');
    assert.equal(context.payload.custom, true);
    assert.equal(context.payload.product_id, null);
    assert.equal(context.payload.unit_price, 80);
});
test('the existing modal contains one direct name input and no manual entry toggle', () => {
    const blade = fs.readFileSync('resources/views/deals/show.blade.php','utf8');
    assert.match(blade, /id="lineItemNameSelect"[^>]*role="combobox"[^>]*aria-controls="lineItemCatalogChoices"/);
    assert.doesNotMatch(blade, /<datalist id="lineItemCatalogChoices"/);
    assert.doesNotMatch(blade, /<textarea id="lineItemDescription"[^>]*readonly/);
    assert.doesNotMatch(blade, /lineItemEntryModeToggle|lineItemCustomName|Add Custom Item \/ Manual Entry/);
    assert.doesNotMatch(source, /toggleLineItemEntryMode|const servicesCatalog|const productsCatalog/);
});

test('editing a legacy catalog item retains its existing unique catalog resolution', async () => {
    const {context, run} = setup();
    context.dealLineItems.push({id:'legacy-2',type:'SERVICE',name:'Audit Support / Coordination',custom:false,unitPrice:80});
    run('openEditLineItemModal("legacy-2")');
    await run('handleSaveLineItem({preventDefault(){}})');
    assert.equal(context.payload.custom, false);
    assert.equal(context.payload.service_id, 21);
    assert.equal(context.payload.unit_price, 80);
});

function linkedSetup() {
    const state=setup();
    state.catalog.push({id:22,type:'SERVICE',name:'Tax Filing',service_identifications:[{id:2,name:'Tax Advisory'}],price:400,desc:'Tax scope',selectable:true});
    state.catalog.push({id:44,type:'PRODUCT',name:'Audit Binder',price:50,desc:'Binder scope',selectable:true,linked_service_ids:[21]});
    state.catalog.push({id:45,type:'PRODUCT',name:'Tax Binder',price:70,desc:'Tax binder scope',selectable:true,linked_service_ids:[22]});
    return state;
}
test('all identifications and services appear before the separate complete Product section', () => {
    const {field,run,catalog}=linkedSetup();
    catalog.push({id:23,type:'SERVICE',name:'Bookkeeping',selectable:true,service_identifications:[{id:1,name:'Accounting & Compliance Advisory'}]});
    run('openLineItemPicker()');
    const html=field('lineItemCatalogChoices').innerHTML;
    assert.match(html,/aria-label="Service"/);assert.match(html,/aria-label="Product"/);
    assert.ok(html.indexOf('Accounting &amp; Compliance Advisory') < html.indexOf('Audit Support'));
    assert.ok(html.indexOf('Bookkeeping') < html.indexOf('Tax Advisory'));
    assert.ok(html.indexOf('Tax Filing') < html.indexOf('aria-label="Product"'));
    for(const name of ['Audit Binder','Tax Binder','Office Supplies']) assert.ok(html.indexOf(name)>html.indexOf('aria-label="Product"'));
    assert.equal((html.match(/data-id="44"/g)||[]).length,1);
    assert.doesNotMatch(html,/UNLINKED PRODUCTS|show linked Products/);
    run('selectLineItemCatalogItem("SERVICE",22)');
    assert.equal(field('lineItemType').value,'SERVICE');assert.equal(field('lineItemUnitPrice').value,'400.00');
    assert.equal(field('lineItemRoute').value,'Regular');assert.equal(field('lineItemRoute').disabled,true);
    assert.equal(field('lineItemCatalogChoices').style.display,'none');
});
test('search matches identification, individual service, and product names in their own sections', () => {
    const {field,run,catalog}=linkedSetup();
    catalog.push({id:23,type:'SERVICE',name:'Bookkeeping',selectable:true,service_identifications:[{id:1,name:'Accounting & Compliance Advisory'}]});
    run('handleLineItemSelectChange("COMPLIANCE")');
    let html=field('lineItemCatalogChoices').innerHTML;
    assert.match(html,/Audit Support/);assert.match(html,/Bookkeeping/);assert.doesNotMatch(html,/Tax Filing|Tax Binder/);
    run('handleLineItemSelectChange("bookkeeping")');html=field('lineItemCatalogChoices').innerHTML;
    assert.match(html,/Accounting &amp; Compliance Advisory/);assert.match(html,/Bookkeeping/);assert.doesNotMatch(html,/Audit Support/);
    run('handleLineItemSelectChange("Tax Binder")');html=field('lineItemCatalogChoices').innerHTML;
    assert.match(html,/Tax Binder/);assert.doesNotMatch(html,/Tax Filing|Audit Binder/);
    assert.ok(html.indexOf('Tax Binder')>html.indexOf('aria-label="Product"'));
    run('handleLineItemSelectChange("does not exist")');html=field('lineItemCatalogChoices').innerHTML;
    assert.match(html,/No services match/);assert.match(html,/No products match/);
});
test('linked Product selection keeps Product ID and validated Service context with editable scope', async () => {
    const {field,context,run}=linkedSetup();run('selectLineItemCatalogItem("SERVICE",21)');
    run('selectLineItemCatalogItem("PRODUCT",44,21)');
    assert.equal(field('lineItemType').value,'PRODUCT');assert.equal(field('lineItemNameSelect').value,'Audit Binder');
    assert.equal(field('lineItemUnitPrice').value,'50.00');assert.equal(field('lineItemDescription').readOnly,false);
    field('lineItemDescription').value='Client agreed scope';
    await run('handleSaveLineItem({preventDefault(){}})');
    assert.equal(context.payload.product_id,44);assert.equal(context.payload.associated_service_id,21);
    assert.equal(context.payload.service_id,null);assert.equal(context.payload.description,'Client agreed scope');
});
test('services do not need linked Products and direct custom typing stays available', async () => {
    const {field,context,run}=setup();run('selectLineItemCatalogItem("SERVICE",21)');
    run('openLineItemPicker()');assert.match(field('lineItemCatalogChoices').innerHTML,/Office Supplies/);
    field('lineItemNameSelect').value='Custom item';run('handleLineItemSelectChange("Custom item")');
    field('lineItemUnitPrice').value='20';await run('handleSaveLineItem({preventDefault(){}})');
    assert.equal(context.payload.custom,true);assert.equal(context.payload.associated_service_id,null);
    assert.equal(context.payload.product_id,null);assert.equal(context.payload.service_id,null);
});
test('keyboard arrows and Enter skip group headings and select services or products; Escape closes suggestions', () => {
    const {field,run}=linkedSetup();run('openLineItemPicker()');
    run('handleLineItemPickerKeydown({key:"ArrowDown",preventDefault(){}})');
    assert.equal(field('lineItemNameSelect').attributes['aria-activedescendant'],'lineItemChoice-0');
    run('handleLineItemPickerKeydown({key:"Enter",preventDefault(){}})');
    assert.equal(field('lineItemNameSelect').value,'Audit Support / Coordination');
    run('handleLineItemPickerKeydown({key:"ArrowDown",preventDefault(){}})');
    run('handleLineItemPickerKeydown({key:"ArrowDown",preventDefault(){}})');
    run('handleLineItemPickerKeydown({key:"Enter",preventDefault(){}})');
    assert.equal(field('lineItemNameSelect').value,'Tax Filing');
    run('openLineItemPicker(); handleLineItemPickerKeydown({key:"End",preventDefault(){}}); handleLineItemPickerKeydown({key:"Enter",preventDefault(){}})');
    assert.equal(field('lineItemNameSelect').value,'Tax Binder');
    assert.equal(field('lineItemType').value,'PRODUCT');
    run('openLineItemPicker(); handleLineItemPickerKeydown({key:"Escape",preventDefault(){}})');
    assert.equal(field('lineItemNameSelect').attributes['aria-expanded'],'false');
});
test('explicit ID selection disambiguates duplicate names and switching to custom clears source details', async () => {
    const {field,context,catalog,run}=linkedSetup();catalog.push({id:46,type:'PRODUCT',name:'Audit Binder',price:90,desc:'Other scope',selectable:true,linked_service_ids:[22]});
    run('selectLineItemCatalogItem("PRODUCT",46,22)');assert.equal(field('lineItemUnitPrice').value,'90.00');
    run('handleLineItemSelectChange("Handmade binder")');field('lineItemNameSelect').value='Handmade binder';
    assert.equal(field('lineItemDescription').value,'');assert.equal(field('lineItemUnitPrice').value,'0.00');
    await run('handleSaveLineItem({preventDefault(){}})');assert.equal(context.payload.associated_service_id,null);
    assert.equal(context.payload.custom,true);
});
test('reopening a linked Product restores its Service context and agreed pricing', async () => {
    const {field,context,run}=linkedSetup();
    context.dealLineItems.push({id:8,type:'PRODUCT',product_id:44,associated_service_id:21,name:'Audit Binder',description:'Agreed',qty:2,unitPrice:65});
    run('openEditLineItemModal(8)');assert.equal(field('lineItemServiceContext').textContent,'Audit Support / Coordination');
    assert.equal(field('lineItemDescription').value,'Agreed');assert.equal(field('lineItemUnitPrice').value,'65.00');
    await run('handleSaveLineItem({preventDefault(){}})');assert.equal(context.payload.associated_service_id,21);
});

test('ArrowUp starts at the final choice and selection keeps identification order', () => {
    const {field,run}=linkedSetup();run('openLineItemPicker(); handleLineItemPickerKeydown({key:"ArrowUp",preventDefault(){}})');
    const options=field('lineItemCatalogChoices').querySelectorAll();
    assert.equal(field('lineItemNameSelect').attributes['aria-activedescendant'],options[options.length-1].id);
    run('selectLineItemCatalogItem("SERVICE",22)');
    assert.equal(field('lineItemCatalogChoices').querySelectorAll()[0].dataset.id,'21');
});

test('reselecting the same Service preserves edited scope and agreed pricing', () => {
    const {field,run}=linkedSetup();run('selectLineItemCatalogItem("SERVICE",21)');
    field('lineItemDescription').value='Agreed scope';field('lineItemUnitPrice').value='275.00';
    run('selectLineItemCatalogItem("SERVICE",21)');
    assert.equal(field('lineItemDescription').value,'Agreed scope');assert.equal(field('lineItemUnitPrice').value,'275.00');
    run('selectLineItemCatalogItem("SERVICE",21)');
    assert.equal(field('lineItemDescription').value,'Agreed scope');assert.match(field('lineItemCatalogChoices').innerHTML,/Audit Binder/);
});

 test('empty database groups are visible and multi-identification services keep one database identity', async () => {
    const {field,context,catalog,run}=setup();
    context.window.DEAL_BOOTSTRAP.serviceIdentifications=[{id:9,name:'Empty Group'}];
    catalog[0].service_identifications.push({id:2,name:'Tax Advisory'});
    run('openLineItemPicker()');const html=field('lineItemCatalogChoices').innerHTML;
    assert.match(html,/Empty Group/);assert.match(html,/No available services in this identification/);
    assert.equal((html.match(/data-id="21"/g)||[]).length,2);
    run('selectLineItemCatalogItem("SERVICE",21)');await run('handleSaveLineItem({preventDefault(){}})');
    assert.equal(context.payload.service_id,21);assert.equal(context.payload.type,'SERVICE');
});
