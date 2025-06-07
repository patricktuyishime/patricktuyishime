<?php
require_once 'config/database.php';
include 'includes/header.php';

if (!hasRole('admin')) {
    header('Location: dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);

        // Check if username already exists
        $query = "SELECT COUNT(*) FROM Users WHERE UserName = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username]);
        
        if ($stmt->fetchColumn() > 0) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Username already exists.</div>';
        } else {
            $query = "INSERT INTO Users (UserName, Password, Role, FirstName, LastName, Email) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$username, $password, $role, $first_name, $last_name, $email])) {
                $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">User added successfully!</div>';
                $action = 'list';
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error adding user.</div>';
            }
        }
    } elseif (isset($_POST['update_user'])) {
        $username = trim($_POST['username']);
        $role = $_POST['role'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);

        // Check if username already exists for other users
        $query = "SELECT COUNT(*) FROM Users WHERE UserName = ? AND User_Id != ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $user_id]);
        
        if ($stmt->fetchColumn() > 0) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Username already exists.</div>';
        } else {
            $query = "UPDATE Users SET UserName=?, Role=?, FirstName=?, LastName=?, Email=? WHERE User_Id=?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$username, $role, $first_name, $last_name, $email, $user_id])) {
                $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">User updated successfully!</div>';
                $action = 'list';
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error updating user.</div>';
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        $query = "UPDATE Users SET Password=? WHERE User_Id=?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$new_password, $user_id])) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Password reset successfully!</div>';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error resetting password.</div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete']) {
    $delete_id = $_GET['delete'];
    
    // Prevent deleting own account
    if ($delete_id == $_SESSION['user_id']) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">You cannot delete your own account.</div>';
    } else {
        $query = "DELETE FROM Users WHERE User_Id = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$delete_id])) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">User deleted successfully!</div>';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error deleting user.</div>';
        }
    }
}

// Get user data for editing
$user_data = null;
if (($action == 'edit' || $action == 'reset_password') && $user_id) {
    $query = "SELECT * FROM Users WHERE User_Id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all users for listing
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$query = "SELECT * FROM Users WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (UserName LIKE ? OR FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
}

if ($role_filter) {
    $query .= " AND Role = ?";
    $params[] = $role_filter;
}

$query .= " ORDER BY FirstName, LastName";
$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$roles = ['admin', 'doctor', 'nurse', 'lab_technician', 'pharmacy_officer', 'vaccination_officer'];
?>

<div class="space-y-6">
    <?php echo $message; ?>

    <?php if ($action == 'list'): ?>
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
            <a href="?action=add" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Add New User
            </a>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <input type="hidden" name="action" value="list">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Search users by name, username, or email..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <select name="role" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role; ?>" <?php echo $role_filter == $role ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $role)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <?php if ($search || $role_filter): ?>
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Users List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <?php echo $search || $role_filter ? 'No users found matching your criteria.' : 'No users found.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user_item): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="bg-indigo-100 p-2 rounded-full mr-3">
                                                <i class="fas fa-user text-indigo-600"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($user_item['FirstName'] . ' ' . $user_item['LastName']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">ID: <?php echo $user_item['User_Id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($user_item['UserName']); ?>
                                        <?php if ($user_item['User_Id'] == $_SESSION['user_id']): ?>
                                            <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                    switch($user_item['Role']) {
                                                        case 'admin': echo 'bg-red-100 text-red-800'; break;
                                                        case 'doctor': echo 'bg-blue-100 text-blue-800'; break;
                                                        case 'nurse': echo 'bg-green-100 text-green-800'; break;
                                                        case 'lab_technician': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        case 'pharmacy_officer': echo 'bg-purple-100 text-purple-800'; break;
                                                        case 'vaccination_officer': echo 'bg-pink-100 text-pink-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $user_item['Role'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($user_item['Email'] ?: 'Not provided'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($user_item['CreatedAt'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="?action=edit&id=<?php echo $user_item['User_Id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=reset_password&id=<?php echo $user_item['User_Id']; ?>" 
                                               class="text-yellow-600 hover:text-yellow-900" title="Reset Password">
                                                <i class="fas fa-key"></i>
                                            </a>
                                            <?php if ($user_item['User_Id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete=<?php echo $user_item['User_Id']; ?>" 
                                                   class="text-red-600 hover:text-red-900" title="Delete"
                                                   onclick="return confirmDelete('Are you sure you want to delete this user?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($action == 'add' || $action == 'edit'): ?>
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">
                <?php echo $action == 'add' ? 'Add New User' : 'Edit User'; ?>
            </h1>
            <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST" id="userForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               value="<?php echo htmlspecialchars($user_data['FirstName'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?php echo htmlspecialchars($user_data['LastName'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                        <input type="text" id="username" name="username" required
                               value="<?php echo htmlspecialchars($user_data['UserName'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                        <select id="role" name="role" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role; ?>" 
                                        <?php echo ($user_data['Role'] ?? '') == $role ? 'selected' : ''; ?>>
                                    <?php echo ucwords(str_replace('_', ' ', $role)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($user_data['Email'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <?php if ($action == 'add'): ?>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" id="password" name="password" required minlength="6"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" name="<?php echo $action == 'add' ? 'add_user' : 'update_user'; ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
                            onclick="return validateForm('userForm')">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $action == 'add' ? 'Add User' : 'Update User'; ?>
                    </button>
                </div>
            </form>
        </div>

    <?php elseif ($action == 'reset_password'): ?>
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Reset Password</h1>
            <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900">
                    Reset password for: <?php echo htmlspecialchars($user_data['FirstName'] . ' ' . $user_data['LastName']); ?>
                </h3>
                <p class="text-sm text-gray-600">Username: <?php echo htmlspecialchars($user_data['UserName']); ?></p>
            </div>

            <form method="POST" id="passwordForm" class="space-y-6">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" name="reset_password"
                            class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors"
                            onclick="return confirm('Are you sure you want to reset this user\'s password?')">
                        <i class="fas fa-key mr-2"></i>
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
