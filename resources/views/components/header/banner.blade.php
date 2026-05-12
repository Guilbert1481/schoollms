<style>
@keyframes marqueeMove {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}

.animate-marquee {
    display: inline-block;
    animation: marqueeMove 18s linear infinite;
}

@keyframes flashRed {
    0%, 100% { background-color: #dc2626; }
    50% { background-color: #b91c1c; }
}

.animate-flashRed {
    animation: flashRed 1s infinite;
}
</style>







<div class="hidden md:flex flex-1 mx-6 justify-center items-center">


    @if(isset($superPriority) && $superPriority)

        {{-- ================= SUPER PRIORITY ================= --}}
        <div class="max-w-2xl w-full rounded-xl shadow-sm 
                    border border-white/40 
                    bg-red-600 animate-flashRed 
                    text-white flex items-center 
                    h-14 px-4 overflow-hidden relative">


            {{-- Marquee --}}
            <div class="flex-1 overflow-hidden h-full flex items-center">
                <div class="animate-marquee whitespace-nowrap w-max">
                    <span class="px-12 font-medium">
                        🚨 {{ $superPriority->title }}
                        <span class="ml-2 font-bold opacity-90">
                        🚨 {{ $superPriority->content }}  
                        </span>
                    </span>
                    <span class="px-12 font-medium">
                        — by {{ $superPriority->creator?->full_name ?? 'System' }}
                    
                    </span>
                </div>
            </div>

            {{-- Acknowledge Button --}}
            <form method="POST"
                  action="{{ route('communication.announcements.acknowledge', $superPriority->id) }}"
                  class=" flex-shrink-0 z-20">
                @csrf
                <button type="submit"
                        class="bg-white/20 border border-white/40 text-white text-[10px] uppercase tracking-widest font-extrabold px-3 py-2 rounded-lg hover:bg-white/40 transition-all">
                    Acknowledge
                </button>
            </form>

        </div>

    @else

        {{-- ================= GLOBAL QUOTE ================= --}}
        <div class="max-w-2xl w-full bg-white/80 rounded-xl shadow-sm border border-white/40 h-14 flex items-center overflow-hidden">

            <div class="flex-1 overflow-hidden h-full flex items-center">
                <div class="animate-marquee whitespace-nowrap w-max text-sm font-medium text-black">

                    <span class="px-12">
                        {{ $globalQuote->content ?? '' }}

                        @if(isset($globalQuote) && $globalQuote->author)
                            <span class="ml-4 opacity-70">
                                — {{ $globalQuote->author }}
                            </span>
                        @endif
                    </span>

                   

                </div>
            </div>

        </div>

    @endif

</div>





