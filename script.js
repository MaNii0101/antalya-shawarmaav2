// ANTALYA SHAWARMA - Main Application
// ====================================

// DATA
let currentUser = null;
let cart = [];
let favorites = [];
let orders = [];
let notifications = [];
let currentCategory = 'grill_wraps';
let selectedFood = null;
let foodQty = 1;
let selectedOptions = [];
let selectedLocation = null;
let isSignUp = false;

// MENU DATA
const categories = {
    grill_wraps: { name: 'Grill Wraps', icon: '🌯' },
    grill_naan: { name: 'Grill Naan', icon: '🫓' },
    burgers: { name: 'Burgers', icon: '🍔' },
    loaded_fries: { name: 'Loaded Fries', icon: '🍟' },
    platters: { name: 'Platters', icon: '🍽️' },
    kids: { name: 'Kids', icon: '👶' },
    extras: { name: 'Extras', icon: '➕' },
    drinks: { name: 'Drinks', icon: '🥤' }
};

const menuData = {
    grill_wraps: [
        { id: 1, name: 'Mix Grill Wrap', desc: 'Served with salad and sauce', price: 9.00, icon: '🌯' },
        { id: 2, name: 'Chicken Grill Wrap', desc: 'Served with salad and sauce', price: 7.50, icon: '🌯' },
        { id: 3, name: 'Lamb Grill Wrap', desc: 'Lamb back strap fillet with salad and naan', price: 9.50, icon: '🌯' },
        { id: 4, name: 'Kofte Wrap', desc: 'Homemade kofte with salad and sauce', price: 7.50, icon: '🌯' }
    ],
    grill_naan: [
        { id: 10, name: 'Chicken Tikka Naan', desc: 'Grilled chicken tikka in fresh naan', price: 8.50, icon: '🫓' },
        { id: 11, name: 'Lamb Seekh Naan', desc: 'Spiced lamb seekh kebab in naan', price: 9.00, icon: '🫓' },
        { id: 12, name: 'Mix Grill Naan', desc: 'Selection of grilled meats in naan', price: 10.50, icon: '🫓' }
    ],
    burgers: [
        { id: 20, name: 'Classic Burger', desc: 'Beef patty with lettuce, tomato, onion', price: 7.00, icon: '🍔' },
        { id: 21, name: 'Chicken Burger', desc: 'Grilled chicken breast burger', price: 7.00, icon: '🍔' },
        { id: 22, name: 'Smash Burger', desc: 'Double smashed beef patties', price: 8.50, icon: '🍔' }
    ],
    loaded_fries: [
        { id: 30, name: 'Chicken Loaded Fries', desc: 'Fries topped with chicken and sauce', price: 7.50, icon: '🍟' },
        { id: 31, name: 'Lamb Loaded Fries', desc: 'Fries topped with lamb and cheese', price: 8.50, icon: '🍟' },
        { id: 32, name: 'Mix Loaded Fries', desc: 'Fries with mixed meats and toppings', price: 9.50, icon: '🍟' }
    ],
    platters: [
        { id: 40, name: 'Family Platter', desc: 'Mixed grill for 4 with sides', price: 35.00, icon: '🍽️' },
        { id: 41, name: 'Chicken Platter', desc: 'Assorted chicken dishes with rice', price: 15.00, icon: '🍽️' },
        { id: 42, name: 'Mix Grill Platter', desc: 'Selection of grilled meats with sides', price: 18.00, icon: '🍽️' }
    ],
    kids: [
        { id: 50, name: 'Kids Chicken Nuggets', desc: '6 nuggets with fries', price: 5.50, icon: '🍗' },
        { id: 51, name: 'Kids Burger', desc: 'Small burger with fries', price: 5.50, icon: '🍔' },
        { id: 52, name: 'Kids Wrap', desc: 'Small chicken wrap with fries', price: 5.50, icon: '🌯' }
    ],
    extras: [
        { id: 60, name: 'Regular Fries', desc: 'Crispy golden fries', price: 3.00, icon: '🍟' },
        { id: 61, name: 'Cheese Fries', desc: 'Fries with melted cheese', price: 4.00, icon: '🍟' },
        { id: 62, name: 'Onion Rings', desc: 'Crispy battered onion rings', price: 3.50, icon: '🧅' },
        { id: 63, name: 'Hummus', desc: 'Creamy chickpea dip', price: 3.00, icon: '🥣' }
    ],
    drinks: [
        { id: 70, name: 'Coca Cola', desc: '330ml can', price: 1.50, icon: '🥤' },
        { id: 71, name: 'Fanta', desc: '330ml can', price: 1.50, icon: '🥤' },
        { id: 72, name: 'Water', desc: '500ml bottle', price: 1.00, icon: '💧' },
        { id: 73, name: 'Ayran', desc: 'Traditional yogurt drink', price: 2.00, icon: '🥛' }
    ]
};

