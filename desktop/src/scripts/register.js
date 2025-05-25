const { ipcRenderer } = require('electron');
const bcrypt = require('bcrypt');
const db = require('../config/database');

document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.querySelector('.login-btn');

    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        try {
            // Basic validation
            if (!username || !email || !password || !confirmPassword) {
                throw new Error('Please fill in all fields');
            }

            if (password !== confirmPassword) {
                throw new Error('Passwords do not match');
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                throw new Error('Please enter a valid email address');
            }

            // Username validation (alphanumeric and underscore only)
            const usernameRegex = /^[a-zA-Z0-9_]+$/;
            if (!usernameRegex.test(username)) {
                throw new Error('Username can only contain letters, numbers, and underscores');
            }

            // Password validation (at least 6 characters)
            if (password.length < 6) {
                throw new Error('Password must be at least 6 characters long');
            }

            registerBtn.disabled = true;
            registerBtn.textContent = 'Creating Account...';

            // Test database connection first
            try {
                const connection = await db.getConnection();
                connection.release();
            } catch (dbError) {
                console.error('Database connection error:', dbError);
                throw new Error('Unable to connect to database. Please try again later.');
            }

            // Check if username or email exists
            const [existingUsers] = await db.query(
                'SELECT id, username, email FROM users WHERE username = ? OR email = ?',
                [username, email]
            );

            if (existingUsers.length > 0) {
                const existing = existingUsers[0];
                if (existing.username === username) {
                    throw new Error('Username is already taken');
                } else {
                    throw new Error('Email is already registered');
                }
            }

            // Hash password
            const hashedPassword = await bcrypt.hash(password, 10);

            // Start transaction
            const connection = await db.getConnection();
            await connection.beginTransaction();

            try {
                // Insert user
                const [userResult] = await connection.query(
                    'INSERT INTO users (username, email, password, is_verified) VALUES (?, ?, ?, ?)',
                    [username, email, hashedPassword, 1]
                );

                // Assign client role
                await connection.query(
                    'INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)',
                    [userResult.insertId, 4] // 4 is the client role ID
                );

                await connection.commit();
                
                showSuccess('Account created successfully! Please login.');
                setTimeout(() => {
                    window.location.href = '../login.html';
                }, 1500);

            } catch (error) {
                await connection.rollback();
                throw error;
            } finally {
                connection.release();
            }

        } catch (error) {
            showError(error.message || 'Failed to create account');
        } finally {
            registerBtn.disabled = false;
            registerBtn.textContent = 'Register';
        }
    });
});

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

    const form = document.getElementById('registerForm');
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

    const form = document.getElementById('registerForm');
    form.insertBefore(successDiv, form.firstChild);

    setTimeout(() => {
        successDiv.remove();
    }, 5000);
} 