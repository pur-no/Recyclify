document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Get theme from localStorage or system preference
    let currentTheme = localStorage.getItem('theme') || 
                      (prefersDark ? 'dark' : 'light');
    
    // Apply theme
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Update button text
        if (themeToggle) {
            themeToggle.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
            themeToggle.setAttribute('aria-label', 
                theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
        }
        
        // Sync with server if logged in
        if (document.body.classList.contains('logged-in')) {
            fetch('update_theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `theme=${theme}&csrf_token=${getCSRFToken()}`
            }).catch(err => console.error('Theme sync failed:', err));
        }
    }
    
    // Initialize
    applyTheme(currentTheme);
    
    // Toggle theme
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const newTheme = document.documentElement.getAttribute('data-theme') === 'dark' 
                ? 'light' 
                : 'dark';
            applyTheme(newTheme);
        });
    }
    
    // Watch for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!localStorage.getItem('theme')) { // Only if no explicit preference
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });
    
    // Helper to get CSRF token
    function getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || 
               document.querySelector('input[name="csrf_token"]')?.value;
    }
});