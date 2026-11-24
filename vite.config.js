import laravel from 'laravel-vite-plugin';

import fg from 'fast-glob';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                ...fg.sync('resources/css/**/*.(css|scss|sass)'),
                ...fg.sync('resources/js/**/*.(js|ts|tsx)')
            ],
            refresh: true
        })
    ]
});
