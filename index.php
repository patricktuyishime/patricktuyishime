    <?php
session_start();

// If user is logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutsiro Health Center - Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <!-- Header -->
    <header class="fixed top-0 left-0 w-full z-50 bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <i class="fas fa-hospital text-blue-600 text-3xl"></i>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Rutsiro Health Center</h1>
                    <p class="text-gray-600">Health Management System</p>
                </div>
            </div>
            <div class="flex space-x-4">
                <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
                <a href="signup.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-user-plus mr-2"></i>Sign Up
                </a>
            </div>
        </div>
    </div>
</header>


    <!-- Hero Section -->
    <main class="max-w-7xl mx-auto px-4 py-16">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-800 mb-6">
                Welcome to Rutsiro Health Center
            </h2>
            <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                A comprehensive health management system designed to streamline patient care, 
                medical records, pharmacy operations, and laboratory services in the Western Province, 
                Rutsiro District, Manihira Sector Muyira cell.
            </p>
            <div class="flex justify-center space-x-4">
                <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-lg font-medium transition-colors">
                    <i class="fas fa-sign-in-alt mr-2"></i>Access System
                </a>
                <a href="signup.php" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg text-lg font-medium transition-colors">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </a>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="bg-blue-100 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Patient Management</h3>
                <p class="text-gray-600">Comprehensive patient registration, medical history tracking, and consultation records.</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="bg-green-100 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-stethoscope text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Medical Consultations</h3>
                <p class="text-gray-600">Digital consultation records with symptoms, diagnosis, and treatment tracking.</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="bg-purple-100 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-syringe text-purple-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Vaccination Records</h3>
                <p class="text-gray-600">Complete immunization tracking with dose schedules and follow-up reminders.</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="bg-yellow-100 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-flask text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Laboratory Services</h3>
                <p class="text-gray-600">Lab test management with results tracking and patient history integration.</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="bg-red-100 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-pills text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Pharmacy Management</h3>
                <p class="text-gray-600">Medicine inventory control, prescription management, and stock monitoring.</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="bg-indigo-100 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-chart-bar text-indigo-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Reports & Analytics</h3>
                <p class="text-gray-600">Comprehensive reporting system with customizable filters and data insights.</p>
            </div>
        </div>

        <!-- System Info -->
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">System Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-3">Location</h4>
                    <p class="text-gray-600 mb-4">
                        <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                        Western Province, Rutsiro District<br>
                        Mukura Sector, Rwanda
                    </p>
                    
                    <h4 class="text-lg font-semibold text-gray-800 mb-3">Services Offered</h4>
                    <ul class="text-gray-600 space-y-1">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>General Consultation</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Maternal Health Services</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Vaccination Programs</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Laboratory Services</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Pharmacy Services</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Family Planning</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Emergency Care</li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-3">User Roles</h4>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium mr-3">Admin</span>
                            <span class="text-gray-600">System administration and user management</span>
                        </div>
                        <div class="flex items-center">
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium mr-3">Doctor</span>
                            <span class="text-gray-600">Medical consultations and prescriptions</span>
                        </div>
                        <div class="flex items-center">
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium mr-3">Nurse</span>
                            <span class="text-gray-600">Patient care and record management</span>
                        </div>
                        <div class="flex items-center">
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium mr-3">Lab Tech</span>
                            <span class="text-gray-600">Laboratory test management</span>
                        </div>
                        <div class="flex items-center">
                            <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium mr-3">Pharmacy</span>
                            <span class="text-gray-600">Medicine inventory and dispensing</span>
                        </div>
                        <div class="flex items-center">
                            <span class="bg-pink-100 text-pink-800 px-3 py-1 rounded-full text-sm font-medium mr-3">Vaccination</span>
                            <span class="text-gray-600">Immunization program management</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="flex items-center justify-center mb-4">
                <i class="fas fa-hospital text-2xl mr-3"></i>
                <span class="text-xl font-bold">Rutsiro Health Center</span>
            </div>
            <p class="text-gray-400">
                Developed by Tuyishime patrick AT ESTG 2025 <br>
                Providing quality healthcare services to the community since establishment.
            </p>
            <p class="text-gray-400 mt-2">
                &copy; <?php echo date('Y'); ?> Rutsiro Health Center. All rights reserved.
            </p>
        </div>
    </footer>
</body>
</html>
