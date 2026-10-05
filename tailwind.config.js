import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './public/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                navy: {
                    50: '#f0f5ff',
                    100: '#e0ecff',
                    200: '#c7dcfe',
                    300: '#a0c4fd',
                    400: '#70a2fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e3a8a',
                    850: '#142852',
                    900: '#0f1e4a',
                    950: '#0a1128',
                    975: '#060b1b',
                },
                brand: {
                    primary: '#1d4ed8',
                    accent: '#3b82f6',
                    dark: '#0a1128',
                    darker: '#060b1b',
                    surface: '#0f1e4a',
                }
            },
            boxShadow: {
                'navy-glow': '0 0 25px -5px rgba(59, 130, 246, 0.35)',
                'navy-card': '0 10px 30px -10px rgba(10, 17, 40, 0.12)',
                'navy-elevated': '0 20px 40px -15px rgba(10, 17, 40, 0.25)',
            }
        },
    },

    plugins: [forms],
};
