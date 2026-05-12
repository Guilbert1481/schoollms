@extends('layouts.app')

@section('content')
@php
    $toolCards = [
        [
            'key' => 'image_to_pdf',
            'title' => 'Image to PDF',
            'description' => 'Combine one or more images into a single PDF file for sharing and printing.',
            'icon' => 'image-plus',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'pdf_to_image',
            'title' => 'PDF to Image',
            'description' => 'Convert PDF pages into image files for previews, social posting, or design edits.',
            'icon' => 'file-image',
            'status' => 'Live',
            'enabled' => true,
        ],
        [
            'key' => 'image_resize',
            'title' => 'Image Resize',
            'description' => 'Resize photos and graphics in bulk with width and height presets.',
            'icon' => 'maximize-2',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'image_compressor',
            'title' => 'Image Compressor',
            'description' => 'Optimize image size while preserving visual quality.',
            'icon' => 'aperture',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'video_to_mp3',
            'title' => 'Video to MP3',
            'description' => 'Extract audio from video files and save as MP3.',
            'icon' => 'music-2',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'video_compressor',
            'title' => 'Video Compressor',
            'description' => 'Reduce video file size for faster uploads and smoother playback.',
            'icon' => 'video',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'pdf_to_word',
            'title' => 'PDF to Word',
            'description' => 'Convert PDF files into editable Word documents.',
            'icon' => 'file-text',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'edit_pdf',
            'title' => 'Edit PDF',
            'description' => 'Edit text style, add or delete images, add transparent signature, and highlight text.',
            'icon' => 'file-pen-line',
            'status' => 'Live',
            'enabled' => true,
            'route' => 'tools.edit-pdf',
        ],
        [
            'key' => 'certificate_creator',
            'title' => 'Certificate Creator',
            'description' => 'Design and manage certificate templates, then prepare event-based awards and recipients.',
            'icon' => 'award',
            'status' => 'Live',
            'enabled' => true,
            'route' => 'school.settings.master-data.certificates.builder',
        ],
        [
            'key' => 'text_to_speech',
            'title' => 'Text to Speech',
            'description' => 'Generate spoken audio from written text.',
            'icon' => 'volume-2',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'audio_recorder',
            'title' => 'Audio Recorder',
            'description' => 'Record voice notes and save them as audio files.',
            'icon' => 'mic',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'video_recorder',
            'title' => 'Video Recorder',
            'description' => 'Capture video directly from your device camera.',
            'icon' => 'video',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'camera',
            'title' => 'Camera',
            'description' => 'Take photos directly from the browser camera feed.',
            'icon' => 'camera',
            'status' => 'Live',
            'enabled' => true,
            'route' => 'tools.camera',
        ],
        [
            'key' => 'pdf_merger',
            'title' => 'PDF Merger',
            'description' => 'Merge multiple PDF files into one organized document.',
            'icon' => 'files',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'file_converter',
            'title' => 'File Converter',
            'description' => 'Convert supported files between common office and media formats.',
            'icon' => 'refresh-cw',
            'status' => 'Planned',
            'enabled' => false,
        ],
        [
            'key' => 'scientific_calculator',
            'title' => 'Scientific Calculator',
            'description' => 'Perform advanced calculations with scientific functions for math and engineering needs.',
            'icon' => 'calculator',
            'status' => 'Live',
            'enabled' => true,
            'route' => 'tools.scientific-calculator',
        ],
        [
            'key' => 'budget_tool',
            'title' => 'Budget Tool',
            'description' => 'Track income, expenses, monthly budget limits, and remaining balance in one dashboard.',
            'icon' => 'wallet',
            'status' => 'Live',
            'enabled' => true,
            'route' => 'tools.budget',
        ],
        [
            'key' => 'video_conference',
            'title' => 'Video Conference',
            'description' => 'Phase 1 virtual classroom scaffold with rooms, chat, whiteboard, notes, recording, and buzz-in tools.',
            'icon' => 'video',
            'status' => 'Live',
            'enabled' => true,
            'route' => 'tools.video-conference.index',
        ],
        [
            'key' => 'sophentis_drive',
            'title' => 'Sophentis Drive',
            'description' => 'Upload, organize, and share documents with view or edit permissions for other users.',
            'icon' => 'hard-drive',
            'status' => 'Live',
            'enabled' => true,
            'route' => 'tools.drive.index',
        ],
        [
            'key' => 'scheduler',
            'title' => 'Scheduler',
            'description' => 'Generate, optimize, and apply academic timetables across sections, teachers, and rooms.',
            'icon' => 'calendar-clock',
            'status' => 'Live',
            'enabled' => true,
            'route' => 'scheduler.index',
        ],
        [
            'key' => 'gamified_quiz',
            'title' => 'Gamified Quiz',
            'description' => 'Turn quizzes into interactive games — Millionaire, Tower Defense, Memory Match, Hangman, and more.',
            'icon' => 'gamepad-2',
            'status' => 'Live',
            'enabled' => true,
            'route' => 'tools.games.index',
        ],
    ];

    $pdfConverterAvailable = $pdfConverterAvailable ?? false;
    $pdfConverterEngine = $pdfConverterEngine ?? null;

    $toolVisuals = [
        'image_to_pdf' => 'from-rose-500 via-orange-500 to-amber-400',
        'pdf_to_image' => 'from-sky-500 via-blue-600 to-indigo-700',
        'image_resize' => 'from-fuchsia-500 via-pink-500 to-rose-500',
        'image_compressor' => 'from-cyan-500 via-teal-500 to-emerald-500',
        'video_to_mp3' => 'from-violet-500 via-purple-600 to-indigo-700',
        'video_compressor' => 'from-amber-500 via-orange-500 to-red-500',
        'pdf_to_word' => 'from-blue-500 via-sky-600 to-cyan-700',
        'edit_pdf' => 'from-slate-700 via-blue-700 to-indigo-800',
        'certificate_creator' => 'from-emerald-500 via-teal-600 to-cyan-700',
        'text_to_speech' => 'from-emerald-500 via-teal-600 to-cyan-700',
        'audio_recorder' => 'from-pink-500 via-rose-500 to-red-600',
        'video_recorder' => 'from-slate-600 via-slate-700 to-zinc-800',
        'camera' => 'from-lime-500 via-emerald-600 to-teal-700',
        'pdf_merger' => 'from-indigo-500 via-blue-600 to-sky-700',
        'file_converter' => 'from-purple-500 via-fuchsia-600 to-pink-700',
        'scientific_calculator' => 'from-cyan-500 via-blue-600 to-indigo-700',
        'budget_tool' => 'from-emerald-500 via-green-600 to-teal-700',
        'video_conference' => 'from-rose-500 via-red-600 to-orange-600',
        'sophentis_drive' => 'from-indigo-500 via-violet-600 to-purple-700',
        'scheduler' => 'from-amber-500 via-orange-600 to-red-600',
        'gamified_quiz' => 'from-fuchsia-500 via-purple-600 to-indigo-700',
    ];
