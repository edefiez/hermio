/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Import Bootstrap CSS
import './styles/bootstrap-custom.scss';

// Import Dashboard styles
import './styles/dashboard.scss';

// Import Admin Layout styles
import './styles/admin-layout.scss';

// Import Font Awesome
import '@fortawesome/fontawesome-free/css/all.css';

// Import Bootstrap JS
import 'bootstrap';

console.log('Hermio app loaded with Bootstrap 5');

// Admin sidebar toggle functionality
// Wait for both DOM and Bootstrap to be ready
(function() {
    function initAdminSidebar() {
        const sidebarElement = document.getElementById('adminSidebar');
        if (!sidebarElement) {
            return; // Not on an admin page
        }
        
        // Initialize sidebar on desktop (always visible)
        function initializeSidebar() {
            if (window.innerWidth >= 992) {
                // Desktop: Force sidebar to be visible
                sidebarElement.classList.add('show');
                // Don't set inline styles that would override CSS transitions
                // Only set essential styles
                sidebarElement.style.position = 'fixed';
                
                // Remove any Bootstrap offcanvas width restrictions
                sidebarElement.style.removeProperty('width');
                sidebarElement.style.removeProperty('min-width');
                sidebarElement.style.removeProperty('max-width');
                
                // Remove backdrop if it exists
                const backdrop = document.querySelector('.offcanvas-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                
                // Remove body class that Bootstrap adds for offcanvas
                document.body.classList.remove('offcanvas-open');
            }
        }
        
        // Initialize immediately
        initializeSidebar();
        
        // Re-initialize on window resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(initializeSidebar, 100);
        });
        
        // Prevent Bootstrap from hiding sidebar on desktop
        if (window.innerWidth >= 992 && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
            // Override Bootstrap offcanvas hide behavior on desktop
            const originalHide = bootstrap.Offcanvas.prototype.hide;
            bootstrap.Offcanvas.prototype.hide = function() {
                if (window.innerWidth >= 992 && this._element && this._element.id === 'adminSidebar') {
                    // Don't hide on desktop
                    return;
                }
                return originalHide.call(this);
            };
        }
    
        // Function to update collapse button icons and text
        function updateCollapseButtons(isCollapsed) {
            const collapseButtons = document.querySelectorAll('.sidebar-collapse-btn');
            collapseButtons.forEach(button => {
                const icon = button.querySelector('i');
                if (icon) {
                    if (isCollapsed) {
                        icon.classList.remove('fa-chevron-left');
                        icon.classList.add('fa-chevron-right');
                    } else {
                        icon.classList.remove('fa-chevron-right');
                        icon.classList.add('fa-chevron-left');
                    }
                }
                
                // Update text in footer button
                const collapseText = button.querySelector('.collapse-text');
                if (collapseText) {
                    collapseText.textContent = isCollapsed 
                        ? (button.getAttribute('data-expand-text') || 'Expand')
                        : (button.getAttribute('data-collapse-text') || 'Collapse');
                }
            });
        }
        
        // Get admin layout container
        const adminLayout = document.querySelector('.admin-layout');
        if (!adminLayout) {
            return; // Not on an admin page
        }
        
        // Create or get dynamic style element for sidebar
        let dynamicStyleElement = document.getElementById('admin-sidebar-dynamic-styles');
        if (!dynamicStyleElement) {
            dynamicStyleElement = document.createElement('style');
            dynamicStyleElement.id = 'admin-sidebar-dynamic-styles';
            document.head.appendChild(dynamicStyleElement);
        }
        
        // Function to apply sidebar styles
        function applySidebarStyles(isCollapsed) {
            if (!sidebarElement) {
                console.error('sidebarElement not found');
                return;
            }
            
            const width = isCollapsed ? '80px' : '250px';
            
            // Use dynamic style element to inject CSS with !important
            dynamicStyleElement.textContent = `
                .admin-layout.sidebar-collapsed #adminSidebar,
                body.sidebar-collapsed .admin-layout #adminSidebar {
                    width: ${width} !important;
                    min-width: ${width} !important;
                    max-width: ${width} !important;
                }
                .admin-layout.sidebar-collapsed .admin-content,
                body.sidebar-collapsed .admin-layout .admin-content {
                    margin-left: ${width} !important;
                }
            `;
            
            // Also apply inline styles as backup
            sidebarElement.style.width = width;
            sidebarElement.style.minWidth = width;
            sidebarElement.style.maxWidth = width;
            
            const adminContent = document.querySelector('.admin-content');
            if (adminContent) {
                adminContent.style.marginLeft = width;
            }
            
            console.log('Applied sidebar styles:', { 
                width, 
                isCollapsed, 
                computedWidth: window.getComputedStyle(sidebarElement).width,
                inlineWidth: sidebarElement.style.width
            });
        }
        
        // Load sidebar state from localStorage
        const sidebarCollapsed = localStorage.getItem('hermio-admin-sidebar-collapsed') === 'true';
        if (sidebarCollapsed && window.innerWidth >= 992) {
            adminLayout.classList.add('sidebar-collapsed');
            document.body.classList.add('sidebar-collapsed');
            updateCollapseButtons(true);
            applySidebarStyles(true);
        }
        
        // Function to toggle sidebar collapse
        function toggleSidebarCollapse(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            if (window.innerWidth >= 992 && adminLayout && sidebarElement) {
                const isCollapsed = adminLayout.classList.toggle('sidebar-collapsed');
                document.body.classList.toggle('sidebar-collapsed', isCollapsed);
                localStorage.setItem('hermio-admin-sidebar-collapsed', isCollapsed);
                updateCollapseButtons(isCollapsed);
                applySidebarStyles(isCollapsed);
                
                console.log('Sidebar toggled:', isCollapsed ? 'collapsed' : 'expanded', 
                    'adminLayout:', adminLayout, 
                    'has class:', adminLayout.classList.contains('sidebar-collapsed'),
                    'sidebarElement width:', sidebarElement.style.width);
            }
        }
        
        // Attach event listeners using event delegation (more reliable)
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.sidebar-collapse-btn');
            if (button) {
                console.log('Collapse button clicked');
                toggleSidebarCollapse(e);
            }
        });
        
        // Also attach directly for immediate binding
        setTimeout(function() {
            const collapseButtons = document.querySelectorAll('.sidebar-collapse-btn');
            console.log('Found collapse buttons:', collapseButtons.length);
            collapseButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    console.log('Direct click handler fired');
                    toggleSidebarCollapse(e);
                });
            });
        }, 100);
        
        // Close mobile sidebar on navigation item click
        const sidebarLinks = document.querySelectorAll('#adminSidebar .nav-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992 && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    const offcanvas = bootstrap.Offcanvas.getInstance(sidebarElement);
                    if (offcanvas) {
                        offcanvas.hide();
                    }
                }
            });
            
            // Keyboard navigation support (Enter/Space activation)
            link.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    link.click();
                }
            });
        });
        
        // Focus management for mobile sidebar
        if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
            sidebarElement.addEventListener('shown.bs.offcanvas', function() {
                // Focus first navigation link when sidebar opens
                const firstLink = sidebarElement.querySelector('.nav-link');
                if (firstLink) {
                    firstLink.focus();
                }
            });
            
            sidebarElement.addEventListener('hidden.bs.offcanvas', function() {
                // Return focus to hamburger button when sidebar closes
                const hamburgerButton = document.querySelector('.sidebar-toggle');
                if (hamburgerButton) {
                    hamburgerButton.focus();
                }
            });
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminSidebar);
    } else {
        initAdminSidebar();
    }
    
    // Also try after a short delay to ensure Bootstrap is loaded
    setTimeout(initAdminSidebar, 100);
})();


