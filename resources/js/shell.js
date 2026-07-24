import {
    defaultSpotlightSteps,
    hasSeenSpotlight,
    markSpotlightSeen,
    shouldAutoStartSpotlight,
} from './spotlight';

/**
 * Shell Alpine component: rail panel, command palette, spotlight.
 */
export function registerAppShell(Alpine) {
    Alpine.data('appShell', ({ initialGroup = null } = {}) => ({
        activeGroup: initialGroup,
        mobileMore: false,
        cmdOpen: false,
        cmdQuery: '',
        cmdIndex: 0,
        cmdItems: [],
        spotlightOpen: false,
        spotlightStep: 0,
        spotlightSteps: defaultSpotlightSteps,

        init() {
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
