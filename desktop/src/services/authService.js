const bcrypt = require('bcrypt');
const db = require('../config/database');

class AuthService {
    async login(email, password) {
        try {
            // First, get the user details
            const [users] = await db.query(
                'SELECT * FROM users WHERE email = ?',
                [email]
            );

            const user = users[0];
            if (!user) {
                throw new Error('No account found with this email');
            }

            // Verify password
            const isValidPassword = await bcrypt.compare(password, user.password);
            if (!isValidPassword) {
                throw new Error('Invalid password');
            }

            // Get user role
            const [userRoles] = await db.query(
                `SELECT r.name as role_name 
                FROM roles r 
                JOIN user_roles ur ON r.id = ur.role_id 
                WHERE ur.user_id = ?`,
                [user.id]
            );

            if (!userRoles.length) {
                throw new Error('User role not found');
            }

            const userRole = userRoles[0].role_name.toLowerCase();

            // Log successful login
            console.log('Login successful:', {
                userId: user.id,
                email: user.email,
                role: userRole
            });

            // Return user data
            return {
                id: user.id,
                username: user.username,
                email: user.email,
                role: userRole,
                created_at: user.created_at
            };
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    }
}

module.exports = new AuthService(); 