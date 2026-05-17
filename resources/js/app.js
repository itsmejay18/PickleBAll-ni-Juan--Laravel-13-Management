import './bootstrap';
import '../vendor/soft-ui/assets/js/core/bootstrap.bundle.min.js';
import '../vendor/soft-ui/assets/js/plugins/perfect-scrollbar.min.js';
import '../vendor/soft-ui/assets/js/plugins/smooth-scrollbar.min.js';
import '../vendor/soft-ui/assets/js/plugins/chartjs.min.js';
import '../vendor/soft-ui/assets/js/soft-ui-dashboard.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const sidenav = document.getElementById('sidenav-main');
    const toggles = [
        document.getElementById('iconNavbarSidenav'),
        document.getElementById('iconSidenav'),
    ].filter(Boolean);

    const toggleSidenav = (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();

        const isPinned = body.classList.toggle('g-sidenav-pinned');

        if (sidenav) {
            sidenav.classList.toggle('bg-white', isPinned);
            sidenav.classList.remove('bg-transparent');
        }
    };

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', toggleSidenav, true);
    });
});
