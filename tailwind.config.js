/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    "./public/**/*.{php,js}", // Scans all PHP and JS files in the public directory   
    "./public/*.php",             // Scans PHP files in the root directory
    "./views/**/*.{php,html}", // Scans all PHP and HTNL files in the views directory
  ],
  // Tambahkan ini agar warna-warna tersebut selalu ada di output CSS meskipun belum dipakai
  safelist: [
    {
      pattern: /(bg|text|border)-(sky|violet|orange|cyan|slate)-(100|200|300|400|500|600|700|800|900)/,
    },
  ],
  theme: {
    extend: {
      colors: {
        'dark-navy-header': 'rgb(10 17 34)',
        app: {
          bg: 'rgb(var(--color-bg) / <alpha-value>)',
          card: 'rgb(var(--color-card) / <alpha-value>)',
          text: 'rgb(var(--color-text) / <alpha-value>)',
          border: 'rgb(var(--color-border) / <alpha-value>)',
        }
      }
    }
  },
  plugins: [],
}