// INIT
document.addEventListener('DOMContentLoaded', () => {
    loadData();
    renderCategories();
    renderMenu(currentCategory);
    updateBadges();
    updateLoginBtn();
});

// STORAGE
function loadData() {
    try {
        currentUser = JSON.parse(localStorage.getItem('as_user'));
        cart = JSON.parse(localStorage.getItem('as_cart')) || [];
        favorites = JSON.parse(localStorage.getItem('as_favorites')) || [];
        orders = JSON.parse(localStorage.getItem('as_orders')) || [];
        notifications = JSON.parse(localStorage.getItem('as_notifications')) || [];
    } catch(e) { console.error('Load error:', e); }
}

function saveData() {
    localStorage.setItem('as_user', JSON.stringify(currentUser));
    localStorage.setItem('as_cart', JSON.stringify(cart));
    localStorage.setItem('as_favorites', JSON.stringify(favorites));
    localStorage.setItem('as_orders', JSON.stringify(orders));
    localStorage.setItem('as_notifications', JSON.stringify(notifications));
}

// UTILS
function formatPrice(p) { return '£' + p.toFixed(2); }

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showToast(msg) {
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;bottom:100px;left:50%;transform:translateX(-50%);background:#27ae60;color:#fff;padding:12px 24px;border-radius:8px;z-index:99999;font-weight:500;animation:fadeIn .3s';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
}

function updateBadges() {
    const cartCount = cart.reduce((sum, i) => sum + i.qty, 0);
    const notifCount = notifications.filter(n => !n.read).length;
    
    document.querySelectorAll('#cartBadge, #mCartBadge').forEach(el => {
        el.textContent = cartCount;
        el.style.display = cartCount > 0 ? 'flex' : 'none';
    });
    
    document.querySelectorAll('#notifBadge, #mNotifBadge').forEach(el => {
        el.textContent = notifCount;
        el.style.display = notifCount > 0 ? 'flex' : 'none';
    });
}

function updateLoginBtn() {
    const btn = document.getElementById('headerLoginBtn');
    if (currentUser) {
        btn.textContent = currentUser.name ? currentUser.name.split(' ')[0] : 'Account';
        btn.onclick = showAccount;
    } else {
        btn.textContent = 'Login';
        btn.onclick = showLogin;
    }
}

// MODALS
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal') && e.target.classList.contains('active')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// CATEGORIES
function renderCategories() {
    const container = document.getElementById('categoriesList');
    container.innerHTML = '';
    
    Object.entries(categories).forEach(([key, cat], idx) => {
        const btn = document.createElement('button');
        btn.className = `cat-btn ${idx === 0 ? 'active' : ''}`;
        btn.onclick = () => selectCategory(key);
        btn.innerHTML = `<span class="icon">${cat.icon}</span><span>${cat.name}</span>`;
        container.appendChild(btn);
    });
}

function selectCategory(key) {
    currentCategory = key;
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    event.target.closest('.cat-btn').classList.add('active');
    renderMenu(key);
}

