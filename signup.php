<?php
session_start();
require_once 'config/database.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if username already exists
        $query = "SELECT COUNT(*) FROM Users WHERE UserName = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username already exists. Please choose a different username.';
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO Users (UserName, Password, Role, FirstName, LastName, Email) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$username, $hashed_password, $role, $first_name, $last_name, $email])) {
                $success = 'Account created successfully! You can now login with your credentials.';
                // Clear form data
                $_POST = array();
            } else {
                $error = 'Error creating account. Please try again.';
            }
        }
    }
}

$available_roles = ['doctor', 'nurse', 'lab_technician', 'pharmacy_officer', 'vaccination_officer'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Rutsiro Health Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <i class="fas fa-hospital text-blue-600 text-4xl mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-800">Rutsiro Health Center</h1>
                <p class="text-gray-600 mt-2">Create New Account</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-3">
                        <a href="login.php" class="text-green-800 font-medium hover:underline">
                            <i class="fas fa-sign-in-alt mr-1"></i>Go to Login
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                        <input type="text" id="username" name="username" required
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                        <input type="email" id="email" name="email"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    <select id="role" name="role" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="">Select Your Role</option>
                        <?php foreach ($available_roles as $role): ?>
                            <option value="<?php echo $role; ?>" 
                                    <?php echo ($_POST['role'] ?? '') == $role ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $role)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                        <input type="password" id="password" name="password" required minlength="6"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors text-sm">
                    <i class="fas fa-user-plus mr-2"></i>
                    Create Account
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        Sign in here
                    </a>
                </p>
            </div>

            <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <h3 class="font-medium text-yellow-800 mb-2 text-sm">Note:</h3>
                <p class="text-xs text-yellow-700">
                    New accounts require admin approval before full access is granted. 
                    Contact your system administrator if you need immediate access.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
