/** Spotlight tour defaults and helpers for the app shell. */

export const SPOTLIGHT_STORAGE_KEY = 'g2m.tour.v1';

export const defaultSpotlightSteps = [
    {
        title: 'Navegação por módulos',
        body: 'No desktop, use a barra de ícones à esquerda. Clique em um módulo para abrir o painel com as telas. No celular, use a barra inferior e “Mais”.',
    },
    {
        title: 'Busca rápida (⌘K)',
        body: 'Pressione ⌘K (ou Ctrl+K) ou o botão Buscar para ir direto a qualquer tela permitida ao seu perfil.',
    },
    {
        title: 'Ajuda nas telas',
        body: 'Telas principais têm um painel “Como usar esta tela”. Reabra este tour a qualquer momento pelo botão “?” na barra superior.',
    },
];

export function shouldAutoStartSpotlight() {
    const skipAuto = document.body?.dataset?.skipSpotlight === '1'
        || window.location.pathname.includes('/pdv');

    return !skipAuto && !localStorage.getItem(SPOTLIGHT_STORAGE_KEY);
}

export function markSpotlightSeen() {
    localStorage.setItem(SPOTLIGHT_STORAGE_KEY, '1');
}

export function hasSeenSpotlight() {
    return Boolean(localStorage.getItem(SPOTLIGHT_STORAGE_KEY));
}
