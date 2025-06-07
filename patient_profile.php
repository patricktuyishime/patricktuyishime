<?php
require_once 'config/database.php';
include 'includes/header.php';

if (!hasAnyRole(['admin', 'nurse', 'doctor', 'lab_technician', 'pharmacy_officer'])) {
    header('Location: dashboard.php');
    exit();
}

$patient_id = $_GET['id'] ?? null;
if (!$patient_id) {
    header('Location: patients.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get patient information
$query = "SELECT * FROM Patients WHERE Patient_Id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    header('Location: patients.php');
    exit();
}

// Get patient's consultations
$query = "SELECT * FROM Consultations WHERE Patient_Id = ? ORDER BY Date DESC";
$stmt = $db->prepare($query);
$stmt->execute([$patient_id]);
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get patient's vaccinations
$query = "SELECT * FROM Vaccinations WHERE Patient_Id = ? ORDER BY DateAdministered DESC";
$stmt = $db->prepare($query);
$stmt->execute([$patient_id]);
$vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get patient's lab tests
$query = "SELECT * FROM Laboratory WHERE Patient_Id = ? ORDER BY DateTested DESC";
$stmt = $db->prepare($query);
$stmt->execute([$patient_id]);
$lab_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get patient's prescriptions
$query = "SELECT * FROM Prescriptions WHERE Patient_Id = ? ORDER BY DatePrescribed DESC";
$stmt = $db->prepare($query);
$stmt->execute([$patient_id]);
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$age = date_diff(date_create($patient['DateOfBirth']), date_create('today'))->y;
?>

<div class="space-y-6">
    <!-- Patient Header -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-start">
            <div class="flex items-center space-x-4">
                <div class="bg-blue-100 p-4 rounded-full">
                    <i class="fas fa-user text-blue-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        <?php echo htmlspecialchars($patient['FirstName'] . ' ' . $patient['LastName']); ?>
                    </h1>
                    <div class="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                        <span><i class="fas fa-id-card mr-1"></i>ID: <?php echo $patient['Patient_Id']; ?></span>
                        <span><i class="fas fa-birthday-cake mr-1"></i><?php echo $age; ?> years old</span>
                        <span><i class="fas fa-venus-mars mr-1"></i><?php echo $patient['Gender']; ?></span>
                        <span><i class="fas fa-calendar mr-1"></i>Born: <?php echo date('M j, Y', strtotime($patient['DateOfBirth'])); ?></span>
                    </div>
                    <div class="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                        <span><i class="fas fa-phone mr-1"></i><?php echo $patient['PhoneNumber'] ?: 'Not provided'; ?></span>
                        <span><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($patient['Address'] ?: 'Not provided'); ?></span>
                    </div>
                </div>
            </div>
            <div class="flex space-x-2">
                <a href="patients.php?action=edit&id=<?php echo $patient['Patient_Id']; ?>" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-edit mr-2"></i>Edit Patient
                </a>
                <a href="patients.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php if (hasAnyRole(['admin', 'doctor', 'nurse'])): ?>
            <a href="consultations.php?action=add&patient_id=<?php echo $patient['Patient_Id']; ?>" 
               class="bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-4 text-center transition-colors">
                <i class="fas fa-stethoscope text-green-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium text-green-800">New Consultation</p>
            </a>
        <?php endif; ?>
        
        <?php if (hasAnyRole(['admin', 'vaccination_officer'])): ?>
            <a href="vaccinations.php?action=add&patient_id=<?php echo $patient['Patient_Id']; ?>" 
               class="bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg p-4 text-center transition-colors">
                <i class="fas fa-syringe text-purple-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium text-purple-800">Add Vaccination</p>
            </a>
        <?php endif; ?>
        
        <?php if (hasAnyRole(['admin', 'lab_technician'])): ?>
            <a href="laboratory.php?action=add&patient_id=<?php echo $patient['Patient_Id']; ?>" 
               class="bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 rounded-lg p-4 text-center transition-colors">
                <i class="fas fa-flask text-yellow-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium text-yellow-800">Order Lab Test</p>
            </a>
        <?php endif; ?>
        
        <?php if (hasAnyRole(['admin', 'doctor', 'pharmacy_officer'])): ?>
            <a href="prescriptions.php?action=add&patient_id=<?php echo $patient['Patient_Id']; ?>" 
               class="bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg p-4 text-center transition-colors">
                <i class="fas fa-prescription text-red-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium text-red-800">New Prescription</p>
            </a>
        <?php endif; ?>
    </div>

    <!-- Patient Records Tabs -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button onclick="showTab('consultations')" id="tab-consultations" 
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-stethoscope mr-2"></i>Consultations (<?php echo count($consultations); ?>)
                </button>
                <button onclick="showTab('vaccinations')" id="tab-vaccinations"
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-syringe mr-2"></i>Vaccinations (<?php echo count($vaccinations); ?>)
                </button>
                <button onclick="showTab('lab-tests')" id="tab-lab-tests"
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-flask mr-2"></i>Lab Tests (<?php echo count($lab_tests); ?>)
                </button>
                <button onclick="showTab('prescriptions')" id="tab-prescriptions"
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-prescription mr-2"></i>Prescriptions (<?php echo count($prescriptions); ?>)
                </button>
            </nav>
        </div>

        <!-- Tab Contents -->
        <div class="p-6">
            <!-- Consultations Tab -->
            <div id="content-consultations" class="tab-content">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Medical Consultations</h3>
                <?php if (empty($consultations)): ?>
                    <p class="text-gray-500 text-center py-8">No consultations recorded for this patient.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($consultations as $consultation): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-medium text-gray-800">
                                            <?php echo date('F j, Y', strtotime($consultation['Date'])); ?>
                                        </h4>
                                        <p class="text-sm text-gray-600">Dr. <?php echo htmlspecialchars($consultation['DoctorName']); ?></p>
                                    </div>
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                                        Consultation #<?php echo $consultation['Consultation_Id']; ?>
                                    </span>
                                </div>
                                
                                <?php if ($consultation['Symptoms']): ?>
                                    <div class="mb-3">
                                        <h5 class="text-sm font-medium text-gray-700 mb-1">Symptoms:</h5>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($consultation['Symptoms']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($consultation['Diagnosis']): ?>
                                    <div class="mb-3">
                                        <h5 class="text-sm font-medium text-gray-700 mb-1">Diagnosis:</h5>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($consultation['Diagnosis']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($consultation['Treatment']): ?>
                                    <div>
                                        <h5 class="text-sm font-medium text-gray-700 mb-1">Treatment:</h5>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($consultation['Treatment']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Vaccinations Tab -->
            <div id="content-vaccinations" class="tab-content hidden">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Vaccination History</h3>
                <?php if (empty($vaccinations)): ?>
                    <p class="text-gray-500 text-center py-8">No vaccinations recorded for this patient.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($vaccinations as $vaccination): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-medium text-gray-800">
                                            <?php echo htmlspecialchars($vaccination['VaccineType']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            Dose <?php echo $vaccination['DoseNumber']; ?> - 
                                            <?php echo date('F j, Y', strtotime($vaccination['DateAdministered'])); ?>
                                        </p>
                                    </div>
                                    <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs">
                                        Vaccination #<?php echo $vaccination['Vaccination_Id']; ?>
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-700">Administered by:</span>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($vaccination['AdministeredBy']); ?></p>
                                    </div>
                                    <?php if ($vaccination['NextDueDate']): ?>
                                        <div>
                                            <span class="font-medium text-gray-700">Next due:</span>
                                            <p class="text-gray-600"><?php echo date('F j, Y', strtotime($vaccination['NextDueDate'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lab Tests Tab -->
            <div id="content-lab-tests" class="tab-content hidden">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Laboratory Tests</h3>
                <?php if (empty($lab_tests)): ?>
                    <p class="text-gray-500 text-center py-8">No lab tests recorded for this patient.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($lab_tests as $test): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-medium text-gray-800">
                                            <?php echo htmlspecialchars($test['TestType']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            <?php echo date('F j, Y', strtotime($test['DateTested'])); ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="<?php echo $test['Status'] == 'Completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> px-2 py-1 rounded text-xs">
                                            <?php echo $test['Status']; ?>
                                        </span>
                                        <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">
                                            Test #<?php echo $test['LabTest_Id']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-700">Lab Technician:</span>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($test['LabTechnician']); ?></p>
                                    </div>
                                    <?php if ($test['Result']): ?>
                                        <div>
                                            <span class="font-medium text-gray-700">Result:</span>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($test['Result']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Prescriptions Tab -->
            <div id="content-prescriptions" class="tab-content hidden">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Prescriptions</h3>
                <?php if (empty($prescriptions)): ?>
                    <p class="text-gray-500 text-center py-8">No prescriptions recorded for this patient.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($prescriptions as $prescription): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-medium text-gray-800">
                                            <?php echo htmlspecialchars($prescription['MedicineName']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            Quantity: <?php echo $prescription['Quantity']; ?> - 
                                            <?php echo date('F j, Y', strtotime($prescription['DatePrescribed'])); ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="<?php echo $prescription['Status'] == 'Dispensed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> px-2 py-1 rounded text-xs">
                                            <?php echo $prescription['Status']; ?>
                                        </span>
                                        <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">
                                            Rx #<?php echo $prescription['Prescription_Id']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-700">Prescribed by:</span>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($prescription['PrescribedBy']); ?></p>
                                    </div>
                                    <?php if ($prescription['Instructions']): ?>
                                        <div>
                                            <span class="font-medium text-gray-700">Instructions:</span>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($prescription['Instructions']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.classList.add('hidden'));
    
    // Remove active class from all tab buttons
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.remove('border-transparent', 'text-gray-500');
    activeButton.classList.add('border-blue-500', 'text-blue-600');
}

// Show consultations tab by default
document.addEventListener('DOMContentLoaded', function() {
    showTab('consultations');
});
</script>

<?php include 'includes/footer.php'; ?>
