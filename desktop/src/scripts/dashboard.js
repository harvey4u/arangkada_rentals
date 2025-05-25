const { ipcRenderer } = require('electron');
const db = require('../config/database');

document.addEventListener('DOMContentLoaded', async () => {
    // Check if user is logged in
    const userData = JSON.parse(localStorage.getItem('user'));
    if (!userData || !['superadmin', 'admin'].includes(userData.role)) {
        window.location.href = '../../login.html';
        return;
    }

    // Set user name
    document.querySelector('.user-name').textContent = userData.username;

    // Handle logout
    document.querySelector('.logout-btn').addEventListener('click', () => {
        localStorage.removeItem('user');
        window.location.href = '../../login.html';
    });

    // Load dashboard data
    await loadDashboardData();

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

async function loadDashboardData() {
    try {
        // Load total users
        const [userResults] = await db.query('SELECT COUNT(*) as count FROM users');
        document.getElementById('totalUsers').textContent = userResults[0].count;

        // Load available cars
        const [carResults] = await db.query('SELECT COUNT(*) as count FROM cars WHERE status = "available"');
        document.getElementById('availableCars').textContent = carResults[0].count;

        // Load active rentals
        const [rentalResults] = await db.query('SELECT COUNT(*) as count FROM rentals WHERE status = "active"');
        document.getElementById('activeRentals').textContent = rentalResults[0].count;

        // Load total revenue
        const [revenueResults] = await db.query('SELECT SUM(total_price) as total FROM rentals WHERE status != "cancelled"');
        const totalRevenue = revenueResults[0].total || 0;
        document.getElementById('totalRevenue').textContent = `$${totalRevenue.toFixed(2)}`;

        // Load recent activity
        const [activities] = await db.query(`
            SELECT 
                r.id,
                u.username,
                c.make,
                c.model,
                r.start_date,
                r.status
            FROM rentals r
            JOIN users u ON r.user_id = u.id
            JOIN cars c ON r.car_id = c.id
            ORDER BY r.created_at DESC
            LIMIT 5
        `);

        const activityList = document.getElementById('recentActivity');
        activityList.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <strong>${activity.username}</strong> rented a 
                ${activity.make} ${activity.model}
                (Status: ${activity.status})
            </div>
        `).join('');

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