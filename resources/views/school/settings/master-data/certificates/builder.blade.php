@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-4">

    {{-- =========================
        ROW 1: TITLE
    ========================== --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold">
            Certificate Builder{{ !empty($template?->name) ? ' - '.$template->name : '' }}
        </h2>
        <a href="{{ route('school.settings.master-data.certificates.index') }}"
           class="inline-flex items-center gap-2 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 px-3 py-1 rounded text-sm">
            <span aria-hidden="true">&larr;</span>
            <span>Back to Certidicate</span>
        </a>
    </div>


    {{-- =========================
        ROW 2: TOOLBAR
    ========================== --}}
    <div id="certificate-toolbar" class="toolbar bg-white p-3 rounded shadow flex flex-nowrap items-center gap-3 overflow-x-auto overflow-y-visible">

        <select id="certificate-orientation" onchange="setCertificateOrientation(this.value)" class="border p-1 text-sm">
            <option value="landscape">Landscape</option>
            <option value="portrait">Portrait</option>
        </select>

        <select id="certificate-paper-size" onchange="setCertificatePaperSize(this.value)" class="border p-1 text-sm">
            <option value="a4">Paper Size: A4</option>
            <option value="a5">Paper Size: A5</option>
            <option value="letter">Paper Size: Letter</option>
            <option value="legal">Paper Size: Legal</option>
        </select>

        {{-- ADD TEXT --}}
        <button onclick="addText()" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
            + Text
        </button>

        <button onclick="addNameText()" class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1 rounded text-sm">
            + Name Text
        </button>

        {{-- TYPOGRAPHY --}}
        <div class="flex items-center gap-2 border-l pl-3">

            {{-- FONT FAMILY --}}
            <select id="certificate-font-family" onchange="setFontFamily(this.value)" class="border p-1 text-sm">
                <option value="">Font</option>
                <option value="Arial">Arial</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Georgia">Georgia</option>
                <option value="Courier New">Courier New</option>
                <option value="Poppins">Poppins</option>
                <option value="Great Vibes">Great Vibes (Calligraphy)</option>
                <option value="Dancing Script">Dancing Script</option>
                <option value="Allura">Allura (Script)</option>
                <option value="Monotype Corsiva">Monotype Corsiva (Script)</option>
            </select>

            {{-- FONT SIZE --}}
            <input type="number"
                id="certificate-font-size"
                min="8"
                max="200"
                value="24"
                oninput="setFontSize(this.value)"
                class="w-16 border p-1 text-sm">

            {{-- TEXT STYLE --}}
            <select
                onchange="if(this.value==='bold'){toggleBold();}else if(this.value==='italic'){toggleItalic();}else if(this.value==='underline'){toggleUnderline();}this.selectedIndex=0;"
                class="border p-1 text-sm">
                <option value="">Style</option>
                <option value="bold">Bold</option>
                <option value="italic">Italic</option>
                <option value="underline">Underline</option>
            </select>

            {{-- COLOR --}}
            <input type="color" onchange="setTextColor(this.value)">

            {{-- ALIGN --}}
            <select onchange="if(this.value){setAlign(this.value);this.selectedIndex=0;}" class="border p-1 text-sm">
                <option value="">Align</option>
                <option value="left">L</option>
                <option value="center">C</option>
                <option value="right">R</option>
            </select>

        </div>

        {{-- SHAPES --}}
        <div class="border-l pl-3">
            <select onchange="handleShape(this)" class="border p-1 text-sm">
                <option value="">Shapes</option>
                <option value="lineH">Line —</option>
                <option value="lineV">Line |</option>
                <option value="lineD">Line /</option>
                <option value="square">Square</option>
                <option value="box">Box</option>
                <option value="circle">Circle</option>
                <option value="star">Star</option>
            </select>
        </div>

        {{-- SHAPE COLORS --}}
        <div class="flex items-center gap-2">
            <select id="shape-color-mode" class="border p-1 text-sm">
                <option value="fill">Fill Color</option>
                <option value="edge">Edge Color</option>
            </select>

            <input
                id="shape-color-picker"
                type="color"
                value="#000000"
                onchange="setSelectedShapeColor(this.value)">
        </div>

        {{-- ADD IMAGE --}}
        <div>
            <select id="image-upload-select"
                    onchange="handleImageUploadOption(this)"
                    class="border p-1 text-sm">
                <option value="">+ Image</option>
                <option value="background">Background</option>
                <option value="logo">Logo</option>
            </select>
        </div>

        {{-- DELETE --}}
        <button onclick="deleteSelected()" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
            Delete
        </button>
        
        <button id="save-layout-button"
                onclick="saveLayout()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm">
            Save
        </button>

        <button onclick="previewLayout()"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 border border-gray-300 px-3 py-1 rounded text-sm">
            Preview
        </button>

    </div>


    {{-- =========================
        ROW 3: CANVAS
    ========================== --}}
    <div class="bg-gray-100 p-6 rounded shadow flex justify-center">

        <div id="canvas"
             class="bg-white border relative shadow overflow-hidden"
             style="width: 900px; height: 600px;">

            {{-- Elements render here --}}

        </div>

    </div>


    {{-- =========================
        SAVE BUTTON
    ========================== --}}
    


    {{-- =========================
        HIDDEN INPUTS
    ========================== --}}
    <input type="file" id="imageUploader" accept="image/*" class="hidden">
    <input type="hidden" id="template_id" value="{{ $template->id ?? request('id') }}">
    <p id="save-status" class="text-sm text-gray-600"></p>

</div>


{{-- =========================
    STYLES
========================= --}}
<style>
@import url('https://fonts.googleapis.com/css2?family=Allura&family=Dancing+Script:wght@400;700&family=Great+Vibes&display=swap');

#canvas {
    position: relative;
    overflow: hidden;
    margin: 0 auto;
}

#canvas.background-selected {
    outline: 2px dashed #3b82f6;
    outline-offset: 1px;
}

