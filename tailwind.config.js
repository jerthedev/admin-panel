/**
 * Tailwind CSS v4 Configuration for Admin Panel
 *
 * In Tailwind v4, most configuration is done via CSS custom properties
 * and @theme directives in the CSS file itself. This config file is
 * mainly for content paths and build settings.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @version 1.0.0 - Tailwind v4 Compatible
 */

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './src/**/*.php',
    ],
}
