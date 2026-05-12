/* SchoolLMS Academic Scheduler – Tool Hub
 * Alpine component + helpers
 */
(function () {
    'use strict';

    function deepClone(obj) { return JSON.parse(JSON.stringify(obj || {})); }

    function buildSectionState(rawSections, sectionSubjects, subjectLookup) {
        return rawSections.map(sec => {
            const linked = sectionSubjects.filter(ss => Number(ss.section_id) === Number(sec.id));
            const subs = linked.map(ss => {
                const s = subjectLookup[ss.subject_id];
                return {
                    id: Number(ss.subject_id),
                    name: s ? s.name : ('Subject #' + ss.subject_id),
                    code: s ? s.code : null,
                    units: ss.units != null ? Number(ss.units) : (s && s.units ? Number(s.units) : 3),
                    hours: ss.hours_per_week != null ? Number(ss.hours_per_week) : null,
                    is_lab: !!(s && s.is_lab),
                    preferred_room_id: ss.room_id != null ? Number(ss.room_id) : null,
                    source: ss.source || 'manual',
                };
            });
            return {
                id: Number(sec.id),
                name: sec.name,
                size: Number(sec.capacity || 40),
                block: 'auto',
                subjects: subs,
                _addSubjectId: '',
            };
        });
    }

    window.schedulerApp = function () {
        const cfg = window.__SCHEDULER__ || {};
        const subjectLookup = {};
        (cfg.subjects || []).forEach(s => { subjectLookup[s.id] = s; });
        const sectionLookup = {};
        (cfg.sections || []).forEach(s => { sectionLookup[s.id] = s; });
        const teacherLookup = {};
        (cfg.teachers || []).forEach(t => { teacherLookup[t.id] = t; });
        const roomLookup = {};
        (cfg.rooms || []).forEach(r => { roomLookup[r.id] = r; });

        const defaults = cfg.defaults || {};
        return {
            // form state
            tab: 'create',
            advanced: false,
            generating: false,
            termId: cfg.termId || '',

            allDays: ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'],

            policy:  deepClone(defaults.policy || {}),
            section_policy: deepClone(defaults.section_policy || {
                min_hours_per_day: 0,
                max_hours_per_day: 8,
                min_subjects_per_day: 0,
                max_subjects_per_day: 5,
                min_days_per_week: 1,
                max_days_per_week: 3,
                max_allowed_gap: 60,
                allow_gaps: true,
            }),
            teacher_constraints: deepClone(defaults.teacher_constraints || {
                max_hours_per_week: 24,
                max_hours_per_day: 5,
                work_days_per_week: 5,
                min_hours_per_day: 1,
                prioritize_full_time: true,
                part_time_min_hours_per_day: 1,
            }),
            time:    deepClone(defaults.time   || {}),
            weights: deepClone(defaults.weights|| {}),

            sections: [],
            teachers: cfg.teachers || [],
            rooms:    cfg.rooms    || [],
            subjects: cfg.subjects || [],

            // results
            options: [],
            payloadSent: null,

            // active schedule (for Schedule tab)
            activeSchedule: null,
            viewMode: 'section',
            viewEntityId: '',
            accordion: {
                policy: true,
                sectionPolicy: false,
                teacherConstraints: false,
                time: false,
                sections: false,
            },

            init() {
                if (!this.time.break_time) this.time.break_time = { start: '12:00', end: '13:00' };
                if (!Array.isArray(this.time.days_of_week)) this.time.days_of_week = ['monday','tuesday','wednesday','thursday','friday'];
                this.sections = buildSectionState(cfg.sections || [], cfg.sectionSubjects || [], subjectLookup);
                this.activeSchedule = cfg.activeSchedule || null;
                if (this.activeSchedule) this.tab = 'schedule';
                if (window.lucide && lucide.createIcons) lucide.createIcons();

                // Debounced autosave: persist config (policy/section_policy/teacher_constraints/time/weights)
                // whenever the user changes any of those, so refresh restores values.
                this._saveTimer = null;
                const scheduleSave = () => {
                    if (!cfg.routes || !cfg.routes.saveConfig) return;
                    clearTimeout(this._saveTimer);
                    this._saveTimer = setTimeout(() => this.saveConfig(), 800);
                };
                this.$watch('policy', scheduleSave, { deep: true });
                this.$watch('section_policy', scheduleSave, { deep: true });
                this.$watch('teacher_constraints', scheduleSave, { deep: true });
                this.$watch('time', scheduleSave, { deep: true });
                this.$watch('weights', scheduleSave, { deep: true });
            },

            totalWeeklyHoursForSection(section) {
                // Per-section auto: sum of subject units (1 unit = 1 hr/week).
                let total = 0;
                (section.subjects || []).forEach(s => {
                    const u = Number(s.units);
                    if (!isNaN(u)) total += u;
                });
                return total;
            },

            availableWeeklyCapacity() {
                // Hours available per week = days × (end - start - break).
                const start = this.toMinutes(this.time.start_time || '07:00');
                const end   = this.toMinutes(this.time.end_time   || '17:00');
                const bStart = this.toMinutes((this.time.break_time || {}).start || '');
                const bEnd   = this.toMinutes((this.time.break_time || {}).end   || '');
                const breakMin = (bStart && bEnd && bEnd > bStart) ? (bEnd - bStart) : 0;
                const dayMin = Math.max(0, end - start - breakMin);
                const days = (Array.isArray(this.time.days_of_week) ? this.time.days_of_week.length : 5);
                return (dayMin * days) / 60;
            },

            sectionCapacityWarning(section) {
                const need = this.totalWeeklyHoursForSection(section);
                const have = this.availableWeeklyCapacity();
                if (have > 0 && need > have) {
                    return 'Needs ' + need + ' hr but only ' + have.toFixed(1) + ' hr available in the weekly time window.';
                }
                return null;
            },

            async saveConfig() {
                try {
                    await fetch(cfg.routes.saveConfig, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': cfg.csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            term_id: this.termId || null,
                            policy: this.policy,
                            section_policy: this.section_policy,
                            teacher_constraints: this.teacher_constraints,
                            time: this.time,
                            weights: this.weights,
                        }),
                    });
                } catch (e) { /* silent autosave */ }
            },

            availableSubjectsFor(section) {
                const used = new Set(section.subjects.map(s => s.id));
                return this.subjects.filter(s => !used.has(s.id));
            },

            toggleAccordion(key) {
                this.accordion[key] = !this.accordion[key];
            },

            accordionIconClass(key) {
                return this.accordion[key] ? 'rotate-180' : '';
            },

            roomName(id) {
                if (!id) return null;
                const r = (this.rooms || []).find(rr => Number(rr.id) === Number(id));
                return r ? r.name : null;
            },

            addSubjectToSection(section) {
                const id = Number(section._addSubjectId);
                if (!id) return;
                const s = subjectLookup[id];
                if (!s) return;
                section.subjects.push({
                    id: s.id,
                    name: s.name,
                    code: s.code,
                    units: s.units ? Number(s.units) : 3,
                    hours: null,
                });
                section._addSubjectId = '';
            },

            buildPayload() {
                const sections = this.sections.map(sec => ({
                    id: sec.id,
                    name: sec.name,
                    size: sec.size,
                    block: sec.block,
                    subjects: sec.subjects.map(s => ({
                        id: s.id,
                        name: s.name,
                        units: s.units,
                        is_lab: !!s.is_lab,
                        preferred_room_id: s.preferred_room_id || null,
                        hours: (s.hours != null && s.hours !== '' && !isNaN(s.hours))
                                ? Number(s.hours)
                                : (Number(s.units) || 3),
                    })),
                }));
                return {
                    term_id: this.termId || null,
                    policy: this.policy,
                    section_policy: this.section_policy,
                    teacher_constraints: this.teacher_constraints,
                    time: this.time,
                    weights: this.weights,
                    sections,
                    teachers: this.teachers,
                    rooms: this.rooms,
                };
            },

            async generate() {
                if (!this.termId) {
                    alert('Select a term first.');
                    return;
                }
                this.generating = true;
                this.options = [];
                try {
                    const payload = this.buildPayload();
                    this.payloadSent = payload;
                    const res = await fetch(cfg.routes.generate, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': cfg.csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ...payload, advanced: this.advanced }),
                    });
                    const data = await res.json();
                    this.options = (data.options || []).map(o => ({ ...o, _open: true }));
                } catch (e) {
                    console.error(e);
                    alert('Failed to generate schedules.');
                } finally {
                    this.generating = false;
                }
            },

            preview(idx) {
                this.options[idx]._open = !this.options[idx]._open;
            },

            conflictCount(opt) {
                const sessions = opt.sessions || [];
                const hard = sessions.filter(s => s.status === 'conflict').length;
                const soft = sessions.filter(s => ['needs_teacher','needs_room','needs_teacher_room'].includes(s.status)).length;
                const reported = (opt.conflicts || []).length;
                return hard + soft + reported;
            },

            async resolve(idx) {
                const opt = this.options[idx];
                try {
                    const res = await fetch(cfg.routes.resolve, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': cfg.csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            sessions: opt.sessions || [],
                            payload: this.payloadSent,
                            max_passes: 3,
                        }),
                    });
                    const data = await res.json();
                    if (data.ok) {
                        opt.sessions = data.sessions;
                        opt.score = data.score != null ? data.score : opt.score;
                        opt._open = true;
                    }
                } catch (e) {
                    console.error(e);
                    alert('Auto-fix failed.');
                }
            },

            async apply(idx) {
                const opt = this.options[idx];
                if (!this.termId) {
                    alert('Select a term first.');
                    return;
                }
                if (!confirm('Apply Option ' + (idx+1) + '? Previous active schedule will be deactivated.')) return;
                try {
                    const res = await fetch(cfg.routes.apply, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': cfg.csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            sessions: opt.sessions || [],
                            score: opt.score || 0,
                            name: 'Generated v' + new Date().toISOString().slice(0,16),
                            term_id: this.termId,
                            meta: {
                                time: this.time,
                                payload: this.payloadSent || this.buildPayload(),
                            },
                        }),
                    });
                    const data = await res.json();
                    if (data.ok) {
                        this.activeSchedule = { ...opt };
                        this.tab = 'schedule';
                        alert('Schedule applied (version ' + data.version + ').');
                    } else {
                        alert(data.message || 'Apply failed.');
                    }
                } catch (e) {
                    console.error(e);
                    alert('Apply failed.');
                }
            },

            async autoFix() {
                if (!this.activeSchedule) return;
                try {
                    const res = await fetch(cfg.routes.resolve, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': cfg.csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            sessions: this.activeSchedule.sessions || [],
                            payload: this.payloadSent || this.buildPayload(),
                            max_passes: 3,
                        }),
                    });
                    const data = await res.json();
                    if (data.ok) {
                        this.activeSchedule.sessions = data.sessions;
                    }
                } catch (e) {
                    console.error(e);
                }
            },

            // ===== Grid helpers =====
            gridRows() {
                const rows = [];
                const slot = Number(this.time.slot_duration || 60);
                const start = this.toMinutes(this.time.start_time || '07:00');
                const end   = this.toMinutes(this.time.end_time   || '17:00');
                for (let m = start; m + slot <= end; m += slot) {
                    rows.push({
                        startMin: m,
                        endMin:   m + slot,
                        label: this.toHHMM(m) + ' – ' + this.toHHMM(m + slot),
                    });
                }
                return rows;
            },

            toMinutes(hhmm) {
                if (!hhmm) return 0;
                const [h, m] = String(hhmm).split(':').map(Number);
                return (h || 0) * 60 + (m || 0);
            },
            toHHMM(min) {
                const h = Math.floor(min / 60), m = min % 60;
                return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0');
            },

            viewEntities() {
                if (this.viewMode === 'section') return this.sections.map(s => ({ id: s.id, name: s.name }));
                if (this.viewMode === 'teacher') return (this.teachers || []).map(t => ({ id: t.id, name: t.name }));
                if (this.viewMode === 'room')    return (this.rooms || []).map(r => ({ id: r.id, name: r.name }));
                return [];
            },

            displayName(kind, session) {
                if (kind === 'section') return session.section_name || (sectionLookup[session.section_id] || {}).name || session.section_id || '';
                if (kind === 'subject') return session.subject_name || (subjectLookup[session.subject_id] || {}).name || session.subject_id || '';
                if (kind === 'teacher') return session.teacher_name || (teacherLookup[session.teacher_id] || {}).name || '—';
                if (kind === 'room') return session.room_name || (roomLookup[session.room_id] || {}).name || '—';
                return '';
            },

            cellsAt(row, day) {
                if (!this.activeSchedule) return [];
                const idKey = this.viewMode + '_id';
                const slot = Number(this.time.slot_duration || 60);
                return (this.activeSchedule.sessions || []).filter(s => {
                    if (s.day_of_week !== day) return false;
                    const sStart = this.toMinutes(s.start_time);
                    // Only render the cell on the row where it STARTS, not every overlapping row.
                    if (sStart < row.startMin || sStart >= row.endMin) return false;
                    if (this.viewEntityId && Number(s[idKey]) !== Number(this.viewEntityId)) return false;
                    return true;
                }).map(s => {
                    const sStart = this.toMinutes(s.start_time);
                    const sEnd   = this.toMinutes(s.end_time);
                    const span   = Math.max(1, Math.ceil((sEnd - sStart) / slot));
                    return Object.assign({}, s, {
                        __span: span,
                        section_name: this.displayName('section', s),
                        subject_name: this.displayName('subject', s),
                        teacher_name: this.displayName('teacher', s),
                        room_name: this.displayName('room', s),
                    });
                });
            },
        };
    };
})();
