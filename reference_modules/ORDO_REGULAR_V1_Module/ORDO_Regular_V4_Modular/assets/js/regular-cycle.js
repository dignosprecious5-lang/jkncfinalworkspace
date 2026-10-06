(function () {
  'use strict';
  ['ordoNtpFunctionalV2-','ordoSowReviewV2-'].forEach(prefix=>{const key=prefix+ORDO_PROJECT_ID,raw=localStorage.getItem(key);if(raw){const updated=raw.replace(/\b\x50ROJ-/g,'REG-');if(updated!==raw)localStorage.setItem(key,updated);}});
  const s = ORDO_STATE, names = ['Work Order','Plan','Review','NTP','Execution'];
  const clone = value => JSON.parse(JSON.stringify(value));
  const now = () => new Date().toISOString();
  const record = name => ({name,status:'Not Started',inProgressSeconds:0,onHoldSeconds:0,waitingSeconds:0});
  s.regular ||= {cycle:1,period:'',status:'Active',archives:[],reports:[],schedule:{}};
  if(s.regular.status==='Cycle Completed')s.regular.status='Active';
  s.regular.reporting ||= s.stageManagement.stages[5] || record('RSAT Report');
  s.regular.delivery ||= s.stageManagement.stages[7] || record('Delivery & Completion');
  s.regular.schedule ||= {};
  s.regular.reports ||= [];
  s.regular.archives ||= [];
  s.stageManagement.stages = names.map((name,index)=>({...record(name),...s.stageManagement.stages[index],name}));
  s.stageManagement.selected = Math.min(4,s.stageManagement.selected||0);
  const planName = value => ['SOW','RSAT'].includes(value) ? 'Plan' : value;
  s.workOrder.stageAssignments.forEach(row=>row.stage=planName(row.stage));
  s.workOrder.stageSchedule = names.map(name=>({...s.workOrder.stageSchedule?.find(row=>planName(row.stage)===name),stage:name}));
  s.deliverables.forEach(d=>{if(d.name==='Final SOW Report')d.name='Final RSAT Report';});
  s.projects.forEach(p=>{p.stage=planName(p.stage);if(['Reporting','Presentation','Delivery','Completion'].includes(p.stage))p.stage='Execution';});
  const project = s.projects.find(p=>p.id===ORDO_PROJECT_ID);
  const stopAll = () => {
    ORDO.stopRunning();
    s.streams.forEach(w=>w.tasks.forEach(task=>{const t=task.executionWorkspace?.timer;if(!t)return;for(const [started,total]of [['workingStartedAt','workingSeconds'],['pausedStartedAt','pausedSeconds']]){if(t[started])t[total]=Number(t[total]||0)+Math.max(0,Math.floor((Date.now()-Number(t[started]))/1000));t[started]=null;}if(t.state!=='done')t.state='stopped';}));
  };
  const executionAllowed = () => !s.closed && s.regular.status==='Active' && s.workOrder.status==='Completed' && s.sowWorkflow.status==='final' && s.ntpApproved;
  const reportReady = () => s.regular.reports.length>0;
  window.ORDO_REGULAR = {
    executionAllowed, reportReady,
    snapshotReport(html='') {
      if(!executionAllowed())throw new Error('Approve the current RSAT and NTP before reporting.');

      const entry={id:`REG-RPT-${ORDO_PROJECT_ID}-${s.regular.cycle}-${s.regular.reports.length+1}`,cycle:s.regular.cycle,period:s.regular.period,at:now(),html,streams:clone(s.streams),narrative:clone(s.reportDraft||{})};
      s.regular.reports.unshift(entry);ORDO_SAVE();return entry;
    },
    endEngagement(status,reason) {
      if(!['Suspended','Closed'].includes(status)||!String(reason||'').trim())throw new Error('A reason is required.');
      stopAll();s.regular.status=status;s.regular.reason=String(reason).trim();s.closed=true;
      ORDO.log(`Regular engagement ${status.toLowerCase()}: ${reason}`);ORDO_SAVE();
    },
    resume() {if(s.regular.status!=="Suspended")throw new Error("Only suspended engagements can be resumed.");s.regular.status="Active";s.closed=false;ORDO.log("Regular engagement resumed");ORDO_SAVE();}, completeCycle(transmittalNumber) { if(!executionAllowed())throw new Error("Approve the current RSAT and NTP before completing the cycle."); const archive = { cycle: s.regular.cycle, period: s.regular.period, at: now(), transmittalNumber: transmittalNumber || null, reports: clone(s.regular.reports), streams: clone(s.streams) }; s.regular.archives.unshift(archive); s.regular.status = "Cycle Completed"; ORDO_SAVE(); return archive; }, nextCycle(nextPeriod) { s.regular.cycle = (s.regular.cycle || 1) + 1; if(nextPeriod) s.regular.period = nextPeriod; s.regular.status = "Active"; s.ntpApproved = false; s.sowWorkflow.status = "draft"; s.regular.reports = []; ORDO_SAVE(); }
  };
  ORDO_SAVE();
})();

