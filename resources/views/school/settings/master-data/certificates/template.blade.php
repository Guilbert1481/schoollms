@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold">Certificate Preview</h2>
            <p class="text-sm text-gray-600">
                {{ $template->name ?? 'Certificate Template' }}
            </p>
        </div>

        <a href="{{ route('school.settings.master-data.certificates.builder.withId', $template->id) }}"
           class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">
            Back To Builder
        </a>
    </div>

    <div class="rounded bg-gray-100 p-6 shadow">
        <div class="preview-shell">
            <div id="certificate-preview-canvas" class="preview-canvas"></div>
        </div>

        <p id="certificate-preview-empty" class="hidden text-center text-sm text-gray-500">
            No saved certificate layout yet.
        </p>
    </div>
</div>

<style>
.preview-shell {
    display: flex;
    justify-content: center;
    overflow-x: auto;
}

.preview-canvas {
    position: relative;
    overflow: hidden;
    margin: 0 auto;
    background: #ffffff;
    border: 1px solid #d1d5db;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
}

.preview-canvas .canvas-item {
    position: absolute;
    display: inline-block;
}

.preview-canvas .text-element,
.preview-canvas img,
.preview-canvas .shape {
    pointer-events: none;
}
</style>

<script>
    window.savedLayout = @json($template->layout_json ?? []);
    window.savedCertificateSettings = {
        orientation: @json($template->orientation ?? 'landscape'),
        paperSize: @json($template->paper_size ?? 'a4')
    };

    (function () {
        const PAPER_SIZES = {
            a4: { width: 297, height: 210 },
            a5: { width: 210, height: 148 },
            letter: { width: 279.4, height: 215.9 },
            legal: { width: 355.6, height: 215.9 }
        };
        const LONG_EDGE = 900;

        function normalizeOrientation(value) {
            return value === 'portrait' ? 'portrait' : 'landscape';
        }

        function normalizePaperSize(value) {
            return PAPER_SIZES[value] ? value : 'a4';
        }

        function getCanvasSize(paperSize, orientation) {
            const page = PAPER_SIZES[normalizePaperSize(paperSize)];
            const normalizedOrientation = normalizeOrientation(orientation);
            const longEdge = Math.max(page.width, page.height);
            const shortEdge = Math.min(page.width, page.height);
            const scale = LONG_EDGE / longEdge;

            if (normalizedOrientation === 'portrait') {
                return {
                    width: Math.round(shortEdge * scale),
                    height: Math.round(longEdge * scale)
                };
            }

            return {
                width: Math.round(longEdge * scale),
                height: Math.round(shortEdge * scale)
            };
        }

        function createContentElement(content) {
            if (!content || !content.tagName) return null;

            const element = document.createElement(content.tagName);

            if (content.className) {
                element.className = content.className;
            }

            if (content.style) {
                element.setAttribute('style', content.style);
            }

            if (content.tagName === 'img') {
                element.src = content.src || '';
            } else {
                element.innerHTML = content.html || '';
            }

            element.removeAttribute('contenteditable');
            return element;
        }

        function createPreviewItem(item) {
            if (!item || !item.content) return null;

            const contentEl = createContentElement(item.content);
            if (!contentEl) return null;

            const wrapper = document.createElement('div');
            wrapper.className = 'canvas-item';
            wrapper.style.position = 'absolute';

            if (item.wrapperStyle) {
                wrapper.setAttribute('style', item.wrapperStyle);
            }

            wrapper.appendChild(contentEl);
            return wrapper;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('certificate-preview-canvas');
            const emptyState = document.getElementById('certificate-preview-empty');
            if (!canvas) return;

            const settings = window.savedCertificateSettings || {};
            const size = getCanvasSize(settings.paperSize, settings.orientation);
            canvas.style.width = size.width + 'px';
            canvas.style.height = size.height + 'px';

            const layout = Array.isArray(window.savedLayout) ? window.savedLayout : [];

            if (!layout.length) {
                if (emptyState) {
                    emptyState.classList.remove('hidden');
                }
                return;
            }

            layout.forEach(function (item) {
                const previewItem = createPreviewItem(item);
                if (previewItem) {
                    canvas.appendChild(previewItem);
                }
            });
        });
    })();
</script>
@endsection
