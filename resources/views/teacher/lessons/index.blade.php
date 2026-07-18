@extends('layouts.app')

@section('content')
<div class="w-full p-6 bg-slate-50 min-h-screen flex flex-col gap-6"
     x-data="{
         search: '',
         mode: '{{ session('mode', 'default') }}',
         openCreate: {{ $errors->any() ? 'true' : 'false' }},
         showHidden: false,
         viewC: null,
         addFor: null,
         form: { name: '', code: '', description: '', bloom: '' },
         formError: '',
         groups: @js($groups),
         lessonMatches(l) {
             // Default = Course Architect's lessons; Mine = the teacher's own.
             if (this.mode === 'mine' && !l.mine) return false;
             if (this.mode === 'default' && l.mine) return false;
             const q = this.search.trim().toLowerCase();
             if (!q) return true;
             return (l.name || '').toLowerCase().includes(q)
                 || (l.topic || '').toLowerCase().includes(q)
                 || l.competencies.some(c => (c.name || '').toLowerCase().includes(q) || (c.code || '').toLowerCase().includes(q));
         },
         subjectCount(subject) {
             return subject.lessons.filter(l => this.lessonMatches(l)).length;
         },
         topicsOf(subject) {
             const map = {};
             for (const l of subject.lessons) {
                 if (!this.lessonMatches(l)) continue;
                 const key = l.topic || 'No topic';
                 if (!map[key]) map[key] = [];
                 map[key].push(l);
             }
             return Object.keys(map).map(name => {
                 const lessons = map[name];
                 const recent = lessons.reduce((a, b) => (b.updated_ts > a.updated_ts ? b : a), lessons[0]);
                 return {
                     name,
                     updated: recent.updated,
                     competencyCount: lessons.reduce((n, l) => n + l.competencies.filter(c => this.showHidden || !c.hidden).length, 0),
                     lessons,
                 };
             });
         },
         visibleComps(lesson) {
             return lesson.competencies.filter(c => this.showHidden || !c.hidden);
         },
         csrf() { return document.querySelector('meta[name=csrf-token]').content; },
         async req(url, method, body) {
             const opts = { method, headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' } };
             if (body instanceof FormData) { opts.body = body; }
             else if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
             const res = await fetch(url, opts);
             if (!res.ok) {
                 let msg = 'Something went wrong.';
                 try { const j = await res.json(); msg = j.message || msg; } catch (e) {}
                 throw new Error(msg);
             }
             return res.status === 204 ? null : res.json();
         },
         openView(c) { this.viewC = c; },
         questionUrl(c) {
             const base = '{{ route('teacher.question.metadata') }}';
             const p = new URLSearchParams({ subject_id: c.subject_id, topic_id: c.topic_id, lesson_id: c.lesson_id, competency_id: c.id });
             if (c.academic_level_id) p.set('academic_level_id', c.academic_level_id);
             return base + '?' + p.toString();
         },
         async hide(c) {
             try { await this.req('/teacher/lessons/competencies/' + c.id + '/hide', 'POST'); c.hidden = true; }
             catch (e) { alert(e.message); }
         },
         async unhide(c) {
             try { await this.req('/teacher/lessons/competencies/' + c.id + '/unhide', 'POST'); c.hidden = false; }
             catch (e) { alert(e.message); }
         },
         async del(lesson, c) {
             if (! confirm('Delete “' + c.name + '”? Its attached media is removed too.')) return;
             try {
                 await this.req('/teacher/lessons/competencies/' + c.id, 'DELETE');
                 const i = lesson.competencies.findIndex(x => x.id === c.id);
                 if (i > -1) lesson.competencies.splice(i, 1);
                 if (this.viewC && this.viewC.id === c.id) this.viewC = null;
             } catch (e) { alert(e.message); }
         },
         openAdd(lesson) {
             this.addFor = lesson;
             this.form = { name: '', code: '', description: '', bloom: '' };
             this.formError = '';
             if (this.$refs.compFile) this.$refs.compFile.value = '';
         },
         async submitAdd() {
             this.formError = '';
             const fd = new FormData();
             fd.append('lesson_id', this.addFor.id);
             fd.append('name', this.form.name);
             if (this.form.code) fd.append('code', this.form.code);
             if (this.form.description) fd.append('description', this.form.description);
             if (this.form.bloom) fd.append('bloom_level', this.form.bloom);
             if (this.$refs.compFile && this.$refs.compFile.files[0]) fd.append('file', this.$refs.compFile.files[0]);
             try {
                 const created = await this.req('/teacher/lessons/competencies', 'POST', fd);
                 this.addFor.competencies.push(created);
                 this.addFor = null;
             } catch (e) { this.formError = e.message; }
         },
     }">

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            <i data-lucide="notebook-pen" class="w-6 h-6 text-indigo-600"></i>
            Lessons
        </h1>
        <p class="text-sm text-slate-500 mt-1">Plan and organize lesson content for the subjects you teach.</p>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Control row: search (¼ width) on the left; Default/Mine filter + New Lesson on the right. --}}
    <div class="flex items-center justify-between gap-3">
        <input type="text"
               x-model="search"
               placeholder="Search lessons..."
               class="w-1/4 min-w-[180px] rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />

        <div class="flex items-center gap-2">
            <label class="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-500 cursor-pointer select-none mr-1">
                <input type="checkbox" x-model="showHidden" class="rounded border-slate-300 text-indigo-600">
                Show hidden
            </label>
            {{-- Default = the Course Architect's lessons · Mine = lessons you created. --}}
            <select x-model="mode"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="default">Default</option>
                <option value="mine">Mine</option>
            </select>

            <button type="button"
                    @click="openCreate = true"
                    @if($subjects->isEmpty()) disabled @endif
                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <i data-lucide="plus" class="w-4 h-4"></i>
                New Lesson
            </button>
        </div>
    </div>

    {{-- No subjects assigned --}}
    @if($subjects->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-10 text-center text-slate-400">
            <p class="text-sm">You have no subjects assigned yet. Ask your registrar to assign your teaching subjects.</p>
        </div>
    @endif

    {{-- Subjects — collapsed by default (accordion). Click a subject to reveal its
         Topic → Lesson → Competency tree; a search auto-opens matching subjects. --}}
    <template x-for="subject in groups" :key="subject.id">
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden"
                 x-data="{ open: false }">

            {{-- Accordion header --}}
            <button type="button" @click="open = ! open"
                    class="w-full flex items-center justify-between gap-3 px-5 py-3.5 text-left hover:bg-slate-50">
                <h2 class="font-semibold text-slate-800" x-text="subject.name"></h2>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs text-slate-500"
                          x-text="subjectCount(subject) + (subjectCount(subject) === 1 ? ' lesson' : ' lessons')"></span>
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200"
                         :class="(open || (search && subjectCount(subject) > 0)) && 'rotate-180'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </button>

            {{-- Body: opens on click, and auto-opens while a search has matches. --}}
            <div x-show="open || (search && subjectCount(subject) > 0)" x-collapse style="display: none;">
                <div class="border-t border-slate-100 px-5 py-4 space-y-4">

                    {{-- Empty state for the current mode / search --}}
                    <template x-if="topicsOf(subject).length === 0">
                        <p class="text-sm text-slate-400"
                           x-text="search ? 'No lessons match your search.'
                                          : (mode === 'mine' ? 'You haven\'t created any lessons here yet — click “New Lesson”.'
                                                             : 'No lessons yet.')"></p>
                    </template>

                    {{-- TOPIC · time · competency count --}}
                    <template x-for="topic in topicsOf(subject)" :key="topic.name">
                        <div>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                </svg>
                                <span class="text-sm font-semibold text-slate-800" x-text="topic.name"></span>
                                <span class="text-xs text-slate-400"
                                      x-text="'· ' + topic.updated + ' · ' + topic.competencyCount + (topic.competencyCount === 1 ? ' competency' : ' competencies')"></span>
                            </div>

                            {{-- LESSON (indented under the topic) --}}
                            <div class="mt-2 ml-2 space-y-2 border-l border-slate-200 pl-4">
                                <template x-for="lesson in topic.lessons" :key="lesson.id">
                                    <div>
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                                <span class="text-sm font-medium text-slate-800 truncate" x-text="lesson.name"></span>
                                            </div>
                                            <span class="text-[11px] px-2 py-0.5 rounded-full shrink-0"
                                                  :class="lesson.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'"
                                                  x-text="lesson.is_active ? 'Active' : 'Inactive'"></span>
                                        </div>

                                        {{-- COMPETENCY (indented under the lesson) --}}
                                        <div class="mt-1 ml-1.5 border-l border-slate-100 pl-4">
                                            <template x-if="visibleComps(lesson).length === 0">
                                                <p class="text-xs italic text-slate-400 py-0.5">No competencies.</p>
                                            </template>
                                            <template x-for="c in lesson.competencies" :key="c.id">
                                                <div x-show="showHidden || ! c.hidden"
                                                     class="flex items-start justify-between gap-2 py-0.5">
                                                    {{-- Click the row to open the view modal. --}}
                                                    <button type="button" @click="openView(c)"
                                                            class="flex items-start gap-2 min-w-0 text-left flex-1">
                                                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full"
                                                              :class="c.hidden ? 'bg-slate-300' : 'bg-indigo-400'"></span>
                                                        <span class="min-w-0 text-sm" :class="c.hidden && 'opacity-50'">
                                                            <span x-show="c.code" class="mr-1 font-mono text-[11px] text-indigo-600" x-text="c.code"></span>
                                                            <span class="text-slate-700" x-text="c.name"></span>
                                                            <span x-show="c.bloom"
                                                                  class="ml-1 inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-slate-500"
                                                                  x-text="c.bloom"></span>
                                                            <span x-show="c.resources && c.resources.length" class="ml-1 text-[10px] text-slate-400">▦ media</span>
                                                            <span x-show="c.mine" class="ml-1 text-[10px] text-emerald-600">• yours</span>
                                                        </span>
                                                    </button>

                                                    {{-- Kebab: View / Hide / Unhide / Delete --}}
                                                    <div class="relative shrink-0" x-data="{ menu: false }" @click.outside="menu = false">
                                                        <button type="button" @click="menu = ! menu"
                                                                class="p-1 rounded hover:bg-slate-100 text-slate-400" aria-label="Actions">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/>
                                                            </svg>
                                                        </button>
                                                        <div x-show="menu" x-transition style="display:none;"
                                                             class="absolute right-0 z-20 mt-1 w-40 rounded-lg border border-slate-200 bg-white py-1 text-sm shadow-lg">
                                                            <a :href="questionUrl(c)"
                                                               class="block w-full px-3 py-1.5 text-left font-medium text-indigo-700 hover:bg-indigo-50">Create question</a>
                                                            <button type="button" x-show="c.resources && c.resources.length"
                                                                    @click="openView(c); menu = false"
                                                                    class="block w-full px-3 py-1.5 text-left hover:bg-slate-50">View</button>
                                                            <button type="button" x-show="! c.mine && ! c.hidden"
                                                                    @click="hide(c); menu = false"
                                                                    class="block w-full px-3 py-1.5 text-left hover:bg-slate-50">Hide</button>
                                                            <button type="button" x-show="! c.mine && c.hidden"
                                                                    @click="unhide(c); menu = false"
                                                                    class="block w-full px-3 py-1.5 text-left hover:bg-slate-50">Unhide</button>
                                                            <button type="button" x-show="c.mine"
                                                                    @click="del(lesson, c); menu = false"
                                                                    class="block w-full px-3 py-1.5 text-left text-rose-600 hover:bg-rose-50">Delete</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- Author your own competency under this lesson. --}}
                                            <button type="button" @click="openAdd(lesson)"
                                                    class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                                <span class="text-sm leading-none">+</span> Add competency
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>
    </template>

    {{-- Create Modal --}}
    <div x-show="openCreate"
         x-transition.opacity
         class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6"
             @click.outside="openCreate = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">New Lesson</h3>
                <button type="button" @click="openCreate = false" class="text-slate-400 hover:text-slate-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('teacher.lessons.store') }}" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Subject</label>
                    <select name="subject_id" required
                            class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select a subject…</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" @selected(old('subject_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('subject_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Topic</label>
                    <input type="text" name="topic" value="{{ old('topic') }}" required
                           placeholder="e.g. Fractions, Reading Comprehension…"
                           class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    <p class="text-[11px] text-slate-400 mt-1">Groups the lesson. An existing topic of the same name is reused.</p>
                    @error('topic')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Lesson title</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea name="description" rows="3"
                              class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description') }}</textarea>
                    @error('description')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="openCreate = false"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm">Cancel</button>
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Lesson</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Competency VIEW modal (row-click or kebab "View"). --}}
    <div x-show="viewC" x-transition.opacity @keydown.escape.window="viewC = null"
         class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6"
             @click.outside="viewC = null">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-slate-800 break-words">
                        <span x-show="viewC && viewC.code" class="font-mono text-sm text-indigo-600 mr-1" x-text="viewC && viewC.code"></span>
                        <span x-text="viewC && viewC.name"></span>
                    </h3>
                    <p x-show="viewC && viewC.bloom" class="mt-0.5 text-xs uppercase tracking-wide text-slate-400" x-text="viewC && viewC.bloom"></p>
                </div>
                <button type="button" @click="viewC = null" class="text-slate-400 hover:text-slate-700 shrink-0">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <p x-show="viewC && viewC.description" class="text-sm text-slate-600 whitespace-pre-line" x-text="viewC && viewC.description"></p>

            <div class="mt-4 space-y-4">
                <template x-if="viewC && (! viewC.resources || viewC.resources.length === 0)">
                    <p class="text-sm italic text-slate-400">No media attached to this competency.</p>
                </template>
                <template x-for="(r, i) in ((viewC && viewC.resources) || [])" :key="i">
                    <div>
                        <p class="text-xs text-slate-500 mb-1" x-text="r.name"></p>
                        <template x-if="r.type === 'pdf'">
                            <iframe :src="r.url" class="w-full h-[65vh] rounded-lg border border-slate-200"></iframe>
                        </template>
                        <template x-if="r.type === 'video'">
                            <video :src="r.url" controls class="w-full max-h-[65vh] rounded-lg bg-black"></video>
                        </template>
                        <template x-if="r.type !== 'pdf' && r.type !== 'video'">
                            <a :href="r.url" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                                </svg>
                                Download / open
                            </a>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ADD competency modal (per lesson). --}}
    <div x-show="addFor" x-transition.opacity @keydown.escape.window="addFor = null"
         class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6" @click.outside="addFor = null">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">New Competency</h3>
                <button type="button" @click="addFor = null" class="text-slate-400 hover:text-slate-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <p x-show="formError" class="mb-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 px-3 py-2 text-sm" x-text="formError"></p>

            <form @submit.prevent="submitAdd" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Competency</label>
                    <input type="text" x-model="form.name" required maxlength="255"
                           class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Code <span class="text-slate-400 font-normal">(optional)</span></label>
                        <input type="text" x-model="form.code" maxlength="50"
                               class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Bloom level <span class="text-slate-400 font-normal">(optional)</span></label>
                        <select x-model="form.bloom"
                                class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">—</option>
                            <option value="remember">Remember</option>
                            <option value="understand">Understand</option>
                            <option value="apply">Apply</option>
                            <option value="analyze">Analyze</option>
                            <option value="evaluate">Evaluate</option>
                            <option value="create">Create</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea x-model="form.description" rows="2"
                              class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Media <span class="text-slate-400 font-normal">(optional — pdf, ppt, or video)</span></label>
                    <input type="file" x-ref="compFile" accept=".pdf,.ppt,.pptx,.mp4,.mov,.webm,.mkv"
                           class="w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" />
                    <p class="text-[11px] text-slate-400 mt-1">Up to 200 MB. PDF and video preview inline; PPT downloads.</p>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="addFor = null"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm">Cancel</button>
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Competency</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
