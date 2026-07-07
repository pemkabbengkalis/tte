/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: [
                    'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI',
                    'Roboto', 'Helvetica Neue', 'Arial', 'Noto Sans', 'sans-serif',
                ],
            },
            colors: {
                primary: {
                    50:  '#eef4fb',
                    100: '#d5e3f4',
                    200: '#acc7e9',
                    300: '#7ba6da',
                    400: '#4d83c7',
                    500: '#2f66ac',
                    600: '#234f87',
                    700: '#1d3f6b',
                    800: '#173155',
                    900: '#0f2240',
                },
            },
        },
    },
    plugins: [],
};