<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Admin: Background image
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-900">
                    <ul class="list-inside list-disc space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="space-y-5 p-6 text-gray-900">
                    <p class="text-sm text-slate-600">Upload one background image. This single image is used for all generated frames.</p>

                    @if ($hasBackground)
                        <div class="rounded-lg border border-slate-300 p-3">
                            <p class="mb-2 text-sm font-medium">Current background</p>
                            <img src="{{ route('background.preview') }}" alt="Current background" class="max-h-96 rounded border">
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.background.update') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label for="background" class="mb-1 block text-sm font-medium">Background image</label>
                            <input
                                id="background"
                                type="file"
                                name="background"
                                accept="image/png,image/jpeg,image/webp"
                                required
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                            >
                            <p class="mt-1 text-xs text-slate-500">Accepted: JPG, PNG, WEBP. Max 10 MB.</p>
                        </div>

                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                            Save background
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
