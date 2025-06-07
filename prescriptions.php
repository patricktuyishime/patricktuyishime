<?php
require_once 'config/database.php';
include 'includes/header.php';

if (!hasAnyRole(['admin', 'pharmacy_officer', 'doctor'])) {
    header('Location: dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$action = $_GET['action'] ?? 'list';
$prescription_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_prescription'])) {
        $patient_id = $_POST['patient_id'];
        $medicine_name = trim($_POST['medicine_name']);
        $quantity = $_POST['quantity'];
        $date_prescribed = $_POST['date_prescribed'];
        $prescribed_by = trim($_POST['prescribed_by']);
        $instructions = trim($_POST['instructions']);
        $status = $_POST['status'];

        $query = "INSERT INTO Prescriptions (Patient_Id, MedicineName, Quantity, DatePrescribed, PrescribedBy, Instructions, Status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$patient_id, $medicine_name, $quantity, $date_prescribed, $prescribed_by, $instructions, $status])) {
            // If status is Dispensed, update pharmacy stock
            if ($status == 'Dispensed') {
                $query = "UPDATE Pharmacy SET Stock = Stock - ? WHERE MedicineName = ? AND Stock >= ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$quantity, $medicine_name, $quantity]);
            }
            
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Prescription added successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error adding prescription.</div>';
        }
    } elseif (isset($_POST['update_prescription'])) {
        $patient_id = $_POST['patient_id'];
        $medicine_name = trim($_POST['medicine_name']);
        $quantity = $_POST['quantity'];
        $date_prescribed = $_POST['date_prescribed'];
        $prescribed_by = trim($_POST['prescribed_by']);
        $instructions = trim($_POST['instructions']);
        $status = $_POST['status'];
        $old_status = $_POST['old_status'];

        $query = "UPDATE Prescriptions SET Patient_Id=?, MedicineName=?, Quantity=?, DatePrescribed=?, PrescribedBy=?, Instructions=?, Status=? WHERE Prescription_Id=?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$patient_id, $medicine_name, $quantity, $date_prescribed, $prescribed_by, $instructions, $status, $prescription_id])) {
            // If status changed from Pending to Dispensed, update pharmacy stock
            if ($old_status == 'Pending' && $status == 'Dispensed') {
                $query = "UPDATE Pharmacy SET Stock = Stock - ? WHERE MedicineName = ? AND Stock >= ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$quantity, $medicine_name, $quantity]);
            }
            
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Prescription updated successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error updating prescription.</div>';
        }
    } elseif (isset($_POST['dispense_prescription'])) {
        $prescription_id = $_POST['prescription_id'];
        
        // Get prescription details
        $query = "SELECT MedicineName, Quantity FROM Prescriptions WHERE Prescription_Id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$prescription_id]);
        $prescription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($prescription) {
            // Check if enough stock is available
            $query = "SELECT Stock FROM Pharmacy WHERE MedicineName = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$prescription['MedicineName']]);
            $medicine = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($medicine && $medicine['Stock'] >= $prescription['Quantity']) {
                // Update prescription status
                $query = "UPDATE Prescriptions SET Status = 'Dispensed' WHERE Prescription_Id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$prescription_id]);
                
                // Update pharmacy stock
                $query = "UPDATE Pharmacy SET Stock = Stock - ? WHERE MedicineName = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$prescription['Quantity'], $prescription['MedicineName']]);
                
                $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Prescription dispensed successfully!</div>';
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error: Insufficient stock available.</div>';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete']) {
    $delete_id = $_GET['delete'];
    $query = "DELETE FROM Prescriptions WHERE Prescription_Id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$delete_id])) {
        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Prescription deleted successfully!</div>';
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error deleting prescription.</div>';
    }
}

