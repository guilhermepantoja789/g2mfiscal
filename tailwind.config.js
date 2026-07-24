import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    DEFAULT: '#1e676d',
                    hover: '#2a8a91',
                    soft: '#e6f3f4',
                    muted: '#4a8f94',
                },
                surface: {
                    DEFAULT: '#f1f5f9',
                    raised: '#ffffff',
                    ink: '#0f172a',
                    muted: '#64748b',
                },
            },
            spacing: {
                rail: '4rem',
                panel: '15rem',
            },
            maxWidth: {
                content: '80rem',
            },
        },
    },

    plugins: [forms],
};
