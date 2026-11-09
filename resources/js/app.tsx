import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';

type PageModule = {
    default: ComponentType;
};

const pages = import.meta.glob<PageModule>('./Pages/**/*.tsx');

router.on('httpException', (event) => {
    if (event.detail.response.status !== 419) {
        return;
    }

    event.preventDefault();

    const isLoginPage = window.location.pathname.replace(/\/$/, '') === '/login';
    const search = new URLSearchParams();

    if (!isLoginPage) {
        search.set('session_expired', '1');
    }

    search.set('_', String(Date.now()));

    window.location.replace(`/login?${search.toString()}`);
});

createInertiaApp({
    title: (title) => `${title} - SUPA`,

    resolve: async (name) => {
        const path = `./Pages/${name}.tsx`;
        const page = pages[path];

        if (!page) {
            throw new Error(`Page not found: ${path}`);
        }

        const module = await page();

        return module.default;
    },

    setup({ el, App, props }) {
        createRoot(el).render(
            <App {...props} />
        );
    },
});