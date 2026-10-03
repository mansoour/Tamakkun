import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/**
 * Tamakkun design tokens — see docs/architecture.md#design-system.
 * Muted text is #6B6574 (not #77717F) so it meets WCAG AA (4.5:1) on the
 * lavender canvas.
 */

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Support/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"IBM Plex Sans Arabic"', ...defaultTheme.fontFamily.sans],
                heading: ['Alexandria', '"IBM Plex Sans Arabic"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#F7F4FB',
                    100: '#EFE9F8',
                    200: '#E9E2F2',
                    300: '#C9B9E8',
                    400: '#9B83D1',
                    500: '#8A70C6',
                    600: '#7458B5',
                    700: '#5F4699',
                    800: '#4B377A',
                    900: '#382A5B',
                },
                canvas: '#F7F4FB',
                surface: '#FFFFFF',
                ink: '#302B3A',
                muted: '#6B6574',
                line: '#E9E2F2',
            },
            borderRadius: {
                card: '1.25rem',
            },
            boxShadow: {
                card: '0 1px 2px rgba(48, 43, 58, 0.04), 0 8px 24px -12px rgba(116, 88, 181, 0.18)',
            },
            backgroundImage: {
                'brand-gradient': 'linear-gradient(135deg, #7458B5 0%, #9B83D1 100%)',
            },
        },
    },

    plugins: [forms, typography],
};
