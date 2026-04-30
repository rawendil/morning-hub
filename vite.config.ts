import { existsSync } from 'node:fs';
import { sentryVitePlugin } from '@sentry/vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

const STUB_VIRTUAL_ID_PREFIX = '\0wayfinder-stub:';
const STUB_CONTENT =
    'const s = () => "#"; s.url = () => "#"; s.form = () => ({}); ' +
    'export default new Proxy({}, { get: () => s }); ' +
    'export { s as update, s as store, s as request, s as email, s as edit, ' +
    's as show, s as index, s as destroy, s as reorder, s as send, ' +
    's as disable, s as enable, s as guide, s as createTask, s as updateTask, ' +
    's as task, s as comments, s as createComment, s as statuses, s as test, ' +
    's as allLists, s as me, s as spaces, s as workspaces, s as calendars, ' +
    's as connect, s as callback, s as disconnect, s as linkRedirect, ' +
    's as qrCode, s as secretKey, s as recoveryCodes, s as regenerateRecoveryCodes };';

const JS_ROUTES_PATH = new URL('./resources/js/routes', import.meta.url).pathname;
const JS_ACTIONS_PATH = new URL('./resources/js/actions', import.meta.url).pathname;

function isWayfinderPath(id: string): boolean {
    return id.includes('/resources/js/routes') ||
        id.includes('/resources/js/actions') ||
        id.startsWith('@/routes') ||
        id.startsWith('@/actions');
}

/**
 * Provides empty module stubs for Wayfinder route/action files that don't exist yet
 * while page components are being migrated from Inertia to the REST API.
 * Also stubs existing files that are missing expected named exports.
 * Remove once all pages are fully migrated (Tasks 7–9).
 */
function wayfinderMigrationStubPlugin() {
    return {
        name: 'wayfinder-migration-stub',
        resolveId(source: string, importer: string | undefined) {
            if (!importer) { return null; }
            if (!isWayfinderPath(source)) { return null; }

            // Resolve @/ alias manually
            let resolved = source;
            if (source.startsWith('@/routes')) {
                resolved = `${JS_ROUTES_PATH}${source.slice('@/routes'.length)}`;
            } else if (source.startsWith('@/actions')) {
                resolved = `${JS_ACTIONS_PATH}${source.slice('@/actions'.length)}`;
            }

            // Check if the file exists (with or without .ts extension, or as index.ts)
            const candidates = [
                resolved,
                `${resolved}.ts`,
                `${resolved}/index.ts`,
            ];
            const fileExists = candidates.some(existsSync);
            if (!fileExists) {
                return `${STUB_VIRTUAL_ID_PREFIX}${source}`;
            }
            return null; // let Vite resolve normally
        },
        load(id: string) {
            if (id.startsWith(STUB_VIRTUAL_ID_PREFIX)) {
                return STUB_CONTENT;
            }
            // Handle ENOENT on files that were resolved but don't exist
            if (isWayfinderPath(id)) {
                const cleanId = id.split('?')[0];
                if (!existsSync(cleanId)) {
                    return STUB_CONTENT;
                }
            }
            return null;
        },
    };
}

export default defineConfig({
    build: {
        sourcemap: true,
        rollupOptions: {
            external: ['@inertiajs/vue3'],
            onwarn(warning, warn) {
                // Suppress "not exported" errors for route/action stubs during migration (Tasks 7–9)
                if (
                    warning.code === 'MISSING_EXPORT' &&
                    (warning.id?.includes('resources/js/routes/') ||
                        warning.id?.includes('resources/js/actions/') ||
                        warning.exporter?.includes('resources/js/routes/') ||
                        warning.exporter?.includes('resources/js/actions/'))
                ) {
                    return;
                }
                warn(warning);
            },
        },
    },
    server: {
        port: 5180,
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        wayfinderMigrationStubPlugin(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        sentryVitePlugin({
            org: process.env.SENTRY_ORG,
            project: process.env.SENTRY_PROJECT,
            authToken: process.env.SENTRY_AUTH_TOKEN,
            release: {
                name: process.env.SENTRY_RELEASE,
            },
            sourcemaps: {
                filesToDeleteAfterUpload: ['./public/build/**/*.map'],
            },
            disable: !process.env.SENTRY_AUTH_TOKEN,
        }),
    ],
});
