{{-- Filler — verb-forms trainer (Phase 1, self-study).
     Fully client-side: the student/teacher types verbs into a 3-column table
     (base form given; past simple + past participle are the answers), then
     plays them in short rounds. After a round they can replay it for mastery
     or retry only the ones they missed. No question-bank or DB dependency. --}}
@verbatim
<div data-game="filler">
<style>
[data-game="filler"]{
  --fl-ink:#16233b; --fl-muted:#5c6b85; --fl-faint:#8b98af;
  --fl-line:rgba(20,40,80,.12); --fl-line2:rgba(20,40,80,.2);
  --fl-brand:#1f5fe0; --fl-brand2:#0ea5e9;
  --fl-good:#0e8a5f; --fl-goodbg:#e5f4ec; --fl-bad:#c02747; --fl-badbg:#fbe9ed;
  --fl-given:#12233f; --fl-card:#ffffff; --fl-bg2:#eef3fb; --fl-input:#f4f7fc; --fl-chip:#e9f1fe;
  --fl-serif:"Iowan Old Style","Palatino Linotype",Palatino,Georgia,"Times New Roman",serif;
  --fl-mono:ui-monospace,"Cascadia Code","SFMono-Regular",Consolas,monospace;
  color:var(--fl-ink);
}
[data-game="filler"] *{box-sizing:border-box}
[data-game="filler"] .fl-card{background:var(--fl-card);border:1px solid var(--fl-line);border-radius:18px;overflow:hidden;box-shadow:0 14px 40px -22px rgba(20,45,95,.4)}
[data-game="filler"] .fl-h{padding:18px 20px 14px;border-bottom:1px solid var(--fl-line)}
[data-game="filler"] .fl-h h3{margin:0;font-family:var(--fl-serif);font-size:19px;font-weight:600;color:var(--fl-ink)}
[data-game="filler"] .fl-h p{margin:5px 0 0;color:var(--fl-muted);font-size:13px;line-height:1.5;max-width:64ch}
[data-game="filler"] .fl-scroll{overflow-x:auto}
[data-game="filler"] table.fl-tbl{width:100%;border-collapse:separate;border-spacing:0;min-width:560px}
[data-game="filler"] .fl-tbl thead th{text-align:left;padding:12px 16px;font-size:10.5px;font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:var(--fl-faint);background:var(--fl-bg2);border-bottom:1px solid var(--fl-line);white-space:nowrap}
[data-game="filler"] .fl-tbl thead th .fl-n{display:inline-grid;place-items:center;width:17px;height:17px;border-radius:50%;background:rgba(31,95,224,.14);color:var(--fl-brand);font-size:10px;margin-right:7px;vertical-align:-2px}
[data-game="filler"] .fl-tbl thead th.fl-given-col{color:var(--fl-brand)}
[data-game="filler"] .fl-tbl td{padding:7px 12px;border-bottom:1px solid var(--fl-line)}
[data-game="filler"] .fl-tbl td:first-child{padding-left:16px}
[data-game="filler"] .fl-tbl tr:last-child td{border-bottom:0}
[data-game="filler"] .fl-idx{color:var(--fl-faint);font-size:12px;font-variant-numeric:tabular-nums;width:26px;text-align:right;padding-right:4px}
[data-game="filler"] .fl-cell{width:100%;border:1px solid transparent;background:var(--fl-input);border-radius:10px;padding:10px 13px;font-family:inherit;font-size:15px;color:var(--fl-ink);transition:border-color .15s,box-shadow .15s}
[data-game="filler"] .fl-cell::placeholder{color:var(--fl-faint)}
[data-game="filler"] .fl-cell:hover{border-color:var(--fl-line2)}
[data-game="filler"] .fl-cell:focus{outline:0;border-color:var(--fl-brand);box-shadow:0 0 0 3px rgba(31,95,224,.2)}
[data-game="filler"] .fl-cell.fl-base{font-family:var(--fl-serif);font-size:16px;font-weight:600;background:var(--fl-chip)}
[data-game="filler"] .fl-cell.fl-ans{font-family:var(--fl-mono);font-size:14px}
[data-game="filler"] .fl-del{border:0;background:transparent;color:var(--fl-faint);cursor:pointer;width:34px;height:34px;border-radius:9px;display:grid;place-items:center;font-size:18px;line-height:1}
[data-game="filler"] .fl-del:hover{background:var(--fl-badbg);color:var(--fl-bad)}
[data-game="filler"] .fl-f{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:14px 18px}
[data-game="filler"] .fl-left{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
[data-game="filler"] .fl-add{border:1px dashed var(--fl-line2);background:transparent;color:var(--fl-muted);font:inherit;font-size:13px;font-weight:500;padding:9px 15px;border-radius:11px;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
[data-game="filler"] .fl-add:hover{border-color:var(--fl-brand);color:var(--fl-brand)}
[data-game="filler"] .fl-add .fl-p{font-size:17px;line-height:1;margin-top:-1px}
[data-game="filler"] .fl-per{display:inline-flex;align-items:center;gap:8px;background:var(--fl-bg2);border:1px solid var(--fl-line);border-radius:11px;padding:5px 8px 5px 12px;font-size:12.5px;color:var(--fl-muted)}
[data-game="filler"] .fl-per select{border:1px solid var(--fl-line2);background:var(--fl-card);color:var(--fl-ink);font:inherit;font-size:13px;font-weight:600;border-radius:8px;padding:6px 8px;cursor:pointer}
[data-game="filler"] .fl-pag{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:11px 18px;border-top:1px solid var(--fl-line);background:var(--fl-bg2)}
[data-game="filler"] .fl-nav{display:inline-flex;align-items:center;gap:6px}
[data-game="filler"] .fl-pbtn{border:1px solid var(--fl-line2);background:var(--fl-card);color:var(--fl-muted);width:32px;height:32px;border-radius:9px;cursor:pointer;display:grid;place-items:center;font-size:16px;line-height:1}
[data-game="filler"] .fl-pbtn:hover:not(:disabled){border-color:var(--fl-brand);color:var(--fl-brand)}
[data-game="filler"] .fl-pbtn:disabled{opacity:.4;cursor:default}
[data-game="filler"] .fl-pgno{font-size:12.5px;color:var(--fl-muted);font-variant-numeric:tabular-nums;min-width:84px;text-align:center}
[data-game="filler"] .fl-pgno b{color:var(--fl-ink);font-weight:600}
[data-game="filler"] .fl-go{display:flex;align-items:center;gap:14px}
[data-game="filler"] .fl-count{font-size:13px;color:var(--fl-muted)}
[data-game="filler"] .fl-count b{color:var(--fl-ink);font-variant-numeric:tabular-nums}
[data-game="filler"] .fl-start{border:0;font:inherit;font-size:15px;font-weight:600;color:#fff;padding:12px 28px;border-radius:12px;cursor:pointer;background:linear-gradient(135deg,var(--fl-brand),var(--fl-brand2));box-shadow:0 12px 26px -12px rgba(15,55,150,.9);display:inline-flex;align-items:center;gap:10px;transition:transform .12s}
[data-game="filler"] .fl-start:hover{transform:translateY(-1px)}
[data-game="filler"] .fl-start .fl-ico{font-size:12px}

[data-game="filler"] .fl-scrim{position:fixed;inset:0;z-index:9999;display:none;place-items:center;padding:20px;background:rgba(10,20,42,.46);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
[data-game="filler"] .fl-scrim.fl-open{display:grid}
[data-game="filler"] .fl-play{width:100%;max-width:520px;background:var(--fl-card);border:1px solid var(--fl-line2);border-radius:22px;box-shadow:0 40px 90px -30px rgba(0,0,0,.55);overflow:hidden;animation:fl-pop .3s cubic-bezier(.2,.9,.3,1.2)}
@keyframes fl-pop{from{opacity:0;transform:translateY(14px) scale(.965)}to{opacity:1;transform:none}}
[data-game="filler"] .fl-ptop{display:flex;align-items:flex-start;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--fl-line);background:var(--fl-bg2)}
[data-game="filler"] .fl-prog{display:flex;flex-direction:column;gap:5px;font-size:12px;color:var(--fl-muted)}
[data-game="filler"] .fl-prog .fl-rd{font-weight:600;color:var(--fl-ink);letter-spacing:.02em}
[data-game="filler"] .fl-dots{display:flex;gap:5px;flex-wrap:wrap;max-width:230px}
[data-game="filler"] .fl-dot{width:8px;height:8px;border-radius:50%;background:var(--fl-line2)}
[data-game="filler"] .fl-dot.fl-done{background:var(--fl-good)}
[data-game="filler"] .fl-dot.fl-now{background:var(--fl-brand);box-shadow:0 0 0 4px rgba(31,95,224,.2)}
[data-game="filler"] .fl-dot.fl-miss{background:var(--fl-bad)}
[data-game="filler"] .fl-stats{display:flex;gap:8px;align-items:flex-start}
[data-game="filler"] .fl-stat{display:flex;align-items:center;gap:5px;font-size:12px;font-weight:600;background:var(--fl-chip);border:1px solid var(--fl-line);border-radius:20px;padding:4px 11px;font-variant-numeric:tabular-nums}
[data-game="filler"] .fl-stat.fl-sc{color:var(--fl-brand)}
[data-game="filler"] .fl-stat.fl-st{color:#d97706}
[data-game="filler"] .fl-x{border:0;background:transparent;color:var(--fl-faint);font-size:20px;cursor:pointer;width:32px;height:32px;border-radius:8px}
[data-game="filler"] .fl-x:hover{background:var(--fl-badbg);color:var(--fl-bad)}
[data-game="filler"] .fl-pbody{padding:26px 24px 22px}
[data-game="filler"] .fl-given-lab{text-align:center;font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--fl-faint);margin-bottom:6px}
[data-game="filler"] .fl-given{text-align:center;font-family:var(--fl-serif);font-size:44px;font-weight:600;font-style:italic;color:var(--fl-given);line-height:1.05;word-break:break-word}
[data-game="filler"] .fl-given .fl-to{font-size:18px;font-style:normal;color:var(--fl-muted);font-family:inherit;vertical-align:middle;margin-right:6px}
[data-game="filler"] .fl-rule{width:64px;height:3px;margin:12px auto 0;border-radius:3px;background:linear-gradient(90deg,var(--fl-brand),var(--fl-brand2))}
[data-game="filler"] .fl-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:22px}
[data-game="filler"] .fl-field label{display:block;font-size:10.5px;letter-spacing:.05em;text-transform:uppercase;color:var(--fl-muted);font-weight:600;margin:0 0 6px 2px}
[data-game="filler"] .fl-field input{width:100%;border:1.5px solid var(--fl-line2);background:var(--fl-input);border-radius:12px;padding:13px;font-family:var(--fl-mono);font-size:17px;color:var(--fl-ink);text-align:center;transition:border-color .15s,box-shadow .15s,background .2s}
[data-game="filler"] .fl-field input:focus{outline:0;border-color:var(--fl-brand);box-shadow:0 0 0 3px rgba(31,95,224,.18)}
[data-game="filler"] .fl-field input.fl-ok{border-color:var(--fl-good);background:var(--fl-goodbg);color:var(--fl-good)}
[data-game="filler"] .fl-field input.fl-no{border-color:var(--fl-bad);background:var(--fl-badbg);color:var(--fl-bad)}
[data-game="filler"] .fl-reveal{font-size:11.5px;color:var(--fl-good);margin:6px 0 0 2px;font-family:var(--fl-mono);min-height:15px}
[data-game="filler"] .fl-fb{margin-top:15px;border-radius:12px;padding:11px 14px;font-size:13px;font-weight:500;display:none}
[data-game="filler"] .fl-fb.fl-show{display:block}
[data-game="filler"] .fl-fb.fl-good{background:var(--fl-goodbg);color:var(--fl-good)}
[data-game="filler"] .fl-fb.fl-bad{background:var(--fl-badbg);color:var(--fl-bad)}
[data-game="filler"] .fl-pfoot{display:flex;gap:10px;padding:15px 24px 22px;flex-wrap:wrap}
[data-game="filler"] .fl-btn{flex:1;min-width:120px;border:1px solid var(--fl-line2);background:transparent;color:var(--fl-muted);font:inherit;font-size:14px;font-weight:600;padding:12px;border-radius:12px;cursor:pointer}
[data-game="filler"] .fl-btn:hover{border-color:var(--fl-brand);color:var(--fl-brand)}
[data-game="filler"] .fl-btn.fl-primary{border:0;color:#fff;background:linear-gradient(135deg,var(--fl-brand),var(--fl-brand2));flex:1.6}
[data-game="filler"] .fl-btn.fl-danger:hover{border-color:var(--fl-bad);color:var(--fl-bad)}
[data-game="filler"] .fl-done{padding:32px 26px 24px;text-align:center}
[data-game="filler"] .fl-done .fl-big{font-family:var(--fl-serif);font-size:24px;font-weight:600;margin:0;color:var(--fl-ink)}
[data-game="filler"] .fl-done .fl-sub{color:var(--fl-muted);font-size:13px;margin:8px 0 0;line-height:1.5}
[data-game="filler"] .fl-ring{width:116px;height:116px;margin:2px auto 14px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(var(--fl-brand) var(--fl-pct,0%),var(--fl-line) 0)}
[data-game="filler"] .fl-ring .fl-in{width:94px;height:94px;border-radius:50%;background:var(--fl-card);display:grid;place-items:center;font-family:var(--fl-mono);font-size:24px;font-weight:600;color:var(--fl-brand);line-height:1.1}
[data-game="filler"] .fl-ring .fl-in small{display:block;font-family:inherit;font-size:10px;color:var(--fl-muted);font-weight:500;letter-spacing:.04em}
@media(max-width:440px){[data-game="filler"] .fl-fields{grid-template-columns:1fr}}
@media (prefers-reduced-motion:reduce){[data-game="filler"] *{animation:none!important;transition:none!important}}
</style>

<div class="fl-card">
  <div class="fl-h">
    <h3>Your verb list</h3>
    <p>You'll be shown the base form and fill in the past simple and past participle. Big lists play in short rounds — set how many per round below. Separate alternatives with a slash, like <span style="font-family:var(--fl-mono);color:var(--fl-brand)">learnt / learned</span>.</p>
  </div>
  <div class="fl-scroll">
    <table class="fl-tbl">
      <thead>
        <tr>
          <th style="width:26px"></th>
          <th class="fl-given-col"><span class="fl-n">1</span>Base form</th>
          <th><span class="fl-n">2</span>Past simple</th>
          <th><span class="fl-n">3</span>Past participle</th>
          <th aria-label="Remove"></th>
        </tr>
      </thead>
      <tbody id="flRows"></tbody>
    </table>
  </div>
  <div class="fl-pag">
    <span class="fl-per">Rows per page
      <select id="flRpp" aria-label="Rows per page">
        <option selected>10</option><option>25</option><option>50</option><option value="all">All</option>
      </select>
    </span>
    <div class="fl-nav">
      <button type="button" class="fl-pbtn" id="flPrev" aria-label="Previous page">&lsaquo;</button>
      <span class="fl-pgno" id="flRange">&ndash;</span>
      <button type="button" class="fl-pbtn" id="flNextPg" aria-label="Next page">&rsaquo;</button>
    </div>
  </div>
  <div class="fl-f">
    <div class="fl-left">
      <button type="button" class="fl-add" id="flAdd"><span class="fl-p">+</span> Add verb</button>
      <span class="fl-per">Questions per round
        <select id="flPer" aria-label="Questions per round">
          <option>5</option><option selected>10</option><option>15</option><option>20</option><option value="all">All</option>
        </select>
      </span>
    </div>
    <div class="fl-go">
      <span class="fl-count"><b id="flCount">0</b> verbs ready</span>
      <button type="button" class="fl-start" id="flStart"><span class="fl-ico">&#9654;</span> Start</button>
    </div>
  </div>
</div>

<div class="fl-scrim" id="flScrim" role="dialog" aria-modal="true" aria-label="Play Filler">
  <div class="fl-play" id="flPlay"></div>
</div>

<script>
(function(){
  var SEED=[["go","went","gone"],["eat","ate","eaten"],["see","saw","seen"],["take","took","taken"],
    ["give","gave","given"],["come","came","come"],["make","made","made"],["know","knew","known"],
    ["write","wrote","written"],["run","ran","run"],["begin","began","begun"],["drink","drank","drunk"],
    ["swim","swam","swum"],["sing","sang","sung"],["drive","drove","driven"],["ride","rode","ridden"],
    ["speak","spoke","spoken"],["break","broke","broken"],["choose","chose","chosen"],["fly","flew","flown"],
    ["grow","grew","grown"],["throw","threw","thrown"],["wear","wore","worn"],["get","got","got / gotten"],
    // Lesson 2 textbook charts — irregular verbs that change their spelling.
    ["become","became","become"],["bite","bit","bitten"],["catch","caught","caught"],["do","did","done"],
    ["freeze","froze","frozen"],["lose","lost","lost"],["rise","rose","risen"],["sit","sat","sat"],
    ["meet","met","met"],["creep","crept","crept"],
    // Lesson 2 textbook charts — irregular verbs that retain their spelling.
    ["beat","beat","beat"],["burst","burst","burst"],["cost","cost","cost"],["cut","cut","cut"],
    ["hit","hit","hit"],["hurt","hurt","hurt"],["let","let","let"],["put","put","put"],
    ["quit","quit","quit"],["read","read","read"],["rid","rid","rid"],["set","set","set"],
    ["shut","shut","shut"],["split","split","split"],["bet","bet","bet"],["spread","spread","spread"],
    ["",""],["",""]];
  var rowsEl=document.getElementById('flRows'), countEl=document.getElementById('flCount');
  function esc(s){return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}

  function rowHTML(i,b,p,pp){
    return '<tr><td class="fl-idx">'+i+'</td>'+
      '<td><input class="fl-cell fl-base" placeholder="begin" value="'+esc(b)+'"></td>'+
      '<td><input class="fl-cell fl-ans" placeholder="began" value="'+esc(p)+'"></td>'+
      '<td><input class="fl-cell fl-ans" placeholder="begun" value="'+esc(pp)+'"></td>'+
      '<td><button type="button" class="fl-del" aria-label="Remove verb" title="Remove">&times;</button></td></tr>';
  }
  function renumber(){ var t=rowsEl.children; for(var i=0;i<t.length;i++) t[i].querySelector('.fl-idx').textContent=i+1; paginate(); }
  function addRow(b,p,pp){
    rowsEl.insertAdjacentHTML('beforeend',rowHTML(rowsEl.children.length+1,b||'',p||'',pp||''));
    wire(); renumber(); recount();
  }
  function wire(){
    var dels=rowsEl.querySelectorAll('.fl-del');
    for(var i=0;i<dels.length;i++) dels[i].onclick=function(){ this.closest('tr').remove(); renumber(); recount(); };
    var cells=rowsEl.querySelectorAll('.fl-cell');
    for(var j=0;j<cells.length;j++) cells[j].oninput=recount;
  }
  function verbs(){
    var out=[], trs=rowsEl.querySelectorAll('tr');
    for(var i=0;i<trs.length;i++){ var c=trs[i].querySelectorAll('.fl-cell');
      var v=[c[0].value.trim(),c[1].value.trim(),c[2].value.trim()];
      if(v[0]&&v[1]&&v[2]) out.push(v);
    } return out;
  }
  function recount(){ countEl.textContent=verbs().length; }

  // Pagination hides off-page rows instead of re-rendering, so typed values survive page flips.
  var rppEl=document.getElementById('flRpp'), rangeEl=document.getElementById('flRange');
  var prevEl=document.getElementById('flPrev'), nextPgEl=document.getElementById('flNextPg');
  var pg=0;
  function paginate(){
    var trs=rowsEl.children, n=trs.length;
    var size = rppEl.value==='all' ? (n||1) : parseInt(rppEl.value,10);
    var pages=Math.max(1,Math.ceil(n/size));
    if(pg>pages-1) pg=pages-1; if(pg<0) pg=0;
    var from=pg*size, to=Math.min(n,from+size);
    for(var i=0;i<n;i++) trs[i].style.display=(i>=from&&i<to)?'':'none';
    rangeEl.innerHTML = n ? '<b>'+(from+1)+'&ndash;'+to+'</b> of '+n : '0 of 0';
    prevEl.disabled = pg<=0; nextPgEl.disabled = pg>=pages-1;
  }
  rppEl.onchange=function(){ pg=0; paginate(); };
  prevEl.onclick=function(){ pg--; paginate(); };
  nextPgEl.onclick=function(){ pg++; paginate(); };

  for(var s=0;s<SEED.length;s++) addRow(SEED[s][0],SEED[s][1],SEED[s][2]);
  document.getElementById('flAdd').onclick=function(){ pg=1e9; addRow(); };

  var scrim=document.getElementById('flScrim'), play=document.getElementById('flPlay');
  var order=[], perNum=10, perRaw='10', currentRound=0, roundList=[], idx=0, score=0, streak=0, results=[];
  var completed=new Set(), lastSig='', STORE='filler.progress.v1';
  function shuffle(a){ for(var i=a.length-1;i>0;i--){ var j=Math.floor(Math.random()*(i+1)),t=a[i]; a[i]=a[j]; a[j]=t; } return a; }
  function setArr(s){ var a=[]; s.forEach(function(x){ a.push(x); }); return a; }
  function sigOf(p){ return JSON.stringify(p); }
  function loadState(){ try{ return JSON.parse(localStorage.getItem(STORE)); }catch(e){ return null; } }
  function persist(){ if(!order.length) return; try{ localStorage.setItem(STORE, JSON.stringify({ sig:lastSig, per:perRaw, order:order, completed:setArr(completed) })); }catch(e){} }
  function firstUnfinished(){ for(var k=0;k<totalRounds();k++){ if(!completed.has(k)) return k; } return 0; }
  function norm(x){ return x.trim().toLowerCase().replace(/\s+/g,' '); }
  function accepts(cell,val){ return cell.split('/').map(norm).indexOf(norm(val))!==-1; }
  function totalRounds(){ return Math.max(1,Math.ceil(order.length/perNum)); }
  function roundNo(){ return currentRound+1; }

  function open(){
    var pool=verbs();
    if(pool.length<1){ alert('Add at least one verb (all three forms) to start.'); return; }
    perRaw=document.getElementById('flPer').value;
    perNum = perRaw==='all' ? pool.length : parseInt(perRaw,10);
    score=0; streak=0;
    lastSig=sigOf(pool);
    var st=loadState();
    if(st && st.sig===lastSig && st.per===perRaw && st.order && st.order.length===pool.length){
      order=st.order; completed=new Set(st.completed||[]);   // resume this cycle
    } else {
      order=shuffle(pool.slice()); completed=new Set();       // new cycle (fresh list / size)
    }
    if(completed.size>=totalRounds()){ order=shuffle(pool.slice()); completed=new Set(); } // every round done -> fresh mix
    persist();
    playRound(firstUnfinished());
    scrim.classList.add('fl-open');
  }
  function close(){ persist(); scrim.classList.remove('fl-open'); }
  // Round membership is fixed within a cycle (a slice of the shuffled pool); the
  // display order is reshuffled every time the round is actually played.
  function playRound(k){ currentRound=k; startRound(shuffle(order.slice(k*perNum, k*perNum+perNum))); }
  function newCycle(){ order=shuffle(order.slice()); completed=new Set(); score=0; streak=0; persist(); playRound(0); }
  function startRound(list){ roundList=list; idx=0; results=new Array(list.length).fill(null); render(); }

  function dots(){
    var d='';
    for(var i=0;i<roundList.length;i++){
      var cls = i<idx ? (results[i]?'fl-done':'fl-miss') : (i===idx?'fl-now':'');
      d+='<span class="fl-dot '+cls+'"></span>';
    } return d;
  }
  function render(){
    var v=roundList[idx];
    play.innerHTML=
    '<div class="fl-ptop"><div class="fl-prog"><span class="fl-rd">Round '+roundNo()+' of '+totalRounds()+' &middot; '+(idx+1)+' / '+roundList.length+'</span>'+
      '<span class="fl-dots">'+dots()+'</span></div>'+
      '<div class="fl-stats"><span class="fl-stat fl-sc">&#9670; '+score+'</span><span class="fl-stat fl-st">&#128293; '+streak+'</span>'+
      '<button type="button" class="fl-x" id="flXclose" aria-label="Close">&times;</button></div></div>'+
    '<div class="fl-pbody"><div class="fl-given-lab">Base form &middot; infinitive</div>'+
      '<div class="fl-given"><span class="fl-to">to</span>'+esc(v[0])+'</div><div class="fl-rule"></div>'+
      '<div class="fl-fields">'+
        '<div class="fl-field"><label>Past simple</label><input id="flA1" autocomplete="off" spellcheck="false" placeholder="&mdash;"><div class="fl-reveal" id="flR1"></div></div>'+
        '<div class="fl-field"><label>Past participle</label><input id="flA2" autocomplete="off" spellcheck="false" placeholder="&mdash;"><div class="fl-reveal" id="flR2"></div></div>'+
      '</div><div class="fl-fb" id="flFb"></div></div>'+
    '<div class="fl-pfoot"><button type="button" class="fl-btn" id="flReveal">Reveal</button><button type="button" class="fl-btn fl-primary" id="flCheck">Check answer</button></div>';
    document.getElementById('flXclose').onclick=close;
    document.getElementById('flCheck').onclick=function(){ grade(false); };
    document.getElementById('flReveal').onclick=function(){ grade(true); };
    document.getElementById('flA1').focus();
    var ins=play.querySelectorAll('input');
    for(var i=0;i<ins.length;i++) ins[i].addEventListener('keydown',function(e){ if(e.key==='Enter') grade(false); });
  }
  function grade(revealed){
    var v=roundList[idx], a1=document.getElementById('flA1'), a2=document.getElementById('flA2');
    var ok1=!revealed&&accepts(v[1],a1.value), ok2=!revealed&&accepts(v[2],a2.value), both=ok1&&ok2;
    a1.className='fl-'+(ok1?'ok':'no'); a2.className='fl-'+(ok2?'ok':'no'); a1.disabled=a2.disabled=true;
    if(!ok1) document.getElementById('flR1').textContent='→ '+v[1];
    if(!ok2) document.getElementById('flR2').textContent='→ '+v[2];
    results[idx]=both;
    var fb=document.getElementById('flFb');
    fb.className='fl-fb fl-show '+(both?'fl-good':'fl-bad');
    fb.textContent = both ? '✓ Perfect — both forms correct.'
      : (revealed ? 'Revealed. This one comes back if you retry mistakes.' : 'Not quite — the correct forms are shown.');
    if(both){ score+=10+Math.min(streak,5); streak++; } else { streak=0; }
    var btn=document.getElementById('flCheck');
    btn.textContent = (idx+1<roundList.length) ? 'Next verb →' : 'Round results';
    btn.onclick=next;
    document.getElementById('flReveal').style.visibility='hidden';
  }
  function next(){ idx++; if(idx<roundList.length) render(); else finish(); }
  function finish(){
    var right=0, misses=[];
    for(var i=0;i<roundList.length;i++){ if(results[i]) right++; else misses.push(roundList[i]); }
    var total=roundList.length, pct=Math.round(right/Math.max(1,total)*100);
    completed.add(currentRound); persist();            // round counts as done (score doesn't matter)
    var allDone = completed.size>=totalRounds();
    var b='<button type="button" class="fl-btn fl-primary" id="flReplay">Replay this round</button>';
    if(misses.length) b+='<button type="button" class="fl-btn fl-danger" id="flRetry">Retry mistakes ('+misses.length+')</button>';
    if(allDone) b+='<button type="button" class="fl-btn" id="flNew">Shuffle &amp; play again &#8635;</button>';
    else b+='<button type="button" class="fl-btn" id="flNext">Next round &rarr;</button>';
    b+='<button type="button" class="fl-btn" id="flBack">Back to list</button>';
    play.innerHTML='<div class="fl-done"><div class="fl-ring" style="--fl-pct:'+pct+'%"><div class="fl-in">'+right+'/'+total+'<small>this round</small></div></div>'+
      '<p class="fl-big">'+(allDone?'All rounds done!':pct===100?'Mastered!':pct>=70?'Nice work.':'Keep going.')+'</p>'+
      '<p class="fl-sub">Round '+roundNo()+' of '+totalRounds()+' &middot; '+right+' correct &middot; score '+score+
        (allDone?'<br>Every round is complete — shuffle for a fresh mix.':'<br>Next time you open Filler it resumes at Round '+(firstUnfinished()+1)+'.')+'</p>'+
      '<div class="fl-pfoot" style="padding:22px 0 0">'+b+'</div></div>';
    document.getElementById('flBack').onclick=close;
    document.getElementById('flReplay').onclick=function(){ streak=0; playRound(currentRound); };
    if(misses.length) document.getElementById('flRetry').onclick=function(){ streak=0; startRound(shuffle(misses.slice())); };
    if(allDone) document.getElementById('flNew').onclick=function(){ newCycle(); };
    else document.getElementById('flNext').onclick=function(){ streak=0; playRound(firstUnfinished()); };
  }
  document.getElementById('flStart').onclick=open;
  scrim.addEventListener('click',function(e){ if(e.target===scrim) close(); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&scrim.classList.contains('fl-open')) close(); });
})();
</script>
</div>
@endverbatim
