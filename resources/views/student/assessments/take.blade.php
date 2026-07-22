@extends('layouts.app')

@section('page-title', 'Test')

@section('content')
<div id="otRoot" class="w-full">
<style>
    #otRoot{ --ot-accent:#1e5fa8; --ot-header:#1c477f; --ot-line:#e2e8f0; --ot-muted:#64748b;
        --ot-ink:#0f172a; --ot-surface:#fff; --ot-surface2:#f8fafc;
        --ot-answered:#16a34a; --ot-unanswered:#e0342c; --ot-review:#f59e0b; --ot-unvisited:#e2e8f0; }
    #otRoot *{ box-sizing:border-box }
    #otRoot .ot-top{ display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
        background:var(--ot-header);color:#fff;border-radius:12px;padding:12px 18px }
    #otRoot .ot-top .t{ font-weight:800;font-size:16px }
    #otRoot .ot-top .s{ font-size:12px;opacity:.8 }
    #otRoot .ot-timer{ display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.12);
        border:1px solid rgba(255,255,255,.22);border-radius:10px;padding:6px 12px }
    #otRoot .ot-timer .lbl{ font-size:10px;text-transform:uppercase;letter-spacing:.7px;opacity:.85 }
    #otRoot .ot-timer .clk{ font-variant-numeric:tabular-nums;font-weight:800;font-size:19px;letter-spacing:1px }
    #otRoot .ot-timer.warn{ background:#dc2626;border-color:#ef4444 }
    #otRoot .ot-tabs{ display:flex;gap:8px;flex-wrap:wrap;margin:14px 0 4px }
    #otRoot .ot-tab{ border:1px solid var(--ot-line);background:var(--ot-surface);color:var(--ot-muted);
        border-radius:999px;padding:7px 14px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;gap:8px;align-items:center }
    #otRoot .ot-tab .dot{ width:8px;height:8px;border-radius:50%;background:#94a3b8 }
    #otRoot .ot-tab.incomplete{ background:var(--ot-answered);border-color:var(--ot-answered);color:#fff }
    #otRoot .ot-tab.incomplete .dot{ background:#bbf7d0 }
    #otRoot .ot-tab.complete{ background:var(--ot-accent);border-color:var(--ot-accent);color:#fff }
    #otRoot .ot-tab.complete .dot{ background:#cfe4ff }
    #otRoot .ot-tab.active{ box-shadow:0 0 0 3px color-mix(in srgb,var(--ot-accent) 32%,transparent) }
    #otRoot .ot-note{ font-size:12px;color:var(--ot-muted);margin:6px 2px 0 }
    #otRoot .ot-body{ display:grid;grid-template-columns:1fr 280px;gap:16px;margin-top:12px;align-items:start }
    @media (max-width:820px){ #otRoot .ot-body{ grid-template-columns:1fr } }
    #otRoot .ot-panel{ background:var(--ot-surface);border:1px solid var(--ot-line);border-radius:12px;overflow:hidden }
    #otRoot .ot-phead{ display:flex;justify-content:space-between;align-items:center;padding:13px 18px;
        border-bottom:1px solid var(--ot-line);background:var(--ot-surface2) }
    #otRoot .ot-phead .sec{ font-weight:800 } #otRoot .ot-phead .prog{ font-size:12px;color:var(--ot-muted) }
    #otRoot .ot-pbody{ padding:20px }
    #otRoot .ot-qn{ font-weight:800;color:var(--ot-accent);font-size:13px;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px }
    #otRoot .ot-qtext{ font-size:16px;font-weight:600;margin:0 0 16px;max-width:60ch }
    #otRoot .ot-opts{ display:flex;flex-direction:column;gap:10px }
    #otRoot .ot-opt{ display:flex;align-items:center;gap:12px;border:1px solid var(--ot-line);background:var(--ot-surface2);
        border-radius:8px;padding:12px 14px;cursor:pointer }
    #otRoot .ot-opt.sel{ border-color:var(--ot-accent);background:color-mix(in srgb,var(--ot-accent) 10%,var(--ot-surface)) }
    #otRoot .ot-opt .k{ font-weight:700;color:var(--ot-muted);min-width:16px }
    #otRoot .ot-txt{ width:100%;font:inherit;color:var(--ot-ink);background:var(--ot-surface2);
        border:1px solid var(--ot-line);border-radius:8px;padding:10px 12px }
    #otRoot textarea.ot-txt{ min-height:120px;resize:vertical }
    #otRoot .ot-rows{ display:flex;flex-direction:column;gap:16px }
    #otRoot .ot-row{ border:1px solid var(--ot-line);border-radius:8px;padding:14px 16px;background:var(--ot-surface2) }
    #otRoot .ot-row .rq{ font-weight:600;margin:0 0 10px;display:flex;gap:10px }
    #otRoot .ot-row .rq .rn{ color:var(--ot-accent);font-weight:800 }
    #otRoot .ot-colB{ display:flex;flex-wrap:wrap;gap:8px;margin-bottom:6px }
    #otRoot .ot-colB .chip{ border:1px dashed #cbd5e1;border-radius:999px;padding:4px 11px;font-size:12px;color:var(--ot-muted);background:#fff }
    #otRoot .ot-enum{ display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px }
    #otRoot .ot-pfoot{ display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;
        padding:14px 18px;border-top:1px solid var(--ot-line);background:var(--ot-surface2) }
    #otRoot .ot-review{ display:inline-flex;align-items:center;gap:8px;font-size:13px;color:var(--ot-muted);cursor:pointer }
    #otRoot .ot-btn{ border:0;border-radius:8px;padding:10px 18px;font:inherit;font-weight:700;cursor:pointer }
    #otRoot .ot-btn-primary{ background:var(--ot-accent);color:#fff } #otRoot .ot-btn-primary:hover{ background:#276fbf }
    #otRoot .ot-btn-ghost{ background:transparent;color:var(--ot-muted);border:1px solid var(--ot-line) }
    #otRoot .ot-btn-ghost:disabled{ opacity:.4;cursor:default }
    #otRoot .ot-nav{ background:var(--ot-surface);border:1px solid var(--ot-line);border-radius:12px;overflow:hidden;position:sticky;top:12px }
    #otRoot .ot-nhead{ background:var(--ot-header);color:#fff;padding:11px 16px;font-weight:800;font-size:13px;display:flex;justify-content:space-between }
    #otRoot .ot-nbody{ padding:14px 16px }
    #otRoot .ot-grid{ display:grid;grid-template-columns:repeat(5,1fr);gap:9px }
    #otRoot .ot-cell{ aspect-ratio:1;border-radius:999px;border:1px solid var(--ot-line);background:var(--ot-unvisited);
        color:#64748b;font-weight:700;font-size:13px;font-variant-numeric:tabular-nums;cursor:pointer;display:flex;align-items:center;justify-content:center }
    #otRoot .ot-cell.answered{ background:var(--ot-answered);color:#fff;border-color:var(--ot-answered) }
    #otRoot .ot-cell.unanswered{ background:var(--ot-unanswered);color:#fff;border-color:var(--ot-unanswered) }
    #otRoot .ot-cell.review{ background:var(--ot-review);color:#3a2c00;border-color:var(--ot-review) }
    #otRoot .ot-cell.current{ box-shadow:0 0 0 3px color-mix(in srgb,var(--ot-accent) 45%,transparent) }
    #otRoot .ot-onepage-note{ font-size:12px;color:var(--ot-muted);text-align:center;padding:6px 4px }
    #otRoot .ot-mini{ display:flex;flex-direction:column;gap:7px }
    #otRoot .ot-mini .mrow{ display:flex;justify-content:space-between;font-size:12px;background:var(--ot-surface2);
        border:1px solid var(--ot-line);border-radius:8px;padding:7px 10px }
    #otRoot .ot-mini .mrow .st{ width:9px;height:9px;border-radius:50%;background:var(--ot-unanswered) }
    #otRoot .ot-mini .mrow.done .st{ background:var(--ot-answered) }
    #otRoot .ot-legend{ margin-top:14px;border-top:1px solid var(--ot-line);padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:7px }
    #otRoot .ot-legend .li{ display:flex;align-items:center;gap:7px;font-size:12px;color:var(--ot-muted) }
    #otRoot .ot-legend .sw{ width:13px;height:13px;border-radius:4px }
    #otRoot .ot-submit{ margin-top:14px;width:100%;background:var(--ot-answered);color:#fff;padding:12px }
    #otRoot .ot-save{ font-size:12px;color:var(--ot-muted) }
    #otRoot .ot-warn{ background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:10px;
        padding:8px 14px;font-size:13px;margin:10px 0 0 }
    #otRoot .ot-fsgate{ position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.94);
        display:flex;align-items:center;justify-content:center;padding:20px }
    #otRoot .ot-fsbox{ background:#fff;border-radius:16px;padding:30px 28px;max-width:430px;text-align:center;
        box-shadow:0 20px 60px rgba(0,0,0,.4) }
    #otRoot .ot-fsicon{ font-size:40px;line-height:1 }
    #otRoot .ot-fstitle{ font-size:20px;font-weight:800;color:var(--ot-ink);margin-top:10px }
    #otRoot .ot-fstext{ font-size:14px;color:var(--ot-muted);margin:8px 0 20px;line-height:1.5 }
