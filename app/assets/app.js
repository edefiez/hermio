/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// Import Silktide Consent Manager CSS
import './styles/silktide-consent-manager.css';

// Import Silktide Consent Manager JS
import './silktide-consent-manager.js';

// Import consent configuration
import { initConsentManager } from './consent-config.js';

// Initialize consent manager when DOM is ready.
// Skip initialization on pages with data-no-cookie-consent attribute.
document.addEventListener('DOMContentLoaded', function() {
    // Check if the body has the data-no-cookie-consent attribute
    if (!document.body.hasAttribute('data-no-cookie-consent')) {
        initConsentManager();
    }
});
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

// Import Chart.js for analytics
import Chart from 'chart.js/auto';

// Make Chart.js globally available
window.Chart = Chart;

// Import analytics dashboard JavaScript
import './analytics.js';

console.log('Hermio app loaded with Bootstrap 5');

// Admin sidebar toggle functionality
// Wait for both DOM and Bootstrap to be ready
(function() {
    function initAdminSidebar() {
        const sidebarElement = document.getElementById('adminSidebar');
        if (!sidebarElement) {
            return; // Not on an admin page
        }

        // Get admin layout container
        const adminLayout = document.querySelector('.admin-layout');
        if (!adminLayout) {
            return; // Not on an admin page
        }

        // Initialize sidebar on desktop (always visible)
        function initializeSidebar() {
            const width = window.innerWidth;
            console.log('[Sidebar] Initializing - Window width:', width);

            if (width >= 992) {
                // Desktop: Don't add .show class to avoid triggering Bootstrap events that steal focus
                // The CSS already forces visibility with @media queries
                console.log('[Sidebar] Desktop mode - Using CSS for visibility (no .show class)');

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
            } else {
                // Mobile: Ensure sidebar is hidden
                sidebarElement.classList.remove('show');
                console.log('[Sidebar] Mobile mode - Removed .show class');
                console.log('[Sidebar] Computed styles:', {
                    transform: window.getComputedStyle(sidebarElement).transform,
                    visibility: window.getComputedStyle(sidebarElement).visibility,
                    pointerEvents: window.getComputedStyle(sidebarElement).pointerEvents,
                    zIndex: window.getComputedStyle(sidebarElement).zIndex
                });
            }
        }

        // Create a MutationObserver to prevent Bootstrap from adding inline width styles on desktop
        if (window.innerWidth >= 992) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        // Check if we're on desktop and if width was added inline
                        if (window.innerWidth >= 992 && sidebarElement.style.width &&
                            sidebarElement.style.width !== '80px' && sidebarElement.style.width !== '250px') {
                            console.log('Bootstrap tried to set inline width, removing it:', sidebarElement.style.width);
                            sidebarElement.style.removeProperty('width');
                            sidebarElement.style.removeProperty('min-width');
                            sidebarElement.style.removeProperty('max-width');
                        }
                    }
                });
            });

            // Observe the sidebar element for attribute changes
            observer.observe(sidebarElement, {
                attributes: true,
                attributeFilter: ['style']
            });
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

        // Function to apply sidebar styles
        function applySidebarStyles(isCollapsed) {
            if (!sidebarElement) {
                console.error('sidebarElement not found');
                return;
            }

            // Let CSS handle the styles via classes instead of inline styles
            // Remove any inline width styles to let CSS take over
            sidebarElement.style.removeProperty('width');
            sidebarElement.style.removeProperty('min-width');
            sidebarElement.style.removeProperty('max-width');

            const adminContent = document.querySelector('.admin-content');
            if (adminContent) {
                // Remove inline margin to let CSS take over
                adminContent.style.removeProperty('margin-left');
            }

            // Force a reflow to ensure CSS is applied
            void sidebarElement.offsetWidth;

            console.log('Applied sidebar styles:', {
                isCollapsed,
                computedWidth: window.getComputedStyle(sidebarElement).width,
                hasCollapsedClass: adminLayout.classList.contains('sidebar-collapsed'),
                bodyHasCollapsedClass: document.body.classList.contains('sidebar-collapsed')
            });
        }

        // Load sidebar state from localStorage
        const sidebarCollapsed = localStorage.getItem('hermio-admin-sidebar-collapsed') === 'true';
        if (sidebarCollapsed && window.innerWidth >= 992) {
            adminLayout.classList.add('sidebar-collapsed');
            document.body.classList.add('sidebar-collapsed');
            sidebarElement.classList.add('sidebar-collapsed'); // Add class to sidebar itself
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
                sidebarElement.classList.toggle('sidebar-collapsed', isCollapsed); // Add class to sidebar itself
                localStorage.setItem('hermio-admin-sidebar-collapsed', isCollapsed);
                updateCollapseButtons(isCollapsed);
                applySidebarStyles(isCollapsed);

                console.log('Sidebar toggled:', isCollapsed ? 'collapsed' : 'expanded',
                    'adminLayout:', adminLayout,
                    'has class:', adminLayout.classList.contains('sidebar-collapsed'),
                    'sidebar has class:', sidebarElement.classList.contains('sidebar-collapsed'),
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
        });

        // Focus management for mobile sidebar ONLY
        if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
            sidebarElement.addEventListener('shown.bs.offcanvas', function() {
                // Only manage focus on mobile/tablet, not on desktop
                if (window.innerWidth < 992) {
                    const firstLink = sidebarElement.querySelector('.nav-link');
                    if (firstLink) {
                        firstLink.focus();
                    }
                }
            });

            sidebarElement.addEventListener('hidden.bs.offcanvas', function() {
                // Only manage focus on mobile/tablet, not on desktop
                if (window.innerWidth < 992) {
                    const hamburgerButton = document.querySelector('.sidebar-toggle');
                    if (hamburgerButton) {
                        hamburgerButton.focus();
                    }
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


