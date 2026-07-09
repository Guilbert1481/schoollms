<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $schoolName }} | {{ $pageTitle }}</title>
    @vite(['resources/css/app.css'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.78); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(148, 163, 184, 0.22); }
        .bg-executive { background: radial-gradient(circle at top left, #f8fbff 0%, #eef4ff 55%, #eaf0ff 100%); }
        .course-title-clamp {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-executive text-slate-800 min-h-screen">
    @include('layout.website-header', ['schoolSlug' => $schoolSlug, 'schoolName' => $schoolName, 'schoolLogo' => $schoolLogo, 'activePage' => $activePage, 'loginUrl' => $loginUrl])

    <main class="pt-32 px-6 pb-16">
        <section class="max-w-7xl mx-auto">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-8">Courses</h1>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <article class="glass rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all cursor-pointer course-card"
                    data-title="Modern Web Development: Build Real-World Apps"
                    data-instructor="Prof. Daniel Cruz"
                    data-rating="4.8"
                    data-hours="36 total hours"
                    data-students="1,285 students"
                    data-level="Beginner to Intermediate"
                    data-price="$49.99"
                    data-original="$79.99"
                    data-description="Learn HTML, CSS, JavaScript, PHP, and Laravel by building portfolio-ready projects. This course is designed for students who want practical full-stack web development skills."
                    data-image="https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=900&q=80">
                    <img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=900&q=80" alt="Modern Web Development" class="h-44 w-full object-cover">
                    <div class="p-5">
                        <h3 class="text-slate-900 font-semibold text-lg course-title-clamp">Modern Web Development: Build Real-World Apps</h3>
                        <p class="text-sm text-slate-600 mt-1">Prof. Daniel Cruz</p>
                        <p class="text-xs text-amber-600 font-semibold mt-2">4.8 ★ • 1,285 students</p>
                        <div class="mt-4 flex items-center gap-2">
                            <span class="text-xl font-bold text-slate-900">$49.99</span>
                            <span class="text-sm text-slate-500 line-through">$79.99</span>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <button type="button" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 open-enrol" data-course="Modern Web Development: Build Real-World Apps">Enroll</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 open-course">Details</button>
                        </div>
                    </div>
                </article>

                <article class="glass rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all cursor-pointer course-card"
                    data-title="Data Analytics Essentials with Excel and Power BI"
                    data-instructor="Ms. Angela Santos"
                    data-rating="4.7"
                    data-hours="28 total hours"
                    data-students="980 students"
                    data-level="Beginner"
                    data-price="$39.99"
                    data-original="$59.99"
                    data-description="Master data cleaning, analysis, dashboards, and reporting for academic and business use cases. Perfect for students preparing for internship work."
                    data-image="https://images.unsplash.com/photo-1551281044-8b6d3f8f4f7d?auto=format&fit=crop&w=900&q=80">
                    <img src="https://images.unsplash.com/photo-1551281044-8b6d3f8f4f7d?auto=format&fit=crop&w=900&q=80" alt="Data Analytics" class="h-44 w-full object-cover">
                    <div class="p-5">
                        <h3 class="text-slate-900 font-semibold text-lg course-title-clamp">Data Analytics Essentials with Excel and Power BI</h3>
                        <p class="text-sm text-slate-600 mt-1">Ms. Angela Santos</p>
                        <p class="text-xs text-amber-600 font-semibold mt-2">4.7 ★ • 980 students</p>
                        <div class="mt-4 flex items-center gap-2">
                            <span class="text-xl font-bold text-slate-900">$39.99</span>
                            <span class="text-sm text-slate-500 line-through">$59.99</span>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <button type="button" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 open-enrol" data-course="Data Analytics Essentials with Excel and Power BI">Enroll</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 open-course">Details</button>
                        </div>
                    </div>
                </article>

                <article class="glass rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all cursor-pointer course-card"
                    data-title="Public Speaking and Presentation Masterclass"
                    data-instructor="Dr. Miguel Reyes"
                    data-rating="4.9"
                    data-hours="18 total hours"
                    data-students="742 students"
                    data-level="All Levels"
                    data-price="$29.99"
                    data-original="$44.99"
                    data-description="Develop confidence, delivery, and persuasive speaking techniques for reports, defense presentations, and leadership communication."
                    data-image="https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=900&q=80">
                    <img src="https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=900&q=80" alt="Public Speaking" class="h-44 w-full object-cover">
                    <div class="p-5">
                        <h3 class="text-slate-900 font-semibold text-lg course-title-clamp">Public Speaking and Presentation Masterclass</h3>
                        <p class="text-sm text-slate-600 mt-1">Dr. Miguel Reyes</p>
                        <p class="text-xs text-amber-600 font-semibold mt-2">4.9 ★ • 742 students</p>
                        <div class="mt-4 flex items-center gap-2">
                            <span class="text-xl font-bold text-slate-900">$29.99</span>
                            <span class="text-sm text-slate-500 line-through">$44.99</span>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <button type="button" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 open-enrol" data-course="Public Speaking and Presentation Masterclass">Enroll</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 open-course">Details</button>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </main>

    @include('website.components.modal.enrol')

    <div id="courseModal" class="hidden fixed inset-0 z-[100] bg-slate-900/45 backdrop-blur-sm p-4">
        <div class="mx-auto mt-8 max-w-3xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
            <div class="grid md:grid-cols-2 gap-0">
                <img id="modalImage" src="" alt="Course image" class="h-56 md:h-full w-full object-cover">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-3">
                        <h2 id="modalTitle" class="text-2xl font-bold text-slate-900"></h2>
                        <button id="closeCourseModal" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100">&times;</button>
                    </div>
                    <p id="modalInstructor" class="text-sm text-slate-600 mt-2"></p>
                    <p id="modalMeta" class="text-xs text-amber-600 font-semibold mt-2"></p>
                    <p id="modalLevel" class="text-xs text-slate-500 mt-1"></p>
                    <p id="modalDescription" class="text-slate-700 mt-4 leading-relaxed"></p>
                    <div class="mt-6 flex items-center gap-2">
                        <span id="modalPrice" class="text-2xl font-bold text-slate-900"></span>
                        <span id="modalOriginal" class="text-sm text-slate-500 line-through"></span>
                    </div>
                    <button id="modalEnrollNow" type="button" class="mt-5 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500">Enroll Now</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('courseModal');
            const closeBtn = document.getElementById('closeCourseModal');
            const title = document.getElementById('modalTitle');
            const instructor = document.getElementById('modalInstructor');
            const meta = document.getElementById('modalMeta');
            const level = document.getElementById('modalLevel');
            const description = document.getElementById('modalDescription');
            const image = document.getElementById('modalImage');
            const price = document.getElementById('modalPrice');
            const original = document.getElementById('modalOriginal');
            const modalEnrollNow = document.getElementById('modalEnrollNow');
            const enrolModal = document.getElementById('enrolModal');
            const closeEnrolBtn = document.getElementById('closeEnrolModal');
            const enrolFormTitle = document.getElementById('enrolFormTitle');
            const enrolLoginForm = document.getElementById('enrolLoginForm');
            const enrolSignupForm = document.getElementById('enrolSignupForm');
            const enrolSwitchers = Array.from(document.querySelectorAll('.switch-enrol-tab'));
            let selectedCourse = '';

            const showEnrolForm = (target = 'login') => {
                const mode = target === 'signup' ? 'signup' : 'login';
                if (enrolFormTitle) {
                    enrolFormTitle.textContent = mode === 'signup' ? 'Sign up' : 'Login';
                }
                if (enrolLoginForm) {
                    enrolLoginForm.classList.toggle('hidden', mode !== 'login');
                }
                if (enrolSignupForm) {
                    enrolSignupForm.classList.toggle('hidden', mode !== 'signup');
                }
            };

            const openEnrolModal = (courseTitle = '', form = 'login') => {
                selectedCourse = courseTitle || selectedCourse;
                showEnrolForm(form);
                if (enrolModal) {
                    enrolModal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }
            };

            const closeEnrolModal = () => {
                if (enrolModal) {
                    enrolModal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            };

            const openCourseModal = (card) => {
                title.textContent = card.dataset.title || '';
                instructor.textContent = card.dataset.instructor ? `Instructor: ${card.dataset.instructor}` : '';
                meta.textContent = `${card.dataset.rating || ''} ★ • ${card.dataset.students || ''} • ${card.dataset.hours || ''}`;
                level.textContent = card.dataset.level ? `Level: ${card.dataset.level}` : '';
                description.textContent = card.dataset.description || '';
                image.src = card.dataset.image || '';
                image.alt = card.dataset.title || 'Course image';
                price.textContent = card.dataset.price || '';
                original.textContent = card.dataset.original || '';
                selectedCourse = card.dataset.title || '';

                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            const closeCourseModal = () => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('.course-card').forEach((card) => {
                card.addEventListener('click', () => openCourseModal(card));
                const detailBtn = card.querySelector('.open-course');
                if (detailBtn) {
                    detailBtn.addEventListener('click', (event) => {
                        event.stopPropagation();
                        openCourseModal(card);
                    });
                }

                const enrolBtn = card.querySelector('.open-enrol');
                if (enrolBtn) {
                    enrolBtn.addEventListener('click', (event) => {
                        event.stopPropagation();
                        closeCourseModal();
                        openEnrolModal(enrolBtn.dataset.course || card.dataset.title || '');
                    });
                }
            });

            if (closeBtn) closeBtn.addEventListener('click', closeCourseModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeCourseModal();
                }
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeCourseModal();
                }
            });

            if (modalEnrollNow) {
                modalEnrollNow.addEventListener('click', () => {
                    closeCourseModal();
                    openEnrolModal(selectedCourse);
                });
            }

            enrolSwitchers.forEach((switcher) => {
                switcher.addEventListener('click', () => {
                    showEnrolForm(switcher.dataset.target || 'login');
                });
            });

            if (closeEnrolBtn) closeEnrolBtn.addEventListener('click', closeEnrolModal);
            if (enrolModal) {
                enrolModal.addEventListener('click', (event) => {
                    if (event.target === enrolModal) {
                        closeEnrolModal();
                    }
                });
            }

            const shouldOpenEnroll = @json(session('openEnrollModal', false));
            const initialEnrollTab = @json(session('enrollTab', 'login'));
            if (shouldOpenEnroll) {
                openEnrolModal('', initialEnrollTab);
            } else {
                showEnrolForm('login');
            }
        })();
    </script>
</body>
</html>

