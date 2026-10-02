(function(){
  const s=ORDO_STATE,R=RSAT_RECURRENCE,p=s.projects.find(x=>x.id===ORDO_PROJECT_ID);
  window.RSAT_ADAPTER={
    summary:()=>s.rsatReportSummary||'',saveSummary:value=>{s.rsatReportSummary=value;ORDO_SAVE();},
    editable:()=>s.workOrder.status==='Completed'&&s.sowWorkflow.status==='draft'&&s.regular.status==='Active'&&!s.closed,
    metadata:()=>({client:p.rsatClient||(p.id===120?'May Flor D. Dabatos and Stephan Tenten':p.client),business:p.business,deal:p.deal,version:String(s.regular.planRevision||1)+'.0',ref:p.rsatNo||(p.id===120?'RSAT-2026-032':`REG-RSAT-${p.id}-${s.regular.cycle}`),prepared:p.datePrepared||(p.id===120?'08/20/2026':'Not recorded'),lead:p.lead,associate:p.leadAssociate||'Rubeca Potayre',servicePreparedBy:p.servicePreparedBy||p.lead+' · '+(p.leadAssociate||'Rubeca Potayre'),activityPreparedBy:p.activityPreparedBy||p.lead+' · '+(p.leadAssociate||'Rubeca Potayre'),cycle:s.regular.cycle}),
    read:()=>s.streams.flatMap(stream=>stream.tasks.map(task=>({id:task.id,service:stream.name,activity:task.title,schedules:task.recurringRules?.length?task.recurringRules:[{...R.defaults(),legacy:s.regular.schedule?.[task.id]||null}]}))).sort((a,b)=>{const order=s.rsatRowOrder||[];const ai=order.indexOf(a.id),bi=order.indexOf(b.id);return (ai<0?Infinity:ai)-(bi<0?Infinity:bi);}),
    save(rows){
      s.rsatRowOrder=rows.map(row=>row.id);
      const old=new Map(s.streams.flatMap(w=>w.tasks.map(t=>[t.id,t]))),groups=new Map();
      const assignee=s.workOrder.stageAssignments.find(row=>row.stage==='Execution'&&row.responsibilities?.includes('Executor'))?.persons?.[0]||p.lead;
      rows.forEach(row=>{if(!groups.has(row.service))groups.set(row.service,{id:'rsat-service-'+row.id,name:row.service,owner:p.lead,responsibilities:['Responsible'],tasks:[]});groups.get(row.service).tasks.push({...old.get(row.id),id:row.id,title:row.activity,assignee:old.get(row.id)?.assignee||assignee,responsibilities:old.get(row.id)?.responsibilities||['Responsible'],status:old.get(row.id)?.status||'Ready',seconds:old.get(row.id)?.seconds||0,report:true,recurringRules:row.schedules});});
      s.streams=[...groups.values()];s.sowWorkflow.lastSavedAt=new Date().toISOString();ORDO_SAVE();
    }
  };
})();