.canvas-item.selected {
    outline: 2px dashed #3b82f6;
}

.resize-handle {
    position: absolute;
    width: 10px;
    height: 10px;
    right: -5px;
    bottom: -5px;
    border-radius: 2px;
    border: 1px solid #1d4ed8;
    background: #ffffff;
    cursor: nwse-resize;
    display: none;
    z-index: 20;
}

.canvas-item.selected .resize-handle {
    display: block;
}

#certificate-toolbar > * {
    flex: 0 0 auto;
}
</style>


{{-- =========================
    SCRIPTS
========================= --}}
<script src="{{ asset('js/certificate/core.js') }}"></script>
<script src="{{ asset('js/certificate/save.js') }}"></script>
<script src="{{ asset('js/certificate/print.js') }}"></script>
<script src="{{ asset('js/certificate/preview.js') }}"></script>
<script src="{{ asset('js/certificate/text.js') }}"></script>
<script src="{{ asset('js/certificate/logo.js') }}"></script>
<script src="{{ asset('js/certificate/shapes.js') }}"></script>
<script src="{{ asset('js/certificate/load.js') }}"></script>

<script>
    window.savedLayout = @json($template->layout_json ?? []);
    window.savedCertificateSettings = {
        orientation: @json($template->orientation ?? 'landscape'),
        paperSize: @json($template->paper_size ?? 'a4')
    };
    window.savedCertificateAssets = {
        backgroundImage: @json($template->background_image ?? null),
        logo: @json($template->logo ?? null)
    };

    // SHAPE HANDLER
    function handleShape(select) {
        const value = select.value;

        switch(value) {
            case 'lineH': addLineHorizontal(); break;
            case 'lineV': addLineVertical(); break;
            case 'lineD': addLineDiagonal(); break;
            case 'square': addSquare(); break;
            case 'box': addBox(); break;
            case 'circle': addCircle(); break;
            case 'star': addStar(); break;
        }

        select.selectedIndex = 0;
    }

    // DELETE SELECTED ELEMENT
    function deleteSelected() {
        if (typeof window.isBackgroundImageSelected === 'function' &&
            window.isBackgroundImageSelected() &&
            typeof window.clearBackgroundImage === 'function') {
            window.clearBackgroundImage();
            return;
        }

        if (!window.selectedElement) return;

        window.selectedElement.remove();
        window.selectedElement = null;

        autoSaveToLocal();
    }
</script>

@endsection
