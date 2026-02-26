/** @type {import('tailwindcss').Config} */
export default {
  content: [],
  theme: {
    extend: {
      colors: {
        cinema: {
          50: '#faf9fb',
          100: '#f3f0f5',
          200: '#e8e3ec',
          300: '#d4cddd',
          400: '#b8aec6',
          500: '#9b8cb0',
          600: '#7f6d99',
          700: '#6b5584',
          800: '#5a4770',
          900: '#4d3c5f',
          950: '#2a2235',
        }
      },
      fontFamily: {
        'display': ['Playfair Display', 'serif'],
        'body': ['Cormorant Garamond', 'serif'],
      },
      boxShadow: {
        'glamour': '0 25px 50px -12px rgba(107, 85, 132, 0.25)',
        'soft': '0 4px 20px rgba(107, 85, 132, 0.15)',
      }
    }
  },
  plugins: []
}
