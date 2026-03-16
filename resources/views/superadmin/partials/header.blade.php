<header class="admin-header bg-white px-[30px] py-[15px] flex justify-between items-center shadow-sm border-b border-slate-100">
    <div class="header-title">
        <h3 class="m-0 text-[#1e293b] text-xl font-bold tracking-tight">System Overview</h3>
        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Global Platform Control</p>
    </div>

    <div class="flex items-center gap-5">
        <div class="text-right border-r pr-5 border-slate-100">
            <div class="text-sm font-bold text-[#1e293b] leading-tight">{{ Auth::user()->name }}</div>
            <div class="text-[10px] font-extrabold text-indigo-500 uppercase tracking-tighter">Super Admin</div>
        </div>
        
        <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="bg-slate-50 hover:bg-rose-50 hover:text-rose-600 text-slate-500 px-4 py-2 rounded-lg text-xs font-bold transition-all border border-slate-100">
                Logout
            </button>
        </form>
    </div>
</header>