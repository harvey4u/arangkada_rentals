const { ipcRenderer } = require('electron');
const db = require('../config/database');

document.addEventListener('DOMContentLoaded', async () => {
    // Check if user is logged in
    const userData = localStorage.getItem('user');
    if (!userData) {
        window.location.href = '../../login.html';
        return;
    }

    const user = JSON.parse(userData);
    
    // Verify user is a client
    if (user.role !== 'client') {
        alert('Unauthorized access');
        window.location.href = '../../login.html';
        return;
    }

    // Set user name
    document.querySelector('.user-name').textContent = user.username;

    // Handle logout
    document.querySelector('.logout-btn').addEventListener('click', () => {
        localStorage.removeItem('user');
        window.location.href = '../../login.html';
    });

    // Load dashboard data
    await loadDashboardData(user.id);

    // Handle navigation
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = e.target.closest('a').dataset.section;
            switchSection(section);
        });
    });
});

async function loadDashboardData(userId) {
    try {
        // Load active rentals
        const [activeRentals] = await db.query(
            'SELECT COUNT(*) as count FROM rentals WHERE user_id = ? AND status = "active"',
            [userId]
        );
        document.getElementById('activeRentals').textContent = activeRentals[0].count;

        // Load total rentals
        const [totalRentals] = await db.query(
            'SELECT COUNT(*) as count FROM rentals WHERE user_id = ?',
            [userId]
        );
        document.getElementById('totalRentals').textContent = totalRentals[0].count;

        // Load amount spent
        const [amountSpent] = await db.query(
            'SELECT SUM(total_price) as total FROM rentals WHERE user_id = ? AND status != "cancelled"',
            [userId]
        );
        const total = amountSpent[0].total || 0;
        document.getElementById('amountSpent').textContent = `$${total.toFixed(2)}`;

        // Load recent activity
        const [activities] = await db.query(`
            SELECT 
                r.id,
                c.make,
                c.model,
                r.start_date,
                r.end_date,
                r.status,
                r.total_price
            FROM rentals r
            JOIN cars c ON r.car_id = c.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT 5
        `, [userId]);

        const activityList = document.getElementById('recentActivity');
        activityList.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <strong>${activity.make} ${activity.model}</strong><br>
                From: ${new Date(activity.start_date).toLocaleDateString()}<br>
                To: ${new Date(activity.end_date).toLocaleDateString()}<br>
                Status: ${activity.status}<br>
                Price: $${activity.total_price}
            </div>
        `).join('') || '<p>No recent activity</p>';

    } catch (error) {
        console.error('Error loading dashboard data:', error);
        alert('Error loading dashboard data');
    }
}

function switchSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.dashboard-section').forEach(section => {
        section.classList.remove('active');
    });

    // Remove active class from all nav items
    document.querySelectorAll('.sidebar-nav li').forEach(item => {
        item.classList.remove('active');
    });

    // Show selected section
    const selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.classList.add('active');
    }

    // Add active class to nav item
    const selectedNavItem = document.querySelector(`.sidebar-nav a[data-section="${sectionId}"]`);
    if (selectedNavItem) {
        selectedNavItem.closest('li').classList.add('active');
    }
} 