// MENU
function renderMenu(category) {
    const grid = document.getElementById('menuGrid');
    const title = document.getElementById('menuTitle');
    const cat = categories[category];
    const items = menuData[category] || [];
    
    title.textContent = cat.name;
    grid.innerHTML = '';
    
    items.forEach(item => {
        const isFav = favorites.includes(item.id);
        const card = document.createElement('div');
        card.className = 'food-card';
        card.innerHTML = `
            <div class="food-card-info">
                <div class="food-card-name">${item.name}</div>
                <div class="food-card-desc">${item.desc}</div>
                <div class="food-card-bottom">
                    <span class="food-card-price">${formatPrice(item.price)}</span>
                    <button class="btn-add" onclick="event.stopPropagation();openFoodModal(${item.id})">Add</button>
                </div>
            </div>
            <div class="food-card-img">
                <div class="emoji">${item.icon}</div>
                <button class="btn-fav" onclick="event.stopPropagation();toggleFav(${item.id})">${isFav ? '❤️' : '🤍'}</button>
            </div>
        `;
        card.onclick = () => openFoodModal(item.id);
        grid.appendChild(card);
    });
}

// FOOD MODAL
function findFood(id) {
    for (const cat of Object.values(menuData)) {
        const found = cat.find(i => i.id === id);
        if (found) return found;
    }
    return null;
}

function openFoodModal(id) {
    const food = findFood(id);
    if (!food) return;
    
    selectedFood = food;
    foodQty = 1;
    selectedOptions = [];
    
    document.getElementById('foodModalImg').innerHTML = `<div class="emoji">${food.icon}</div>`;
    document.getElementById('foodModalName').textContent = food.name;
    document.getElementById('foodModalDesc').textContent = food.desc;
    document.getElementById('foodModalPrice').textContent = formatPrice(food.price);
    document.getElementById('foodQty').textContent = '1';
    document.getElementById('foodTotal').textContent = formatPrice(food.price);
    document.getElementById('foodModalOptions').innerHTML = '';
    
    openModal('foodModal');
}

function changeQty(delta) {
    foodQty = Math.max(1, foodQty + delta);
    document.getElementById('foodQty').textContent = foodQty;
    updateFoodTotal();
}

function updateFoodTotal() {
    const total = selectedFood.price * foodQty;
    document.getElementById('foodTotal').textContent = formatPrice(total);
}

function addToCart() {
    if (!selectedFood) return;
    
    const existing = cart.find(i => i.id === selectedFood.id);
    if (existing) {
        existing.qty += foodQty;
    } else {
        cart.push({
            id: selectedFood.id,
            name: selectedFood.name,
            price: selectedFood.price,
            icon: selectedFood.icon,
            qty: foodQty
        });
    }
    
    saveData();
    updateBadges();
    closeModal('foodModal');
    showToast(`${selectedFood.name} added to cart`);
}

// FAVORITES
function toggleFav(id) {
    const idx = favorites.indexOf(id);
    if (idx > -1) {
        favorites.splice(idx, 1);
    } else {
        favorites.push(id);
    }
    saveData();
    renderMenu(currentCategory);
}

