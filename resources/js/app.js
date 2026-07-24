import './bootstrap';
import Alpine from 'alpinejs';
import * as Turbo from '@hotwired/turbo';
import { registerAppShell } from './shell';

window.Alpine = Alpine;
window.Turbo = Turbo;

registerAppShell(Alpine);

document.addEventListener('turbo:load', () => {
    if (!window.__g2mAlpineStarted) {
        Alpine.start();
        window.__g2mAlpineStarted = true;
        return;
    }

    // Snapshot was cleaned in turbo:before-cache; re-bind the swapped body.
    Alpine.initTree(document.body);
});

document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('[x-cloak]').forEach((el) => {
        el.setAttribute('style', 'display: none !important');
    });

    // Remove x-for clones before Turbo snapshots the DOM. Otherwise restored
    // pages keep orphan nodes with item/index bindings and Alpine throws
    // "item is not defined" / "index is not defined" on re-init.
    Alpine.destroyTree(document.body);
});
