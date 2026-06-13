import * as Preline from 'preline';
import Alpine from 'alpinejs';

// Preline's bundle does NOT auto-init in a build pipeline — we must call
// autoInit() once the DOM is ready, and again after dynamic DOM swaps.
window.HSStaticMethods = Preline.HSStaticMethods;
window.HSOverlay = Preline.HSOverlay;

const initPreline = () => window.HSStaticMethods.autoInit();
document.addEventListener('DOMContentLoaded', initPreline);
window.refreshPreline = initPreline;

window.Alpine = Alpine;
Alpine.start();
