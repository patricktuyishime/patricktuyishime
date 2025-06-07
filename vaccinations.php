<?php
require_once 'config/database.php';
include 'includes/header.php';

if (!hasAnyRole(['admin', 'vaccination_officer'])) {
    header('Location: dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$action = $_GET['action'] ?? 'list';
$vaccination_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_vaccination'])) {
        $patient_id = $_POST['patient_id'];
        $vaccine_type = trim($_POST['vaccine_type']);
        $dose_number = $_POST['dose_number'];
        $date_administered = $_POST['date_administered'];
        $administered_by = trim($_POST['administered_by']);
        $next_due_date = !empty($_POST['next_due_date']) ? $_POST['next_due_date'] : null;

        $query = "INSERT INTO Vaccinations (Patient_Id, VaccineType, DoseNumber, DateAdministered, AdministeredBy, NextDueDate) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$patient_id, $vaccine_type, $dose_number, $date_administered, $administered_by, $next_due_date])) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Vaccination record added successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error adding vaccination record.</div>';
        }
    } elseif (isset($_POST['update_vaccination'])) {
        $patient_id = $_POST['patient_id'];
        $vaccine_type = trim($_POST['vaccine_type']);
        $dose_number = $_POST['dose_number'];
        $date_administered = $_POST['date_administered'];
        $administered_by = trim($_POST['administered_by']);
        $next_due_date = !empty($_POST['next_due_date']) ? $_POST['next_due_date'] : null;

        $query = "UPDATE Vaccinations SET Patient_Id=?, VaccineType=?, DoseNumber=?, DateAdministered=?, AdministeredBy=?, NextDueDate=? WHERE Vaccination_Id=?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$patient_id, $vaccine_type, $dose_number, $date_administered, $administered_by, $next_due_date, $vaccination_id])) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Vaccination record updated successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error updating vaccination record.</div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete']) {
    $delete_id = $_GET['delete'];
    $query = "DELETE FROM Vaccinations WHERE Vaccination_Id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$delete_id])) {
        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Vaccination record deleted successfully!</div>';
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error deleting vaccination record.</div>';
    }
}

