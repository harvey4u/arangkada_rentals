const { ipcRenderer } = require('electron');
const authService = require('../services/authService');

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const loginBtn = document.querySelector('.login-btn');

    // Check if already logged in
    const existingUser = localStorage.getItem('user');
    if (existingUser) {
        const user = JSON.parse(existingUser);
        redirectToDashboard(user.role);
    }

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        try {
            // Basic validation
            if (!email || !password) {
                showError('Please fill in all fields');
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError('Please enter a valid email address');
                return;
            }

            // Update button state
            loginBtn.disabled = true;
            loginBtn.textContent = 'Logging in...';

            // Attempt login
            const user = await authService.login(email, password);
            console.log('Login successful:', user);

            // Store user data
            localStorage.setItem('user', JSON.stringify(user));

            // Show success message
            showSuccess(`Welcome back, ${user.username}!`);

            // Determine redirect path based on role
            let redirectPath;
            switch(user.role.toLowerCase()) {
                case 'superadmin':
                    redirectPath = './pages/admin/dashboard.html';
                    break;
                case 'admin':
                    redirectPath = './pages/admin/dashboard.html';
                    break;
                case 'staff':
                    redirectPath = './pages/staff/dashboard.html';
                    break;
                case 'client':
                    redirectPath = './pages/client/dashboard.html';
                    break;
                default:
                    redirectPath = './pages/client/dashboard.html';
            }

            console.log('Redirecting to:', redirectPath);
            
            // Redirect after a short delay
            setTimeout(() => {
                window.location.href = redirectPath;
            }, 1000);

        } catch (error) {
            console.error('Login error:', error);
            showError(error.message || 'Login failed. Please try again.');
        } finally {
            loginBtn.disabled = false;
            loginBtn.textContent = 'Login';
        }
    });

    // Add input validation and styling
    [emailInput, passwordInput].forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', () => {
            input.parentElement.classList.remove('focused');
        });
    });
});

function redirectToDashboard(role) {
    console.log('Redirecting to dashboard for role:', role); // Debug log
    
    let dashboardPath;
    switch(role.toLowerCase()) {
        case 'superadmin':
        case 'admin':
            dashboardPath = './pages/admin/dashboard.html';
            break;
        case 'staff':
            dashboardPath = './pages/staff/dashboard.html';
            break;
        case 'client':
            dashboardPath = './pages/client/dashboard.html';
            break;
        default:
            dashboardPath = './pages/dashboard.html';
    }

    console.log('Dashboard path:', dashboardPath); // Debug log
    window.location.href = dashboardPath;
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.cssText = `
        background-color: #ffebee;
        color: #c62828;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 10px;
        text-align: center;
    `;
    errorDiv.textContent = message;

    const form = document.getElementById('loginForm');
    form.insertBefore(errorDiv, form.firstChild);

    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.style.cssText = `
        background-color: #e8f5e9;
        color: #2e7d32;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 10px;
        text-align: center;
    `;
    successDiv.textContent = message;

    const form = document.getElementById('loginForm');
    form.insertBefore(successDiv, form.firstChild);

    setTimeout(() => {
        successDiv.remove();
    }, 5000);
} 