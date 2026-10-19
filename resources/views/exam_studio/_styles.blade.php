<style>
    .st-card { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1.2rem; box-shadow: var(--shadow); margin-bottom:1rem; }
    .st-btn { display:inline-flex; align-items:center; gap:.5rem; padding:.55rem 1.2rem; border-radius:.7rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; text-decoration:none; font-size:.85rem; }
    .st-btn.sec { background:transparent; color: var(--primary-color); border:1px solid var(--primary-color); }
    .st-btn.danger { background:#c62828; }
    .st-btn.big { padding:.9rem 1.8rem; font-size:1.05rem; border-radius:.9rem; }
    .st-btn.mini { padding:.15rem .55rem; font-size:.72rem; }
    .st-btn[disabled] { opacity:.5; cursor:not-allowed; }
    .st-form { display:flex; gap:.6rem; flex-wrap:wrap; align-items:flex-end; }
    .st-form label, .st-dialog label { display:block; font-size:.75rem; color: var(--text-secondary); margin-bottom:.2rem; }
    .st-form input, .st-form select, .st-dialog input, .st-dialog select, .st-dialog textarea { padding:.5rem .7rem; border-radius:.7rem; border:1px solid var(--border-color); background:transparent; color: var(--text-primary); font-family:inherit; }
    .st-table { width:100%; border-collapse:collapse; font-size:.85rem; }
    .st-table th, .st-table td { padding:.55rem .7rem; border-bottom:1px solid var(--border-color); text-align:right; color: var(--text-primary); vertical-align:middle; }
    .st-table th { color: var(--text-secondary); font-size:.78rem; }
    .st-badge { padding:.15rem .65rem; border-radius:2rem; font-size:.75rem; font-weight:800; }
    .st-h2 { font-size:1.05rem; margin:0 0 .7rem; color: var(--text-primary); font-weight:800; }
    .st-muted { color: var(--text-secondary); font-size:.85rem; }
    .st-bar { display:flex; gap:.6rem; flex-wrap:wrap; align-items:center; }
    .st-banner { padding:.7rem 1rem; border-radius:.9rem; margin-bottom:1rem; font-size:.88rem; font-weight:700; background: rgba(21,101,192,.12); color:#1565c0; }
    .st-err { background: rgba(198,40,40,.1); color:#c62828; padding:.5rem .8rem; border-radius:.7rem; margin-bottom:.4rem; font-weight:700; font-size:.83rem; }
    .st-warnbox { background: rgba(230,81,0,.1); color:#bf4a00; padding:.5rem .8rem; border-radius:.7rem; margin-bottom:.4rem; font-size:.83rem; }

    .st-hero { display:grid; grid-template-columns: 2fr 1fr; gap:1rem; margin-bottom:1.2rem; }
    @media (max-width: 800px) { .st-hero { grid-template-columns: 1fr; } }
    .st-main { background: linear-gradient(135deg, color-mix(in srgb, var(--primary-color) 90%, #000), var(--primary-color)); color:#fff; border-radius:1.2rem; padding:1.5rem 1.6rem; box-shadow: var(--shadow); }
    .st-main h2 { margin:0 0 .4rem; font-size:1.35rem; font-weight:900; }
    .st-main p { margin:0 0 1rem; opacity:.92; line-height:1.8; font-size:.9rem; }
    .st-main .st-btn { background:#fff; color:#1a1a1a; }
    .st-main .st-pill { display:inline-block; margin-inline-start:.8rem; padding:.2rem .8rem; border-radius:2rem; background:rgba(255,255,255,.22); font-size:.8rem; font-weight:800; }

    .rd-list { display:flex; flex-direction:column; gap:.55rem; }
    .rd-item { display:flex; gap:.8rem; align-items:flex-start; padding:.7rem .9rem; border-radius:.8rem; border:1px solid var(--border-color); border-right:5px solid var(--c); background: var(--bg-primary); }
    .rd-ico { font-size:1.1rem; color: var(--c); line-height:1.6; }
    .rd-body { flex:1; }
    .rd-body b { color: var(--text-primary); font-size:.92rem; }
    .rd-body small { display:block; color: var(--text-secondary); margin-top:.15rem; line-height:1.7; }

    dialog.st-dialog { border:none; border-radius:1.2rem; padding:0; width:min(640px, 94vw); background: var(--bg-secondary); color: var(--text-primary); box-shadow: 0 20px 60px rgba(0,0,0,.4); }
    dialog.st-dialog::backdrop { background: rgba(0,0,0,.55); }
    .st-dialog .hd { padding:1.1rem 1.3rem; border-bottom:1px solid var(--border-color); font-weight:900; font-size:1.1rem; }
    .st-dialog .bd { padding:1.2rem 1.3rem; display:flex; flex-direction:column; gap:1rem; max-height:70vh; overflow:auto; }
    .st-dialog .ft { padding:1rem 1.3rem; border-top:1px solid var(--border-color); display:flex; gap:.6rem; justify-content:flex-end; }
    .kind-box { border:1px solid var(--border-color); border-radius:1rem; padding:.9rem 1rem; }
    .kind-box.off .kind-fields { display:none; }
    .kind-box > label.kind-title { display:flex; gap:.5rem; align-items:center; font-weight:800; font-size:.95rem; color: var(--text-primary); margin:0; cursor:pointer; }
    .kind-fields { margin-top:.8rem; display:grid; grid-template-columns: 1fr 1fr 1fr; gap:.7rem; }
    .kind-fields .wide { grid-column: 1 / -1; }
    .kind-fields input, .kind-fields textarea { width:100%; box-sizing:border-box; }
    .slot-row { display:flex; gap:.4rem; align-items:center; margin-bottom:.35rem; }
    .slot-row input { width:auto; flex:1; }
    @media (max-width: 560px) { .kind-fields { grid-template-columns: 1fr; } }

    .ex-day { margin-bottom:1rem; }
    .ex-day h3 { margin:0 0 .5rem; font-size:.95rem; color: var(--text-primary); }
    .ex-item { border:1px solid var(--border-color); border-right:5px solid var(--k); border-radius:.9rem; padding:.7rem .9rem; margin-bottom:.55rem; background: var(--bg-primary); }
    .ex-item.locked { border-color: #e65100; }
    .ex-head { display:flex; gap:.6rem; flex-wrap:wrap; align-items:center; }
    .ex-head b { color: var(--text-primary); }
    .ex-rooms { margin-top:.5rem; display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:.5rem; }
    .ex-room { border:1px dashed var(--border-color); border-radius:.7rem; padding:.5rem .6rem; font-size:.82rem; color: var(--text-primary); }
    .ex-room select { width:100%; margin-top:.25rem; padding:.3rem .4rem; border-radius:.5rem; border:1px solid var(--border-color); background:transparent; color: var(--text-primary); font-family:inherit; }
    .ex-room details summary { cursor:pointer; color: var(--primary-color); font-size:.76rem; font-weight:700; }
    .ex-stu { display:flex; justify-content:space-between; gap:.4rem; align-items:center; padding:.15rem 0; font-size:.78rem; }
    .st-stats { display:grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap:.8rem; margin-bottom:1.1rem; }
    .st-stat { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1rem; padding:.8rem; text-align:center; }
    .st-stat b { display:block; font-size:1.5rem; font-weight:800; color: var(--c, var(--text-primary)); }
    .st-stat small { color: var(--text-secondary); font-size:.75rem; }
</style>
