export function initRevealAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) entry.target.classList.add('is-visible');
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
}
