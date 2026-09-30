module.exports = {
  content: [
    './includes/**/*.php',
    './assets/js/**/*.js'
  ],
  prefix: 'tw-',
  important: '.tiamis-admin-wrap',
  corePlugins: {
    preflight: false
  },
  theme: {
    extend: {
      colors: {
        tiamis: {
          50: '#f8f4ff',
          100: '#efe7ff',
          200: '#dfd0ff',
          300: '#c5a7ff',
          400: '#a16dff',
          500: '#7d36e8',
          600: '#5f13bd',
          700: '#48099a',
          800: '#38008a',
          900: '#2a0068'
        }
      },
      boxShadow: {
        'tiamis-soft': '18px 18px 42px rgba(45, 12, 84, .14), -14px -14px 36px rgba(255, 255, 255, .92)',
        'tiamis-inset': 'inset 4px 4px 10px rgba(56, 0, 138, .09), inset -4px -4px 10px rgba(255, 255, 255, .9)'
      },
      borderRadius: {
        '4xl': '2rem'
      }
    }
  },
  plugins: []
};
