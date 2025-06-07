<?php
require_once 'config/database.php';
include 'includes/header.php';

if (!hasAnyRole(['admin', 'lab_technician'])) {
    header('Location: dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$action = $_GET['action'] ?? 'list';
$lab_test_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_lab_test'])) {
        $patient_id = $_POST['patient_id'];
        $test_type = trim($_POST['test_type']);
        $result = trim($_POST['result']);
        $date_tested = $_POST['date_tested'];
        $lab_technician = trim($_POST['lab_technician']);
        $status = $_POST['status'];

        $query = "INSERT INTO Laboratory (Patient_Id, TestType, Result, DateTested, LabTechnician, Status) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$patient_id, $test_type, $result, $date_tested, $lab_technician, $status])) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Lab test added successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error adding lab test.</div>';
        }
    } elseif (isset($_POST['update_lab_test'])) {
        $patient_id = $_POST['patient_id'];
        $test_type = trim($_POST['test_type']);
        $result = trim($_POST['result']);
        $date_tested = $_POST['date_tested'];
        $lab_technician = trim($_POST['lab_technician']);
        $status = $_POST['status'];

        $query = "UPDATE Laboratory SET Patient_Id=?, TestType=?, Result=?, DateTested=?, LabTechnician=?, Status=? WHERE LabTest_Id=?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$patient_id, $test_type, $result, $date_tested, $lab_technician, $status, $lab_test_id])) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Lab test updated successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error updating lab test.</div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete']) {
    $delete_id = $_GET['delete'];
    $query = "DELETE FROM Laboratory WHERE LabTest_Id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$delete_id])) {
        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Lab test deleted successfully!</div>';
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error deleting lab test.</div>';
    }
}

// Get lab test data for editing
$lab_test_data = null;
if ($action == 'edit' && $lab_test_id) {
    $query = "SELECT * FROM Laboratory WHERE LabTest_Id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$lab_test_id]);
    $lab_test_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all patients for dropdown
$query = "SELECT Patient_Id, CONCAT(FirstName, ' ', LastName) as FullName FROM Patients ORDER BY FirstName, LastName";
$stmt = $db->prepare($query);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Common test types
$test_types = ['Blood Count', 'Malaria Test', 'Urinalysis', 'Blood Glucose', 'HIV Test', 'Pregnancy Test', 'Typhoid Test', 'Stool Analysis', 'Liver Function', 'Kidney Function'];

// Get all lab tests for listing
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$query = "SELECT l.*, CONCAT(p.FirstName, ' ', p.LastName) as PatientName 
          FROM Laboratory l
          JOIN Patients p ON l.Patient_Id = p.Patient_Id
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (p.FirstName LIKE ? OR p.LastName LIKE ? OR l.TestType LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $query .= " AND l.Status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY l.DateTested DESC, l.CreatedAt DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$lab_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <?php echo $message; ?>

    <?php if ($action == 'list'): ?>
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Laboratory Management</h1>
            <a href="?action=add" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Add New Lab Test
            </a>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <input type="hidden" name="action" value="list">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Search by patient name or test type..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
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

        <!-- Lab Tests List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Technician</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($lab_tests)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    <?php echo $search || $status_filter ? 'No lab tests found matching your criteria.' : 'No lab tests recorded yet.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lab_tests as $test): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($test['DateTested'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="bg-yellow-100 p-2 rounded-full mr-3">
                                                <i class="fas fa-user text-yellow-600"></i>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($test['PatientName']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($test['TestType']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($test['Result'] ?: 'Not available'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $test['Status'] == 'Completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $test['Status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($test['LabTechnician']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="?action=edit&id=<?php echo $test['LabTest_Id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $test['LabTest_Id']; ?>" 
                                               class="text-red-600 hover:text-red-900"
                                               onclick="return confirmDelete('Are you sure you want to delete this lab test?')">
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
                <?php echo $action == 'add' ? 'Add New Lab Test' : 'Edit Lab Test'; ?>
            </h1>
            <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST" id="labTestForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                        <select id="patient_id" name="patient_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['Patient_Id']; ?>" 
                                        <?php echo ($lab_test_data['Patient_Id'] ?? '') == $patient['Patient_Id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['FullName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="test_type" class="block text-sm font-medium text-gray-700 mb-2">Test Type *</label>
                        <select id="test_type" name="test_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Test Type</option>
                            <?php foreach ($test_types as $type): ?>
                                <option value="<?php echo $type; ?>" 
                                        <?php echo ($lab_test_data['TestType'] ?? '') == $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="other">Other</option>
                        </select>
                        <div id="other_test_container" class="mt-2 hidden">
                            <input type="text" id="other_test" placeholder="Specify test type"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label for="date_tested" class="block text-sm font-medium text-gray-700 mb-2">Date Tested *</label>
                        <input type="date" id="date_tested" name="date_tested" required
                               value="<?php echo htmlspecialchars($lab_test_data['DateTested'] ?? date('Y-m-d')); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="lab_technician" class="block text-sm font-medium text-gray-700 mb-2">Lab Technician *</label>
                        <input type="text" id="lab_technician" name="lab_technician" required
                               value="<?php echo htmlspecialchars($lab_test_data['LabTechnician'] ?? $user['first_name'] . ' ' . $user['last_name']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select id="status" name="status" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Pending" <?php echo ($lab_test_data['Status'] ?? '') == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Completed" <?php echo ($lab_test_data['Status'] ?? '') == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="result" class="block text-sm font-medium text-gray-700 mb-2">Result</label>
                    <textarea id="result" name="result" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($lab_test_data['Result'] ?? ''); ?></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" name="<?php echo $action == 'add' ? 'add_lab_test' : 'update_lab_test'; ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
                            onclick="return validateForm('labTestForm')">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $action == 'add' ? 'Add Lab Test' : 'Update Lab Test'; ?>
                    </button>
                </div>
            </form>
        </div>

        <script>
            // Handle "Other" test type selection
            document.getElementById('test_type').addEventListener('change', function() {
                const otherContainer = document.getElementById('other_test_container');
                const otherInput = document.getElementById('other_test');
                
                if (this.value === 'other') {
                    otherContainer.classList.remove('hidden');
                    otherInput.setAttribute('required', 'required');
                    
                    // Update the hidden input when the other input changes
                    otherInput.addEventListener('input', function() {
                        document.getElementById('test_type').value = this.value;
                    });
                } else {
                    otherContainer.classList.add('hidden');
                    otherInput.removeAttribute('required');
                }
            });

            // Show result field only when status is Completed
            document.getElementById('status').addEventListener('change', function() {
                const resultField = document.getElementById('result');
                const resultLabel = document.querySelector('label[for="result"]');
                
                if (this.value === 'Completed') {
                    resultField.setAttribute('required', 'required');
                    resultLabel.textContent = 'Result *';
                } else {
                    resultField.removeAttribute('required');
                    resultLabel.textContent = 'Result';
                }
            });

            // Trigger the status change event on page load
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('status').dispatchEvent(new Event('change'));
            });
        </script>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
