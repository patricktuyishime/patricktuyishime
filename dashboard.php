<?php
require_once 'config/database.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats = [];

// Total patients
$query = "SELECT COUNT(*) as count FROM Patients";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['patients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Today's consultations
$query = "SELECT COUNT(*) as count FROM Consultations WHERE Date = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['consultations_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending lab tests
$query = "SELECT COUNT(*) as count FROM Laboratory WHERE Status = 'Pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_labs'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Low stock medicines (less than 50)
$query = "SELECT COUNT(*) as count FROM Pharmacy WHERE Stock < 50";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent activities
$recent_activities = [];

// Recent consultations
$query = "SELECT c.Date, CONCAT(p.FirstName, ' ', p.LastName) as patient_name, c.Diagnosis 
          FROM Consultations c 
          JOIN Patients p ON c.Patient_Id = p.Patient_Id 
          ORDER BY c.Date DESC, c.CreatedAt DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent vaccinations
$query = "SELECT v.DateAdministered, CONCAT(p.FirstName, ' ', p.LastName) as patient_name, v.VaccineType 
          FROM Vaccinations v 
          JOIN Patients p ON v.Patient_Id = p.Patient_Id 
          ORDER BY v.DateAdministered DESC, v.CreatedAt DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
        <div class="text-sm text-gray-600">
            <i class="fas fa-calendar mr-1"></i>
            <?php echo date('l, F j, Y'); ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Patients</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['patients']); ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Today's Consultations</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['consultations_today']); ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-stethoscope text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Lab Tests</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['pending_labs']); ?></p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-flask text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Low Stock Items</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['low_stock']); ?></p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Consultations -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-800">Recent Consultations</h2>
                <a href="consultations.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
            </div>
            <div class="space-y-4">
                <?php if (empty($recent_consultations)): ?>
                    <p class="text-gray-500 text-center py-4">No recent consultations</p>
                <?php else: ?>
                    <?php foreach ($recent_consultations as $consultation): ?>
                        <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                            <div class="bg-blue-100 p-2 rounded-full">
                                <i class="fas fa-user-md text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($consultation['patient_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($consultation['Diagnosis']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($consultation['Date'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Vaccinations -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-800">Recent Vaccinations</h2>
                <a href="vaccinations.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
            </div>
            <div class="space-y-4">
                <?php if (empty($recent_vaccinations)): ?>
                    <p class="text-gray-500 text-center py-4">No recent vaccinations</p>
                <?php else: ?>
                    <?php foreach ($recent_vaccinations as $vaccination): ?>
                        <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                            <div class="bg-green-100 p-2 rounded-full">
                                <i class="fas fa-syringe text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($vaccination['patient_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($vaccination['VaccineType']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($vaccination['DateAdministered'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php if (hasAnyRole(['admin', 'nurse', 'doctor'])): ?>
                <a href="patients.php?action=add" class="flex flex-col items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                    <i class="fas fa-user-plus text-blue-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-800">Add Patient</span>
                </a>
            <?php endif; ?>

            <?php if (hasAnyRole(['admin', 'doctor', 'nurse'])): ?>
                <a href="consultations.php?action=add" class="flex flex-col items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                    <i class="fas fa-stethoscope text-green-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-800">New Consultation</span>
                </a>
            <?php endif; ?>

            <?php if (hasAnyRole(['admin', 'vaccination_officer'])): ?>
                <a href="vaccinations.php?action=add" class="flex flex-col items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                    <i class="fas fa-syringe text-purple-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-800">Record Vaccination</span>
                </a>
            <?php endif; ?>

            <?php if (hasAnyRole(['admin', 'lab_technician'])): ?>
                <a href="laboratory.php?action=add" class="flex flex-col items-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors">
                    <i class="fas fa-flask text-yellow-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-800">Lab Test</span>
                </a>
            <?php endif; ?>

            <?php if (hasAnyRole(['admin', 'pharmacy_officer'])): ?>
                <a href="pharmacy.php?action=add" class="flex flex-col items-center p-4 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                    <i class="fas fa-pills text-red-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-800">Add Medicine</span>
                </a>
            <?php endif; ?>

            <a href="reports.php" class="flex flex-col items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="fas fa-chart-bar text-gray-600 text-2xl mb-2"></i>
                <span class="text-sm font-medium text-gray-800">View Reports</span>
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
