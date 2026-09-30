import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';

type PageModule = {
    default: ComponentType;
};

const pages = import.meta.glob<PageModule>('./Pages/**/*.tsx');

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