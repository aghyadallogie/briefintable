// Alpine powers the navbar menus (open/close state and their enter/leave transitions).
//
// Pages built on the Blade layout load Livewire, which ships and starts its own copy of Alpine
// and sets window.Alpine before this deferred module runs. Starting a second copy would break
// both, so we only load and start Alpine ourselves on pages without Livewire (the Antlers pages).
// The dynamic import keeps Alpine out of the bundle that Livewire pages download.
if (!window.Alpine) {
    import('alpinejs').then(({ default: Alpine }) => {
        window.Alpine = Alpine;
        Alpine.start();
    });
}
