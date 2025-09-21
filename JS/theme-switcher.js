document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.querySelector('.btn-toggle');
    
    themeToggle.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Store theme preference
        const currentTheme = document.body.classList.contains('dark') ? 'light' : 'dark';
        localStorage.setItem('theme', currentTheme);
        
        // Update theme without page reload
        document.body.classList.toggle('dark');
        
        // Update toggle button icon
        const icon = this.querySelector('i');
        if (currentTheme === 'dark') {
            icon.classList.replace('fa-moon', 'fa-sun');
        } else {
            icon.classList.replace('fa-sun', 'fa-moon');
        }
        
        // Send theme preference to server
        fetch('update_theme.php?theme=' + currentTheme);
    });
});