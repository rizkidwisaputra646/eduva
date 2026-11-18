/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        // Konsep Warna Utama LMS Eduva - Fresh & Modern
        page: '#F5F8FC',
        card: '#FFFFFF',
        'card-border': '#E5EAF0',
        border: '#E5EAF0',
        
        // Tipografi
        'text-main': '#263445',
        'text-secondary': '#718096',
        'text-muted': '#718096',

        // Warna Utama & Aksen Eduva
        primary: {
          DEFAULT: '#438FCE',
          hover: '#387EB8',
          dark: '#3071A6',
          light: '#DFEAF9',
          50: '#F0F6FC',
          100: '#DFEAF9',
          500: '#438FCE',
          600: '#387EB8',
          700: '#2E6C9E',
        },
        
        // Palet Kategori & Aktivitas Eduva
        eduva: {
          green: '#32865B',
          'green-bg': '#E0F0E6',
          'green-border': '#C4E4CF',
          teal: '#419D98',
          'teal-bg': '#DDF1F0',
          'teal-border': '#BDE3E1',
          orange: '#B8873E',
          'orange-bg': '#FCEAD9',
          'orange-border': '#F8D5B5',
          purple: '#895EB5',
          'purple-bg': '#EEE5F8',
          'purple-border': '#DDCBEF',
          blue: '#438FCE',
          'blue-bg': '#DFEAF9',
          'blue-border': '#C3DAF5',
          indigo: '#5B68BE',
          'indigo-bg': '#E7E8F8',
          'indigo-border': '#CDD2F4',
          red: '#C54B4B',
          'red-bg': '#FDF0F0',
          'red-border': '#F8D5D5',
        },

        // Backward compatibility
        accent: '#32865B',
        background: '#F5F8FC',
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      boxShadow: {
        'subtle': '0 1px 2px 0 rgba(38, 52, 69, 0.04)',
        'card': '0 2px 4px 0 rgba(38, 52, 69, 0.03), 0 1px 2px 0 rgba(38, 52, 69, 0.02)',
        'dropdown': '0 10px 25px -5px rgba(38, 52, 69, 0.08), 0 8px 10px -6px rgba(38, 52, 69, 0.04)',
      }
    },
  },
  plugins: [],
}
