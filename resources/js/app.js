import './bootstrap';
// Must run BEFORE soft-ui-dashboard.js — exposes bootstrap/PerfectScrollbar/Scrollbar/Chart on window.
import './soft-ui-globals';
import '../vendor/soft-ui/assets/js/soft-ui-dashboard.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
