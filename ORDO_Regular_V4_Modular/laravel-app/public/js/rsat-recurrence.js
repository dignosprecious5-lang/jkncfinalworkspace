(function(root,factory){const api=factory();if(typeof module==='object'&&module.exports)module.exports=api;else root.RSAT_RECURRENCE=api;})(typeof globalThis!=='undefined'?globalThis:this,function(){
  'use strict';
  const DAY=86400000,units={day:'day',week:'week',month:'month',quarter:'quarter',year:'year',event:'event'};
  const months=['January','February','March','April','May','June','July','August','September','October','November','December'];
  const weekdays=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  const iso=d=>d.toISOString().slice(0,10);
  function date(s){if(!/^\d{4}-\d{2}-\d{2}$/.test(s||''))throw Error('Use a valid anchor or event date.');const d=new Date(s+'T00:00:00Z');if(!Number.isFinite(+d)||iso(d)!==s)throw Error('Use a valid anchor or event date.');return d;}
  const add=(d,n)=>new Date(+d+n*DAY),last=(y,m)=>new Date(Date.UTC(y,m+1,0)).getUTCDate();
  const integer=(n,min,max)=>Number.isInteger(Number(n))&&Number(n)>=min&&Number(n)<=max;
  function defaults(){return {version:1,configured:false,frequency:{unit:'month',interval:1},anchor:'2026-01-01',deadline:{type:'day',day:15,monthInPeriod:1,ordinal:1,weekday:1,offsetDays:30,event:'Annual meeting',eventDate:''},reminder:{amount:7,unit:'calendar_days'},shortMonth:'last_day',adjustment:'none',holidays:[]};}
  function validate(r){
    if(!r||r.version!==1||!units[r.frequency?.unit])throw Error('Choose a frequency.');
    if(!integer(r.frequency.interval,1,99))throw Error('Frequency interval must be between 1 and 99.');
    date(r.anchor);const d=r.deadline,u=r.frequency.unit;
    const allowed=u==='event'?['event']:u==='day'?['interval']:u==='week'?['weekday']:['day','nth_weekday','period_end','event'];
    if(!allowed.includes(d?.type))throw Error('Choose a deadline rule compatible with the frequency.');
    if(['day','nth_weekday'].includes(d.type)&&!integer(d.monthInPeriod,1,u==='year'?12:u==='quarter'?3:1))throw Error('Choose the month within the period.');
    if(d.type==='day'&&!integer(d.day,1,31))throw Error('Deadline day must be 1–31.');
    if(['nth_weekday','weekday'].includes(d.type)&&!integer(d.weekday,0,6))throw Error('Choose a weekday.');
    if(d.type==='nth_weekday'&&![1,2,3,4,5,-1].includes(Number(d.ordinal)))throw Error('Choose a weekday occurrence.');
    if(['period_end','event'].includes(d.type)&&!integer(d.offsetDays,0,366))throw Error('Days after the period or event must be 0–366.');
    if(d.type==='event'&&!String(d.event||'').trim())throw Error('Choose or name the triggering event.');
    if(d.eventDate)date(d.eventDate);
    if(!integer(r.reminder?.amount,0,366)||!['calendar_days','business_days'].includes(r.reminder.unit))throw Error('Choose a reminder lead time of 0–366 days.');
    if(!['last_day','skip'].includes(r.shortMonth)||!['none','next','previous'].includes(r.adjustment))throw Error('Choose short-month and non-working-day handling.');
    if(!Array.isArray(r.holidays))throw Error('Holiday dates must be a list.');r.holidays.forEach(date);return true;
  }
  const working=(d,r)=>![0,6].includes(d.getUTCDay())&&!r.holidays.includes(iso(d));
  function adjust(d,r){if(r.adjustment==='none')return d;let n=0;while(!working(d,r)){d=add(d,r.adjustment==='next'?1:-1);if(++n>740)throw Error('No working day found.');}return d;}
  function reminder(d,r){let n=Number(r.reminder.amount);if(r.reminder.unit==='calendar_days')return add(d,-n);while(n){d=add(d,-1);if(working(d,r))n--;}return d;}
  function monthDate(y,m,rule,r){if(rule.type==='day'){const day=Number(rule.day),end=last(y,m);if(day>end&&r.shortMonth==='skip')return null;return new Date(Date.UTC(y,m,Math.min(day,end)));}const ord=Number(rule.ordinal),wd=Number(rule.weekday);if(ord===-1){const end=new Date(Date.UTC(y,m+1,0));return add(end,-((end.getUTCDay()-wd+7)%7));}const first=new Date(Date.UTC(y,m,1));const n=1+(wd-first.getUTCDay()+7)%7+(ord-1)*7;return n<=last(y,m)?new Date(Date.UTC(y,m,n)):null;}
  function occurrences(r,from=iso(new Date()),count=3){
    validate(r);const start=date(from),anchor=date(r.anchor),out=[],seen=new Set(),d=r.deadline,u=r.frequency.unit,step=Number(r.frequency.interval);
    function push(raw){if(!raw)return;const due=adjust(raw,r);if(+due<+start||seen.has(iso(due)))return;seen.add(iso(due));out.push({deadline:iso(due),reminder:iso(reminder(due,r))});}
    if(d.type==='event'){if(!d.eventDate)return [];push(add(date(d.eventDate),Number(d.offsetDays)));return out;}
    if(u==='day'||u==='week'){
      let first=anchor,stride=step*(u==='week'?7:1);
      if(u==='week')first=add(anchor,(Number(d.weekday)-anchor.getUTCDay()+7)%7);
      let n=Math.max(0,Math.floor((+start-+first)/DAY/stride)-2);
      for(let k=0;k<count+20&&out.length<count;k++,n++)push(add(first,n*stride));
    }else{
      const period=u==='year'?12:u==='quarter'?3:1,stride=period*step,base=anchor.getUTCFullYear()*12+anchor.getUTCMonth();
      const diff=(start.getUTCFullYear()*12+start.getUTCMonth())-base;
      let n=Math.max(0,Math.floor(diff/stride)-Math.ceil(366/(28*stride))-2);
      for(let k=0;k<2400&&out.length<count;k++,n++){
        const index=base+n*stride,y=Math.floor(index/12),m=index%12;
        const raw=d.type==='period_end'?add(new Date(Date.UTC(y,m+period,0)),Number(d.offsetDays)):monthDate(y,m+Number(d.monthInPeriod)-1,d,r);
        if(raw&&+raw>=+anchor)push(raw);
      }
    }
    return out.sort((a,b)=>a.deadline.localeCompare(b.deadline)).slice(0,count);
  }
  function frequency(r){const {unit,interval}=r.frequency;return unit==='event'?'Per event':Number(interval)===1?{day:'Daily',week:'Weekly',month:'Monthly',quarter:'Quarterly',year:'Yearly'}[unit]:`Every ${interval} ${unit}s`;}
  function summary(r){const d=r.deadline,u=r.frequency.unit,month=u==='year'?months[(date(r.anchor).getUTCMonth()+Number(d.monthInPeriod)-1)%12]:u==='quarter'?`month ${d.monthInPeriod} of each quarter`:'each due month';
    if(d.type==='day')return `Day ${d.day} of ${month}`;
    if(d.type==='nth_weekday')return `${{'-1':'Last',1:'First',2:'Second',3:'Third',4:'Fourth',5:'Fifth'}[d.ordinal]} ${weekdays[d.weekday]} of ${month}`;
    if(d.type==='period_end')return `${d.offsetDays} calendar days after ${u}-end`;
    if(d.type==='event')return `${d.offsetDays} calendar days after ${d.event}`;
    if(d.type==='weekday')return `Every due ${weekdays[d.weekday]}`;
    return `Every ${r.frequency.interval} day(s), from ${r.anchor}`;
  }
  return {defaults,validate,occurrences,frequency,summary,months,weekdays};
});
