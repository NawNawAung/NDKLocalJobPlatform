import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { NodeTypes } from '@vue/compiler-dom';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { translations } from './resources/js/i18n.js';

function localizeStaticInterfaceText(node, context) {
    if (node.type !== NodeTypes.TEXT || !translations[node.content]) return;

    context.replaceNode({
        type: NodeTypes.INTERPOLATION,
        content: {
            type: NodeTypes.SIMPLE_EXPRESSION,
            content: `$t(${JSON.stringify(node.content)})`,
            isStatic: false,
            constType: 0,
            loc: node.loc,
        },
        loc: node.loc,
    });
}

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('/resources/js/i18n.js')) return 'locale';
                },
            },
        },
    },
    plugins: [
        vue({ template: { compilerOptions: { nodeTransforms: [localizeStaticInterfaceText] } } }),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
