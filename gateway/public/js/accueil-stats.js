(() => {
    const section = document.querySelector('#accueil-stats');
    const counters = [...document.querySelectorAll('[data-stat]')];

    if (!section || counters.length === 0) {
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const values = new Map();
    const animations = new Map();
    let observer;
    let hasIntersected = false;
    let etag = null;
    let timer = null;
    let failures = 0;

    const format = (value) => {
        const displayed = value >= 1000 ? Math.floor(value / 100) * 100 : Math.round(value);
        const number = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 })
            .format(displayed)
            .replace(/[\u202f\u00a0]/g, ' ');

        return value >= 1000 ? `+${number}` : number;
    };

    const render = (element, value) => {
        element.textContent = format(value);
    };

    const animate = (element, from, to, duration = 700) => {
        const key = element.dataset.stat;
        const existing = animations.get(key);

        if (existing) {
            cancelAnimationFrame(existing.frame);
        }

        if (reducedMotion || from === to) {
            render(element, to);
            return;
        }

        const start = performance.now();
        const animation = { frame: null, current: from };

        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - ((1 - progress) ** 3);
            animation.current = from + ((to - from) * eased);
            render(element, animation.current);

            if (progress < 1) {
                animation.frame = requestAnimationFrame(step);
            } else {
                animation.current = to;
                animations.delete(key);
                render(element, to);
            }
        };

        animations.set(key, animation);
        animation.frame = requestAnimationFrame(step);
    };

    counters.forEach((element) => {
        if (element.dataset.value === '') {
            return;
        }

        const value = Number(element.dataset.value);

        if (Number.isFinite(value) && value >= 0) {
            values.set(element.dataset.stat, value);
        }
    });

    const revealCounters = () => {
        if (hasIntersected) {
            return;
        }

        hasIntersected = true;
        counters.forEach((element) => {
            const value = values.get(element.dataset.stat);

            if (value !== undefined) {
                animate(element, 0, value);
            }
        });
        observer?.disconnect();
    };

    if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                revealCounters();
            }
        }, { threshold: 0.2 });
        observer.observe(section);
    } else {
        revealCounters();
    }

    const updateCounters = (updatedStats) => {
        counters.forEach((element) => {
            const key = element.dataset.stat;
            const nextValue = updatedStats[key];

            if (!Number.isInteger(nextValue) || nextValue < 0 || values.get(key) === nextValue) {
                return;
            }

            const currentValue = animations.get(key)?.current ?? values.get(key) ?? 0;
            values.set(key, nextValue);
            element.dataset.value = String(nextValue);
            animate(element, hasIntersected ? currentValue : nextValue, nextValue);
        });
    };

    const nextDelay = () => {
        const baseDelay = failures === 0
            ? 30000
            : Math.min(30000 * (2 ** failures), 300000);

        return baseDelay * (0.8 + (Math.random() * 0.4));
    };

    const schedule = () => {
        window.clearTimeout(timer);

        if (!document.hidden) {
            timer = window.setTimeout(refresh, nextDelay());
        }
    };

    const refresh = async () => {
        if (document.hidden) {
            return;
        }

        try {
            const headers = etag ? { 'If-None-Match': etag } : {};
            const response = await fetch('/api/stats-publiques', {
                headers,
                cache: 'no-store',
            });

            if (response.status === 304) {
                failures = 0;
                return;
            }

            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}`);
            }

            etag = response.headers.get('ETag') ?? etag;
            updateCounters(await response.json());
            failures = 0;
        } catch (error) {
            failures += 1;
            console.error('La mise à jour des statistiques publiques a échoué.', error);
        } finally {
            schedule();
        }
    };

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            window.clearTimeout(timer);
        } else {
            refresh();
        }
    });

    schedule();
})();
