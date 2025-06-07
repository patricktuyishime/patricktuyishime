<?php
require_once 'config/database.php';
include 'includes/header.php';

if (!hasAnyRole(['admin', 'doctor', 'nurse'])) {
    header('Location: dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$action = $_GET['action'] ?? 'list';
$consultation_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_consultation'])) {
        $patient_id = $_POST['patient_id'];
        $date = $_POST['date'];
        $symptoms = trim($_POST['symptoms']);
        $diagnosis = trim($_POST['diagnosis']);
        $treatment = trim($_POST['treatment']);
        $doctor_name = trim($_POST['doctor_name']);

        $query = "INSERT INTO Consultations (Patient_Id, Date, Symptoms, Diagnosis, Treatment, DoctorName) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$patient_id, $date, $symptoms, $diagnosis, $treatment, $doctor_name])) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Consultation added successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error adding consultation.</div>';
        }
    } elseif (isset($_POST['update_consultation'])) {
        $patient_id = $_POST['patient_id'];
        $date = $_POST['date'];
        $symptoms = trim($_POST['symptoms']);
        $diagnosis = trim($_POST['diagnosis']);
        $treatment = trim($_POST['treatment']);
        $doctor_name = trim($_POST['doctor_name']);

        $query = "UPDATE Consultations SET Patient_Id=?, Date=?, Symptoms=?, Diagnosis=?, Treatment=?, DoctorName=? WHERE Consultation_Id=?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$patient_id, $date, $symptoms, $diagnosis, $treatment, $doctor_name, $consultation_id])) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Consultation updated successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error updating consultation.</div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete']) {
    $delete_id = $_GET['delete'];
    $query = "DELETE FROM Consultations WHERE Consultation_Id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$delete_id])) {
        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Consultation deleted successfully!</div>';
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error deleting consultation.</div>';
    }
}

// Get consultation data for editing
$consultation_data = null;
if ($action == 'edit' && $consultation_id) {
    $query = "SELECT * FROM Consultations WHERE Consultation_Id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$consultation_id]);
    $consultation_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all patients for dropdown
$query = "SELECT Patient_Id, CONCAT(FirstName, ' ', LastName) as FullName FROM Patients ORDER BY FirstName, LastName";
$stmt = $db->prepare($query);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all consultations for listing
$search = $_GET['search'] ?? '';
$query = "SELECT c.*, CONCAT(p.FirstName, ' ', p.LastName) as PatientName 
          FROM Consultations c
          JOIN Patients p ON c.Patient_Id = p.Patient_Id";
$params = [];

if ($search) {
    $query .= " WHERE p.FirstName LIKE ? OR p.LastName LIKE ? OR c.Diagnosis LIKE ? OR c.DoctorName LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
}

$query .= " ORDER BY c.Date DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <?php echo $message; ?>

    <?php if ($action == 'list'): ?>
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Consultations Management</h1>
            <a href="?action=add" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Add New Consultation
            </a>
        </div>

        <!-- Search -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="GET" class="flex gap-4">
                <input type="hidden" name="action" value="list">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Search consultations by patient name, diagnosis or doctor..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <?php if ($search): ?>
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Consultations List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($consultations)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    <?php echo $search ? 'No consultations found matching your search.' : 'No consultations recorded yet.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($consultations as $consultation): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($consultation['Date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="bg-green-100 p-2 rounded-full mr-3">
                                                <i class="fas fa-user text-green-600"></i>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($consultation['PatientName']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($consultation['Diagnosis'] ?: 'Not specified'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($consultation['DoctorName']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="?action=edit&id=<?php echo $consultation['Consultation_Id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $consultation['Consultation_Id']; ?>" 
                                               class="text-red-600 hover:text-red-900"
                                               onclick="return confirmDelete('Are you sure you want to delete this consultation?')">
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
                <?php echo $action == 'add' ? 'Add New Consultation' : 'Edit Consultation'; ?>
            </h1>
            <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST" id="consultationForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                        <select id="patient_id" name="patient_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['Patient_Id']; ?>" 
                                        <?php echo ($consultation_data['Patient_Id'] ?? '') == $patient['Patient_Id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['FullName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                        <input type="date" id="date" name="date" required
                               value="<?php echo htmlspecialchars($consultation_data['Date'] ?? date('Y-m-d')); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="doctor_name" class="block text-sm font-medium text-gray-700 mb-2">Doctor Name *</label>
                        <input type="text" id="doctor_name" name="doctor_name" required
                               value="<?php echo htmlspecialchars($consultation_data['DoctorName'] ?? $user['first_name'] . ' ' . $user['last_name']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label for="symptoms" class="block text-sm font-medium text-gray-700 mb-2">Symptoms</label>
                    <textarea id="symptoms" name="symptoms" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($consultation_data['Symptoms'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label for="diagnosis" class="block text-sm font-medium text-gray-700 mb-2">Diagnosis</label>
                    <textarea id="diagnosis" name="diagnosis" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($consultation_data['Diagnosis'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label for="treatment" class="block text-sm font-medium text-gray-700 mb-2">Treatment</label>
                    <textarea id="treatment" name="treatment" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($consultation_data['Treatment'] ?? ''); ?></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" name="<?php echo $action == 'add' ? 'add_consultation' : 'update_consultation'; ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
                            onclick="return validateForm('consultationForm')">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $action == 'add' ? 'Add Consultation' : 'Update Consultation'; ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
