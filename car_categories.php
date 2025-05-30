<?php
session_start();

// Check if user is logged in and is a superadmin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $base_price = $_POST['base_price'];
    
    // Check for duplicate category name
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM car_categories WHERE name = ?");
    $stmt->execute([$name]);
    $name_exists = $stmt->fetchColumn() > 0;
    if ($name_exists) {
        $error_message = "Category name already exists. Please choose another name.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO car_categories (name, description, base_price) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$name, $description, $base_price]);
            $success_message = "Category added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding category: " . $e->getMessage();
        }
    }
}

// Handle category update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $category_id = $_POST['category_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $base_price = $_POST['base_price'];
    
    // Check for duplicate category name (excluding current category)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM car_categories WHERE name = ? AND id != ?");
    $stmt->execute([$name, $category_id]);
    $name_exists = $stmt->fetchColumn() > 0;
    if ($name_exists) {
        $error_message = "Category name already exists. Please choose another name.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE car_categories 
                SET name = ?, description = ?, base_price = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $base_price, $category_id]);
            $success_message = "Category updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating category: " . $e->getMessage();
        }
    }
}

// Handle category deletion
if (isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    try {
        // Check if category has cars
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cars WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $car_count = $stmt->fetchColumn();
        
        if ($car_count > 0) {
            $error_message = "Cannot delete category: There are cars assigned to this category.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM car_categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $success_message = "Category deleted successfully!";
        }
    } catch (PDOException $e) {
        $error_message = "Error deleting category: " . $e->getMessage();
    }
}

