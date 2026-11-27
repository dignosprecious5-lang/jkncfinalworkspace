const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const exported={exports:{}};
vm.runInNewContext(fs.readFileSync('public/js/deal-finance-documents.js','utf8'),{module:exported});
const {previewKind,previewHtml}=exported.exports;
const source=fs.readFileSync('public/js/deal-finance-documents.js','utf8');
const record=(type,mime='application/pdf')=>({id:type,document_type:type,original_name:type+'.pdf',mime_type:mime,size:100,available:true,url:'/files/'+type,download_url:'/files/'+type+'?download=1'});
function setup(records=[]) {
    const fields=new Map();
    const field=id=>{if(!fields.has(id)) fields.set(id,{value:'',style:{},files:[],textContent:'',innerHTML:'',focus(){},reset(){field('financeDocumentType').value='';field('financeDocumentFile').value='';}});return fields.get(id);};
    const document={getElementById:field,querySelector(){return {content:'csrf'};},addEventListener(){},readyState:'complete'};
    const context=vm.createContext({document,window:{document,DEAL_BOOTSTRAP:{financeDocuments:records,financeDocumentsUploadUrl:'/uploads'}},FormData:class{constructor(){this.values={};}append(k,v){this.values[k]=v;}},Date,console,
        fetch:async(url,options)=>{context.payload=options.body.values;return {ok:true,json:async()=>({id:'new',documents:[{...record(context.payload.document_type),id:'new'}]})};}});
    vm.runInContext(source,context);
    return {field,context,api:context.window};
}
test('actual MIME selects PDF/image/download previews and escapes names',()=>{
    assert.equal(previewKind(record('quotation')),'pdf');
    assert.equal(previewKind(record('payment_notice','image/png')),'image');
    const word={...record('quotation','application/vnd.openxmlformats-officedocument.wordprocessingml.document'),original_name:'proposal.docx'};
    const html=previewHtml(word);assert.match(html,/Download Original/);assert.doesNotMatch(html,/<img|<iframe/);
    assert.equal(previewKind({...word,original_name:'trick.png'}),'download');
    assert.match(previewHtml({...record('quotation'),original_name:'<script>alert(1)</script>.pdf'}),/&lt;script&gt;/);
    assert.doesNotMatch(previewHtml({...record('quotation'),available:false}),/<iframe|<img/);
});
test('previously saved documents stay in separate categories and use the matching modal',()=>{
    const {field,api}=setup([record('quotation'),record('payment_notice','image/jpeg')]);
    const html=field('financeDocumentsList').innerHTML;
    assert.match(html,/aria-label="Quotation"/);assert.match(html,/aria-label="Payment Notice"/);
    assert.equal(api.ordoFinanceDocuments.previewLatest('quotation'),true);
    assert.match(field('previewQuotationPaperContainer').innerHTML,/<iframe/);
    assert.doesNotMatch(field('previewQuotationPaperContainer').innerHTML,/payment_notice.pdf/);
    api.ordoFinanceDocuments.previewLatest('payment_notice');
    assert.match(field('previewPaymentNoticePaperContainer').innerHTML,/<img/);
    assert.match(field('previewPaymentNoticePaperContainer').innerHTML,/Payment Notice/);
});
test('type selection is required before revealing file input or submitting',async()=>{
    const {field,api,context}=setup();api.openFinanceDocumentUpload();
    assert.equal(field('financeDocumentFile').disabled,true);assert.equal(field('financeDocumentFileGroup').hidden,true);
    await api.submitFinanceDocument({preventDefault(){}});assert.match(field('financeDocumentUploadError').textContent,/Select Quotation or Payment Notice/);assert.equal(context.payload,undefined);
    field('financeDocumentType').value='payment_notice';api.changeFinanceDocumentType();
    assert.equal(field('financeDocumentFile').disabled,false);assert.equal(field('financeDocumentFileGroup').hidden,false);
    assert.equal(field('financeDocumentUploadTitle').textContent,'Upload Payment Notice');
});
test('both uploads send their classification and preview the persisted server document',async()=>{
    for(const type of ['quotation','payment_notice']) {
        const {field,api,context}=setup();api.openFinanceDocumentUpload();field('financeDocumentType').value=type;api.changeFinanceDocumentType();
        field('financeDocumentFile').files=[{name:'Original.pdf',size:123}];await api.submitFinanceDocument({preventDefault(){}});
        assert.equal(context.payload.document_type,type);assert.equal(context.payload.document.name,'Original.pdf');
        const target=type==='quotation'?'previewQuotationPaperContainer':'previewPaymentNoticePaperContainer';
        assert.match(field(target).innerHTML,new RegExp(type+'.pdf'));assert.equal(field('financeDocumentUploadModal').style.display,'none');
    }
});
test('changing classification clears the chosen file, and validation errors keep the form open',async()=>{
    const {field,api,context}=setup();api.openFinanceDocumentUpload();field('financeDocumentType').value='quotation';api.changeFinanceDocumentType();
    field('financeDocumentFile').value='old.pdf';field('financeDocumentType').value='payment_notice';api.changeFinanceDocumentType();assert.equal(field('financeDocumentFile').value,'');
    field('financeDocumentFile').files=[{size:12}];context.fetch=async()=>({ok:false,json:async()=>({errors:{document:['Invalid document.']}})});
    await api.submitFinanceDocument({preventDefault(){}});assert.equal(field('financeDocumentUploadError').textContent,'Invalid document.');
    assert.equal(field('financeDocumentUploadModal').style.display,'flex');assert.equal(field('financeDocumentSubmit').disabled,false);
});
test('no upload means existing generated previews remain available',()=>{
    const {api}=setup();assert.equal(api.ordoFinanceDocuments.previewLatest('quotation'),false);assert.equal(api.ordoFinanceDocuments.previewLatest('payment_notice'),false);
});
