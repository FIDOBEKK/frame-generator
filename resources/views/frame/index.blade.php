<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Frame Generator') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
<div class="mx-auto max-w-6xl px-4 py-10">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold">Frame Generator</h1>
            <p class="mt-1 text-sm text-slate-600">Upload your photo, position it, and download a LinkedIn-ready JPG.</p>
        </div>
        <a href="{{ route('login') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Admin login
        </a>
    </div>

    @if (! $hasBackground)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900">
            No background image is configured yet. Please contact the administrator.
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-red-900">
            <ul class="list-inside list-disc space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('frame.generate') }}" enctype="multipart/form-data" class="grid gap-8 lg:grid-cols-5" x-data="frameComposer()">
        @csrf

        <div class="space-y-4 lg:col-span-2">
            <div>
                <label for="photo" class="mb-1 block text-sm font-medium">Your photo</label>
                <input
                    id="photo"
                    name="photo"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    required
                    @change="loadPhoto($event)"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                >
                <p class="mt-1 text-xs text-slate-500">Accepted: JPG, PNG, WEBP. Max 5 MB.</p>
            </div>

            <div>
                <label for="scale" class="mb-1 block text-sm font-medium">Scale</label>
                <input id="scale" type="range" min="0.1" max="4" step="0.01" x-model="scale" class="w-full">
                <p class="text-xs text-slate-500">Current: <span x-text="Number(scale).toFixed(2)"></span>x</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="x" class="mb-1 block text-sm font-medium">X Position</label>
                    <input id="x" type="number" x-model="x" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="y" class="mb-1 block text-sm font-medium">Y Position</label>
                    <input id="y" type="number" x-model="y" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                </div>
            </div>

            <input type="hidden" name="scale" :value="scale">
            <input type="hidden" name="x" :value="Math.round(x)">
            <input type="hidden" name="y" :value="Math.round(y)">

            <button
                type="submit"
                x-bind:disabled="!hasBackground"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400"
            >
                Generate JPG
            </button>
        </div>

        <div class="lg:col-span-3">
            <div class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                <p class="mb-3 text-sm font-medium">Preview</p>
                <div class="relative mx-auto w-full max-w-3xl overflow-hidden rounded-lg border bg-slate-100"
                     @mousedown="startDrag($event)"
                     @mousemove="onDrag($event)"
                     @mouseup="stopDrag()"
                     @mouseleave="stopDrag()"
                >
                    <img
                        src="{{ route('background.preview') }}"
                        alt="Background"
                        class="block w-full"
                        @load="setBackgroundSize($event)"
                    >

                    <img
                        x-show="photoUrl"
                        :src="photoUrl"
                        alt="Your preview"
                        class="pointer-events-none absolute top-0 left-0"
                        :style="`transform: translate(${x}px, ${y}px) scale(${scale}); transform-origin: top left; max-width: none;`"
                    >
                </div>
                <p class="mt-2 text-xs text-slate-500">Tip: Drag your image in the preview, then fine-tune with X/Y/Scale.</p>
            </div>
        </div>
    </form>
</div>

<script>
    function frameComposer() {
        return {
            hasBackground: @json($hasBackground),
            photoUrl: null,
            scale: 1,
            x: 0,
            y: 0,
            dragging: false,
            dragStartX: 0,
            dragStartY: 0,
            originX: 0,
            originY: 0,
            loadPhoto(event) {
                const file = event.target.files[0];

                if (!file) {
                    this.photoUrl = null;
                    return;
                }

                if (this.photoUrl) {
                    URL.revokeObjectURL(this.photoUrl);
                }

                this.photoUrl = URL.createObjectURL(file);
                this.scale = 1;
                this.x = 0;
                this.y = 0;
            },
            startDrag(event) {
                if (!this.photoUrl) {
                    return;
                }

                this.dragging = true;
                this.dragStartX = event.clientX;
                this.dragStartY = event.clientY;
                this.originX = Number(this.x);
                this.originY = Number(this.y);
            },
            onDrag(event) {
                if (!this.dragging) {
                    return;
                }

                this.x = this.originX + (event.clientX - this.dragStartX);
                this.y = this.originY + (event.clientY - this.dragStartY);
            },
            stopDrag() {
                this.dragging = false;
            },
            setBackgroundSize() {
                // Reserved for future ratio helpers.
            },
        };
    }
</script>
</body>
</html>
