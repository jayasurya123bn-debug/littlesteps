/**
 * Little Steps - Main Application JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-dismiss Flash Messages
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Mobile Sidebar Toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1200 && 
                !sidebar.contains(e.target) && 
                !mobileMenuBtn.contains(e.target) && 
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Modal Handling
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    const modalCloses = document.querySelectorAll('.modal-close, [data-dismiss="modal"]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = trigger.getAttribute('data-target');
            const targetModal = document.querySelector(targetId);
            if (targetModal) {
                targetModal.classList.add('active');
            }
        });
    });

    modalCloses.forEach(close => {
        close.addEventListener('click', (e) => {
            e.preventDefault();
            const modal = close.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
            }
        });
    });

    // Form Validation (Basic Client-Side)
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Check required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Check emails
            const emailFields = form.querySelectorAll('input[type="email"]');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            emailFields.forEach(field => {
                if (field.value && !emailRegex.test(field.value)) {
                    isValid = false;
                    field.classList.add('is-invalid');
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
                
                // Shake animation for invalid form
                form.style.animation = 'shake 0.5s';
                setTimeout(() => form.style.animation = '', 500);
            } else {
                // Show loading state on submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    // Store original text to revert if needed (though usually it redirects)
                    submitBtn.setAttribute('data-original-text', originalText);
                }
            }
        });
        
        // Real-time validation on blur
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                if (input.hasAttribute('required') && !input.value.trim()) {
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                    if (input.type === 'email' && input.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                        input.classList.add('is-invalid');
                    }
                }
            });
        });
    });
});

// Add shake keyframes to document head
const style = document.createElement('style');
style.textContent = `
@keyframes shake {
  0% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  50% { transform: translateX(5px); }
  75% { transform: translateX(-5px); }
  100% { transform: translateX(0); }
}`;
document.head.appendChild(style);

// --- Custom Flying Butterfly Cursor ---
const isTouchDevice = (('ontouchstart' in window) || (navigator.maxTouchPoints > 0));
if (!isTouchDevice && window.innerWidth > 768) {
    const butterflyIcon = '🦋';
    
    const butterfly = document.createElement('div');
    butterfly.innerHTML = butterflyIcon;
    butterfly.style.position = 'fixed';
    butterfly.style.pointerEvents = 'none';
    butterfly.style.zIndex = '999999';
    butterfly.style.fontSize = '24px';
    butterfly.style.textShadow = '0 2px 4px rgba(0,0,0,0.3)';

    // Offset so the pointer is at the center top of the butterfly
    butterfly.style.marginTop = '-4px';
    butterfly.style.marginLeft = '-14px';
    document.body.appendChild(butterfly);

    const flapStyle = document.createElement('style');
    flapStyle.textContent = `
    * { cursor: none !important; }
    @keyframes flyFlap {
        0%, 100% { transform: rotate(-5deg) scaleX(1) translateY(0); }
        50% { transform: rotate(5deg) scaleX(0.4) translateY(-4px); }
    }
    @keyframes rainbowColor {
        0% { filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2)) hue-rotate(0deg); }
        100% { filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2)) hue-rotate(360deg); }
    }
    .flying-butterfly {
        animation: flyFlap 0.3s infinite ease-in-out, rainbowColor 3s linear infinite;
        transform-origin: center center;
    }
    .trail-shadow {
        opacity: 0.3;
        transform: scale(0.8);
        filter: blur(2px) drop-shadow(0 4px 8px rgba(0,0,0,0.2));
    }
    `;
    document.head.appendChild(flapStyle);
    butterfly.classList.add('flying-butterfly');

    // Create the trailing shadow
    const trail = document.createElement('div');
    trail.innerHTML = butterflyIcon;
    trail.style.position = 'fixed';
    trail.style.pointerEvents = 'none';
    trail.style.zIndex = '999998';
    trail.style.fontSize = '24px';

    trail.style.marginTop = '-4px';
    trail.style.marginLeft = '-14px';
    document.body.appendChild(trail);
    trail.classList.add('flying-butterfly', 'trail-shadow');

    // Smooth follow logic using requestAnimationFrame for 60fps
    let mouseX = window.innerWidth / 2;
    let mouseY = window.innerHeight / 2;
    let butterflyX = mouseX;
    let butterflyY = mouseY;
    let trailX = mouseX;
    let trailY = mouseY;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    function animateButterfly() {
        // Linear interpolation for smooth trailing flight
        butterflyX += (mouseX - butterflyX) * 0.4;
        butterflyY += (mouseY - butterflyY) * 0.4;
        
        // Trail follows the butterfly with more lag
        trailX += (butterflyX - trailX) * 0.15;
        trailY += (butterflyY - trailY) * 0.15;
        
        butterfly.style.left = butterflyX + 'px';
        butterfly.style.top = butterflyY + 'px';
        
        trail.style.left = trailX + 'px';
        trail.style.top = trailY + 'px';
        
        requestAnimationFrame(animateButterfly);
    }
    animateButterfly();

    // --- Single Click Burst Effect (Chinna Chinna Butterflies) ---
    document.addEventListener('click', (e) => {
        const numButterflies = 8;
        const colors = ['#E91E63', '#AD1457', '#F50057', '#F8BBD9', '#FF4081', '#9C27B0', '#FFC107', '#4CAF50', '#2196F3'];
        
        for (let i = 0; i < numButterflies; i++) {
            const mini = document.createElement('div');
            mini.innerHTML = butterflyIcon;
            mini.style.position = 'fixed';
            mini.style.pointerEvents = 'none';
            mini.style.zIndex = '999998';
            mini.style.left = e.clientX + 'px';
            mini.style.top = e.clientY + 'px';
            
            // Randomize color for varied look using CSS hue-rotate
            const hue = Math.random() * 360;
            mini.style.filter = `hue-rotate(${hue}deg) drop-shadow(0 2px 4px rgba(0,0,0,0.2))`;
            
            // Randomize size (tiny butterflies)
            const size = Math.random() * 12 + 10; // 10px to 22px
            mini.style.fontSize = size + 'px';
            mini.style.marginTop = -(size/2) + 'px';
            mini.style.marginLeft = -(size/2) + 'px';
            
            // Randomize flight direction and distance
            const angle = Math.random() * Math.PI * 2;
            const velocity = Math.random() * 80 + 50; 
            const tx = Math.cos(angle) * velocity;
            const ty = Math.sin(angle) * velocity - 30; // Fly slightly upwards
            
            const rot = Math.random() * 360;
            
            mini.style.transition = 'transform 0.8s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.8s ease-out';
            mini.style.transform = `translate(0px, 0px) rotate(${rot}deg) scale(0.1)`;
            mini.style.opacity = '1';
            
            document.body.appendChild(mini);
            
            // Trigger reflow
            void mini.offsetWidth;
            
            // Fly away and fade out
            mini.style.transform = `translate(${tx}px, ${ty}px) rotate(${rot + (Math.random() > 0.5 ? 90 : -90)}deg) scale(1.5)`;
            mini.style.opacity = '0';
            
            // Remove from DOM after animation
            setTimeout(() => {
                mini.remove();
            }, 800);
        }
    });
}