function showFavorites() {
    const content = document.getElementById('favoritesContent');
    
    if (favorites.length === 0) {
        content.innerHTML = `<div class="empty"><div class="icon">❤️</div><p>No saved items yet</p></div>`;
    } else {
        content.innerHTML = favorites.map(id => {
            const food = findFood(id);
            if (!food) return '';
            return `
                <div class="fav-item">
                    <div class="fav-item-img">${food.icon}</div>
                    <div class="fav-item-info">
                        <div class="fav-item-name">${food.name}</div>
                        <div class="fav-item-price">${formatPrice(food.price)}</div>
                        <div class="fav-item-btns">
                            <button class="add" onclick="quickAddToCart(${food.id})">Add to Cart</button>
                            <button class="remove" onclick="toggleFav(${food.id});showFavorites()">Remove</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    openModal('favoritesModal');
}

function quickAddToCart(id) {
    const food = findFood(id);
    if (!food) return;
    
    const existing = cart.find(i => i.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ id: food.id, name: food.name, price: food.price, icon: food.icon, qty: 1 });
    }
    
    saveData();
    updateBadges();
    showToast(`${food.name} added to cart`);
}

// CART
function showCart() {
    renderCart();
    openModal('cartModal');
}

function renderCart() {
    const content = document.getElementById('cartContent');
    
    if (cart.length === 0) {
        content.innerHTML = `<div class="empty"><div class="icon">🛒</div><p>Your cart is empty</p></div>`;
        return;
    }
    
    const subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
    const delivery = 2.50;
    const total = subtotal + delivery;
    
    content.innerHTML = `
        ${cart.map(item => `
            <div class="cart-item">
                <div class="cart-item-img">${item.icon}</div>
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">${formatPrice(item.price * item.qty)}</div>
                </div>
                <div class="cart-item-qty">
                    <button onclick="updateCartQty(${item.id}, -1)">−</button>
                    <span>${item.qty}</span>
                    <button onclick="updateCartQty(${item.id}, 1)">+</button>
                </div>
            </div>
        `).join('')}
        <div class="cart-summary">
            <div class="cart-row"><span>Subtotal</span><span>${formatPrice(subtotal)}</span></div>
            <div class="cart-row"><span>Delivery</span><span>${formatPrice(delivery)}</span></div>
            <div class="cart-row total"><span>Total</span><span>${formatPrice(total)}</span></div>
        </div>
        <button class="btn-checkout" onclick="proceedToCheckout()">Checkout ${formatPrice(total)}</button>
    `;
}

function updateCartQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    
    item.qty += delta;
    if (item.qty <= 0) {
        cart = cart.filter(i => i.id !== id);
    }
    
    saveData();
    updateBadges();
    renderCart();
}

// CHECKOUT
function proceedToCheckout() {
    if (!currentUser) {
        closeModal('cartModal');
        showLogin();
        return;
    }
    
    closeModal('cartModal');
    
    const subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
    const delivery = 2.50;
    const total = subtotal + delivery;
    
    document.getElementById('checkoutContent').innerHTML = `
        <div class="field">
            <label>Delivery Address</label>
            <input type="text" id="checkoutAddress" value="${currentUser.address || ''}" placeholder="Enter delivery address" onclick="pickLocation()">
        </div>
        <div class="field">
            <label>Phone Number</label>
            <input type="tel" id="checkoutPhone" value="${currentUser.phone || ''}" placeholder="Your phone number">
        </div>
        <div class="field">
            <label>Notes (optional)</label>
            <textarea id="checkoutNotes" rows="2" placeholder="Any special instructions"></textarea>
        </div>
        <div class="cart-summary">
            <div class="cart-row"><span>Subtotal</span><span>${formatPrice(subtotal)}</span></div>
            <div class="cart-row"><span>Delivery</span><span>${formatPrice(delivery)}</span></div>
            <div class="cart-row total"><span>Total</span><span>${formatPrice(total)}</span></div>
        </div>
        <button class="btn-checkout" onclick="placeOrder()">Place Order</button>
    `;
    
    openModal('checkoutModal');
}

function placeOrder() {
    const address = document.getElementById('checkoutAddress').value;
    const phone = document.getElementById('checkoutPhone').value;
    const notes = document.getElementById('checkoutNotes').value;
    
    if (!address) {
        alert('Please enter delivery address');
        return;
    }
    
    const subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
    const delivery = 2.50;
    const total = subtotal + delivery;
    
    const order = {
        id: 'ORD' + Date.now(),
        oderId: currentUser.email,
        items: [...cart],
        subtotal,
        delivery,
        total,
        address,
        phone,
        notes,
        status: 'pending',
        createdAt: new Date().toISOString()
    };
    
    orders.push(order);
    cart = [];
    
    notifications.push({
        id: Date.now(),
        title: 'Order Placed',
        message: `Your order #${order.id} has been placed successfully.`,
        time: new Date().toISOString(),
        read: false
    });
    
    saveData();
    updateBadges();
    closeModal('checkoutModal');
    
    alert('Order placed successfully!');
    showOrderHistory();
}

// ORDER HISTORY
function showOrderHistory() {
    const content = document.getElementById('historyContent');
    const userOrders = orders.filter(o => o.userId === currentUser?.email).reverse();
    
    if (userOrders.length === 0) {
        content.innerHTML = `<div class="empty"><div class="icon">📋</div><p>No orders yet</p></div>`;
    } else {
        content.innerHTML = userOrders.map(order => {
            const canCancel = order.status === 'pending';
            const itemsList = order.items.map(i => `${i.qty}x ${i.name}`).join(', ');
            
            return `
                <div class="order-card">
                    <div class="order-top">
                        <span class="order-id">#${order.id}</span>
                        <span class="order-status ${order.status}">${order.status}</span>
                    </div>
                    <div class="order-items">${itemsList}</div>
                    <div class="order-total">${formatPrice(order.total)}</div>
                    <div class="order-time">${new Date(order.createdAt).toLocaleString()}</div>
                    ${canCancel ? `<button class="btn-cancel" onclick="cancelOrder('${order.id}')">Cancel Order</button>` : ''}
                    ${order.status === 'cancelled' ? `<div style="margin-top:10px;color:#e74c3c;font-size:0.85rem">Cancelled on ${new Date(order.cancelledAt).toLocaleString()}</div>` : ''}
                </div>
            `;
        }).join('');
    }
    
    openModal('historyModal');
}

// CANCEL ORDER
function cancelOrder(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (!order) return;
    
    if (order.status !== 'pending') {
        alert('Order can only be cancelled before restaurant accepts it.');
        return;
    }
    
    if (!confirm('Are you sure you want to cancel this order?')) return;
    
    order.status = 'cancelled';
    order.cancelledAt = new Date().toISOString();
    
    notifications.push({
        id: Date.now(),
        title: 'Order Cancelled',
        message: `Your order #${orderId} has been cancelled.`,
        time: new Date().toISOString(),
        read: false
    });
    
    saveData();
    updateBadges();
    showOrderHistory();
}

// NOTIFICATIONS
function showNotifications() {
    const content = document.getElementById('notificationsContent');
    
    if (notifications.length === 0) {
        content.innerHTML = `<div class="empty"><div class="icon">🔔</div><p>No notifications</p></div>`;
    } else {
        content.innerHTML = notifications.slice().reverse().map(n => `
            <div class="notif-item ${n.read ? '' : 'unread'}" onclick="markNotifRead(${n.id})">
                <div class="notif-title">${n.title}</div>
                <div class="notif-msg">${n.message}</div>
                <div class="notif-time">${new Date(n.time).toLocaleString()}</div>
            </div>
        `).join('');
    }
    openModal('notificationsModal');
}

function markNotifRead(id) {
    const notif = notifications.find(n => n.id === id);
    if (notif) notif.read = true;
    saveData();
    updateBadges();
}

// LOGIN
function showLogin() {
    isSignUp = false;
    document.getElementById('authTitle').textContent = 'Login';
    document.getElementById('authBtn').textContent = 'Login';
    document.getElementById('authSwitchText').textContent = 'No account?';
    document.getElementById('authSwitchLink').textContent = 'Sign Up';
    document.getElementById('nameField').style.display = 'none';
    document.getElementById('phoneField').style.display = 'none';
    document.getElementById('addressField').style.display = 'none';
    document.getElementById('authForm').reset();
    openModal('loginModal');
}

function toggleAuthMode() {
    isSignUp = !isSignUp;
    if (isSignUp) {
        document.getElementById('authTitle').textContent = 'Sign Up';
        document.getElementById('authBtn').textContent = 'Create Account';
        document.getElementById('authSwitchText').textContent = 'Have account?';
        document.getElementById('authSwitchLink').textContent = 'Login';
        document.getElementById('nameField').style.display = 'block';
        document.getElementById('phoneField').style.display = 'block';
        document.getElementById('addressField').style.display = 'block';
    } else {
        showLogin();
    }
}

function handleAuth(e) {
    e.preventDefault();
    
    const email = document.getElementById('authEmail').value;
    const password = document.getElementById('authPassword').value;
    
    if (isSignUp) {
        const name = document.getElementById('authName').value;
        const phone = document.getElementById('authPhone').value;
        const address = document.getElementById('authAddress').value;
        
        if (!name) { alert('Please enter your name'); return; }
        
        currentUser = { email, name, phone, address };
        saveData();
        updateLoginBtn();
        closeModal('loginModal');
        showToast('Account created successfully!');
    } else {
        currentUser = { email, name: email.split('@')[0] };
        saveData();
        updateLoginBtn();
        closeModal('loginModal');
        showToast('Welcome back!');
    }
}

function loginWithGoogle() {
    currentUser = { email: 'user@gmail.com', name: 'Google User' };
    saveData();
    updateLoginBtn();
    closeModal('loginModal');
    showToast('Logged in with Google');
}

function loginWithApple() {
    currentUser = { email: 'user@icloud.com', name: 'Apple User' };
    saveData();
    updateLoginBtn();
    closeModal('loginModal');
    showToast('Logged in with Apple');
}

// ACCOUNT
function showAccount() {
    if (!currentUser) {
        showLogin();
        return;
    }
    
    const content = document.getElementById('accountContent');
    content.innerHTML = `
        <div class="profile-head">
            <div class="profile-avatar">${currentUser.name?.charAt(0) || '👤'}</div>
            <div class="profile-name">${currentUser.name || 'User'}</div>
            <div class="profile-email">${currentUser.email}</div>
        </div>
        <div class="profile-menu">
            <div class="profile-item" onclick="showOrderHistory();closeModal('accountModal')">
                <span class="icon">📋</span><span>My Orders</span><span class="arrow">›</span>
            </div>
            <div class="profile-item" onclick="showFavorites();closeModal('accountModal')">
                <span class="icon">❤️</span><span>Saved Items</span><span class="arrow">›</span>
            </div>
            <div class="profile-item" onclick="editProfile()">
                <span class="icon">✏️</span><span>Edit Profile</span><span class="arrow">›</span>
            </div>
            <div class="profile-item" onclick="showNotifications();closeModal('accountModal')">
                <span class="icon">🔔</span><span>Notifications</span><span class="arrow">›</span>
            </div>
        </div>
        <button class="btn-logout" onclick="logout()">Logout</button>
    `;
    openModal('accountModal');
}

function editProfile() {
    const content = document.getElementById('accountContent');
    content.innerHTML = `
        <div class="modal-header" style="border:none;padding-bottom:0"><h3>Edit Profile</h3></div>
        <div style="padding:0 20px 20px">
            <div class="field"><label>Name</label><input type="text" id="editName" value="${currentUser.name || ''}"></div>
            <div class="field"><label>Phone</label><input type="tel" id="editPhone" value="${currentUser.phone || ''}"></div>
            <div class="field"><label>Address</label><input type="text" id="editAddress" value="${currentUser.address || ''}" onclick="pickLocation()"></div>
            <button class="btn-submit" onclick="saveProfile()">Save Changes</button>
            <button class="btn-submit" style="background:#333;margin-top:10px" onclick="showAccount()">Cancel</button>
        </div>
    `;
}

function saveProfile() {
    currentUser.name = document.getElementById('editName').value;
    currentUser.phone = document.getElementById('editPhone').value;
    currentUser.address = document.getElementById('editAddress').value;
    saveData();
    updateLoginBtn();
    showToast('Profile updated');
    showAccount();
}

function logout() {
    if (!confirm('Are you sure you want to logout?')) return;
    currentUser = null;
    saveData();
    updateLoginBtn();
    closeModal('accountModal');
    showToast('Logged out');
}

// MAP
let map = null;
let marker = null;

function pickLocation() {
    openModal('mapModal');
    
    setTimeout(() => {
        if (!map) {
            map = L.map('map').setView([53.4513, -2.0813], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);
            
            map.on('click', (e) => {
                if (marker) map.removeLayer(marker);
                marker = L.marker(e.latlng).addTo(map);
                selectedLocation = e.latlng;
                
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${e.latlng.lat}&lon=${e.latlng.lng}&format=json`)
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('selectedAddress').textContent = data.display_name || 'Location selected';
                    });
            });
        }
        map.invalidateSize();
    }, 300);
}

function confirmLocation() {
    if (!selectedLocation) {
        alert('Please select a location on the map');
        return;
    }
    
    const address = document.getElementById('selectedAddress').textContent;
    
    ['authAddress', 'editAddress', 'checkoutAddress'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = address;
    });
    
    if (currentUser) {
        currentUser.address = address;
        saveData();
    }
    
    closeModal('mapModal');
}

// Add CSS animation
const style = document.createElement('style');
style.textContent = '@keyframes fadeIn{from{opacity:0}to{opacity:1}}';
document.head.appendChild(style);
