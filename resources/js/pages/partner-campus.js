import {
    BadgeCheck, CalendarClock, ChevronLeft, ChevronRight, Clock3, Facebook, GraduationCap,
    Instagram, MapPin, MessageCircle, Plus, Quote, Search, Sparkles, X, Youtube,
} from 'lucide';
import { initLucideIcons } from '../support/icons';
import { initRevealAnimations } from '../support/reveal';

const hideSkeleton = () => document.querySelector('.skeleton-loader')?.classList.add('is-hidden');
requestAnimationFrame(() => requestAnimationFrame(hideSkeleton));
window.setTimeout(hideSkeleton, 3000);

initLucideIcons({
    BadgeCheck, CalendarClock, ChevronLeft, ChevronRight, Clock3, Facebook, GraduationCap,
    Instagram, MapPin, MessageCircle, Plus, Quote, Search, Sparkles, X, Youtube,
});
initRevealAnimations();