</style>

    <div class="ot-top">
        <div>
            <div class="t">{{ $test->title }}</div>
            <div class="s">{{ $test->subject?->name ?? 'Assessment' }} · answers save automatically</div>
        </div>
        <div class="ot-timer" id="otTimer" style="{{ $remainingSeconds === null ? 'display:none' : '' }}">
            <span class="lbl">Time left</span><span class="clk" id="otClock">—</span>
        </div>
    </div>

    <div class="ot-tabs" id="otTabs"></div>
    <div class="ot-note">A section tab is <b style="color:var(--ot-answered)">green</b> while it has an unanswered
        question and turns <b style="color:var(--ot-accent)">blue</b> when complete. <span class="ot-save" id="otSave"></span></div>
    <div class="ot-warn" id="otWarn" style="display:none"></div>

    <div class="ot-body">
        <div class="ot-panel">
            <div class="ot-phead"><span class="sec" id="otSecName">—</span><span class="prog" id="otProg"></span></div>
            <div class="ot-pbody" id="otPbody"></div>
            <div class="ot-pfoot">
                <label class="ot-review" id="otReviewWrap" style="display:none">
                    <input type="checkbox" id="otReview"> Mark for review
                </label>
                <span style="flex:1"></span>
                <button class="ot-btn ot-btn-ghost" id="otPrev">← Back</button>
                <button class="ot-btn ot-btn-primary" id="otNext">Save &amp; Next →</button>
            </div>
        </div>
        <aside class="ot-nav">
            <div class="ot-nhead"><span id="otNavTitle">Questions</span><span id="otNavCount"></span></div>
            <div class="ot-nbody">
                <div id="otNavArea"></div>
                <div class="ot-legend">
                    <div class="li"><span class="sw" style="background:var(--ot-answered)"></span>Answered</div>
                    <div class="li"><span class="sw" style="background:var(--ot-unanswered)"></span>Not answered</div>
                    <div class="li"><span class="sw" style="background:var(--ot-unvisited)"></span>Not visited</div>
                    <div class="li"><span class="sw" style="background:var(--ot-review)"></span>For review</div>
                </div>
                <button class="ot-btn ot-submit" id="otSubmit">Submit Test</button>
            </div>
        </aside>
    </div>

    <form id="otSubmitForm" method="POST" action="{{ route('student.assessments.submit', $attempt) }}" style="display:none">@csrf</form>

    @if ($requireFullscreen)
        <div class="ot-fsgate" id="otFsGate" style="display:none">
            <div class="ot-fsbox">
                <div class="ot-fsicon">⛶</div>
                <div class="ot-fstitle">Fullscreen required</div>
                <div class="ot-fstext">This test must be taken in fullscreen. Leaving fullscreen is recorded for your teacher.</div>
                <button type="button" class="ot-btn ot-btn-primary" id="otFsBtn">Enter fullscreen</button>
            </div>
        </div>
    @endif
