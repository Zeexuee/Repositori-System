/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                corporate: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    600: '#1e3a8a',
                    700: '#1e40af',
                    900: '#0f172a',
                },
            },
        },
    },
    plugins: [],
};
