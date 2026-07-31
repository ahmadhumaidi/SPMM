import { createIcons } from 'lucide';

/**
 * @param {Record<string, unknown>} icons Only the icons this page actually
 * renders (e.g. `{ Search, ArrowRight }`) — importing the full lucide set
 * bloats the bundle by hundreds of KB for a handful of glyphs.
 */
export function initLucideIcons(icons) {
    try {
        createIcons({ icons });
    } catch (error) {
        console.error('Lucide icon library failed to initialize.', error);
    }
}