</div>

<script>
window.OT = {
    csrf: @json(csrf_token()),
    answerUrl: @json(route('student.assessments.answer', $attempt)),
    eventUrl: @json(route('student.assessments.event', $attempt)),
    remaining: @json($remainingSeconds),
    requireFullscreen: @json($requireFullscreen),
    sections: @json($sections),
};
(function(){
    const OT = window.OT, el = id => document.getElementById(id);
    const S = OT.sections;
    const BUBBLE = ['multiple_choice','true_false','mtf'];
    S.forEach(sec => { sec.cur = 0; sec.st = sec.questions.map(q => ({ visited:false, review:!!q.review })); });
    if (S.length) S[0].st[0].visited = true;
    let active = 0;

    const answered = q => {
        const r = q.response;
        if (r == null) return false;
        if (BUBBLE.includes(q.type)) return !!r.choice_id;
        if (q.type === 'enumeration') return Array.isArray(r.items) && r.items.some(v => (v||'').trim() !== '');
        return ((r.text ?? r.value ?? '') + '').trim() !== '';
    };
    const secDone = s => s.questions.every(answered);
    const secTouched = s => s.st.some(x => x.visited);
    const letter = i => String.fromCharCode(65+i);

    // ---- autosave ----
    let timer=null;
    function save(q, review){
        const body = { question_id:q.question_id, response:q.response, marked_for_review: !!review };
        el('otSave').textContent = 'Saving…';
        fetch(OT.answerUrl, { method:'POST', credentials:'include',
            headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':OT.csrf,'Accept':'application/json' },
            body: JSON.stringify(body) })
          .then(r => { if (r.status===409) return r.json().then(d=>{ location.href=d.redirect; }); el('otSave').textContent='Saved'; })
          .catch(()=> el('otSave').textContent='Save failed — check connection');
    }
    function queueSave(q, review){ clearTimeout(timer); timer=setTimeout(()=>save(q,review), 400); }

    // ---- tabs ----
    function renderTabs(){
        const t = el('otTabs'); t.innerHTML='';
        S.forEach((s,i)=>{
            const done = s.questions.filter(answered).length;
            const b = document.createElement('button'); b.className='ot-tab';
            if (i===active) b.classList.add('active');
            if (secDone(s)) b.classList.add('complete'); else if (secTouched(s)) b.classList.add('incomplete');
            b.innerHTML = '<span class="dot"></span>'+s.title+' <span>'+done+'/'+s.questions.length+'</span>';
            b.onclick = ()=>{ active=i; markVisited(); render(); };
            t.appendChild(b);
        });
    }
    function markVisited(){ const s=S[active]; if (s.mode==='perq') s.st[s.cur].visited=true; else s.st.forEach(x=>x.visited=true); }

    function render(){
        const s=S[active];
        el('otSecName').textContent = 'Section '+(active+1)+' · '+s.title;
        renderTabs();
        s.mode==='perq' ? renderPerQ(s) : renderOnePage(s);
        renderNav(s);
    }

    function renderPerQ(s){
        const i=s.cur, q=s.questions[i], st=s.st[i];
        el('otProg').textContent = 'Question '+(i+1)+' of '+s.questions.length;
        el('otReviewWrap').style.display='inline-flex';
        el('otReview').checked = st.review;
        el('otReview').onchange = e => { st.review=e.target.checked; queueSave(q, st.review); renderNav(s); };
        let h='<div class="ot-qn">Q'+(i+1)+'.</div><p class="ot-qtext">'+esc(q.text)+'</p><div class="ot-opts">';
        q.choices.forEach((c,ci)=>{
            const sel = q.response && +q.response.choice_id === +c.id;
            h += '<label class="ot-opt'+(sel?' sel':'')+'"><input type="radio" name="ot-q" '+(sel?'checked':'')+' data-cid="'+c.id+'">'
               + '<span class="k">'+letter(ci)+'</span><span>'+esc(c.text)+'</span></label>';
        });
        h+='</div>';
        if (s.type==='mtf') h += '<div style="margin-top:8px"><label style="font-size:13px;color:var(--ot-muted)">If FALSE, write the correction:</label>'
            + '<input class="ot-txt" style="margin-top:6px" id="ot-corr" value="'+esc((q.response&&q.response.correction)||'')+'"></div>';
        el('otPbody').innerHTML=h;
        el('otPbody').querySelectorAll('input[name=ot-q]').forEach(r=> r.onchange = e => {
            q.response = Object.assign({}, q.response, { choice_id:+e.target.dataset.cid }); queueSave(q, st.review); render();
        });
        const corr = el('ot-corr'); if (corr) corr.oninput = e => { q.response = Object.assign({choice_id:q.response?.choice_id}, {correction:e.target.value}); queueSave(q, st.review); };
        el('otPrev').disabled = i===0;
        el('otPrev').onclick = ()=>{ if (s.cur>0){ s.cur--; s.st[s.cur].visited=true; render(); } };
        const last = i===s.questions.length-1;
        el('otNext').textContent = last ? 'Finish Section →' : 'Save & Next →';
        el('otNext').onclick = ()=> last ? nextSection() : (s.cur++, s.st[s.cur].visited=true, render());
    }

    function renderOnePage(s){
        el('otProg').textContent = s.questions.length+' items · one page';
        el('otReviewWrap').style.display='none';
        let h='';
        if (s.type==='matching' && s.columnB) h += '<div style="font-size:13px;color:var(--ot-muted);margin-bottom:6px">Column B:</div>'
            + '<div class="ot-colB">'+s.columnB.map(c=>'<span class="chip">'+esc(c)+'</span>').join('')+'</div>';
        h+='<div class="ot-rows">';
        s.questions.forEach((q,i)=>{
            h+='<div class="ot-row"><div class="rq"><span class="rn">'+(i+1)+'.</span><span>'+esc(q.text)+'</span></div>';
            if (s.type==='matching'){
                h+='<select class="ot-txt" data-i="'+i+'"><option value="">— select —</option>'
                  + (s.columnB||[]).map(c=>'<option'+((q.response&&q.response.value===c)?' selected':'')+'>'+esc(c)+'</option>').join('')+'</select>';
            } else if (s.type==='enumeration'){
                h+='<div class="ot-enum">';
                for (let b=0;b<(q.blanks||1);b++) h+='<input class="ot-txt" data-i="'+i+'" data-b="'+b+'" placeholder="'+(b+1)+'." value="'+esc((q.response&&q.response.items&&q.response.items[b])||'')+'">';
                h+='</div>';
            } else if (s.type==='essay'){
                h+='<textarea class="ot-txt" data-i="'+i+'" placeholder="Write your answer…">'+esc((q.response&&q.response.text)||'')+'</textarea>';
            } else {
                h+='<input class="ot-txt" data-i="'+i+'" placeholder="Your answer" value="'+esc((q.response&&q.response.text)||'')+'">';
            }
            h+='</div>';
        });
        h+='</div>';
        el('otPbody').innerHTML=h;
        el('otPbody').querySelectorAll('[data-i]').forEach(inp=>{
            const q=s.questions[+inp.dataset.i];
            inp.oninput = inp.onchange = e => {
                if (s.type==='matching') q.response = { value:e.target.value };
                else if (s.type==='enumeration'){ q.response = q.response||{items:[]}; q.response.items = q.response.items||[]; q.response.items[+e.target.dataset.b]=e.target.value; }
                else q.response = { text:e.target.value };
                queueSave(q, false); renderTabs(); renderNav(s);
            };
        });
        el('otPrev').disabled=true; el('otPrev').onclick=null;
        const lastSec = active===S.length-1;
        el('otNext').textContent = lastSec ? 'Review & Submit →' : 'Finish Section →';
        el('otNext').onclick = ()=> lastSec ? doSubmit() : nextSection();
    }

    function nextSection(){ if (active<S.length-1){ active++; markVisited(); render(); } else doSubmit(); }

    function renderNav(s){
        el('otNavTitle').textContent = s.mode==='perq' ? s.title : 'Section progress';
        el('otNavCount').textContent = s.questions.filter(answered).length+'/'+s.questions.length;
        if (s.mode==='perq'){
            let g='<div class="ot-grid">';
            s.questions.forEach((q,i)=>{
                let c='ot-cell';
                if (s.st[i].review) c+=' review'; else if (answered(q)) c+=' answered'; else if (s.st[i].visited) c+=' unanswered';
                if (i===s.cur) c+=' current';
                g+='<button class="'+c+'" data-i="'+i+'">'+(i+1)+'</button>';
            });
            g+='</div>';
            el('otNavArea').innerHTML=g;
            el('otNavArea').querySelectorAll('.ot-cell').forEach(b=> b.onclick=()=>{ s.cur=+b.dataset.i; s.st[s.cur].visited=true; render(); });
        } else {
            let m='<div class="ot-onepage-note">One page — no per-question grid.</div><div class="ot-mini">';
            s.questions.forEach((q,i)=> m+='<div class="mrow'+(answered(q)?' done':'')+'"><span>Item '+(i+1)+'</span><span class="st"></span></div>');
            el('otNavArea').innerHTML = m+'</div>';
        }
    }

    function doSubmit(){
        let un=0; S.forEach(s=>s.questions.forEach(q=>{ if(!answered(q)) un++; }));
        const msg = un ? ('You have '+un+' unanswered question'+(un>1?'s':'')+'. Submit anyway?') : 'Submit your test now?';
        if (confirm(msg)) el('otSubmitForm').submit();
    }
    el('otSubmit').onclick = doSubmit;

    // ---- server-authoritative timer ----
    if (OT.remaining !== null){
        let left = OT.remaining;
        const clk=el('otClock'), box=el('otTimer');
        (function tick(){
            const m=String(Math.floor(left/60)).padStart(2,'0'), sec=String(left%60).padStart(2,'0');
            clk.textContent = m+':'+sec; box.classList.toggle('warn', left<=300);
            if (left<=0){ el('otSubmitForm').submit(); return; }
            left--; setTimeout(tick,1000);
        })();
    }

    // ---- soft proctoring: log tab-blur / hidden / fullscreen exit ----
    // The browser can't be truly locked down, so this is an audit trail for the
    // teacher, not a block. Server-stamps each event; the client only labels it.
    (function(){
        let leaves = 0, last = 0;
        function send(type){
            fetch(OT.eventUrl, { method:'POST', keepalive:true, credentials:'include',
                headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':OT.csrf,'Accept':'application/json' },
                body: JSON.stringify({ type }) }).catch(()=>{});
        }
        function leave(type){
            const now = Date.now();
            if (now - last < 800) return;   // blur + visibilitychange often fire together — count once
            last = now;
            leaves++; send(type);
            const w = el('otWarn');
            w.style.display = 'block';
            w.textContent = 'You left the test '+leaves+' time'+(leaves>1?'s':'')+'. This is recorded for your teacher.';
        }
        document.addEventListener('visibilitychange', ()=>{ if (document.hidden) leave('hidden'); });
        window.addEventListener('blur', ()=> leave('blur'));
        document.addEventListener('fullscreenchange', ()=>{ if (!document.fullscreenElement) send('fullscreen_exit'); });
    })();

    // ---- fullscreen lockdown (Phase 5 Part C, per-test) ----
    // A deterrent, not a cage: gate the test behind fullscreen and re-gate on exit.
    // Exits are already logged by the proctoring block above.
    if (OT.requireFullscreen) {
        const gate = el('otFsGate'), btn = el('otFsBtn');
        const inFull = () => !!document.fullscreenElement;
        const enter = () => { const d = document.documentElement;
            if (d.requestFullscreen) { try { d.requestFullscreen().catch(()=>{}); } catch(e){} } };
        const sync = () => { if (gate) gate.style.display = inFull() ? 'none' : 'flex'; };
        if (btn) btn.onclick = enter;
        document.addEventListener('fullscreenchange', sync);
        sync(); // show the gate until the student enters fullscreen
    }

    function esc(s){ return (s==null?'':''+s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    render();
})();
</script>
@endsection
