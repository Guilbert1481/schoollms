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

    // Pastel tile palette (inline hex — purge-safe): [tile background, icon color].
    $tilePalette = [
        ['#fdf3d8', '#a16207'], // amber
        ['#fbdde2', '#be123c'], // rose
        ['#d3f4e0', '#059669'], // green
        ['#d5f0f2', '#0f766e'], // teal
        ['#fce7d8', '#9a3412'], // orange
        ['#e3f9d3', '#4d7c0f'], // lime
        ['#f7d9f7', '#a21caf'], // fuchsia
        ['#dbe7fb', '#1d4ed8'], // blue
        ['#d7ecfb', '#0369a1'], // sky
        ['#e5ddfb', '#6d28d9'], // violet
    ];

    // Tile view-model handed to the detail modal via Alpine (@js).
    $toolTiles = collect($toolCards)->values()->map(function ($t, $i) use ($tilePalette) {
        [$bg, $fg] = $tilePalette[$i % count($tilePalette)];

        return [
            'key'         => $t['key'],
            'title'       => $t['title'],
            'description' => $t['description'],
            'icon'        => $t['icon'],
            'status'      => $t['status'],
            'enabled'     => $t['enabled'],
            'url'         => isset($t['route']) ? route($t['route']) : null,
            'bg'          => $bg,
            'fg'          => $fg,
        ];
    })->all();
@endphp

<div x-data="{ pdfToImageModal: {{ $errors->has('pdf_file') ? 'true' : 'false' }}, detail: null }" class="p-4 md:p-6">
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

    {{-- Icon grid: tiles + labels only (6 per row on desktop); details live in the click-to-open modal --}}
    <div class="grid grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-x-4 gap-y-8">
        @foreach($toolTiles as $tool)
            <button type="button"
                    x-on:click='detail = @js($tool); $nextTick(() => window.lucide && lucide.createIcons())'
                    class="group flex flex-col items-center focus:outline-none">
                <span class="flex h-20 w-20 items-center justify-center rounded-[1.5rem] shadow-sm transition duration-150 group-hover:scale-105 group-hover:shadow-md"
                      style="background-color: {{ $tool['bg'] }};">
                    <i data-lucide="{{ $tool['icon'] }}" class="h-8 w-8" style="color: {{ $tool['fg'] }};"></i>
                </span>
                <span class="mt-3 text-sm font-semibold text-slate-700 text-center leading-tight">
                    {{ $tool['title'] }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- Tool detail modal (description + status + actions) --}}
    <div x-show="detail !== null" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
         x-on:click.self="detail = null"
         x-on:keydown.escape.window="detail = null">
        <template x-if="detail">
            <div class="w-full max-w-sm rounded-2xl bg-white shadow-xl border border-slate-200 p-6">
                <div class="flex items-start justify-between">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl"
                          :style="`background-color: ${detail.bg}`">
                        <i :data-lucide="detail.icon" class="h-7 w-7" :style="`color: ${detail.fg}`"></i>
                    </span>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" x-on:click="detail = null">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <h3 class="text-lg font-bold text-slate-800" x-text="detail.title"></h3>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                          :class="detail.enabled
                              ? 'border border-emerald-200 bg-emerald-100 text-emerald-700'
                              : 'border border-amber-200 bg-amber-100 text-amber-700'"
                          x-text="detail.status"></span>
                </div>

                <p class="mt-2 text-sm text-slate-600 leading-relaxed" x-text="detail.description"></p>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            x-on:click="detail = null">
                        Close
                    </button>

                    {{-- Live tool with its own page --}}
                    <template x-if="detail.enabled && detail.url">
                        <a :href="detail.url"
                           class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                            Open Tool
                        </a>
                    </template>

                    {{-- Live tool that runs in a modal (PDF to Image) --}}
                    <template x-if="detail.enabled && !detail.url">
                        <button type="button"
                                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100"
                                x-on:click="detail = null; pdfToImageModal = true">
                            Open Tool
                        </button>
                    </template>

                    {{-- Planned --}}
                    <template x-if="!detail.enabled">
                        <button type="button" disabled
                                class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-500 cursor-not-allowed opacity-80">
                            Coming Soon
                        </button>
                    </template>
                </div>
            </div>
        </template>
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
