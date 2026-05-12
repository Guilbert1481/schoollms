@extends('layouts.app')

@section('content')
<div class="max-w-screen-2xl mx-auto px-8 py-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">Account Access</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-2">
        <button id="currentBtn" onclick="showTab('current')"
            class="px-4 py-2 bg-blue-200 rounded border">
            Current
        </button>

        <button id="historicalBtn" onclick="showTab('historical')"
            class="px-4 py-2 bg-gray-100 rounded border">
            Historical
        </button>
    </div>



    {{-- CURRENT TABLE --}}
<div id="currentTab" class="bg-white p-6 rounded-xl border">
    <input type="text"
       id="currentFilter"
       placeholder="Filter..."
       class="border rounded px-3 py-2 mb-3 w-64">
    <table id="currentTable" class="min-w-full table-fixed text-sm border border-gray-200">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-3 py-2 text-left w-[20%]">Account</th>
                <th class="px-3 py-2 text-left w-[15%]">Office</th>
                <th class="px-3 py-2 text-left w-[15%]">Role</th>
                <th class="px-3 py-2 text-left w-[20%]">Current Holder</th>
                <th class="px-3 py-2 text-left w-[10%]">Status</th>
                <th class="px-3 py-2 text-left w-[20%]">Action</th>
            </tr>
        </thead>

        <tbody class="bg-white">
            @foreach($accounts as $account)
                <tr class="hover:bg-gray-50 border-t">
                    <td class="px-3 py-2">{{ $account->email }}</td>

                    <td class="px-3 py-2">
                        {{ $account->office ?? '' }}
                    </td>

                    <td class="px-3 py-2">
                        {{ ucfirst($account->role) }}
                    </td>

                    <td class="px-3 py-2">
                        @php
                            $holder = $access->firstWhere('user_id', $account->id);
                        @endphp

                        @if($holder)
                            {{ $holder->first_name }} {{ $holder->middle_name }} {{ $holder->last_name }}
                        @else
                            <span class="text-gray-400">No holder</span>
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        @if($account->access_id && $account->is_active)
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">
                                Active
                            </span>
                        @elseif($account->access_id && !$account->is_active)
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs">
                                Inactive
                            </span>
                        @else
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">
                                Vacant
                            </span>
                        @endif
                    </td>

                    <td class="px-3 py-2">
                        <div class="flex gap-2">

                            @if($account->access_id)
                                {{-- Has current holder --}}
                                <button onclick="openTransferModal({{ $account->id }}, '{{ $account->email }}')"
    class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded">
    Transfer
