/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/views/**/*.{blade.php,js}",
    "./resources/js/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        indigo: {
          900: '#1e1b4b',
        },
        orange: {
          500: '#f97316',
          600: '#ea580c',
        },
      },
    },
  },
  plugins: [],
}
