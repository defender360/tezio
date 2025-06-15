/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Defender360 colors
        'deep-sea': {
          DEFAULT: '#043659',
          50: '#E6EEF4',
          100: '#CCDDE9',
          200: '#99BBD3',
          300: '#6699BD',
          400: '#3377A7',
          500: '#043659',
          600: '#032B47',
          700: '#022035',
          800: '#011523',
          900: '#000A12'
        },
        'tech-horizon': {
          DEFAULT: '#0070AF',
          50: '#E6F2FA',
          100: '#CCE5F5',
          200: '#99CBEB',
          300: '#66B1E1',
          400: '#3397D7',
          500: '#0070AF',
          600: '#005A8C',
          700: '#004369',
          800: '#002D46',
          900: '#001623'
        },
        'arctic-breeze': {
          DEFAULT: '#60B4CD',
          500: '#60B4CD',
          400: '#7FC0D6',
          300: '#9ECDDF'
        },
        'vital-energy': {
          DEFAULT: '#009F8D',
          500: '#009F8D',
          600: '#007F71'
        },
        // Priority colors
        'priority': {
          critical: '#D32F2F',
          high: '#F57C00',
          medium: '#FBC02D',
          low: '#388E3C'
        }
      },
      fontFamily: {
        'brain': ['Brain Wants', 'Helvetica', 'Arial', 'sans-serif'],
        'tipografix': ['Tipografix', 'Arial', 'sans-serif'],
        'playfair': ['PlayFair Display', 'Georgia', 'serif']
      },
      boxShadow: {
        'defender-sm': '0 2px 4px rgba(4, 54, 89, 0.1)',
        'defender-md': '0 4px 8px rgba(4, 54, 89, 0.15)',
        'defender-lg': '0 8px 16px rgba(4, 54, 89, 0.2)'
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography')
  ],
}