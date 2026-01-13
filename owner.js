// ANTALYA SHAWARMA - Owner/Restaurant Dashboard
// ==============================================

// RESTAURANT LOGIN
function showRestaurantLogin() {
    const pass = prompt('Enter restaurant password:');
    if (pass === '1234' || pass === 'admin') {
        closeModal('loginModal');
        showRestaurantDashboard();
    } else if (pass) {
        alert('Invalid password');
    }
}

// RESTAURANT DASHBOARD
function showRestaurantDashboard() {
    const allOrders = orders;
    const pending = allOrders.filter(o => o.status === 'pending');
    const preparing = allOrders.filter(o => o.status === 'preparing' || o.status === 'accepted');
    const ready = allOrders.filter(o => o.status === 'ready');
    const completed = allOrders.filter(o => o.status === 'delivered');
    
    const todayRevenue = completed.reduce((s, o) => s + o.total, 0);
    
    document.getElementById('restaurantContent').innerHTML = `
        <div class="dash-stats">
            <div class="dash-stat orange">
                <div class="num">${pending.length}</div>
                <div class="label">Pending</div>
            </div>
            <div class="dash-stat blue">
                <div class="num">${preparing.length}</div>
                <div class="label">Preparing</div>
            </div>
            <div class="dash-stat green">
                <div class="num">${completed.length}</div>
                <div class="label">Completed</div>
            </div>
            <div class="dash-stat purple">
                <div class="num">${formatPrice(todayRevenue)}</div>
                <div class="label">Revenue</div>
            </div>
        </div>
        
        <div class="dash-section">
            <h4>🔔 Pending Orders (${pending.length})</h4>
            ${pending.length === 0 ? '<p style="color:#666;padding:10px 0">No pending orders</p>' : pending.map(o => `
                <div class="dash-order">
                    <div class="dash-order-top">
                        <span><strong>#${o.id}</strong></span>
                        <span class="order-status pending">Pending</span>
                    </div>
                    <div class="dash-order-items">${o.items.map(i => `${i.qty}x ${i.name}`).join(', ')}</div>
                    <div style="font-size:0.85rem;color:#999;margin:8px 0">
                        📍 ${o.address}<br>
                        📞 ${o.phone || 'No phone'}
                        ${o.notes ? `<br>📝 ${o.notes}` : ''}
                    </div>
                    <div style="font-weight:600;color:var(--primary);font-size:1.1rem">${formatPrice(o.total)}</div>
                    <div class="dash-order-btns">
                        <button class="accept" onclick="acceptOrder('${o.id}')">✓ Accept Order</button>
                        <button style="background:#e74c3c;color:#fff" onclick="rejectOrder('${o.id}')">✕ Reject</button>
                    </div>
                </div>
            `).join('')}
        </div>
        
        <div class="dash-section">
            <h4>👨‍🍳 In Progress (${preparing.length})</h4>
            ${preparing.length === 0 ? '<p style="color:#666;padding:10px 0">No orders in progress</p>' : preparing.map(o => `
                <div class="dash-order">
                    <div class="dash-order-top">
                        <span><strong>#${o.id}</strong></span>
                        <span class="order-status ${o.status}">${o.status}</span>
                    </div>
                    <div class="dash-order-items">${o.items.map(i => `${i.qty}x ${i.name}`).join(', ')}</div>
                    <div style="font-size:0.85rem;color:#999;margin:5px 0">📍 ${o.address}</div>
                    <div class="dash-order-btns">
                        ${o.status === 'accepted' ? `<button class="ready" onclick="startPreparing('${o.id}')">🍳 Start Preparing</button>` : ''}
                        ${o.status === 'preparing' ? `<button class="complete" onclick="markReady('${o.id}')">✓ Ready for Pickup</button>` : ''}
                    </div>
                </div>
            `).join('')}
        </div>
        
        <div class="dash-section">
            <h4>📦 Ready for Pickup (${ready.length})</h4>
            ${ready.length === 0 ? '<p style="color:#666;padding:10px 0">No orders ready</p>' : ready.map(o => `
                <div class="dash-order">
                    <div class="dash-order-top">
                        <span><strong>#${o.id}</strong></span>
                        <span class="order-status ready">Ready</span>
                    </div>
                    <div class="dash-order-items">${o.items.map(i => `${i.qty}x ${i.name}`).join(', ')}</div>
                    <div style="font-size:0.85rem;color:#999;margin:5px 0">📍 ${o.address}</div>
                    <p style="color:#27ae60;font-size:0.85rem;margin-top:8px">⏳ Waiting for driver...</p>
                </div>
            `).join('')}
        </div>
        
        <div class="dash-section">
            <h4>📊 Recent Completed (${completed.length})</h4>
            ${completed.slice(-5).reverse().map(o => `
                <div class="dash-order" style="opacity:0.7">
                    <div class="dash-order-top">
                        <span><strong>#${o.id}</strong></span>
                        <span class="order-status delivered">Delivered</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:0.85rem;color:#666">${o.items.length} items</span>
                        <span style="font-weight:600;color:var(--green)">${formatPrice(o.total)}</span>
                    </div>
                </div>
            `).join('') || '<p style="color:#666;padding:10px 0">No completed orders</p>'}
        </div>
        
        <button onclick="closeModal('restaurantModal')" class="btn-close-dash">Close Dashboard</button>
    `;
    
    openModal('restaurantModal');
}

// ACCEPT ORDER
function acceptOrder(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (order) {
        order.status = 'accepted';
        order.acceptedAt = new Date().toISOString();
        
        notifications.push({
            id: Date.now(),
            title: 'Order Accepted',
            message: `Your order #${orderId} has been accepted by the restaurant.`,
            time: new Date().toISOString(),
            read: false
        });
        
        saveData();
        showRestaurantDashboard();
        showToast('Order accepted!');
    }
}

// REJECT ORDER
function rejectOrder(orderId) {
    if (!confirm('Are you sure you want to reject this order?')) return;
    
    const order = orders.find(o => o.id === orderId);
    if (order) {
        order.status = 'cancelled';
        order.cancelledAt = new Date().toISOString();
        order.cancelReason = 'Rejected by restaurant';
        
        notifications.push({
            id: Date.now(),
            title: 'Order Rejected',
            message: `Your order #${orderId} was rejected by the restaurant.`,
            time: new Date().toISOString(),
            read: false
        });
        
        saveData();
        showRestaurantDashboard();
    }
}

// START PREPARING
function startPreparing(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (order) {
        order.status = 'preparing';
        order.preparingAt = new Date().toISOString();
        
        notifications.push({
            id: Date.now(),
            title: 'Order Being Prepared',
            message: `Your order #${orderId} is now being prepared.`,
            time: new Date().toISOString(),
            read: false
        });
        
        saveData();
        showRestaurantDashboard();
    }
}

// MARK READY
function markReady(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (order) {
        order.status = 'ready';
        order.readyAt = new Date().toISOString();
        
        notifications.push({
            id: Date.now(),
            title: 'Order Ready',
            message: `Your order #${orderId} is ready and waiting for pickup.`,
            time: new Date().toISOString(),
            read: false
        });
        
        saveData();
        showRestaurantDashboard();
        showToast('Order marked as ready!');
    }
}
