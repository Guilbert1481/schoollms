{{-- Chart cards section --}}

@if(!empty($trendCharts))
    <div class="grid gap-6"
         style="grid-template-columns: repeat({{ count($trendCharts) }}, minmax(0, 1fr));">

        @foreach($trendCharts as $chartKey => $chart)
            <x-charts.trend-card
                :title="$chart['title']"
                :id="$chartKey"
                :type="$chart['type']"
                :color="$chart['color']"
            />
        @endforeach

    </div>
@endif