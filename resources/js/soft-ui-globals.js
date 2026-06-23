// Soft UI Dashboard's theme JS (and the dashboard's inline chart scripts) expect
// these libraries as GLOBALS — e.g. `new PerfectScrollbar(...)`, `new bootstrap.Tooltip(...)`,
// `new Chart(...)`. Their UMD builds, when imported by Vite, only set module.exports
// and never touch `window`, so the theme JS threw "X is not defined" and aborted —
// taking the sidenav toggle, tooltips and scrollbars down with it. Pin them to
// `window` here, BEFORE soft-ui-dashboard.js runs.
import * as bootstrap from '../vendor/soft-ui/assets/js/core/bootstrap.bundle.min.js';
import PerfectScrollbar from '../vendor/soft-ui/assets/js/plugins/perfect-scrollbar.min.js';
import Scrollbar from '../vendor/soft-ui/assets/js/plugins/smooth-scrollbar.min.js';
import Chart from '../vendor/soft-ui/assets/js/plugins/chartjs.min.js';

window.bootstrap = bootstrap?.default ?? bootstrap;
window.PerfectScrollbar = PerfectScrollbar?.default ?? PerfectScrollbar;
window.Scrollbar = Scrollbar?.default ?? Scrollbar;
window.Chart = Chart?.default ?? Chart;