// Fetch all categories with car counts
$stmt = $pdo->query("
    SELECT cc.*, 
           COUNT(c.id) as car_count,
           AVG(c.price_per_day) as avg_price
    FROM car_categories cc
    LEFT JOIN cars c ON cc.id = c.category_id
    GROUP BY cc.id
    ORDER BY cc.name
");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Categories - Arangkada Car Rentals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reuse existing styles */
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --secondary: #16a34a;
            --secondary-light: #dcfce7;
            --warning: #ca8a04;
            --warning-light: #fef9c3;
            --danger: #dc2626;
            --danger-light: #fee2e2;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #f1f5f9;
            --white: #ffffff;
            
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.375rem;
            --radius: 0.5rem;
            --transition: all 0.2s ease;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--gray-light);
            color: var(--dark);
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: var(--spacing-lg);
            min-height: 100vh;
        }

        /* Category specific styles */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--spacing-lg);
        }

        .category-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: var(--spacing-md);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
        }

        .category-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .category-stats {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-sm);
            background: var(--gray-light);
            border-radius: var(--radius-sm);
        }

        .stat-item {
            text-align: center;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--gray);
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark);
        }

        .category-description {
            color: var(--gray);
            margin-bottom: var(--spacing-md);
        }

        .category-actions {
            display: flex;
            gap: var(--spacing-xs);
            margin-top: var(--spacing-md);
            border-top: 1px solid var(--gray-light);
            padding-top: var(--spacing-sm);
            justify-content: flex-end;
        }
        .category-actions .btn {
            min-width: 40px;
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
            border-radius: var(--radius-sm);
            box-shadow: none;
        }
        .category-actions .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        .category-actions .btn-primary:hover {
            background: #1746a2;
        }
        .category-actions .btn-danger {
            background: var(--danger);
            color: var(--white);
        }
        .category-actions .btn-danger:hover {
            background: #a61b1b;
        }
        .category-actions .btn i {
            margin-right: 0.5em;
        }
        @media (max-width: 600px) {
            .category-actions {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }
            .category-card {
                padding: var(--spacing-sm);
            }
        }
        /* Modal button improvements */
        .modal .form-group {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .modal .form-group button {
            flex: 1 1 0;
            min-width: 120px;
        }
        @media (max-width: 600px) {
            .modal .form-group {
                flex-direction: column;
                gap: 0.5rem;
            }
            .modal .form-group button {
                min-width: 0;
            }
        }
        /* Add/Edit/Delete Button Enhancements */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
            font-weight: 500;
            border: none;
            outline: none;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .btn:focus {
            box-shadow: 0 0 0 2px var(--primary-light);
        }
        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        .btn-primary:hover, .btn-primary:focus {
            background: #1746a2;
        }
        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }
        .btn-danger:hover, .btn-danger:focus {
            background: #a61b1b;
        }
        .btn-edit {
            background: var(--warning-light);
            color: var(--warning);
        }
        .btn-edit:hover, .btn-edit:focus {
            background: var(--warning);
            color: var(--white);
        }
        .btn-add {
            background: var(--secondary);
            color: var(--white);
        }
        .btn-add:hover, .btn-add:focus {
            background: #12813b;
        }
        /* Floating Add Button on Mobile */
        @media (max-width: 600px) {
            .floating-add-btn {
                position: fixed;
                bottom: 1.5rem;
                right: 1.5rem;
                z-index: 1001;
                border-radius: 50%;
                width: 56px;
                height: 56px;
                justify-content: center;
                font-size: 1.5rem;
                box-shadow: var(--shadow);
                padding: 0;
            }
            .main-content .card-header .btn-add {
                display: none;
            }
        }
        /* Icon-only buttons for Edit/Delete on mobile */
        @media (max-width: 600px) {
            .category-actions .btn {
                padding: 0.5rem;
                min-width: 40px;
                font-size: 1.2rem;
                justify-content: center;
            }
            .category-actions .btn span {
                display: none;
            }
        }
        /* Tooltip for icon-only buttons */
        .btn[title]:hover::after, .btn[title]:focus::after {
            content: attr(title);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: var(--dark);
            color: var(--white);
            padding: 0.25em 0.75em;
            border-radius: var(--radius-sm);
            font-size: 0.85em;
            white-space: nowrap;
            margin-left: 0.5em;
            z-index: 1002;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_superadmin.php'; ?>

    <main class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-tags"></i>
                    Car Categories
                </h2>
                <button class="btn btn-add" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    <span>Add New Category</span>
                </button>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <div class="category-header">
                            <h3 class="category-title"><?= htmlspecialchars($category['name']) ?></h3>
                            <span class="price-tag">
                                <i class="fas fa-tag"></i>
                                ₱<?= number_format($category['base_price'], 2) ?>
                            </span>
                        </div>

                        <div class="category-stats">
                            <div class="stat-item">
                                <div class="stat-label">Cars</div>
                                <div class="stat-value"><?= $category['car_count'] ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Avg. Price</div>
                                <div class="stat-value">₱<?= number_format($category['avg_price'] ?: 0, 2) ?></div>
                            </div>
                        </div>

                        <div class="category-description">
                            <?= nl2br(htmlspecialchars($category['description'])) ?>
                        </div>

                        <div class="category-actions">
                            <button class="btn btn-edit" title="Edit" onclick="editCategory(<?= htmlspecialchars(json_encode($category)) ?>)">
                                <i class="fas fa-edit"></i>
                                <span>Edit</span>
                            </button>
                            <?php if ($category['car_count'] === 0): ?>
                                <form method="POST" style="display: inline; position: relative;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                    <button type="submit" name="delete_category" class="btn btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                        <span>Delete</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Add/Edit Category Modal -->
    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add New Category</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="category_id" id="categoryId">
                <div class="form-group">
                    <label class="form-label" for="name">Category Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="base_price">Base Price Per Day (₱)</label>
                    <input type="number" id="base_price" name="base_price" class="form-control" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <button type="submit" id="submitBtn" name="create_category" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Category
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Add Button for Mobile -->
    <button class="btn btn-add floating-add-btn" onclick="openModal()" title="Add New Category" style="display:none;">
        <i class="fas fa-plus"></i>
    </button>

    <script>
        function openModal() {
            document.getElementById('modalTitle').textContent = 'Add New Category';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus"></i> Add Category';
            document.getElementById('submitBtn').name = 'create_category';
            document.getElementById('categoryId').value = '';
            document.getElementById('name').value = '';
            document.getElementById('description').value = '';
            document.getElementById('base_price').value = '';
            document.getElementById('categoryModal').classList.add('active');
        }

        function editCategory(category) {
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-edit"></i> Update Category';
            document.getElementById('submitBtn').name = 'update_category';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('name').value = category.name;
            document.getElementById('description').value = category.description;
            document.getElementById('base_price').value = category.base_price;
            document.getElementById('categoryModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('categoryModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        // Show floating add button on mobile
        function handleFloatingAddBtn() {
            const btn = document.querySelector('.floating-add-btn');
            if (window.innerWidth <= 600) {
                btn.style.display = 'flex';
            } else {
                btn.style.display = 'none';
            }
        }
        window.addEventListener('resize', handleFloatingAddBtn);
        window.addEventListener('DOMContentLoaded', handleFloatingAddBtn);
    </script>
</body>
</html> 