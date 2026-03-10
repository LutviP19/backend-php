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
    extend: {},
  },
  plugins: [],
}

