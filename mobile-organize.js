/**
 * MOBILE ORGANIZATION - Better spacing and layout
 */

(function() {
    'use strict';
    
    // ============================================
    // 1. HIDE NAVIGATION WHEN MODALS OPEN
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
    // 2. ORGANIZE AND CLEAN MOBILE LAYOUT
    // ============================================
    function organizeOnMobile() {
        if (window.innerWidth > 768) return;
        
        // REMOVE EXCESSIVE SPACING
        document.body.style.padding = '0';
        document.body.style.margin = '0';
        
        // MODAL ORGANIZATION
        document.querySelectorAll('.modal-content').forEach(modal => {
            modal.style.maxWidth = '92%';
            modal.style.width = '92%';
            modal.style.padding = '1.3rem 1rem';
            modal.style.margin = '1rem auto';
        });
        
        // MODAL TITLES - Compact
        document.querySelectorAll('.modal-content h2').forEach(h2 => {
            h2.style.fontSize = '1.25rem';
            h2.style.marginBottom = '1rem';
            h2.style.marginTop = '0';
            h2.style.paddingTop = '0';
        });
        
        // FORM GROUPS - Better spacing
        document.querySelectorAll('.form-group').forEach(group => {
            group.style.marginBottom = '0.9rem';
        });
        
        // FORM LABELS - Compact
        document.querySelectorAll('.form-group label').forEach(label => {
            label.style.fontSize = '0.8rem';
            label.style.marginBottom = '0.35rem';
            label.style.display = 'block';
        });
        
        // ALL INPUTS - Clean and organized
        document.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(input => {
            input.style.fontSize = '15px';
            input.style.padding = '0.75rem 0.9rem';
            input.style.minHeight = '44px';
            input.style.width = '100%';
            input.style.boxSizing = 'border-box';
            input.style.marginBottom = '0';
        });
        
        // DATE INPUT - Organized
        document.querySelectorAll('input[type="date"], #authDOB').forEach(input => {
            input.style.fontSize = '14px';
            input.style.padding = '0.7rem 0.9rem';
            input.style.minHeight = '44px';
            input.style.height = '44px';
        });
        
        // BUTTONS - Well spaced
        document.querySelectorAll('.submit-btn, button[type="submit"]').forEach(btn => {
            btn.style.padding = '0.9rem';
            btn.style.fontSize = '0.93rem';
            btn.style.minHeight = '48px';
            btn.style.width = '100%';
            btn.style.marginTop = '0.5rem';
        });
        
        // SOCIAL LOGIN BUTTONS - Organized
        document.querySelectorAll('button[onclick*="googleLogin"], button[onclick*="appleLogin"]').forEach(btn => {
            btn.style.padding = '0.8rem';
            btn.style.fontSize = '0.88rem';
            btn.style.minHeight = '46px';
            btn.style.marginBottom = '0.7rem';
        });
        
        // FOOD CARDS - Better organization
        document.querySelectorAll('.food-card').forEach(card => {
            card.style.padding = '1rem';
            card.style.marginBottom = '0.8rem';
            card.style.display = 'flex';
            card.style.gap = '0.9rem';
        });
        
        // FOOD CARD IMAGE - Clean
        document.querySelectorAll('.food-card .food-image').forEach(img => {
            img.style.width = '85px';
            img.style.height = '85px';
            img.style.flexShrink = '0';
        });
        
        // FOOD CARD INFO - Organized
        document.querySelectorAll('.food-card .food-info').forEach(info => {
            info.style.flex = '1';
            info.style.display = 'flex';
            info.style.flexDirection = 'column';
        });
        
        // FOOD NAME - Clean
        document.querySelectorAll('.food-card .food-name').forEach(name => {
            name.style.fontSize = '1rem';
            name.style.marginBottom = '0.4rem';
            name.style.lineHeight = '1.3';
        });
        
        // FOOD DESCRIPTION - Compact
        document.querySelectorAll('.food-card .food-desc').forEach(desc => {
            desc.style.fontSize = '0.85rem';
            desc.style.lineHeight = '1.4';
            desc.style.marginBottom = '0.5rem';
        });
        
        // FOOD PRICE - Clear
        document.querySelectorAll('.food-card .food-price').forEach(price => {
            price.style.fontSize = '1.15rem';
            price.style.fontWeight = '700';
        });
        
        // FOOD MODAL - Well organized
        const foodModal = document.querySelector('#foodItemModal .modal-content');
        if (foodModal) {
            foodModal.style.padding = '1.2rem 1rem';
        }
        
        document.querySelectorAll('#foodItemModal .food-image').forEach(img => {
            img.style.width = '80px';
            img.style.height = '80px';
            img.style.fontSize = '2.8rem';
            img.style.marginBottom = '0.8rem';
        });
        
        // QUANTITY CONTROLS - Organized
        document.querySelectorAll('.quantity-control').forEach(control => {
            control.style.display = 'flex';
            control.style.alignItems = 'center';
            control.style.gap = '0.8rem';
            control.style.padding = '0.8rem';
            control.style.justifyContent = 'center';
        });
        
        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.style.width = '44px';
            btn.style.height = '44px';
            btn.style.fontSize = '1.3rem';
            btn.style.display = 'flex';
            btn.style.alignItems = 'center';
            btn.style.justifyContent = 'center';
        });
        
        document.querySelectorAll('.qty-display').forEach(display => {
            display.style.fontSize = '1.4rem';
            display.style.minWidth = '45px';
            display.style.textAlign = 'center';
        });
        
        // OWNER MODAL - Compact and organized
        document.querySelectorAll('#ownerModal .modal-content, #ownerAccessModal .modal-content').forEach(modal => {
            modal.style.padding = '1.2rem 1rem';
            modal.style.maxWidth = '90%';
        });
        
        document.querySelectorAll('#ownerModal h2, #ownerAccessModal h2').forEach(h2 => {
            h2.style.fontSize = '1.2rem';
            h2.style.marginBottom = '1rem';
        });
        
        document.querySelectorAll('#ownerModal input, #ownerAccessModal input').forEach(input => {
            input.style.fontSize = '14px';
            input.style.padding = '0.75rem';
            input.style.minHeight = '44px';
        });
        
        // OWNER DASHBOARD - Better organization
        const ownerDash = document.querySelector('#ownerDashboard');
        if (ownerDash) {
            ownerDash.style.padding = '1rem';
            
            // Remove excessive spacing
            ownerDash.querySelectorAll('*').forEach(el => {
                const computed = window.getComputedStyle(el);
                if (parseFloat(computed.marginTop) > 20) {
                    el.style.marginTop = '1rem';
                }
                if (parseFloat(computed.marginBottom) > 20) {
                    el.style.marginBottom = '1rem';
                }
                if (parseFloat(computed.paddingTop) > 20) {
                    el.style.paddingTop = '0.8rem';
                }
                if (parseFloat(computed.paddingBottom) > 20) {
                    el.style.paddingBottom = '0.8rem';
                }
            });
            
            // Organize titles
            ownerDash.querySelectorAll('h2, h3').forEach(h => {
                h.style.fontSize = '1.1rem';
                h.style.marginBottom = '0.7rem';
                h.style.marginTop = '1rem';
            });
            
            // Organize content
            ownerDash.querySelectorAll('p, span').forEach(el => {
                el.style.fontSize = '0.88rem';
                el.style.lineHeight = '1.5';
            });
            
            // Organize buttons
            ownerDash.querySelectorAll('button').forEach(btn => {
                btn.style.fontSize = '0.88rem';
                btn.style.padding = '0.7rem 1rem';
                btn.style.marginBottom = '0.5rem';
            });
        }
        
        // RESTAURANT DASHBOARD - Better organization
        const restDash = document.querySelector('#restaurantLoginModal, #restaurantDashboard');
        if (restDash) {
            restDash.style.padding = '1rem';
            
            // Clean spacing
            restDash.querySelectorAll('*').forEach(el => {
                const computed = window.getComputedStyle(el);
                if (parseFloat(computed.margin) > 20) {
                    el.style.margin = '1rem auto';
                }
            });
            
            // Organize headings
            restDash.querySelectorAll('h2, h3').forEach(h => {
                h.style.fontSize = '1.1rem';
                h.style.marginBottom = '0.8rem';
            });
            
            // Organize inputs
            restDash.querySelectorAll('input').forEach(input => {
                input.style.fontSize = '14px';
                input.style.padding = '0.75rem';
                input.style.marginBottom = '0.7rem';
            });
        }
        
        // CATEGORIES - Clean layout
        document.querySelectorAll('.category-item').forEach(cat => {
            cat.style.padding = '0.7rem 0.5rem';
            cat.style.margin = '0';
        });
        
        document.querySelectorAll('.category-icon').forEach(icon => {
            icon.style.marginBottom = '0.3rem';
        });
        
        document.querySelectorAll('.category-name').forEach(name => {
            name.style.marginTop = '0.3rem';
        });
        
        // CLOSE BUTTONS - Organized
        document.querySelectorAll('.close-btn').forEach(btn => {
            btn.style.position = 'absolute';
            btn.style.top = '0.8rem';
            btn.style.right = '0.8rem';
            btn.style.width = '38px';
            btn.style.height = '38px';
        });
        
        // REMOVE DOUBLE SPACING
        document.querySelectorAll('br + br').forEach(br => {
            br.style.display = 'none';
        });
        
        // PARAGRAPHS - Clean spacing
        document.querySelectorAll('p').forEach(p => {
            const computed = window.getComputedStyle(p);
            if (parseFloat(computed.marginBottom) > 20) {
                p.style.marginBottom = '0.8rem';
            }
        });
    }
    
    // Run immediately and periodically
    document.addEventListener('DOMContentLoaded', organizeOnMobile);
    setInterval(organizeOnMobile, 500);
    window.addEventListener('resize', organizeOnMobile);
    
    console.log('✅ Mobile organized');
})();
