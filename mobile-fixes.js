/**
 * MOBILE FIXES - Overrides inline styles
 */

(function() {
    'use strict';
    
    // Hide navigation bar when modals open
    function hideNavWhenModalOpen() {
        const nav = document.getElementById('mobileBottomNav');
        if (!nav) return;
        
        const modals = document.querySelectorAll('.modal, [id*="Modal"]');
        let anyModalOpen = false;
        
        modals.forEach(modal => {
            const style = window.getComputedStyle(modal);
            if (style.display !== 'none') {
                anyModalOpen = true;
            }
        });
        
        if (anyModalOpen) {
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
    
    setInterval(hideNavWhenModalOpen, 100);
    
    // Make modals smaller on mobile
    function makeModalsSmallerOnMobile() {
        if (window.innerWidth > 768) return;
        
        document.querySelectorAll('.modal-content').forEach(content => {
            content.style.maxWidth = '420px';
            content.style.padding = '1.5rem 1.2rem';
        });
        
        document.querySelectorAll('.modal-content h2').forEach(h2 => {
            h2.style.fontSize = '1.3rem';
            h2.style.marginBottom = '1.2rem';
        });
        
        document.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(input => {
            input.style.fontSize = '15px';
            input.style.padding = '0.85rem';
            input.style.minHeight = '46px';
        });
        
        document.querySelectorAll('input[type="date"], #authDOB').forEach(input => {
            input.style.fontSize = '14px';
            input.style.padding = '0.75rem';
            input.style.minHeight = '44px';
        });
        
        document.querySelectorAll('.submit-btn').forEach(btn => {
            btn.style.padding = '0.95rem';
            btn.style.fontSize = '0.95rem';
            btn.style.minHeight = '50px';
        });
        
        const ownerModal = document.querySelector('#ownerModal .modal-content, #ownerAccessModal .modal-content');
        if (ownerModal) {
            ownerModal.style.maxWidth = '380px';
        }
        
        document.querySelectorAll('button[onclick*="googleLogin"], button[onclick*="appleLogin"]').forEach(btn => {
            btn.style.padding = '0.85rem 1rem';
            btn.style.fontSize = '0.9rem';
            btn.style.minHeight = '48px';
        });
    }
    
    document.addEventListener('DOMContentLoaded', makeModalsSmallerOnMobile);
    setInterval(makeModalsSmallerOnMobile, 500);
    
    console.log('✅ Mobile fixes loaded');
})();
