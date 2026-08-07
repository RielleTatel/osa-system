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
            colors: {
                navy: {
                    900: '#0B1930',
                    800: '#142A4D',
                    700: '#1B3868',
                },
                slate: {
                    500: '#5C77A6',
                    200: '#C9D6EA',
                },
                gold: {
                    500: '#D4AF37',
                    700: '#7A5C14',
                },
                paper: {
                    DEFAULT: '#FFFFFF',
                    muted: '#F4F6FA',
                },
                ink: {
                    900: '#16233F',
                },
            },
            fontFamily: {
                sans: ['Inter', 'Poppins', ...defaultTheme.fontFamily.sans],
                display: ['Montserrat', ...defaultTheme.fontFamily.sans],
                wordmark: ['"Playfair Display"', 'serif'],
            },
            backgroundImage: {
                'navy-gradient': 'linear-gradient(180deg, #1B3868 0%, #0B1930 100%)',
            },
        },
    },

    plugins: [forms],
};
