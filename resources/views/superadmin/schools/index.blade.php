@extends('layouts.superadmin')

@section('content')
<div class="max-w-[1600px] mx-auto">
    {{-- Header Section --}}
    <div class="flex justify-between items-center mb-10">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">Partner Institutions</h2>
            <p class="text-slate-500 font-medium mt-1">Manage global feature access for schools and freelancers.</p>
        </div>
        
        <a href="{{ route('superadmin.schools.create') }}" 
           class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-2xl font-bold shadow-xl shadow-indigo-100 transition-all flex items-center gap-3">
            <i class="fas fa-plus-circle"></i>
            <span>Add New Partner</span>
        </a>
    </div>

    {{-- Elegant Table Container --}}
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-visible">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Institution Name</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Type</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active Plan</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Add-ons</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                    <th class="px-8 py-5 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-50">
                @forelse($schools as $school)
                <tr x-data="{ openModal: false }" class="hover:bg-slate-50/30 transition-colors">
                    {{-- Institution Name --}}
                    <td class="px-8 py-6 align-middle">
                        <a href="{{ route('superadmin.schools.show', $school->id) }}" class="group block no-underline">
                            <div class="font-bold text-slate-700 text-lg group-hover:text-indigo-600 transition-colors">{{ $school->name }}</div>
                            <div class="text-[11px] text-slate-400 font-bold uppercase tracking-tighter mt-1">
                                ID: #{{ $school->id }}
                            </div>
                        </a>
                    </td>

                    {{-- Type --}}
                    <td class="px-8 py-6 align-middle">
                        <span class="text-slate-600 text-[11px] font-black uppercase tracking-widest">
                            {{ $school->type }}
                        </span>
                    </td>

                    {{-- Active Plan badge --}}
                    <td class="px-8 py-6 align-middle">
                        <div class="inline-flex items-center px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100">
                            <span class="text-indigo-600 font-black text-[10px] uppercase tracking-wider">
                                {{ $school->plan_name ?? 'Basic' }}
                            </span>
                        </div>
                    </td>

                    {{-- Add-ons Column --}}
                    <td class="px-8 py-6 align-middle relative">
                        @if($school->modules_count > 0)
                            @php
                                $hasExpired = false;
                                $hasCritical = false;
                                $hasWarning = false;
                                
                                foreach($school->modules as $m) {
                                    if ($m->pivot->expires_at && now()->greaterThan($m->pivot->expires_at)) {
                                        $hasExpired = true;
                                        break;
                                    }
                                }
                                
                                if (!$hasExpired) {
                                    foreach($school->modules as $m) {
                                        if ($m->pivot->expires_at) {
                                            $days = now()->diffInDays($m->pivot->expires_at, false);
                                            if ($days <= 5 && $days >= 0) {
                                                $hasCritical = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                                
                                if (!$hasExpired && !$hasCritical) {
                                    foreach($school->modules as $m) {
                                        if ($m->pivot->expires_at) {
                                            $days = now()->diffInDays($m->pivot->expires_at, false);
                                            if ($days <= 10 && $days >= 6) {
                                                $hasWarning = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            @endphp

                            <button @click="$dispatch('open-modules-modal', { schoolId: {{ $school->id }} })"
                                class="flex items-center gap-2 px-3 py-1 bg-white rounded-full border transition-all shadow-sm
                                {{ $hasExpired ? 'border-red-200 text-red-600' : ($hasCritical ? 'border-red-200 text-red-600' : ($hasWarning ? 'border-amber-200 text-amber-600' : 'border-emerald-100 text-emerald-600')) }}">
                                
                                <span class="text-[10px] font-black">{{ $school->modules_count }}</span>
                                <span class="text-[9px] font-bold uppercase tracking-tighter">Add-ons</span>

                                @if($hasExpired || $hasCritical)
                                    <i class="fas fa-exclamation-circle text-[10px] {{ $hasExpired || $hasCritical ? 'animate-pulse' : '' }}"></i>
                                @elseif($hasWarning)
                                    <i class="fas fa-exclamation-triangle text-[10px]"></i>
                                @else
                                    <i class="fas fa-check-circle text-[10px] opacity-40"></i>
                                @endif
                            </button>
                        @else
                            <span class="text-[11px] text-slate-300 italic font-medium">No add-ons</span>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td class="px-8 py-6 align-middle">
                        @if($school->is_active)
                            <div class="flex items-center gap-2 text-emerald-600 font-black text-[10px] uppercase tracking-widest">
                                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                Active
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-red-500 font-black text-[10px] uppercase tracking-widest">
                                <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                                Inactive
                            </div>
                        @endif
                    </td>
                    
                    {{-- Actions --}}
                    <td class="px-8 py-6 align-middle text-center">
                        <div class="inline-flex items-center gap-4 whitespace-nowrap">
                            <a href="{{ route('superadmin.schools.show', $school->id) }}" 
                               class="text-[12px] font-black uppercase tracking-widest no-underline text-indigo-500 hover:text-indigo-800 transition-colors">
                               View
                            </a>

                            <span class="text-slate-200 opacity-50">|</span>

                            <form action="{{ route('superadmin.schools.destroy', $school->id) }}" 
                                  method="POST" 
                                  class="m-0 p-0 leading-none" 
                                  onsubmit="return confirm('Permanently remove this institution?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="text-[12px] font-black text-red-500 hover:text-red-700 border-0 bg-transparent p-0 m-0 uppercase tracking-widest cursor-pointer">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-20 text-center text-slate-400 font-medium italic">
                        No partners found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div x-data="modalData()" 
             @open-modules-modal.window="openModal($event.detail.schoolId)"
             @keydown.escape.window="open = false"
             x-show="open"
             x-cloak
             style="display: none; position: fixed; inset: 0; z-index: 9999; overflow-y: auto;">
            
            <!-- Premium Backdrop with blur -->
            <div style="position: fixed; inset: 0; background: linear-gradient(135deg, rgba(79, 70, 229, 0.4) 0%, rgba(236, 72, 153, 0.3) 100%); backdrop-filter: blur(8px);" 
                 @click="open = false"></div>
            
            <!-- Modal Container -->
            <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem;">
                <div style="position: relative; background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%); border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1); width: 100%; max-width: 42rem; overflow: hidden;" 
                     @click.stop
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100">
                    
                    <!-- Elegant Header with gradient -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem 2.5rem; position: relative; overflow: hidden;">
                        <!-- Decorative elements -->
                        <div style="position: absolute; top: -50%; right: -10%; width: 200px; height: 200px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; filter: blur(40px);"></div>
                        <div style="position: absolute; bottom: -30%; left: -5%; width: 150px; height: 150px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; filter: blur(30px);"></div>
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 1;">
                            <div>
                                <h3 style="font-size: 1.5rem; font-weight: 700; color: white; margin: 0; letter-spacing: -0.025em;">
                                    Module Subscriptions
                                </h3>
                                <p style="font-size: 0.875rem; color: rgba(255, 255, 255, 0.8); margin: 0.5rem 0 0 0; font-weight: 500;">
                                    School ID: <span style="font-weight: 700;" x-text="currentSchoolId"></span>
                                </p>
                            </div>
                            <button @click="open = false" 
                                    style="color: white; background: rgba(255, 255, 255, 0.2); width: 2.5rem; height: 2.5rem; border: none; border-radius: 0.75rem; cursor: pointer; font-size: 1.25rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; backdrop-filter: blur(10px);"
                                    onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'; this.style.transform='rotate(90deg)'" 
                                    onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.transform='rotate(0deg)'">
                                ✕
                            </button>
                        </div>
                    </div>
                    
                    <!-- Premium Content Area -->
                    <div style="padding: 2rem 2.5rem; max-height: 28rem; overflow-y: auto;">
                        <template x-for="module in currentModules" :key="module.id">
                            <div style="background: white; border-radius: 1rem; padding: 1.5rem 1.75rem; margin-bottom: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); transition: all 0.3s ease; position: relative; overflow: hidden;"
                                 :style="'border-left: 4px solid ' + getStatusColor(module)"
                                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'"
                                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'">
                                
                                <!-- Subtle background gradient based on status -->
                                <div style="position: absolute; inset: 0; opacity: 0.03; z-index: 0;"
                                     :style="'background: linear-gradient(135deg, ' + getStatusColor(module) + ' 0%, transparent 100%)'"></div>
                                
                                <div style="position: relative; z-index: 1;">
                                    <!-- Module Header -->
                                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem; margin-bottom: 0.875rem;">
                                        <div style="flex: 1; min-width: 0;">
                                            <h4 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin: 0 0 0.5rem 0; letter-spacing: -0.025em; line-height: 1.4;" 
                                                x-text="module.name"></h4>
                                            <p style="font-size: 0.75rem; color: #64748b; margin: 0; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">
                                                Module ID: <span style="font-weight: 700;" x-text="module.id"></span>
                                            </p>
                                        </div>
                                        
                                        <!-- Premium Status Badge -->
                                        <span style="padding: 0.625rem 1.25rem; border-radius: 2rem; font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); flex-shrink: 0;"
                                              :style="'color: ' + getStatusTextColor(module) + '; background: ' + getStatusBgColor(module) + '; border: 2px solid ' + getStatusColor(module) + '20'"
                                              x-text="getStatusText(module)"></span>
                                    </div>
                                    
                                    <!-- Progress Bar for expiration -->
                                    <template x-if="getDaysRemaining(module) !== null && getDaysRemaining(module) >= 0">
                                        <div style="margin-top: 1rem; padding: 0 0.25rem;">
                                            <div style="height: 6px; background: #e2e8f0; border-radius: 1rem; overflow: hidden; position: relative;">
                                                <div style="height: 100%; border-radius: 1rem; transition: all 0.3s ease;"
                                                     :style="'width: ' + Math.min(100, (getDaysRemaining(module) / 30) * 100) + '%; background: linear-gradient(90deg, ' + getStatusColor(module) + ' 0%, ' + getStatusColor(module) + 'cc 100%)'"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Premium Empty State -->
                        <template x-if="currentModules.length === 0">
                            <div style="text-align: center; padding: 4rem 2rem;">
                                <div style="width: 5rem; height: 5rem; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #e0e7ff 0%, #ddd6fe 100%); border-radius: 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
                                    📦
                                </div>
                                <h4 style="font-size: 1.25rem; font-weight: 600; color: #334155; margin: 0 0 0.5rem 0;">No Modules Found</h4>
                                <p style="font-size: 0.875rem; color: #94a3b8; margin: 0;">This school doesn't have any add-on modules yet.</p>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Elegant Footer -->
                    <div style="background: linear-gradient(to top, #f8fafc 0%, #ffffff 100%); padding: 1.5rem 2.5rem; border-top: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                            <button @click="open = false" 
                                    style="padding: 0.75rem 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.4); text-transform: uppercase; letter-spacing: 0.05em;"
                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 15px -3px rgba(102, 126, 234, 0.4)'" 
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(102, 126, 234, 0.4)'">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function modalData() {
            return {
                open: false,
                currentSchoolId: null,
                allSchools: @json($schools->keyBy('id')),
                currentModules: [],
                
                openModal(schoolId) {
                    this.currentSchoolId = schoolId;
                    this.currentModules = this.allSchools[schoolId]?.modules || [];
                    this.open = true;
                },
                
                getDaysRemaining(module) {
                    if (!module.pivot.expires_at) return null;
                    const expiresAt = new Date(module.pivot.expires_at);
                    const now = new Date();
                    const diffTime = expiresAt - now;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    return diffDays;
                },
                
                isExpired(module) {
                    const days = this.getDaysRemaining(module);
                    return days !== null && days < 0;
                },
                
                isCritical(module) {
                    const days = this.getDaysRemaining(module);
                    return days !== null && days >= 0 && days <= 5;
                },
                
                isWarning(module) {
                    const days = this.getDaysRemaining(module);
                    return days !== null && days >= 6 && days <= 10;
                },
                
                getStatusColor(module) {
                    if (this.isExpired(module) || this.isCritical(module)) return '#ef4444';
                    if (this.isWarning(module)) return '#f59e0b';
                    return '#10b981';
                },
                
                getStatusTextColor(module) {
                    if (this.isExpired(module) || this.isCritical(module)) return '#dc2626';
                    if (this.isWarning(module)) return '#d97706';
                    return '#059669';
                },
                
                getStatusBgColor(module) {
                    if (this.isExpired(module) || this.isCritical(module)) return '#fee2e2';
                    if (this.isWarning(module)) return '#fef3c7';
                    return '#d1fae5';
                },
                
                getStatusText(module) {
                    if (!module.pivot.expires_at) return 'Active';
                    
                    const days = this.getDaysRemaining(module);
                    
                    if (days < 0) return 'Expired';
                    if (days === 0) return 'Today';
                    if (days === 1) return '1 Day';
                    return days + ' Days';
                }
            }
        }
        </script>

    </div>
</div>
@endsection