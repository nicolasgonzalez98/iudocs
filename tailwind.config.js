import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js}',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Tokens de marca IUDocs (light-first, fondo crema + acento verde bosque)
                cream: '#FEFCF8', // fondo base (se mantiene)
                ink: '#241C12', // texto principal
                // Acento "verde bosque" (reemplaza al ámbar como color principal)
                brand: {
                    50: '#EFF6E6',
                    100: '#DBEBC7',
                    200: '#C2DDA1',
                    300: '#A2CB77',
                    400: '#7CB342',
                    500: '#4F8A38', // acento principal
                    600: '#427730', // hover
                    700: '#356026',
                    800: '#2A4C1F',
                    900: '#22401A',
                },
            },
        },
    },

    plugins: [forms],
};
