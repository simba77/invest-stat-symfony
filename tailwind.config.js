module.exports = {
  content: ['./assets/**/*.{js,ts,vue}'],
  theme: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('tailwindcss-primeui'),
  ],
  darkMode: ['selector', '.app-dark'],
}
