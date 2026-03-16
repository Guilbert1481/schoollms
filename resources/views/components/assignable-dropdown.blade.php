<div class="mb-4">
    <label class="block font-bold text-lg mb-2">Assign To:</label>
    <div class="w-full border rounded-xl bg-white p-4 shadow-sm">
        
        <div class="flex flex-row gap-4 items-start w-full">
            
            <div class="flex-1 min-w-0" 
                x-data="{
                    open: false,
                    groupSearch: '',
                    groups: @js($groups ?? []),
                    selected: [],
                    // This filters the list as you type
                    get filteredGroups() {
                        return this.groups.filter(g => 
                            g.name.toLowerCase().includes(this.groupSearch.toLowerCase())
                        );
                    },
                    toggle(g) {
                        const v = `${g.type}:${g.id}`;
                        this.selected = this.selected.includes(v) ? this.selected.filter(i => i !== v) : [...this.selected, v];
                    }
                }" @click.outside="open = false">
                
                <label class="block font-medium mb-1 text-gray-700 text-sm">Group</label>
                <div class="relative">
                    <button type="button" @click="open = !open" 
                        class="w-full border rounded-lg px-3 py-2 bg-white flex justify-between items-center hover:border-indigo-400 transition-all">
                        <span class="truncate text-xs sm:text-sm" x-text="selected.length > 0 ? selected.length + ' selected' : 'Select group(s)'"></span>
                        <svg class="w-4 h-4 ml-1 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-transition class="absolute left-0 right-0 z-50 mt-2 bg-white border rounded-xl shadow-xl p-3 max-h-64 overflow-y-auto" style="display: none;">
                        <input type="text" x-model="groupSearch" placeholder="Search groups..." 
                            class="w-full border rounded-lg px-3 py-1.5 mb-2 text-sm focus:outline-none focus:border-indigo-500">
                        
                        <template x-for="group in filteredGroups.slice(0, 10)" :key="`${group.type}-${group.id}`">
                            <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-indigo-50 rounded-lg cursor-pointer">
                                <input type="checkbox" :checked="selected.includes(`${group.type}:${group.id}`)" @change="toggle(group)" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                <span class="text-sm text-gray-700 truncate" x-text="group.name"></span>
                            </label>
                        </template>

                        <template x-if="filteredGroups.length === 0">
                            <div class="text-xs text-gray-400 py-2 text-center">No matching groups</div>
                        </template>
                    </div>

                    <template x-for="val in selected" :key="val">
                        <input type="hidden" name="assignments[]" :value="val">
                    </template>
                </div>
            </div>

            <div class="flex-1 min-w-0" 
                x-data="{
                    open: false,
                    search: '',
                    users: [],
                    selected: [],
                    loading: false,
                    timer: null,
                    async fetchUsers() {
                        this.loading = true;
                        try {
                            const r = await fetch(`/assignables/users?search=${encodeURIComponent(this.search)}`);
                            this.users = await r.json();
                        } catch (e) { this.users = []; }
                        this.loading = false;
                    },
                    init() {
                        this.fetchUsers();
                        this.$watch('search', () => {
                            clearTimeout(this.timer);
                            this.timer = setTimeout(() => this.fetchUsers(), 300);
                        });
                    },
                    toggle(id) {
                        const v = `user:${id}`;
                        this.selected = this.selected.includes(v) ? this.selected.filter(i => i !== v) : [...this.selected, v];
                    }
                }" x-init="init()" @click.outside="open = false">

                <label class="block font-medium mb-1 text-gray-700 text-sm">User</label>
                <div class="relative">
                    <button type="button" @click="open = !open" 
                        class="w-full border rounded-lg px-3 py-2 bg-white flex justify-between items-center hover:border-indigo-400 transition-all">
                        <span class="truncate text-xs sm:text-sm" x-text="selected.length > 0 ? selected.length + ' selected' : 'Select user(s)'"></span>
                        <svg class="w-4 h-4 ml-1 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-transition class="absolute left-0 right-0 z-50 mt-2 bg-white border rounded-xl shadow-xl p-3 max-h-64 overflow-y-auto" style="display: none;">
                        <input type="text" x-model="search" placeholder="Search..." class="w-full border rounded-lg px-3 py-1.5 mb-2 text-sm focus:outline-none focus:border-indigo-500">
                        <template x-for="u in users" :key="u.id">
                            <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-indigo-50 rounded-lg cursor-pointer">
                                <input type="checkbox" :checked="selected.includes(`user:${u.id}`)" @change="toggle(u.id)" class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                                <span class="text-sm text-gray-700 truncate" x-text="u.name"></span>
                            </label>
                        </template>
                    </div>

                    <template x-for="val in selected" :key="val">
                        <input type="hidden" name="assignments[]" :value="val">
                    </template>
                </div>
            </div>

        </div>
    </div>
</div>