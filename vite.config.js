import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import fs from 'fs';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const port = parseInt(env.VITE_PORT || 5173);
    const domain = env.APP_DOMAIN || 'localhost';
    const certPath = './docker/certs/cert.pem';
    const keyPath = './docker/certs/key.pem';
    const hasCerts = fs.existsSync(certPath) && fs.existsSync(keyPath);

    return {
        plugins: [
            laravel({
                input: 'resources/js/app.js',
                refresh: true,
            }),
            tailwindcss(),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        server: {
            https: hasCerts ? { key: keyPath, cert: certPath } : false,
            host: '0.0.0.0',
            port,
            strictPort: true,
            hmr: {
                host: domain,
                port,
            },
        },
    };
});
