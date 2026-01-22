/**
 * MOBILE FIXES - Navigation + Smaller Sizes
 */

(function() {
    'use strict';
    
    // ============================================
    // HIDE NAVIGATION WHEN MODALS OPEN
    // ============================================
    function hideNav() {
        const nav = document.getElementById('mobileBottomNav');
        if (!nav) return;
        
        const modals = document.querySelectorAll('.modal, [id*="Modal"]');
        let anyOpen = false;
        
        modals.forEach(modal => {
            if (window.getComputedStyle(modal).display !== 'none') {
                anyOpen = true;
            }
        });
        
        if (anyOpen) {
            nav.style.transform = 'translateY(100%)';
            nav.style.opacity = '0';
            nav.style.pointerEvents = 'none';
            document.body.style.paddingBottom = '0';
        } else {
            nav.style.transform = 'translateY(0)';
            nav.style.opacity = '1';
            nav.style.pointerEvents = 'auto';
            if (window.innerWidth <= 768) {
                document.body.style.paddingBottom = '88px';
            }
        }
    }
    
    setInterval(hideNav, 100);
    
    // ============================================
    // MAKE EVERYTHING SMALLER
    // ============================================
    function makeSmallerOnMobile() {
        if (window.innerWidth > 768) return;
        
        // ALL MODALS
        document.querySelectorAll('.modal-content').forEach(el => {
            el.style.maxWidth = '385px';
            el.style.padding = '1.1rem 0.95rem';
        });
        
        // MODAL TITLES
        document.querySelectorAll('.modal-content h2').forEach(el => {
            el.style.fontSize = '1.15rem';
            el.style.marginBottom = '0.9rem';
        });
        
        // ALL INPUTS
        document.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(el => {
            el.style.fontSize = '14px';
            el.style.padding = '0.7rem';
            el.style.minHeight = '43px';
        });
        
        // DATE OF BIRTH - VERY SMALL
        document.querySelectorAll('input[type="date"], #authDOB').forEach(el => {
            el.style.fontSize = '13px';
            el.style.padding = '0.6rem';
            el.style.minHeight = '40px';
            el.style.height = '40px';
        });
        
        // FORM LABELS
        document.querySelectorAll('.form-group label').forEach(el => {
            el.style.fontSize = '0.73rem';
        });
        
        // ALL BUTTONS
        document.querySelectorAll('.submit-btn, button[type="submit"]').forEach(el => {
            el.style.padding = '0.8rem';
            el.style.fontSize = '0.88rem';
            el.style.minHeight = '46px';
        });
        
        // SOCIAL BUTTONS
        document.querySelectorAll('button[onclick*="googleLogin"], button[onclick*="appleLogin"]').forEach(el => {
            el.style.padding = '0.7rem';
            el.style.fontSize = '0.83rem';
            el.style.minHeight = '44px';
        });
        
        // FOOD MODAL - VERY SMALL
        const foodModal = document.querySelector('#foodItemModal .modal-content');
        if (foodModal) {
            foodModal.style.maxWidth = '370px';
            foodModal.style.padding = '0.95rem 0.85rem';
        }
        
        document.querySelectorAll('#foodItemModal h2').forEach(el => {
            el.style.fontSize = '1.1rem';
        });
        
        document.querySelectorAll('#foodItemModal .food-image').forEach(el => {
            el.style.width = '75px';
            el.style.height = '75px';
            el.style.fontSize = '2.6rem';
        });
        
        document.querySelectorAll('#foodItemModal p').forEach(el => {
            el.style.fontSize = '0.78rem';
        });
        
        // OWNER ACCESS - VERY SMALL
        document.querySelectorAll('#ownerModal .modal-content, #ownerAccessModal .modal-content').forEach(el => {
            el.style.maxWidth = '350px';
            el.style.padding = '1rem 0.85rem';
        });
        
        document.querySelectorAll('#ownerModal input, #ownerAccessModal input').forEach(el => {
            el.style.fontSize = '13px';
            el.style.padding = '0.65rem';
            el.style.minHeight = '40px';
        });
        
        document.querySelectorAll('#ownerModal h2, #ownerAccessModal h2').forEach(el => {
            el.style.fontSize = '1.1rem';
        });
        
        // OWNER DASHBOARD - VERY SMALL
        const ownerDash = document.querySelector('#ownerDashboard');
        if (ownerDash) {
            ownerDash.querySelectorAll('h2, h3').forEach(el => {
                el.style.fontSize = '1.05rem';
            });
            
            ownerDash.querySelectorAll('p, span, div').forEach(el => {
                const size = parseFloat(window.getComputedStyle(el).fontSize);
                if (size > 14) {
                    el.style.fontSize = '0.82rem';
                }
            });
            
            ownerDash.querySelectorAll('button').forEach(el => {
                el.style.fontSize = '0.82rem';
                el.style.padding = '0.65rem 0.9rem';
                el.style.minHeight = '40px';
            });
        }
        
        // RESTAURANT DASHBOARD - VERY SMALL
        const restDash = document.querySelector('#restaurantLoginModal');
        if (restDash) {
            restDash.querySelectorAll('h2, h3').forEach(el => {
                el.style.fontSize = '1.05rem';
            });
            
            restDash.querySelectorAll('input').forEach(el => {
                el.style.fontSize = '13px';
                el.style.padding = '0.65rem';
                el.style.minHeight = '40px';
            });
            
            restDash.querySelectorAll('button').forEach(el => {
                el.style.fontSize = '0.82rem';
                el.style.padding = '0.65rem';
            });
        }
        
        // CLOSE BUTTONS
        document.querySelectorAll('.close-btn').forEach(el => {
            el.style.width = '36px';
            el.style.height = '36px';
            el.style.fontSize = '1.2rem';
        });
    }
    
    document.addEventListener('DOMContentLoaded', makeSmallerOnMobile);
    setInterval(makeSmallerOnMobile, 500);
    window.addEventListener('resize', makeSmallerOnMobile);
    
    console.log('✅ Mobile fixes active');
})();
