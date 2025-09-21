// Improved menu toggle with accessibility features
function toggleMenu() {
    const hamburger = document.querySelector('.hamburger');
    const menu = document.querySelector('#menu');
    const isExpanded = menu.classList.toggle('active');
    
    // Update ARIA attributes
    hamburger.setAttribute('aria-expanded', isExpanded);
    menu.setAttribute('aria-hidden', !isExpanded);
    
    // Toggle hamburger animation
    hamburger.classList.toggle('active');
    
    // Focus management
    if (isExpanded) {
        const firstMenuItem = menu.querySelector('a');
        if (firstMenuItem) firstMenuItem.focus();
    }
}

// Close menu when clicking outside (improved)
document.addEventListener('click', (e) => {
    const menu = document.querySelector('#menu');
    const hamburger = document.querySelector('.hamburger');
    
    if (menu.classList.contains('active') && 
        !menu.contains(e.target) && 
        !hamburger.contains(e.target)) {
        menu.classList.remove('active');
        hamburger.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
        menu.setAttribute('aria-hidden', 'true');
    }
});

// Keyboard navigation for menu
document.addEventListener('keydown', (e) => {
    const menu = document.querySelector('#menu');
    if (menu.classList.contains('active')) {
        if (e.key === 'Escape') {
            menu.classList.remove('active');
            document.querySelector('.hamburger').classList.remove('active');
            document.querySelector('.hamburger').focus();
        }
        
        // Trap focus within menu when open
        if (e.key === 'Tab') {
            const focusable = menu.querySelectorAll('a, button, [tabindex]');
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            
            if (e.shiftKey && document.activeElement === first) {
                last.focus();
                e.preventDefault();
            } else if (!e.shiftKey && document.activeElement === last) {
                first.focus();
                e.preventDefault();
            }
        }
    }
});

// Initialize ARIA attributes
document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.hamburger');
    const menu = document.querySelector('#menu');
    
    hamburger.setAttribute('aria-label', 'Toggle menu');
    hamburger.setAttribute('aria-controls', 'menu');
    hamburger.setAttribute('aria-expanded', 'false');
    menu.setAttribute('aria-hidden', 'true');
});