import {
    Sparkles, Send, Search, ArrowRight, Briefcase, MonitorSmartphone, Shuffle, BadgeCheck,
    MapPin, WalletCards, MessageCircle, FileCheck2, GraduationCap, BookOpen, Rocket, Plus,
} from 'lucide';
import { initLucideIcons } from '../support/icons';
import { initRevealAnimations } from '../support/reveal';

const hideSkeleton = () => document.querySelector('.skeleton-loader')?.classList.add('is-hidden');
requestAnimationFrame(() => requestAnimationFrame(hideSkeleton));
window.setTimeout(hideSkeleton, 3000);

initLucideIcons({
    Sparkles, Send, Search, ArrowRight, Briefcase, MonitorSmartphone, Shuffle, BadgeCheck,
    MapPin, WalletCards, MessageCircle, FileCheck2, GraduationCap, BookOpen, Rocket, Plus,
});
initRevealAnimations();

const searchForm = document.getElementById('campus-search-form');
const search = document.getElementById('campus-search');
const campusSection = document.getElementById('kampus');
const filterButtons = Array.from(document.querySelectorAll('[data-program-filter]'));
const cards = Array.from(document.querySelectorAll('[data-campus-card]'));
const showAllButton = document.getElementById('campus-show-all');
const selectedPrograms = new Set();
let showAllCampuses = false;
const programAliases = {
    'kuliah karyawan': ['kuliah karyawan', 'kelas karyawan', 'professional', 'profesional', 'program kuliah professional'],
    'full online': ['full online', 'online'],
    'hybrid learning': ['hybrid learning', 'hybird learning', 'hybrid', 'hybird'],
    'rpl': ['rpl', 'rekognisi pembelajaran lampau'],
};

const applyCampusFilters = () => {
    const term = search?.value.toLowerCase().trim() || '';
    const activePrograms = Array.from(selectedPrograms);
    const isMobile = window.matchMedia('(max-width: 767px)').matches;
    const initialCampusLimit = isMobile ? 3 : 6;
    const shouldLimitCampuses = ! showAllCampuses && ! term && activePrograms.length === 0;
    let visibleIndex = 0;

    cards.forEach((card) => {
        const searchableText = card.dataset.search || '';
        const programText = card.dataset.programs || '';
        const matchesSearch = ! term || searchableText.includes(term);
        const matchesProgram = activePrograms.length === 0 || activePrograms.some((program) => {
            return (programAliases[program] || [program]).some((alias) => programText.includes(alias));
        });

        const isMatch = matchesSearch && matchesProgram;
        const shouldHideByLimit = shouldLimitCampuses && visibleIndex >= initialCampusLimit;

        card.hidden = ! isMatch || shouldHideByLimit;

        if (isMatch) {
            visibleIndex += 1;
        }
    });

    if (showAllButton) {
        showAllButton.hidden = ! shouldLimitCampuses || visibleIndex <= initialCampusLimit;
    }
};

showAllButton?.addEventListener('click', () => {
    showAllCampuses = true;
    applyCampusFilters();
});

filterButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const filter = button.dataset.programFilter;

        if (selectedPrograms.has(filter)) {
            selectedPrograms.delete(filter);
            button.classList.remove('is-active');
        } else {
            selectedPrograms.add(filter);
            button.classList.add('is-active');
        }

        applyCampusFilters();
    });
});

search?.addEventListener('input', applyCampusFilters);
window.addEventListener('resize', applyCampusFilters);
searchForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    applyCampusFilters();
    campusSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

applyCampusFilters();
