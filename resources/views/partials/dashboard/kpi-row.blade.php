@if(!empty($cards))
    <div class="grid gap-6"
         style="grid-template-columns: repeat({{ count($cards) }}, minmax(0, 1fr));">

        @foreach($cards as $card)
            <x-kpi-row.kpi-shell 
                :title="$card['title']"
                :icon="$card['icon']"
                :color="$card['color']"
            >
                {{ $card['value'] }}
            </x-kpi-row.kpi-shell>
        @endforeach

    </div>
@endif
