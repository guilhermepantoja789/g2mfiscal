import {
    defaultSpotlightSteps,
    hasSeenSpotlight,
    markSpotlightSeen,
    shouldAutoStartSpotlight,
} from './spotlight';

const PDV_OPERA_KEY = 'g2m-pdv-opera';

/**
 * Shell Alpine component: rail panel, command palette, spotlight.
 * Store `pdv` controla modo operação (tela cheia) na página do PDV.
 */
export function registerAppShell(Alpine) {
    Alpine.store('pdv', {
        isPdvPage: false,
        opera: false,

        configure({ isPdv = false } = {}) {
            this.isPdvPage = !!isPdv;
            if (!this.isPdvPage) {
                this.opera = false;
                document.documentElement.classList.remove('pdv-opera');
                return;
            }

            const saved = sessionStorage.getItem(PDV_OPERA_KEY);
            // Padrão: modo operação ligado (tela cheia).
            this.opera = saved === null ? true : saved === '1';
            this.applyDom();
        },

        toggleOpera() {
            if (!this.isPdvPage) {
                return;
            }
            this.opera = !this.opera;
            sessionStorage.setItem(PDV_OPERA_KEY, this.opera ? '1' : '0');
            this.applyDom();
        },

        applyDom() {
            document.documentElement.classList.toggle('pdv-opera', this.isPdvPage && this.opera);
        },
    });

    Alpine.data('appShell', ({ initialGroup = null, isPdv = false } = {}) => ({
        activeGroup: initialGroup,
        mobileMore: false,
        cmdOpen: false,
        cmdQuery: '',
        cmdIndex: 0,
        cmdItems: [],
        spotlightOpen: false,
        spotlightStep: 0,
        spotlightSteps: defaultSpotlightSteps,
        isPdv: !!isPdv,

        init() {
            Alpine.store('pdv').configure({ isPdv: this.isPdv });

            try {
                const el = document.getElementById('g2m-nav-commands');
                this.cmdItems = el ? JSON.parse(el.textContent || '[]') : [];
            } catch {
                this.cmdItems = [];
            }

            if (shouldAutoStartSpotlight()) {
                this.$nextTick(() => this.startSpotlight(false));
            }
        },

        get operaMode() {
            return Alpine.store('pdv').opera;
        },

        contentPadClass() {
            if (this.operaMode) {
                return 'pl-0';
            }
            return this.activeGroup ? 'md:pl-[19rem]' : 'md:pl-rail';
        },

        toggleGroup(id) {
            this.activeGroup = this.activeGroup === id ? null : id;
        },

        openCmd() {
            this.cmdOpen = true;
            this.cmdQuery = '';
            this.cmdIndex = 0;
            this.$nextTick(() => this.$refs.cmdInput?.focus());
        },

        get cmdFiltered() {
            const q = (this.cmdQuery || '').trim().toLowerCase();
            if (!q) {
                return this.cmdItems.slice(0, 40);
            }

            return this.cmdItems
                .filter((item) => {
                    const hay = `${item.label} ${item.group} ${item.keywords || ''}`.toLowerCase();
                    return hay.includes(q);
                })
                .slice(0, 40);
        },

        cmdMove(delta) {
            const len = this.cmdFiltered.length;
            if (!len) {
                return;
            }
            this.cmdIndex = (this.cmdIndex + delta + len) % len;
        },

        cmdGo() {
            const item = this.cmdFiltered[this.cmdIndex];
            if (item?.href) {
                window.location.href = item.href;
            }
        },

        onGlobalKey(event) {
            const meta = event.metaKey || event.ctrlKey;
            if (meta && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                if (this.cmdOpen) {
                    this.cmdOpen = false;
                } else {
                    this.openCmd();
                }
            }
        },

        startSpotlight(force = false) {
            if (!force && hasSeenSpotlight()) {
                return;
            }
            this.spotlightStep = 0;
            this.spotlightOpen = true;
        },

        nextSpotlight() {
            if (this.spotlightStep >= this.spotlightSteps.length - 1) {
                this.skipSpotlight();
                return;
            }
            this.spotlightStep += 1;
        },

        skipSpotlight() {
            this.spotlightOpen = false;
            markSpotlightSeen();
        },
    }));
}
