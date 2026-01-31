/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './pages/**/*.php',
    './components/**/*.php',
    './includes/**/*.php',
    './test/**/*.php',
    './database/**/*.php',
  ],
  safelist: [
    // 회색 계열
    'bg-white', 'bg-gray-50', 'bg-gray-100', 'bg-gray-200', 'bg-gray-800',
    'text-white', 'text-gray-400', 'text-gray-500', 'text-gray-600', 'text-gray-700', 'text-gray-800', 'text-gray-900',
    'border-gray-200', 'border-gray-300',
    // 파란색 계열
    'bg-blue-50', 'bg-blue-100', 'bg-blue-600', 'bg-blue-700',
    'text-blue-600', 'text-blue-700', 'text-blue-800',
    'border-blue-600',
    // 초록색 계열
    'bg-green-100', 'bg-green-600',
    'text-green-600', 'text-green-800',
    // 빨간색 계열
    'bg-red-50', 'bg-red-100', 'bg-red-600',
    'text-red-500', 'text-red-600', 'text-red-800',
    'border-red-200', 'border-red-300',
    // 노란색 계열
    'bg-yellow-100',
    'text-yellow-800',
    // Hover 상태
    'hover:bg-gray-50', 'hover:bg-gray-100', 'hover:bg-blue-700', 'hover:bg-red-50',
    'hover:text-gray-700', 'hover:text-blue-800',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#2563eb',
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
      },
    },
  },
  plugins: [],
}
