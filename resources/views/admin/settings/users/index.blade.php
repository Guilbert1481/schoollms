@extends('layouts.app')

@section('content')
@php
    $user = auth()->user();
    $school = $user->school;
@endphp

<div x-data="userModals()" class="p-6 lg:p-4 w-full mx-auto">

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">User Management</h2>
        <p class="text-slate-500 text-sm">Create and manage school personnel.</p>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-6 p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 w-full mx-auto">
        {{-- Add New User Button and Filter --}}
        <div class="flex justify-between items-center mb-4">
            {{-- Search / Filter --}}
            <input type="text"
                id="sessionFilter"
                placeholder="Filter session"
                class="border rounded px-3 py-2 mb-3 w-64">

            <div class="flex gap-2">
                {{-- Add New Role Button --}}
                <button
                    class="py-2 px-5 h-10 bg-green-600 text-white rounded-lg font-bold text-xs uppercase hover:bg-green-700 transition flex items-center"
                    @click="openRoleModal()"
                >
                    + New Role
                </button>

                {{-- Add New User Button --}}
                <button 
                    class="py-2 px-5 h-10 bg-indigo-600 text-white rounded-lg font-bold text-xs uppercase hover:bg-indigo-700 transition flex items-center"
                    @click="openCreateModal()"
                >
                    + New User
                </button>
            </div>
            {{-- ===== Create Role Modal ===== --}}
            <div x-show="showRoleModal"
                 class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
                 x-transition
                 style="display: none;"
                 @click.away="closeRoleModal()"
            >
                <div class="bg-white rounded-2xl p-8 w-full max-w-md shadow-lg relative">
                    <button class="absolute top-3 right-3 text-slate-500 hover:text-slate-800 text-lg" @click="closeRoleModal()">&times;</button>
                    <h3 class="font-bold text-slate-800 mb-6 text-lg">Create New Role</h3>
                    <form method="POST" action="{{ route('roles.store') }}" class="space-y-5">
                        @csrf
                        <div class="flex items-center gap-2 mb-4">
                            <input type="text" name="role_name" x-model="roleForm.role_name" required placeholder="Role name..."
                                class="w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                            <select name="is_head_role" x-model="roleForm.is_head_role" class="rounded-lg border border-slate-200 px-2 py-2 text-sm">
                                <option value="0">Regular</option>
                                <option value="1">Head</option>
                            </select>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button type="button" @click="addRoleField()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded font-bold text-xs uppercase tracking-widest hover:bg-slate-300 transition-all">Add Role</button>
                            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-xl font-bold text-sm uppercase tracking-widest hover:bg-green-700 transition-all shadow-md active:scale-95">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Users Table --}} 
            
        <div class="overflow-x-auto">
            <table id="sessionTable" class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-slate-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="text-left py-3 px-4">Full Name</th>
                        <th class="text-left py-3 px-4">Email</th>
                        <th class="text-left py-3 px-4">Password</th>
                        <th class="text-left py-3 px-4">Role</th>
                        <th class="text-left py-3 px-4">Mobile Number</th>
                        <th class="text-left py-3 px-4">Birthday</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="table-row-item hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-medium text-slate-800">{{ trim($user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name) }}</td>
                            <td class="py-3 px-4 text-slate-600">{{ $user->email }}</td>
                            <td class="py-3 px-4">**************</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $user->badge_color ?? 'bg-gray-100' }} 
                                    {{ $user->badge_text_color ?? 'text-gray-700' }}">
                                    
                                    {{ $user->role_name 
                                        ? ucfirst(str_replace('_', ' ', $user->role_name)) 
                                        : 'No Role' 
                                    }}
                                </span>
                            </td>
                            <td class="py-3 px-4">{{ $user->phone ?? '' }}</td>
                            <td class="py-3 px-4">{{ $user->birthday ?? '' }}</td>
                            <td class="py-3 px-4">
                                <div class="flex gap-2">
                                    {{-- Edit: load user data in modal --}}
                                    <button type="button"
                                        class="text-indigo-500 hover:text-indigo-700 focus:outline-none"
                                        title="Edit"
                                        @click='openEditModal(@json($user))'>
                                        <i data-lucide="pencil" class="w-5 h-5"></i>
                                    </button>
                                    {{-- View: load user data in modal --}}
                                    <button type="button"
                                        class="text-sky-500 hover:text-sky-700 focus:outline-none"
                                        title="View"
                                        @click='openViewModal(@json($user))'>

                                        <i data-lucide="inspect" class="w-5 h-5"></i>
                                    </button>
                                    {{-- Delete: form post --}}
                                    <form method="POST" action="{{ route('settings.users.delete', $user->id) }}" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-rose-500 hover:text-rose-700 focus:outline-none"
                                                title="Delete">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="noResults">
                            <td colspan="7" class="py-6 text-center text-slate-400">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Create/Edit User Modal ===== --}}
    <div x-show="showUserModal"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
         x-transition
         style="display: none;"
         @click.away="closeUserModal()"
         >

        <div class="bg-white rounded-2xl p-8 w-full max-w-2xl shadow-lg relative">
            <button class="absolute top-3 right-3 text-slate-500 hover:text-slate-800 text-lg" @click="closeUserModal()">&times;</button>
            <h3 class="font-bold text-slate-800 mb-6 text-lg" x-text="userForm.id ? 'Edit User' : 'Create New User'"></h3>
            <form :action="userForm.id ? '/settings/users/' + userForm.id : '{{ route('settings.users.store') }}'"
                  method="POST" class="space-y-5">
                @csrf
                <template x-if="userForm.id">
                    <input type="hidden" name="_method" value="PUT" />
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- LEFT COLUMN -->
                    <div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">First Name</label>
                            <input type="text" name="first_name" required x-model="userForm.first_name"
                                   class="mt-1 w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Middle Name (Optional)</label>
                            <input type="text" name="middle_name" x-model="userForm.middle_name"
                                   class="mt-1 w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Last Name</label>
                            <input type="text" name="last_name" required x-model="userForm.last_name"
                                   class="mt-1 w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Email</label>
                            <input type="email" name="email" required x-model="userForm.email"
                                   class="mt-1 w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <!-- RIGHT COLUMN -->
                    <div>
                        <template x-if="!userForm.id">
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Password</label>
                                <input type="password" name="password" required 
                                    class="mt-1 w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </template>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Role</label>
                            <select name="role" required x-model="userForm.role"
                                class="mt-1 w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">

                                <option value="">Select Role</option>

                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">
                                        {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                    </option>
                                @endforeach

                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mobile Number</label>
                            <input type="text" name="mobile_number" x-model="userForm.mobile_number"
                                class="mt-1 w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Birthday</label>
                            <input type="date" name="birthday" x-model="userForm.birthday"
                                   class="mt-1 w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
                <!-- Address, full width below birthday -->
                <div class="mt-3">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Address</label>
                    <input type="text" name="address" x-model="userForm.address"
                        class="mt-1 w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex justify-end mt-6 gap-3">
                    <button type="button" @click="closeUserModal()" class="px-6 py-2 bg-slate-300 text-slate-800 rounded-xl font-bold text-sm uppercase tracking-widest hover:bg-slate-400 transition-all shadow-md active:scale-95">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2 bg-slate-900 text-white rounded-xl font-bold text-sm uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-md active:scale-95"
                        x-text="userForm.id ? 'Update User' : 'Create User'">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== View User Modal ===== --}}
    <div x-show="showViewModal"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
         x-transition
         style="display: none;"
         @click.away="closeViewModal()"
    >
        <div class="bg-white rounded-2xl p-8 w-full max-w-lg shadow-lg relative">
            <button class="absolute top-3 right-3 text-slate-500 hover:text-slate-800 text-lg" @click="closeViewModal()">&times;</button>
            <h3 class="font-bold text-slate-800 mb-6 text-lg">View User</h3>
            <ul class="space-y-2">
                <li><strong>Full Name:</strong> <span x-text="viewUser.full_name"></span></li>
                <li><strong>Email:</strong> <span x-text="viewUser.email"></span></li>
                <li><strong>Role:</strong> <span x-text="viewUser.role"></span></li>
                <li><strong>Mobile Number:</strong> <span x-text="viewUser.mobile_number"></span></li>
                <li><strong>Address:</strong> <span x-text="viewUser.address"></span></li>
                <li><strong>Birthday:</strong> <span x-text="viewUser.birthday"></span></li>
            </ul>
        </div>
    </div>
