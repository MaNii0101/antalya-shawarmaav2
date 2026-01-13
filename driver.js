// ANTALYA SHAWARMA - Driver Dashboard
// ====================================

// DRIVER LOGIN
function showDriverLogin() {
    const pass = prompt('Enter driver password:');
    if (pass === '1234' || pass === 'driver') {
        closeModal('loginModal');
        showDriverDashboard();
    } else if (pass) {
        alert('Invalid password');
    }
}

// DRIVER DASHBOARD
function showDriverDashboard() {
    const readyOrders = orders.filter(o => o.status === 'ready');
    const delivering = orders.filter(o => o.status === 'delivering');
    const delivered = orders.filter(o => o.status === 'delivered');
    
    // Today's deliveries
    const todayDeliveries = delivered.filter(o => {
        const deliveredDate = new Date(o.deliveredAt);
        const today = new Date();
        return deliveredDate.toDateString() === today.toDateString();
    });
    
    const todayEarnings = todayDeliveries.length * 3.50;
    
    document.getElementById('driverContent').innerHTML = `
        <div class="dash-stats">
            <div class="dash-stat orange">
                <div class="num">${readyOrders.length}</div>
                <div class="label">Ready for Pickup</div>
            </div>
            <div class="dash-stat blue">
                <div class="num">${delivering.length}</div>
                <div class="label">Delivering</div>
            </div>
            <div class="dash-stat green">
                <div class="num">${todayDeliveries.length}</div>
                <div class="label">Delivered Today</div>
            </div>
            <div class="dash-stat purple">
                <div class="num">${formatPrice(todayEarnings)}</div>
                <div class="label">Today's Earnings</div>
            </div>
        </div>
        
        <div class="dash-section">
            <h4>📦 Ready for Pickup (${readyOrders.length})</h4>
            ${readyOrders.length === 0 ? '<p style="color:#666;padding:10px 0">No orders ready for pickup</p>' : readyOrders.map(o => `
                <div class="dash-order">
                    <div class="dash-order-top">
                        <span><strong>#${o.id}</strong></span>
                        <span class="order-status ready">Ready</span>
                    </div>
                    <div class="dash-order-items">${o.items.map(i => `${i.qty}x ${i.name}`).join(', ')}</div>
                    <div style="margin:10px 0;padding:10px;background:var(--bg);border-radius:8px">
                        <div style="font-weight:600;margin-bottom:5px">📍 Deliver to:</div>
                        <div style="font-size:0.9rem;color:#ccc">${o.address}</div>
                        ${o.phone ? `<div style="font-size:0.9rem;color:#ccc;margin-top:5px">📞 ${o.phone}</div>` : ''}
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-weight:600;color:var(--primary)">${formatPrice(o.total)}</span>
                        <span style="color:#27ae60;font-size:0.85rem">+£3.50 delivery fee</span>
                    </div>
                    <div class="dash-order-btns">
                        <button class="accept" onclick="pickupOrder('${o.id}')">🚗 Pick Up Order</button>
                    </div>
                </div>
            `).join('')}
        </div>
        
        <div class="dash-section">
            <h4>🚗 Currently Delivering (${delivering.length})</h4>
            ${delivering.length === 0 ? '<p style="color:#666;padding:10px 0">No active deliveries</p>' : delivering.map(o => `
                <div class="dash-order" style="border-left:3px solid #3498db">
                    <div class="dash-order-top">
                        <span><strong>#${o.id}</strong></span>
                        <span class="order-status accepted">Delivering</span>
                    </div>
                    <div style="margin:10px 0;padding:10px;background:var(--bg);border-radius:8px">
                        <div style="font-weight:600;margin-bottom:5px">📍 Destination:</div>
                        <div style="font-size:0.9rem;color:#ccc">${o.address}</div>
                        ${o.phone ? `<div style="font-size:0.9rem;color:#ccc;margin-top:5px">📞 ${o.phone}</div>` : ''}
                    </div>
                    <div class="dash-order-btns">
                        <button class="complete" onclick="completeDelivery('${o.id}')">✓ Mark Delivered</button>
                        <button style="background:#333;color:#fff" onclick="openNavigation('${o.address}')">🗺️ Navigate</button>
                    </div>
                </div>
            `).join('')}
        </div>
        
        <div class="dash-section">
            <h4>✅ Today's Deliveries (${todayDeliveries.length})</h4>
            ${todayDeliveries.length === 0 ? '<p style="color:#666;padding:10px 0">No deliveries completed today</p>' : todayDeliveries.slice().reverse().map(o => `
                <div class="dash-order" style="opacity:0.7">
                    <div class="dash-order-top">
                        <span><strong>#${o.id}</strong></span>
                        <span class="order-status delivered">Delivered</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:0.85rem;color:#666">${new Date(o.deliveredAt).toLocaleTimeString()}</span>
                        <span style="color:#27ae60;font-weight:600">+£3.50</span>
                    </div>
                </div>
            `).join('')}
        </div>
        
        <button onclick="closeModal('driverModal')" class="btn-close-dash">Close Dashboard</button>
    `;
    
    openModal('driverModal');
}

// PICKUP ORDER
function pickupOrder(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (order) {
        order.status = 'delivering';
        order.pickedUpAt = new Date().toISOString();
        
        notifications.push({
            id: Date.now(),
            title: 'Order Out for Delivery',
            message: `Your order #${orderId} is on its way!`,
            time: new Date().toISOString(),
            read: false
        });
        
        saveData();
        showDriverDashboard();
        showToast('Order picked up!');
    }
}

// COMPLETE DELIVERY
function completeDelivery(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (order) {
        order.status = 'delivered';
        order.deliveredAt = new Date().toISOString();
        
        notifications.push({
            id: Date.now(),
            title: 'Order Delivered',
            message: `Your order #${orderId} has been delivered. Enjoy your meal!`,
            time: new Date().toISOString(),
            read: false
        });
        
        saveData();
        showDriverDashboard();
        showToast('Delivery completed! +£3.50');
    }
}

// OPEN NAVIGATION
function openNavigation(address) {
    const encoded = encodeURIComponent(address);
    window.open(`https://www.google.com/maps/search/?api=1&query=${encoded}`, '_blank');
}
