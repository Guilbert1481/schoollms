{{--
    Subscribed-roles checklist for the Add/Edit Partner modals.
    Props:
      $roleCatalog — grouped catalog (segment => [key => ['label'=>..]]) from SchoolRole::groupedCatalog()
      $checkedKeys — list of role keys to pre-check (defaults on create; [] on edit where JS sets them)
--}}
@php($checkedKeys = $checkedKeys ?? [])

<div class="col-span-2 pt-3 border-t mt-2">
    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Subscribed Roles</p>
    <p class="text-xs text-slate-400 mb-3">Tick the roles this school can assign users to. You can change these later from Edit.</p>

    <div class="space-y-3 max-h-56 overflow-y-auto pr-1">
        @foreach($roleCatalog as $segment => $roles)
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ $segment }}</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                    @foreach($roles as $key => $meta)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="roles[]" value="{{ $key }}"
                                   class="js-role-checkbox w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   @checked(in_array($key, $checkedKeys, true))>
                            <span>{{ $meta['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
