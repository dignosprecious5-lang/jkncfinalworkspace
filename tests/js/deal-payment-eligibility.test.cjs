const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync('public/js/deals-engine.js', 'utf8');
const finance = source.slice(source.indexOf('    const financeDealId ='), source.indexOf('    /* NOTIFY FINANCE WORKFLOW */'));
const ledger = source.slice(source.indexOf('    function getFinanceLedgerRecords()'), source.indexOf('    /* MODAL: RECORD FLEXIBLE PAYMENT ALLOCATION */'));
const suggestion = source.slice(source.indexOf('    function calculateSuggestedPaymentAmount('), source.indexOf('    /* DUAL UPLOAD UX'));
function setup(state, legacy = []) {
    const fields = new Map();
    for (const id of ['financeTargetItemSelect', 'financeAllocationTableBody', 'financeGridDealValue', 'financeGridProfFee', 'financePaymentAmount']) {
        fields.set(id, {innerHTML: 'stale', innerText: '', value: '', style: {}});
    }
    let writes = 0;
    const storage = JSON.stringify(legacy);
    const document = {hidden:false, getElementById:id => fields.get(id) || null, querySelector:()=>null, addEventListener(){}};
    const window = {document, DEAL_BOOTSTRAP:{dealId:1, financePaymentState:state, financePaymentsUrl:'/allocations'}, addEventListener(){}};
    const context = vm.createContext({window, document, console, setInterval(){},
        localStorage:{getItem:()=>storage, setItem(){writes++;}},
        dealLineItems:[{id:'rejected', name:'Rejected item', price:99999}],
        escapeHtml:v=>String(v??'').replace(/"/g,'&quot;'), alert(){},
        fetch:async()=>({ok:true,json:async()=>state})});
    vm.runInContext(finance + ledger + suggestion, context);
    return {context, fields, writes:()=>writes};
}
const approved = () => ({items:[{id:'approved',name:'Approved item',code:'A',price:100,allocated:25,balance:75,ready:true}],
    records:[], approved_total:100, allocated_total:25, required_payment:75});
test('editing commercial line items cannot reintroduce rejected items into allocation', () => {
    const {context,fields} = setup(approved());
    vm.runInContext('syncFinanceFromLineItems()', context);
    assert.match(fields.get('financeAllocationTableBody').innerHTML,/Approved item/);
    assert.doesNotMatch(fields.get('financeAllocationTableBody').innerHTML,/Rejected item/);
    assert.doesNotMatch(fields.get('financeTargetItemSelect').innerHTML,/rejected/);
    vm.runInContext('calculateSuggestedPaymentAmount("item", "approved")',context);
    assert.equal(fields.get('financePaymentAmount').value,'75.00');
});
test('empty eligibility clears stale allocation rows and item options', () => {
    const {context,fields} = setup({items:[],records:[],approved_total:0,required_payment:0});
    vm.runInContext('syncFinanceFromLineItems()',context);
    assert.equal(fields.get('financeAllocationTableBody').innerHTML,'');
    assert.equal(fields.get('financeTargetItemSelect').innerHTML,'');
});
test('browser payment history stays visible for review without funding readiness or changing storage', () => {
    const {context,fields,writes} = setup(approved(),[{id:1,scope:'item',itemId:'rejected',amount:1000}]);
    const records = vm.runInContext('getFinanceLedgerRecords()',context);
    assert.equal(records[0].requiresReview,true);
    assert.equal(records[0].amount,1000);
    vm.runInContext('calculateSuggestedPaymentAmount("entire", null)',context);
    assert.equal(fields.get('financePaymentAmount').value,'75.00');
    assert.equal(writes(),0);
});
test('server refresh automatically replaces the visible eligibility and totals', async () => {
    const {context,fields} = setup(approved());
    await vm.runInContext('refreshFinanceEligibility()',context);
    assert.match(fields.get('financeGridDealValue').innerText,/100/);
    context.fetch = async()=>({ok:true,json:async()=>({items:[],records:[],approved_total:0,required_payment:0})});
    await vm.runInContext('refreshFinanceEligibility()',context);
    assert.equal(fields.get('financeAllocationTableBody').innerHTML,'');
    assert.equal(fields.get('financeTargetItemSelect').innerHTML,'');
    assert.match(fields.get('financeGridDealValue').innerText,/0\.00/);
});

test('payment submission releases its lock after failure and preserves its retry identity and proof size', async () => {
    const {context,fields} = setup(approved());
    for (const [id,value] of [['financePaymentAmount','20'],['financeTargetItemSelect','approved'],['financePaymentMethod','Cash']]) {
        fields.set(id,{value});
    }
    context.crypto = {randomUUID:()=> 'same-payment-request'};
    context.closeRecordPaymentModal = ()=>{};
    const handler = source.slice(source.indexOf('    async function handleSavePaymentAllocation(e)'),source.indexOf('    /* =========================================================',source.indexOf('    async function handleSavePaymentAllocation(e)')));
    vm.runInContext(handler,context);
    vm.runInContext('currentUploadedFinanceFile = {name:"proof.png", fileObj:{size:1024}, dataUrl:"data:image/png;base64,aA==", isImage:true, fileType:"image/png"}',context);
    const submitted = [];
    let fail = true;
    context.fetch = async (url,options={}) => {
        if (options.method === 'POST') {
            submitted.push(JSON.parse(options.body));
            if (fail) return {ok:false,json:async()=>({message:'Decision changed'})};
        }
        return {ok:true,json:async()=>approved()};
    };
    const button = {disabled:false};
    context.event = {preventDefault(){},submitter:button};
    await vm.runInContext('handleSavePaymentAllocation(event)',context);
    assert.equal(button.disabled,false);
    assert.equal(vm.runInContext('financePaymentSaving',context),false);
    fail = false;
    await vm.runInContext('handleSavePaymentAllocation(event)',context);
    assert.equal(submitted.length,2);
    assert.equal(submitted[0].request_id,submitted[1].request_id);
    assert.equal(submitted[1].fileSize,1024);
    assert.equal(button.disabled,false);
});