</button>
<button onclick="openModal({{ $account->id }}, '{{ $account->email }}')"
                                    class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded">
                                    Transfer
                                </button>

                                <form method="POST" action="{{ route('school.account-access.deactivate') }}">
                                    @csrf
                                    <input type="hidden" name="access_id" value="{{ $account->access_id }}">
                                    <button type="submit"
                                        class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-800 rounded">
                                        Deactivate
                                    </button>
                                </form>

                            @else
                                {{-- No holder --}}
                                <button onclick="openAssignModal({{ $account->id }}, '{{ $account->email }}')"
                                    class="px-3 py-1 bg-green-100 hover:bg-green-200 text-green-800 rounded">
                                    Assign
                                </button>
                            @endif

                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

    {{-- HISTORICAL TABLE --}}
    <div id="historicalTab" class="bg-white p-6 rounded-xl border hidden">
        <input type="text"
       id="historicalFilter"
       placeholder="Filter..."
       class="border rounded px-3 py-2 mb-3 w-64">
        <table id="historicalTable" class="min-w-full table-fixed text-sm border border-gray-200">
            <thead class="bg-gray-100">
                <th class="px-3 py-2 text-left w-[14%]">Email</th>
                <th class="px-3 py-2 text-left w-[12%]">Last Holder</th>
                <th class="px-3 py-2 text-left w-[10%]">Office</th>
                <th class="px-3 py-2 text-left w-[10%]">Role</th>
                <th class="px-3 py-2 text-left w-[12%]">Assigned By</th>
                <th class="px-3 py-2 text-left w-[10%]">Start Date</th>
                <th class="px-3 py-2 text-left w-[10%]">End Date</th>
                <th class="px-3 py-2 text-left w-[12%]">Assigned To</th>
                <th class="px-3 py-2 text-left w-[10%]">Reason</th>
            </thead>

            <tbody class="bg-white">
                @foreach($historical as $h)
                    <tr class="hover:bg-gray-50 border-t">
                        <td class="px-3 py-2">{{ $h->email }}</td>
                        <td class="px-3 py-2">{{ $h->last_holder }}</td>
                        <td class="px-3 py-2">{{ $h->office}}</td>
                        <td class="px-3 py-2">{{ ucfirst($h->role) }}</td>
                        <td class="px-3 py-2">{{ $h->assigned_by }}</td>
                        <td class="px-3 py-2">{{ $h->start_date }}</td>
                        <td class="px-3 py-2">{{ $h->end_date }}</td>
                        <td class="px-3 py-2">{{ $h->assigned_to }}</td>
                        <td class="px-3 py-2">{{ $h->remarks }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

{{-- Modal --}}
<div id="transferModal" class="fixed inset-0 bg-black bg-opacity-40 hidden flex items-center justify-center">
    <div class="bg-white w-1/2 rounded-xl p-6">

        <h2 class="text-xl font-bold mb-4">Transfer Access</h2>

        <form method="POST" action="{{ route('school.account-access.transfer') }}">
            @csrf

            <input type="hidden" name="user_id" id="transfer_user_id">

            <div class="mb-3">
                <label class="font-semibold">Account</label>
                <input type="text" id="transfer_account_name" class="w-full border rounded p-2" readonly>
            </div>

            <div class="mb-3">
                <label class="font-semibold">New Holder</label>
                <select name="person_id" class="w-full border rounded p-2">
                    @foreach($profiles as $profile)
                        <option value="{{ $profile->id }}">
                            {{ $profile->first_name }} {{ $profile->middle_name }} {{ $profile->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="font-semibold">Remarks</label>
                <input type="text" name="remarks" class="w-full border rounded p-2">
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeTransferModal()" class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-blue-500 text-white rounded">
                    Transfer
                </button>
            </div>

        </form>
    </div>
</div>
<div id="assignModal" class="fixed inset-0 bg-black bg-opacity-40 hidden flex items-center justify-center">
    <div class="bg-white w-1/2 rounded-xl p-6">

        <h2 class="text-xl font-bold mb-4">Assign Access</h2>

        <form method="POST" action="{{ route('school.account-access.assign') }}">
            @csrf

            <input type="hidden" name="user_id" id="assign_user_id">

            <div class="mb-3">
                <label class="font-semibold">Account</label>
                <input type="text" id="assign_account_name" class="w-full border rounded p-2" readonly>
            </div>

            <div class="mb-3">
                <label class="font-semibold">Assign Holder</label>
                <select name="person_id" class="w-full border rounded p-2">
                    @foreach($profiles as $profile)
                        <option value="{{ $profile->id }}">
                            {{ $profile->first_name }} {{ $profile->middle_name }} {{ $profile->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeAssignModal()" class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-green-500 text-white rounded">
                    Assign
                </button>
            </div>

        </form>
    </div>
</div>

<script>
function openTransferModal(userId, accountName) {
    document.getElementById('transfer_user_id').value = userId;
    document.getElementById('transfer_account_name').value = accountName;
    document.getElementById('transferModal').classList.remove('hidden');
}

function closeTransferModal() {
    document.getElementById('transferModal').classList.add('hidden');
}

function openAssignModal(userId, accountName) {
    document.getElementById('assign_user_id').value = userId;
    document.getElementById('assign_account_name').value = accountName;
    document.getElementById('assignModal').classList.remove('hidden');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}


function showTab(tab) {
    const currentTab = document.getElementById('currentTab');
    const historicalTab = document.getElementById('historicalTab');

    const currentBtn = document.getElementById('currentBtn');
    const historicalBtn = document.getElementById('historicalBtn');

    // Update URL parameter
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.replaceState(null, '', url);

    // Hide both tables
    currentTab.classList.add('hidden');
    historicalTab.classList.add('hidden');

    // Reset button styles
    currentBtn.classList.remove('bg-blue-200');
    historicalBtn.classList.remove('bg-blue-200');

    currentBtn.classList.add('bg-gray-100');
    historicalBtn.classList.add('bg-gray-100');

    // Show selected tab
    if(tab === 'current') {
        currentTab.classList.remove('hidden');
        currentBtn.classList.remove('bg-gray-100');
        currentBtn.classList.add('bg-blue-200');
    } else {
        historicalTab.classList.remove('hidden');
        historicalBtn.classList.remove('bg-gray-100');
        historicalBtn.classList.add('bg-blue-200');
    }
}

// On page load, open correct tab
window.onload = function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'current';
    showTab(tab);
}


</script>
<script src="{{ asset('js/modules/table-pagination.js') }}"></script>

<script>
let currentTablePagination;
let historicalTablePagination;

document.addEventListener("DOMContentLoaded", function () {

    currentTablePagination = new TablePagination("currentTable", 10);
    historicalTablePagination = new TablePagination("historicalTable", 10);

    const currentFilter = document.getElementById('currentFilter');
    const historicalFilter = document.getElementById('historicalFilter');

    if(currentFilter){
        currentFilter.addEventListener('input', function(e) {
            const value = e.target.value.toLowerCase();

            currentTablePagination.filter(row => {
                return row.innerText.toLowerCase().includes(value);
            });
        });
    }

    if(historicalFilter){
        historicalFilter.addEventListener('input', function(e) {
            const value = e.target.value.toLowerCase();

            historicalTablePagination.filter(row => {
                return row.innerText.toLowerCase().includes(value);
            });
        });
    }

});
</script>
@endsection