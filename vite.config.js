import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'; // Corrected import


export default defineConfig({
    // server: {
    //     host: '0.0.0.0',  // Allow access from external devices
    //     port: 5173,       // Default Vite port
    //     strictPort: true,
    //     hmr: {
    //         host: '172.26.240.1' // Replace with your actual local network IP
    //     }
    // },
    plugins: [
   

        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({  // Use vuePlugin here
            template: {
                transformAssetUrls: {
                    base: true,
                    includeAbsolute: false,
                },
            },
        }),

    ],
    optimizeDeps: {
        include: ['pizzip']
    },
     resolve: {
        alias: {
            // Fix pizzip dependency conflict
            'pizzip': 'pizzip/dist/pizzip.js'
        },
        dedupe: ['pizzip']
    },

});