// Get vaccination data for editing
$vaccination_data = null;
if ($action == 'edit' && $vaccination_id) {
    $query = "SELECT * FROM Vaccinations WHERE Vaccination_Id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$vaccination_id]);
    $vaccination_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all patients for dropdown
$query = "SELECT Patient_Id, CONCAT(FirstName, ' ', LastName) as FullName, DateOfBirth FROM Patients ORDER BY FirstName, LastName";
$stmt = $db->prepare($query);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get common vaccine types for dropdown
$vaccine_types = ['BCG', 'DPT', 'Polio', 'Measles', 'MMR', 'Hepatitis B', 'Rotavirus', 'Pneumococcal', 'HPV', 'COVID-19'];

// Get all vaccinations for listing
$search = $_GET['search'] ?? '';
$query = "SELECT v.*, CONCAT(p.FirstName, ' ', p.LastName) as PatientName, p.DateOfBirth 
          FROM Vaccinations v
          JOIN Patients p ON v.Patient_Id = p.Patient_Id";
$params = [];

if ($search) {
    $query .= " WHERE p.FirstName LIKE ? OR p.LastName LIKE ? OR v.VaccineType LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$query .= " ORDER BY v.DateAdministered DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <?php echo $message; ?>

    <?php if ($action == 'list'): ?>
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Vaccinations Management</h1>
            <a href="?action=add" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Add New Vaccination
            </a>
        </div>

        <!-- Search -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="GET" class="flex gap-4">
                <input type="hidden" name="action" value="list">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Search vaccinations by patient name or vaccine type..." 
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

        <!-- Vaccinations List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vaccine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dose</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Due</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($vaccinations)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    <?php echo $search ? 'No vaccinations found matching your search.' : 'No vaccinations recorded yet.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vaccinations as $vaccination): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($vaccination['DateAdministered'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="bg-purple-100 p-2 rounded-full mr-3">
                                                <i class="fas fa-user text-purple-600"></i>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($vaccination['PatientName']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php 
                                        $age = date_diff(date_create($vaccination['DateOfBirth']), date_create($vaccination['DateAdministered']))->format('%y years, %m months');
                                        echo $age;
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($vaccination['VaccineType']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $vaccination['DoseNumber']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $vaccination['NextDueDate'] ? date('M j, Y', strtotime($vaccination['NextDueDate'])) : 'N/A'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="?action=edit&id=<?php echo $vaccination['Vaccination_Id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $vaccination['Vaccination_Id']; ?>" 
                                               class="text-red-600 hover:text-red-900"
                                               onclick="return confirmDelete('Are you sure you want to delete this vaccination record?')">
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
                <?php echo $action == 'add' ? 'Add New Vaccination' : 'Edit Vaccination'; ?>
            </h1>
            <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST" id="vaccinationForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                        <select id="patient_id" name="patient_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['Patient_Id']; ?>" 
                                        <?php echo ($vaccination_data['Patient_Id'] ?? '') == $patient['Patient_Id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['FullName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="vaccine_type" class="block text-sm font-medium text-gray-700 mb-2">Vaccine Type *</label>
                        <select id="vaccine_type" name="vaccine_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Vaccine Type</option>
                            <?php foreach ($vaccine_types as $type): ?>
                                <option value="<?php echo $type; ?>" 
                                        <?php echo ($vaccination_data['VaccineType'] ?? '') == $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="other">Other</option>
                        </select>
                        <div id="other_vaccine_container" class="mt-2 hidden">
                            <input type="text" id="other_vaccine" placeholder="Specify vaccine type"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label for="dose_number" class="block text-sm font-medium text-gray-700 mb-2">Dose Number *</label>
                        <input type="number" id="dose_number" name="dose_number" min="1" required
                               value="<?php echo htmlspecialchars($vaccination_data['DoseNumber'] ?? '1'); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="date_administered" class="block text-sm font-medium text-gray-700 mb-2">Date Administered *</label>
                        <input type="date" id="date_administered" name="date_administered" required
                               value="<?php echo htmlspecialchars($vaccination_data['DateAdministered'] ?? date('Y-m-d')); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="administered_by" class="block text-sm font-medium text-gray-700 mb-2">Administered By *</label>
                        <input type="text" id="administered_by" name="administered_by" required
                               value="<?php echo htmlspecialchars($vaccination_data['AdministeredBy'] ?? $user['first_name'] . ' ' . $user['last_name']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="next_due_date" class="block text-sm font-medium text-gray-700 mb-2">Next Due Date</label>
                        <input type="date" id="next_due_date" name="next_due_date"
                               value="<?php echo htmlspecialchars($vaccination_data['NextDueDate'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" name="<?php echo $action == 'add' ? 'add_vaccination' : 'update_vaccination'; ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
                            onclick="return validateForm('vaccinationForm')">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $action == 'add' ? 'Add Vaccination' : 'Update Vaccination'; ?>
                    </button>
                </div>
            </form>
        </div>

        <script>
            // Handle "Other" vaccine type selection
            document.getElementById('vaccine_type').addEventListener('change', function() {
                const otherContainer = document.getElementById('other_vaccine_container');
                const otherInput = document.getElementById('other_vaccine');
                
                if (this.value === 'other') {
                    otherContainer.classList.remove('hidden');
                    otherInput.setAttribute('required', 'required');
                    
                    // Update the hidden input when the other input changes
                    otherInput.addEventListener('input', function() {
                        document.getElementById('vaccine_type').value = this.value;
                    });
                } else {
                    otherContainer.classList.add('hidden');
                    otherInput.removeAttribute('required');
                }
            });
        </script>
    <?php endif; ?>
</div>


<?php include 'includes/footer.php'; ?>
