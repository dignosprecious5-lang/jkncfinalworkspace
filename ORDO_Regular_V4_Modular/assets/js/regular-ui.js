(function(){
  if(!window.ORDO_REGULAR)return;
  const s=ORDO_STATE,r=s.regular,esc=ORDO.esc;
  if(!location.pathname.includes('/workspace/'))return;
  // Internal keys stay compatible; the life cycle consistently calls RSAT planning "Plan".
  document.querySelectorAll('#life small').forEach(el=>{if(el.textContent==='RSAT')el.textContent='Plan';});
  if(location.pathname.endsWith('/sow-report.html')){
    document.addEventListener('click',e=>{if(!['reportSend','reportComplete','approve'].includes(e.target.id))return;if(!ORDO_REGULAR.executionAllowed()){e.preventDefault();e.stopImmediatePropagation();alert('Approve the RSAT and NTP first.');}},true);
    const history=document.createElement('section');history.className='regular-plan';history.innerHTML='<h3>Saved RSAT Reports</h3>'+r.reports.map(x=>`<p>${esc(x.id)} · ${esc(x.period)} · ${new Date(x.at).toLocaleString()}</p>`).join('');document.querySelector('.content')?.append(history);
  }
})();
