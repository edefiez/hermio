import './styles/home.scss';

// Smooth scroll for anchor links
document.addEventListener('DOMContentLoaded', () => {
    const links = document.querySelectorAll('a[href^="#"]:not([href="#"])');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // FAQ accordion functionality
    const faqItems = document.querySelectorAll('.faq-question');
    
    faqItems.forEach(item => {
        item.addEventListener('click', function() {
            const parent = this.parentElement;
            const isActive = parent.classList.contains('active');
            
            // Close all other items
            document.querySelectorAll('.faq-item').forEach(faqItem => {
                faqItem.classList.remove('active');
            });
            
            // Toggle current item
            if (!isActive) {
                parent.classList.add('active');
            }
        });
    });
});
