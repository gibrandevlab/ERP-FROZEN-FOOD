import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { networkInterfaces } from 'os';

// Fungsi untuk mendeteksi IP Wi-Fi lokal secara otomatis
const getLocalIp = () => {
    const interfaces = networkInterfaces();
    // Cari interface Wi-Fi terlebih dahulu
    for (const devName in interfaces) {
        if (devName.toLowerCase().includes('wi-fi') || devName.toLowerCase().includes('wireless')) {
            const iface = interfaces[devName];
            for (const alias of iface) {
                if (alias.family === 'IPv4' && !alias.internal) {
                    return alias.address;
                }
            }
        }
    }
    // Fallback ke IP non-localhost pertama jika nama interface Wi-Fi tidak spesifik
    for (const devName in interfaces) {
        const iface = interfaces[devName];
        for (const alias of iface) {
            if (alias.family === 'IPv4' && !alias.internal) {
                return alias.address;
            }
        }
    }
    return 'localhost';
};

const localIp = getLocalIp();

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: localIp,
        },
    },
});
