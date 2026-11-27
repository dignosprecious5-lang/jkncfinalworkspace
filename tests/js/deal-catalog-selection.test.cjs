const test = require('node:test');
const assert = require('node:assert/strict');
require('../../public/js/deal-catalog-selection.js');
function state(options = {}) {
    const scope = { value: options.initialScope || '' };
    const result = Object.assign(global.ordoDealCatalogSelection({url:'/preview', ...options}), {
        selectedServiceIds: ['2'], customerType:'Individual',
        $root:{querySelector: selector => selector.includes('_token') ? {value:'csrf'} : scope},
    });
    return {result,scope};
}
const product = {type:'PRODUCT',product_id:1,name:'Product A',scope_of_work:'Product scope',requirements:[{origin:'product'}]};
const service = {type:'SERVICE',service_id:2,name:'Service A',scope_of_work:'Service scope',requirements:[{origin:'service'}]};
test('loads distinct sources and submits unique numeric IDs', async () => {
    const {result,scope}=state({productIds:[1,1]});let payload;
    global.fetch=async (_url,options) => {payload=JSON.parse(options.body);return {ok:true,json:async()=>({items:[service,product],conflicts:[]})};};
    await result.refreshCatalogConfiguration();
    assert.deepEqual(payload.product_ids,[1]);assert.deepEqual(payload.service_ids,[2]);
    assert.equal(result.catalogItems[0].requirements[0].origin,'service');
    assert.equal(result.catalogItems[1].requirements[0].origin,'product');
    assert.match(scope.value,/Service scope/);assert.match(scope.value,/Product scope/);
});
test('removal discards stale display immediately and recomputes managed scope', async () => {
    const {result,scope}=state({productIds:[1]});
    global.fetch=async()=>({ok:true,json:async()=>({items:[product],conflicts:[]})});
    await result.refreshCatalogConfiguration();result.selectedProductIds=[];result.selectedServiceIds=[];
    await result.refreshCatalogConfiguration();assert.deepEqual(result.catalogItems,[]);assert.equal(scope.value,'');
});
test('manual scope and existing Deal scope are preserved', async () => {
    for(const options of [{initialScope:'Agreed manual scope'},{dealId:9,initialScope:'Existing Deal scope'}]) {
        const {result,scope}=state(options);global.fetch=async()=>({ok:true,json:async()=>({items:[service],conflicts:[]})});
        await result.refreshCatalogConfiguration();assert.equal(scope.value,options.initialScope);
    }
});
test('manual edits stop subsequent automatic scope changes', async () => {
    const {result,scope}=state();scope.value='Manually edited';result.catalogScopeEdited();
    global.fetch=async()=>({ok:true,json:async()=>({items:[service],conflicts:[]})});
    await result.refreshCatalogConfiguration();assert.equal(scope.value,'Manually edited');
});
test('late response cannot restore a removed selection', async () => {
    const {result}=state();let resolve;
    global.fetch=()=>new Promise(done=>{resolve=done;});const pending=result.refreshCatalogConfiguration();
    result.selectedServiceIds=[];await result.refreshCatalogConfiguration();
    resolve({ok:true,json:async()=>({items:[service],conflicts:[]})});await pending;
    assert.deepEqual(result.catalogItems,[]);assert.equal(result.catalogLoading,false);
});
test('backend validation failures clear the preview and show the actual error', async () => {
    const {result}=state();global.fetch=async()=>({ok:false,json:async()=>({errors:{'service_ids.0':['Select an active catalog item.']}})});
    await result.refreshCatalogConfiguration();assert.deepEqual(result.catalogItems,[]);
    assert.match(result.catalogError,/active catalog item/);assert.equal(result.catalogLoading,false);
});

test('generated scope remains managed after server validation redisplays a new Deal', async () => {
    const {result,scope}=state({initialScope:'Previously generated',generatedScope:'Previously generated'});
    result.selectedServiceIds=[];await result.refreshCatalogConfiguration();
    assert.equal(scope.value,'');assert.equal(result.catalogScopeOwned,true);
});