</div>

{{-- Alpine.js state/logic for modals --}}
<script>
function userModals() {
    return {
        showUserModal: false,
        showViewModal: false,
        showRoleModal: false,
        userForm: {
            id: null,
            first_name: '',
            middle_name: '',
            last_name: '',
            email: '',
            role: '',
            phone: '',
            address: '',
            birthday: '',
        },
        roleForm: {
            role_name: '',
            is_head_role: '0',
        },
        viewUser: {
            full_name: '',
            email: '',
            role: '',
            phone: '',
            address: '',
            birthday: '',
        },
        openCreateModal() {
            this.showUserModal = true;
            this.userForm = {
                id: null,
                first_name: '',
                middle_name: '',
                last_name: '',
                email: '',
                role: '',
                phone: '',
                address: '',
                birthday: '',
            };
        },
        openRoleModal() {
            this.showRoleModal = true;
            this.roleForm = {
                role_name: '',
                is_head_role: '0',
            };
        },
        closeRoleModal() {
            this.showRoleModal = false;
        },
        addRoleField() {
            // Placeholder for adding more role fields if needed in the future
        },
        openEditModal(user) {
            this.showUserModal = true;
            this.userForm = {
                id: user.id,
                first_name: user.first_name ?? '',
                middle_name: user.middle_name ?? '',
                last_name: user.last_name ?? '',
                email: user.email ?? '',
                role: user.role ?? '',
                phone: user.phone ?? '',
                address: user.address ?? '',
                birthday: user.birthday ?? '',
            }
        },
        closeUserModal() {
            this.showUserModal = false;
        },
        openViewModal(user) {
            this.showViewModal = true;
            this.viewUser = {
                full_name: user.full_name ?? '',
                email: user.email ?? '',
                role: user.role ?? '',
                phone: user.phone ?? '',
                address: user.address ?? '',
                birthday: user.birthday ?? ''
            }
        },
        closeViewModal() {
            this.showViewModal = false;
        }
    }
}
</script>

{{-- Make sure lucide and alpinejs are included in your main blade layout: --}}
{{-- <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script> --}}
{{-- <script src="//unpkg.com/alpinejs" defer></script> --}}



<script src="{{ asset('js/modules/table-pagination.js') }}"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    initTable("session");
});
</script>
@endsection