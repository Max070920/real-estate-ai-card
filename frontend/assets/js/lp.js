/**
 * Landing Page JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Hero Swiper
    const heroSwiper = new Swiper('.hero-swiper', {
        slidesPerView: 1,
        spaceBetween: 0,
        loop: true,
        autoplay: {
            delay: 10000, // 10 seconds
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        effect: 'fade',
        fadeEffect: {
            crossFade: true
        },
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Fixed CTA button in header (if needed)
    const headerCTA = document.querySelector('.header-cta');
    if (headerCTA) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                headerCTA.classList.add('visible');
            } else {
                headerCTA.classList.remove('visible');
            }
        });
    }
    
    // Animation on scroll (optional)
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe feature sections
    document.querySelectorAll('.feature-section, .tool-card, .step-card, .testimonial-card').forEach(el => {
        observer.observe(el);
    });
    
    // Newsletter form submission
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('newsletter-email').value;
            // TODO: Implement newsletter subscription API
            alert('メールアドレスを登録しました: ' + email);
            newsletterForm.reset();
        });
    }
});

