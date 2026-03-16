/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./resources/**/*.jsx", 
  ],
  
theme: {
  extend: {
    fontFamily: {
      sans: ['Instrument Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
    },

    animation: {
      marquee: 'marquee 20s linear infinite',
      flashRed: 'flashRed 1s ease-in-out infinite',
    },

    keyframes: {
      marquee: {
        '0%': { transform: 'translateX(100%)' },
        '100%': { transform: 'translateX(-100%)' },
      },

      flashRed: {
        '0%, 100%': { backgroundColor: '#dc2626' },
        '50%': { backgroundColor: '#991b1b' },
      },
    },
  },
},


  safelist: [
    {
      // Updated pattern to include all shades (50-900) and all color families
      pattern: /(bg|from|to)-(slate|gray|sky|blue|emerald|purple|rose|amber|cyan|indigo|teal|fuchsia)-(50|100|200|500|600|700|800|900)/,
    },
    // Manual utilities that aren't caught by the pattern
    'bg-white',
    'bg-gradient-to-r',
    'bg-gradient-to-b',
    'text-white',
    'text-slate-900',
    'text-gray-900',
    'border-white/10',
    'border-black/10',
    'border-slate-200'
  ],
  plugins: [],
}