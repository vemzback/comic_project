import './bootstrap';

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.getElementById('site-menu');

    if (!menuToggle || !navMenu) return;

    // Toggle menu on button click
    menuToggle.addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        const newState = !isExpanded;

        this.setAttribute('aria-expanded', newState);
        navMenu.setAttribute('data-open', newState);
    });

    // Close menu when a link is clicked
    navMenu.addEventListener('click', function(e) {
        // Only close if clicking on navigation links, not on buttons
        if (e.target.tagName === 'A') {
            menuToggle.setAttribute('aria-expanded', 'false');
            navMenu.setAttribute('data-open', 'false');
        }
    });

    // Handle logout form submission
    const logoutForms = navMenu.querySelectorAll('.inline-form');
    logoutForms.forEach(form => {
        form.addEventListener('submit', function() {
            // Allow form to submit normally
        });
    });

    // Close menu on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
            menuToggle.setAttribute('aria-expanded', 'false');
            navMenu.setAttribute('data-open', 'false');
            menuToggle.focus();
        }
    });
});
