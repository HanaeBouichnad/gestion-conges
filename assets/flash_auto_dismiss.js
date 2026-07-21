/*
 * Auto-dismiss des messages flash de succès (.lp-alert-success ou .alert-success,
 * quel que soit le template qui les affiche) : disparition en fondu après 5s.
 * Un seul endroit à maintenir : ce fichier, importé une fois dans app.js.
 *
 * Écoute à la fois DOMContentLoaded (premier chargement) et turbo:load (navigations
 * Turbo Drive, qui ne redéclenchent pas DOMContentLoaded) pour fonctionner dans tous les cas.
 */
const DELAI_MS = 5000;
const SELECTEUR = '.lp-alert-success, .alert-success';
const MARQUEUR = 'data-auto-dismiss-armed';

function armerFondu(element) {
    element.style.overflow = 'hidden';
    element.style.maxHeight = element.offsetHeight + 'px';
    element.style.transition = 'opacity 0.4s ease, max-height 0.4s ease, margin 0.4s ease, padding 0.4s ease';

    requestAnimationFrame(() => {
        element.style.opacity = '0';
        element.style.maxHeight = '0';
        element.style.marginTop = '0';
        element.style.marginBottom = '0';
        element.style.paddingTop = '0';
        element.style.paddingBottom = '0';
    });

    element.addEventListener('transitionend', () => element.remove(), { once: true });
}

function initAutoDismiss() {
    document.querySelectorAll(SELECTEUR).forEach((element) => {
        if (element.hasAttribute(MARQUEUR)) {
            return;
        }
        element.setAttribute(MARQUEUR, '');
        setTimeout(() => armerFondu(element), DELAI_MS);
    });
}

document.addEventListener('DOMContentLoaded', initAutoDismiss);
document.addEventListener('turbo:load', initAutoDismiss);
