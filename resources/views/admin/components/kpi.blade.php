<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Card for Teachers (Uses the new variable from the controller) --}}
    <x-kpi-row.stat-card 
        label="Active Teachers" 
        :value="$activeTeachers" 
        icon="briefcase" 
        color="blue" 
    />

    {{-- Card for Students --}}
    <x-kpi-row.stat-card 
        label="Enrolled Students" 
        :value="$enrolledStudents" 
        icon="users" 
        color="indigo" 
    />
</div>