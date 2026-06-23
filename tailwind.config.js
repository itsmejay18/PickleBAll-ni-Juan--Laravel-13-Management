import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                outfit: ['Outfit', 'sans-serif'],
            },
            colors: {
                brand: {
                    green: '#18a37f',
                    greenLight: '#80e6c6',
                    ink: '#0d1117',
                    slate: '#080c12',
                    card: '#161b22',
                    rose: '#f5365c',
                    roseLight: '#f5808f'
                }
            }
        },
    },

    plugins: [forms],
};
