@php
    $flashed = [];

    foreach (['success', 'error'] as $type) {
        if (session()->has($type)) {
            $flashed[] = ['type' => $type, 'message' => (string) session($type)];
        }
    }
@endphp

{{-- The component's logic is in resources/js/toasts.js. --}}
<div class="toast-region" aria-live="polite" x-data="toasts(@js($flashed))" x-on:toast.window="add($event.detail)">
    <template x-for="toast in items" :key="toast.id">
        <div x-show="toast.open" x-init="$nextTick(() => toast.open = true)"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="toast alert"
            :class="toast.type === 'error' ? 'alert-danger' : 'alert-success'"
            :role="toast.type === 'error' ? 'alert' : 'status'">
            <p class="flex-1" x-text="toast.message"></p>
            <button type="button" class="icon-btn -my-2 -mr-2 shrink-0" aria-label="Dismiss" x-on:click="dismiss(toast.id)">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>

    @foreach ($flashed as $message)
    <noscript>
        <div class="toast alert {{ $message['type'] === 'error' ? 'alert-danger' : 'alert-success' }}" role="{{ $message['type'] === 'error' ? 'alert' : 'status' }}">
            {{ $message['message'] }}
        </div>
    </noscript>
    @endforeach
</div>
