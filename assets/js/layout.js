/**
 * Produktivitätstool - Layout JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('show');
        sidebar.classList.toggle('collapsed');
        sidebarOverlay.classList.toggle('show');
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // User Menu Toggle
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userMenuDropdown = document.getElementById('userMenuDropdown');

    if (userMenuToggle && userMenuDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenuDropdown.classList.toggle('show');
        });

        document.addEventListener('click', function() {
            userMenuDropdown.classList.remove('show');
        });
    }

    // Close sidebar on window resize if open
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && sidebar) {
            sidebar.classList.remove('show');
            sidebar.classList.remove('collapsed');
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('show');
            }
        }
    });
});