// Get prescription data for editing
$prescription_data = null;
if ($action == 'edit' && $prescription_id) {
    $query = "SELECT * FROM Prescriptions WHERE Prescription_Id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$prescription_id]);
    $prescription_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all patients for dropdown
$query = "SELECT Patient_Id, CONCAT(FirstName, ' ', LastName) as FullName FROM Patients ORDER BY FirstName, LastName";
$stmt = $db->prepare($query);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all medicines for dropdown
$query = "SELECT DISTINCT MedicineName FROM Pharmacy ORDER BY MedicineName";
$stmt = $db->prepare($query);
$stmt->execute();
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all prescriptions for listing
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$query = "SELECT p.*, CONCAT(pt.FirstName, ' ', pt.LastName) as PatientName 
          FROM Prescriptions p
          JOIN Patients pt ON p.Patient_Id = pt.Patient_Id
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (pt.FirstName LIKE ? OR pt.LastName LIKE ? OR p.MedicineName LIKE ? OR p.PrescribedBy LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $query .= " AND p.Status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY p.DatePrescribed DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <?php echo $message; ?>

    <?php if ($action == 'list'): ?>
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Prescriptions Management</h1>
            <?php if (hasAnyRole(['admin', 'doctor'])): ?>
                <a href="?action=add" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Prescription
                </a>
            <?php endif; ?>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <input type="hidden" name="action" value="list">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Search by patient name, medicine, or doctor..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Dispensed" <?php echo $status_filter == 'Dispensed' ? 'selected' : ''; ?>>Dispensed</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <?php if ($search || $status_filter): ?>
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Prescriptions List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prescribed By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($prescriptions)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    <?php echo $search || $status_filter ? 'No prescriptions found matching your criteria.' : 'No prescriptions recorded yet.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($prescriptions as $prescription): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($prescription['DatePrescribed'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="bg-indigo-100 p-2 rounded-full mr-3">
                                                <i class="fas fa-user text-indigo-600"></i>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($prescription['PatientName']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($prescription['MedicineName']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $prescription['Quantity']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($prescription['PrescribedBy']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $prescription['Status'] == 'Dispensed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $prescription['Status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <?php if ($prescription['Status'] == 'Pending' && hasAnyRole(['admin', 'pharmacy_officer'])): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="prescription_id" value="<?php echo $prescription['Prescription_Id']; ?>">
                                                    <button type="submit" name="dispense_prescription" 
                                                            class="text-green-600 hover:text-green-900" title="Dispense"
                                                            onclick="return confirm('Are you sure you want to dispense this prescription?')">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if (hasAnyRole(['admin', 'doctor'])): ?>
                                                <a href="?action=edit&id=<?php echo $prescription['Prescription_Id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-900" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?delete=<?php echo $prescription['Prescription_Id']; ?>" 
                                               class="text-red-600 hover:text-red-900" title="Delete"
                                               onclick="return confirmDelete('Are you sure you want to delete this prescription?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
                <?php echo $action == 'add' ? 'Add New Prescription' : 'Edit Prescription'; ?>
            </h1>
            <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST" id="prescriptionForm" class="space-y-6">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="old_status" value="<?php echo htmlspecialchars($prescription_data['Status'] ?? ''); ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                        <select id="patient_id" name="patient_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['Patient_Id']; ?>" 
                                        <?php echo ($prescription_data['Patient_Id'] ?? '') == $patient['Patient_Id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['FullName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="medicine_name" class="block text-sm font-medium text-gray-700 mb-2">Medicine *</label>
                        <select id="medicine_name" name="medicine_name" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Medicine</option>
                            <?php foreach ($medicines as $medicine): ?>
                                <option value="<?php echo htmlspecialchars($medicine['MedicineName']); ?>" 
                                        <?php echo ($prescription_data['MedicineName'] ?? '') == $medicine['MedicineName'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($medicine['MedicineName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="1" required
                               value="<?php echo htmlspecialchars($prescription_data['Quantity'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="date_prescribed" class="block text-sm font-medium text-gray-700 mb-2">Date Prescribed *</label>
                        <input type="date" id="date_prescribed" name="date_prescribed" required
                               value="<?php echo htmlspecialchars($prescription_data['DatePrescribed'] ?? date('Y-m-d')); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="prescribed_by" class="block text-sm font-medium text-gray-700 mb-2">Prescribed By *</label>
                        <input type="text" id="prescribed_by" name="prescribed_by" required
                               value="<?php echo htmlspecialchars($prescription_data['PrescribedBy'] ?? $user['first_name'] . ' ' . $user['last_name']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select id="status" name="status" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Pending" <?php echo ($prescription_data['Status'] ?? '') == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Dispensed" <?php echo ($prescription_data['Status'] ?? '') == 'Dispensed' ? 'selected' : ''; ?>>Dispensed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="instructions" class="block text-sm font-medium text-gray-700 mb-2">Instructions</label>
                    <textarea id="instructions" name="instructions" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($prescription_data['Instructions'] ?? ''); ?></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" name="<?php echo $action == 'add' ? 'add_prescription' : 'update_prescription'; ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
                            onclick="return validateForm('prescriptionForm')">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $action == 'add' ? 'Add Prescription' : 'Update Prescription'; ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
