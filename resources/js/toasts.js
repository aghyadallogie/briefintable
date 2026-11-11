// The Alpine component behind <x-toast /> (resources/views/components/toast.blade.php).
//
// Toasts are short messages that show in a corner and go away by themselves. They come from two places:
// - messages flashed into the session before a redirect, which the Blade view passes in as `flashed`, and
// - a browser event named `toast`, for actions that stay on the page. A Livewire component sends one
//   with $this->dispatch('toast', type: 'success', message: '...').
//
// Each toast starts closed and opens itself once its element exists (x-init in the view), so the enter
// transition runs. Closing runs the leave transition first and removes the toast afterwards.

const SHOW_FOR = 4000; // ms a toast stays before it dismisses itself
const LEAVE_FOR = 200; // ms, the same as the leave transition's duration in the view

export default function toasts(flashed = []) {
    return {
        items: [],
        next: 0,

        init() {
            flashed.forEach((toast) => this.add(toast));
        },

        add(toast) {
            const item = { id: this.next++, type: toast.type ?? 'success', message: toast.message, open: false };

            this.items.push(item);
            setTimeout(() => this.dismiss(item.id), SHOW_FOR);
        },

        dismiss(id) {
            const item = this.items.find((i) => i.id === id);

            if (!item) return;

            item.open = false;
            setTimeout(() => (this.items = this.items.filter((i) => i.id !== id)), LEAVE_FOR);
        },
    };
}