@endphp

<div x-data="{ pdfToImageModal: {{ $errors->has('pdf_file') ? 'true' : 'false' }} }" class="p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-slate-800">Tools Hub</h1>
        <p class="text-sm md:text-base text-slate-600 mt-1">
            Quick utilities for documents, images, media conversion, games and other tools.
        </p>
    </div>

    @if($errors->has('pdf_file'))
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first('pdf_file') }}
        </div>
    @endif

    @if(!$pdfConverterAvailable)
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            PDF to Image is not ready on this server yet. Install one of these engines: <strong>Imagick</strong>, <strong>Poppler (pdftoppm)</strong>, or <strong>ImageMagick (magick)</strong>.
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-5">
        @foreach($toolCards as $tool)
            <article
                @if($tool['key'] === 'pdf_to_image' && $tool['enabled'])
                    x-on:click="pdfToImageModal = true"
                @endif
                class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm hover:shadow-md transition-shadow {{ $tool['key'] === 'pdf_to_image' && $tool['enabled'] ? 'cursor-pointer ring-1 ring-emerald-200' : '' }}">
                <div class="relative mb-3 overflow-hidden rounded-xl bg-gradient-to-br {{ $toolVisuals[$tool['key']] ?? 'from-slate-500 to-slate-700' }} p-3 h-28">
                    <div class="absolute -top-6 -right-6 h-24 w-24 rounded-full bg-white/20 blur-xl"></div>
                    <div class="absolute -bottom-8 -left-6 h-24 w-24 rounded-full bg-black/20 blur-xl"></div>

                    <div class="relative flex h-full items-end justify-between">
                        <div class="inline-flex items-center gap-2 rounded-lg border border-white/30 bg-white/20 px-2 py-1 text-white backdrop-blur-sm">
                            <i data-lucide="{{ $tool['icon'] }}" class="w-4 h-4"></i>
                            <span class="text-xs font-medium">{{ $tool['title'] }}</span>
                        </div>

                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $tool['enabled'] ? 'border border-emerald-200/70 bg-emerald-100 text-emerald-700' : 'border border-amber-200/70 bg-amber-100 text-amber-700' }}">
                            {{ $tool['status'] }}
                        </span>
                    </div>
                </div>

                <div class="px-1 pb-1">
                    <h2 class="text-base font-semibold text-slate-800">{{ $tool['title'] }}</h2>
                    <p class="mt-2 text-sm text-slate-600 min-h-[60px]">{{ $tool['description'] }}</p>

                    @if($tool['enabled'])
                        @if(isset($tool['route']))
                            <a href="{{ route($tool['route']) }}" class="mt-4 block w-full rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-sm font-medium text-emerald-700">
                                Open Tool
                            </a>
                        @else
                            <button type="button" x-on:click.stop="pdfToImageModal = true" class="mt-4 w-full rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                                Open Tool
                            </button>
                        @endif
                    @else
                        <button type="button"
                            class="mt-4 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 cursor-not-allowed opacity-80"
                            disabled>
                            Coming Soon
                        </button>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    <div x-show="pdfToImageModal" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" x-on:click.self="pdfToImageModal = false" x-on:keydown.escape.window="pdfToImageModal = false">
        <div class="w-full max-w-xl rounded-2xl bg-white shadow-xl border border-slate-200">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">PDF to Image</h3>
                    <p class="text-sm text-slate-600">Convert each PDF page to JPG or PNG, then download immediately. Engine: {{ $pdfConverterEngine ? strtoupper($pdfConverterEngine) : 'Not Installed' }}</p>
                </div>
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" x-on:click="pdfToImageModal = false">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            @if(!$pdfConverterAvailable)
                <div class="mx-5 mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Converter engine not found. Install Imagick, Poppler, or ImageMagick to enable this tool.
                </div>
            @else
                <div class="mx-5 mt-5 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                    Converted files are generated temporarily and downloaded directly. They are automatically removed from the server after the request.
                </div>
            @endif

            <form action="{{ route('tools.pdf-to-image') }}" method="POST" enctype="multipart/form-data" class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf

                <div class="md:col-span-2">
                    <label for="pdf_file_modal" class="block text-sm font-medium text-slate-700 mb-1">PDF File</label>
                    <input id="pdf_file_modal" name="pdf_file" type="file" accept="application/pdf" required
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700">
                </div>

                <div>
                    <label for="format_modal" class="block text-sm font-medium text-slate-700 mb-1">Output</label>
                    <select id="format_modal" name="format" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                        <option value="jpg" {{ old('format', 'jpg') === 'jpg' ? 'selected' : '' }}>JPG</option>
                        <option value="png" {{ old('format') === 'png' ? 'selected' : '' }}>PNG</option>
                    </select>
                </div>

                <div>
                    <label for="dpi_modal" class="block text-sm font-medium text-slate-700 mb-1">DPI</label>
                    <input id="dpi_modal" name="dpi" type="number" min="72" max="300" value="{{ old('dpi', 150) }}"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                </div>

                <div class="md:col-span-2 flex items-center justify-end gap-2 pt-2">
                    <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" x-on:click="pdfToImageModal = false">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed" {{ $pdfConverterAvailable ? '' : 'disabled' }}>
                        Convert and Download
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
