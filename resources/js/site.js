// Navbar menus are native <details data-menu> elements, so they work without JavaScript.
// This only adds the usual dropdown behaviour: close on an outside click or Escape,
// and keep one menu open at a time.
const menus = () => document.querySelectorAll('details[data-menu]');

document.addEventListener('click', (event) => {
    menus().forEach((menu) => {
        if (menu.open && !menu.contains(event.target)) {
            menu.open = false;
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    menus().forEach((menu) => {
        if (menu.open) {
            menu.open = false;
            menu.querySelector('summary')?.focus();
        }
    });
});

document.addEventListener(
    'toggle',
    (event) => {
        if (event.target.matches?.('details[data-menu]') && event.target.open) {
            menus().forEach((menu) => {
                if (menu !== event.target) {
                    menu.open = false;
                }
            });
        }
    },
    true,
);
