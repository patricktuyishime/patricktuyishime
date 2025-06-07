<?php
require_once 'config/database.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

$report_type = $_GET['type'] ?? 'overview';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Reports & Analytics</h1>
        <div class="text-sm text-gray-600">
            <i class="fas fa-calendar mr-1"></i>
            <?php echo date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)); ?>
        </div>
    </div>

    <!-- Enhanced Report Filters -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Report Filters & Options</h2>
    <form method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                <select id="type" name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Overview Dashboard</option>
                    <option value="patients" <?php echo $report_type == 'patients' ? 'selected' : ''; ?>>Patient Analytics</option>
                    <option value="consultations" <?php echo $report_type == 'consultations' ? 'selected' : ''; ?>>Consultation Reports</option>
                    <option value="vaccinations" <?php echo $report_type == 'vaccinations' ? 'selected' : ''; ?>>Vaccination Reports</option>
                    <option value="laboratory" <?php echo $report_type == 'laboratory' ? 'selected' : ''; ?>>Laboratory Reports</option>
                    <option value="pharmacy" <?php echo $report_type == 'pharmacy' ? 'selected' : ''; ?>>Pharmacy Reports</option>
                    <option value="financial" <?php echo $report_type == 'financial' ? 'selected' : ''; ?>>Financial Summary</option>
                    <option value="staff" <?php echo $report_type == 'staff' ? 'selected' : ''; ?>>Staff Performance</option>
                </select>
            </div>
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="period" class="block text-sm font-medium text-gray-700 mb-2">Quick Period</label>
                <select id="period" name="period" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Custom Range</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="this_week">This Week</option>
                    <option value="last_week">Last Week</option>
                    <option value="this_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="this_quarter">This Quarter</option>
                    <option value="this_year">This Year</option>
                </select>
            </div>
        </div>
        
        <!-- Additional Filters -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="age_group" class="block text-sm font-medium text-gray-700 mb-2">Age Group</label>
                <select id="age_group" name="age_group" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Ages</option>
                    <option value="0-5">Children (0-5 years)</option>
                    <option value="6-17">Youth (6-17 years)</option>
                    <option value="18-64">Adults (18-64 years)</option>
                    <option value="65+">Elderly (65+ years)</option>
                </select>
            </div>
            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                <select id="gender" name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div>
                <label for="export_format" class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                <select id="export_format" name="export_format" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">View Only</option>
                    <option value="pdf">PDF Report</option>
                    <option value="excel">Excel Spreadsheet</option>
                    <option value="csv">CSV Data</option>
                </select>
            </div>
        </div>

        <div class="flex flex-wrap gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                <i class="fas fa-chart-line mr-2"></i>Generate Report
            </button>
            <button type="button" onclick="resetFilters()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                <i class="fas fa-undo mr-2"></i>Reset Filters
            </button>
            <button type="button" onclick="saveReportTemplate()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors">
                <i class="fas fa-save mr-2"></i>Save Template
            </button>
            <button type="button" onclick="scheduleReport()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition-colors">
                <i class="fas fa-clock mr-2"></i>Schedule Report
            </button>
        </div>
    </form>
</div>

<script>
// Quick period selection
document.getElementById('period').addEventListener('change', function() {
    const period = this.value;
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const today = new Date();
    
    switch(period) {
        case 'today':
            startDate.value = endDate.value = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            startDate.value = endDate.value = yesterday.toISOString().split('T')[0];
            break;
        case 'this_week':
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay());
            startDate.value = startOfWeek.toISOString().split('T')[0];
            endDate.value = today.toISOString().split('T')[0];
            break;
        case 'last_week':
            const lastWeekStart = new Date(today);
            lastWeekStart.setDate(today.getDate() - today.getDay() - 7);
            const lastWeekEnd = new Date(lastWeekStart);
            lastWeekEnd.setDate(lastWeekStart.getDate() + 6);
            startDate.value = lastWeekStart.toISOString().split('T')[0];
            endDate.value = lastWeekEnd.toISOString().split('T')[0];
            break;
        case 'this_month':
            startDate.value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            endDate.value = today.toISOString().split('T')[0];
            break;
        case 'last_month':
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
            startDate.value = lastMonth.toISOString().split('T')[0];
            endDate.value = lastMonthEnd.toISOString().split('T')[0];
            break;
        case 'this_quarter':
            const quarter = Math.floor(today.getMonth() / 3);
            const quarterStart = new Date(today.getFullYear(), quarter * 3, 1);
            startDate.value = quarterStart.toISOString().split('T')[0];
            endDate.value = today.toISOString().split('T')[0];
            break;
        case 'this_year':
            startDate.value = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            endDate.value = today.toISOString().split('T')[0];
            break;
    }
});

function resetFilters() {
    document.getElementById('type').value = 'overview';
    document.getElementById('period').value = '';
    document.getElementById('age_group').value = '';
    document.getElementById('gender').value = '';
    document.getElementById('export_format').value = '';
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('start_date').value = firstDay.toISOString().split('T')[0];
    document.getElementById('end_date').value = today.toISOString().split('T')[0];
}

function saveReportTemplate() {
    alert('Report template saving functionality would be implemented here');
}

function scheduleReport() {
    alert('Report scheduling functionality would be implemented here');
}
</script>

    <?php if ($report_type == 'overview'): ?>
        <?php
        // Overview statistics
        $stats = [];
        
        // Total patients registered in period
        $query = "SELECT COUNT(*) as count FROM Patients WHERE DATE(CreatedAt) BETWEEN ? AND ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $stats['new_patients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total consultations in period
        $query = "SELECT COUNT(*) as count FROM Consultations WHERE Date BETWEEN ? AND ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $stats['consultations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total vaccinations in period
        $query = "SELECT COUNT(*) as count FROM Vaccinations WHERE DateAdministered BETWEEN ? AND ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $stats['vaccinations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total lab tests in period
        $query = "SELECT COUNT(*) as count FROM Laboratory WHERE DateTested BETWEEN ? AND ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $stats['lab_tests'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">New Patients</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['new_patients']); ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-user-plus text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Consultations</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['consultations']); ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-stethoscope text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Vaccinations</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['vaccinations']); ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-syringe text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Lab Tests</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['lab_tests']); ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-flask text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($report_type == 'vaccinations'): ?>
        <?php
        // Vaccination report
        $query = "SELECT v.VaccineType, COUNT(*) as count, 
                         CONCAT(p.FirstName, ' ', p.LastName) as patient_name,
                         v.DateAdministered, v.DoseNumber
                  FROM Vaccinations v 
                  JOIN Patients p ON v.Patient_Id = p.Patient_Id 
                  WHERE v.DateAdministered BETWEEN ? AND ?
                  ORDER BY v.DateAdministered DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Vaccination summary by type
        $query = "SELECT VaccineType, COUNT(*) as count 
                  FROM Vaccinations 
                  WHERE DateAdministered BETWEEN ? AND ?
                  GROUP BY VaccineType 
                  ORDER BY count DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $vaccine_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Vaccination Summary -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Vaccination Summary by Type</h2>
                <div class="space-y-3">
                    <?php if (empty($vaccine_summary)): ?>
                        <p class="text-gray-500 text-center py-4">No vaccinations recorded in this period</p>
                    <?php else: ?>
                        <?php foreach ($vaccine_summary as $vaccine): ?>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($vaccine['VaccineType']); ?></span>
                                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">
                                    <?php echo number_format($vaccine['count']); ?> doses
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Vaccinations -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Vaccinations</h2>
                <div class="space-y-3">
                    <?php if (empty($vaccinations)): ?>
                        <p class="text-gray-500 text-center py-4">No vaccinations recorded in this period</p>
                    <?php else: ?>
                        <?php foreach (array_slice($vaccinations, 0, 10) as $vaccination): ?>
                            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                <div class="bg-purple-100 p-2 rounded-full">
                                    <i class="fas fa-syringe text-purple-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($vaccination['patient_name']); ?></p>
                                    <p class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($vaccination['VaccineType']); ?> - Dose <?php echo $vaccination['DoseNumber']; ?>
                                    </p>
                                    <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($vaccination['DateAdministered'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php elseif ($report_type == 'pharmacy'): ?>
        <?php
        // Pharmacy reports
        $query = "SELECT * FROM Pharmacy WHERE Stock < 50 ORDER BY Stock ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $query = "SELECT * FROM Pharmacy WHERE ExpiryDate <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY ExpiryDate ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $expiring_soon = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $query = "SELECT p.MedicineName, SUM(p.Quantity) as total_dispensed 
                  FROM Prescriptions p 
                  WHERE p.DatePrescribed BETWEEN ? AND ? AND p.Status = 'Dispensed'
                  GROUP BY p.MedicineName 
                  ORDER BY total_dispensed DESC 
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $top_medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Low Stock Items -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    Low Stock Items
                </h2>
                <div class="space-y-3">
                    <?php if (empty($low_stock)): ?>
                        <p class="text-gray-500 text-center py-4">No low stock items</p>
                    <?php else: ?>
                        <?php foreach ($low_stock as $item): ?>
                            <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg border border-red-200">
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['MedicineName']); ?></p>
                                    <p class="text-sm text-gray-600">Stock: <?php echo $item['Stock']; ?> units</p>
                                </div>
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium">
                                    Low Stock
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Expiring Soon -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-clock text-yellow-500 mr-2"></i>
                    Expiring Soon
                </h2>
                <div class="space-y-3">
                    <?php if (empty($expiring_soon)): ?>
                        <p class="text-gray-500 text-center py-4">No items expiring soon</p>
                    <?php else: ?>
                        <?php foreach ($expiring_soon as $item): ?>
                            <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['MedicineName']); ?></p>
                                    <p class="text-sm text-gray-600">Expires: <?php echo date('M j, Y', strtotime($item['ExpiryDate'])); ?></p>
                                </div>
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium">
                                    <?php 
                                    $days = (strtotime($item['ExpiryDate']) - time()) / (60 * 60 * 24);
                                    echo ceil($days) . ' days';
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Dispensed Medicines -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-bar text-green-500 mr-2"></i>
                    Top Dispensed
                </h2>
                <div class="space-y-3">
                    <?php if (empty($top_medicines)): ?>
                        <p class="text-gray-500 text-center py-4">No medicines dispensed in this period</p>
                    <?php else: ?>
                        <?php foreach ($top_medicines as $medicine): ?>
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($medicine['MedicineName']); ?></p>
                                    <p class="text-sm text-gray-600">Total dispensed</p>
                                </div>
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                    <?php echo number_format($medicine['total_dispensed']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php elseif ($report_type == 'laboratory'): ?>
        <?php
        // Laboratory reports
        $query = "SELECT l.TestType, COUNT(*) as count,
                         AVG(CASE WHEN l.Status = 'Completed' THEN 1 ELSE 0 END) * 100 as completion_rate
                  FROM Laboratory l 
                  WHERE l.DateTested BETWEEN ? AND ?
                  GROUP BY l.TestType 
                  ORDER BY count DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $test_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $query = "SELECT l.*, CONCAT(p.FirstName, ' ', p.LastName) as patient_name
                  FROM Laboratory l 
                  JOIN Patients p ON l.Patient_Id = p.Patient_Id 
                  WHERE l.DateTested BETWEEN ? AND ?
                  ORDER BY l.DateTested DESC 
                  LIMIT 20";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $recent_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Test Summary -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Laboratory Test Summary</h2>
                <div class="space-y-3">
                    <?php if (empty($test_summary)): ?>
                        <p class="text-gray-500 text-center py-4">No lab tests recorded in this period</p>
                    <?php else: ?>
                        <?php foreach ($test_summary as $test): ?>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium text-gray-800"><?php echo htmlspecialchars($test['TestType']); ?></span>
                                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo number_format($test['count']); ?> tests
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $test['completion_rate']; ?>%"></div>
                                </div>
                                <p class="text-xs text-gray-600 mt-1"><?php echo number_format($test['completion_rate'], 1); ?>% completion rate</p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Tests -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Laboratory Tests</h2>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php if (empty($recent_tests)): ?>
                        <p class="text-gray-500 text-center py-4">No lab tests recorded in this period</p>
                    <?php else: ?>
                        <?php foreach ($recent_tests as $test): ?>
                            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                <div class="bg-yellow-100 p-2 rounded-full">
                                    <i class="fas fa-flask text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($test['patient_name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($test['TestType']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($test['DateTested'])); ?></p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-medium <?php echo $test['Status'] == 'Completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo $test['Status']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php elseif ($report_type == 'consultations'): ?>
        <?php
        // Consultation reports
        $query = "SELECT DATE(c.Date) as consultation_date, COUNT(*) as count
                  FROM Consultations c 
                  WHERE c.Date BETWEEN ? AND ?
                  GROUP BY DATE(c.Date) 
                  ORDER BY consultation_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $daily_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $query = "SELECT c.Diagnosis, COUNT(*) as count
                  FROM Consultations c 
                  WHERE c.Date BETWEEN ? AND ? AND c.Diagnosis IS NOT NULL AND c.Diagnosis != ''
                  GROUP BY c.Diagnosis 
                  ORDER BY count DESC 
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $common_diagnoses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $query = "SELECT c.*, CONCAT(p.FirstName, ' ', p.LastName) as patient_name
                  FROM Consultations c 
                  JOIN Patients p ON c.Patient_Id = p.Patient_Id 
                  WHERE c.Date BETWEEN ? AND ?
                  ORDER BY c.Date DESC, c.CreatedAt DESC 
                  LIMIT 15";
        $stmt = $db->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $recent_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="space-y-6">
            <!-- Daily Consultations Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Daily Consultations</h2>
                <div class="space-y-2">
                    <?php if (empty($daily_consultations)): ?>
                        <p class="text-gray-500 text-center py-4">No consultations recorded in this period</p>
                    <?php else: ?>
                        <?php foreach ($daily_consultations as $day): ?>
                            <div class="flex items-center space-x-4">
                                <div class="w-24 text-sm text-gray-600">
                                    <?php echo date('M j', strtotime($day['consultation_date'])); ?>
                                </div>
                                <div class="flex-1 bg-gray-200 rounded-full h-4">
                                    <div class="bg-blue-600 h-4 rounded-full" style="width: <?php echo min(($day['count'] / max(array_column($daily_consultations, 'count'))) * 100, 100); ?>%"></div>
                                </div>
                                <div class="w-12 text-sm font-medium text-gray-800">
                                    <?php echo $day['count']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Common Diagnoses -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Common Diagnoses</h2>
                    <div class="space-y-3">
                        <?php if (empty($common_diagnoses)): ?>
                            <p class="text-gray-500 text-center py-4">No diagnoses recorded in this period</p>
                        <?php else: ?>
                            <?php foreach ($common_diagnoses as $diagnosis): ?>
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="font-medium text-gray-800"><?php echo htmlspecialchars($diagnosis['Diagnosis']); ?></span>
                                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo number_format($diagnosis['count']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Consultations -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Consultations</h2>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php if (empty($recent_consultations)): ?>
                            <p class="text-gray-500 text-center py-4">No consultations recorded in this period</p>
                        <?php else: ?>
                            <?php foreach ($recent_consultations as $consultation): ?>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="bg-green-100 p-2 rounded-full">
                                        <i class="fas fa-stethoscope text-green-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($consultation['patient_name']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($consultation['Diagnosis'] ?: 'No diagnosis recorded'); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($consultation['Date'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($report_type == 'patients'): ?>
    <?php
    // Patient analytics
    $age_group = $_GET['age_group'] ?? '';
    $gender = $_GET['gender'] ?? '';
    
    // Patient registration trends
    $query = "SELECT DATE(CreatedAt) as reg_date, COUNT(*) as count 
              FROM Patients 
              WHERE DATE(CreatedAt) BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    
    if ($gender) {
        $query .= " AND Gender = ?";
        $params[] = $gender;
    }
    
    $query .= " GROUP BY DATE(CreatedAt) ORDER BY reg_date DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $registration_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Age distribution
    $query = "SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, DateOfBirth, CURDATE()) BETWEEN 0 AND 5 THEN '0-5'
                    WHEN TIMESTAMPDIFF(YEAR, DateOfBirth, CURDATE()) BETWEEN 6 AND 17 THEN '6-17'
                    WHEN TIMESTAMPDIFF(YEAR, DateOfBirth, CURDATE()) BETWEEN 18 AND 64 THEN '18-64'
                    ELSE '65+'
                END as age_group,
                COUNT(*) as count
              FROM Patients 
              WHERE DATE(CreatedAt) BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    
    if ($gender) {
        $query .= " AND Gender = ?";
        $params[] = $gender;
    }
    
    $query .= " GROUP BY age_group ORDER BY age_group";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $age_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Registration Trends -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Patient Registration Trends</h2>
            <div class="space-y-3">
                <?php if (empty($registration_trends)): ?>
                    <p class="text-gray-500 text-center py-4">No patient registrations in this period</p>
                <?php else: ?>
                    <?php foreach ($registration_trends as $trend): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-800">
                                <?php echo date('M j, Y', strtotime($trend['reg_date'])); ?>
                            </span>
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                <?php echo $trend['count']; ?> patients
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Age Distribution -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Age Distribution</h2>
            <div class="space-y-3">
                <?php if (empty($age_distribution)): ?>
                    <p class="text-gray-500 text-center py-4">No patient data available</p>
                <?php else: ?>
                    <?php foreach ($age_distribution as $age): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-800">
                                <?php 
                                switch($age['age_group']) {
                                    case '0-5': echo 'Children (0-5 years)'; break;
                                    case '6-17': echo 'Youth (6-17 years)'; break;
                                    case '18-64': echo 'Adults (18-64 years)'; break;
                                    case '65+': echo 'Elderly (65+ years)'; break;
                                }
                                ?>
                            </span>
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                <?php echo $age['count']; ?> patients
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif ($report_type == 'financial'): ?>
    <?php
    // Financial summary (basic implementation)
    $query = "SELECT 
                SUM(ph.UnitPrice * pr.Quantity) as total_revenue
              FROM Prescriptions pr
              JOIN Pharmacy ph ON pr.MedicineName = ph.MedicineName
              WHERE pr.DatePrescribed BETWEEN ? AND ? AND pr.Status = 'Dispensed'";
    $stmt = $db->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
    
    // Medicine sales breakdown
    $query = "SELECT 
                pr.MedicineName,
                SUM(pr.Quantity) as total_quantity,
                SUM(ph.UnitPrice * pr.Quantity) as total_value
              FROM Prescriptions pr
              JOIN Pharmacy ph ON pr.MedicineName = ph.MedicineName
              WHERE pr.DatePrescribed BETWEEN ? AND ? AND pr.Status = 'Dispensed'
              GROUP BY pr.MedicineName
              ORDER BY total_value DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $medicine_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Summary -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Revenue Summary</h2>
            <div class="text-center">
                <div class="text-4xl font-bold text-green-600 mb-2">
                    RWF <?php echo number_format($revenue, 2); ?>
                </div>
                <p class="text-gray-600">Total Revenue from Medicine Sales</p>
            </div>
        </div>

        <!-- Top Selling Medicines -->
        <div class="bg-white rounded-xl shadow-lg p-6 lg:col-span-2">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Top Selling Medicines</h2>
            <div class="space-y-3">
                <?php if (empty($medicine_sales)): ?>
                    <p class="text-gray-500 text-center py-4">No medicine sales in this period</p>
                <?php else: ?>
                    <?php foreach ($medicine_sales as $sale): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($sale['MedicineName']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo $sale['total_quantity']; ?> units sold</p>
                            </div>
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                RWF <?php echo number_format($sale['total_value'], 2); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif ($report_type == 'staff'): ?>
    <?php
    // Staff performance metrics
    $query = "SELECT 
                u.FirstName, u.LastName, u.Role,
                COUNT(c.Consultation_Id) as consultation_count
              FROM Users u
              LEFT JOIN Consultations c ON CONCAT(u.FirstName, ' ', u.LastName) = c.DoctorName
                AND c.Date BETWEEN ? AND ?
              WHERE u.Role IN ('doctor', 'nurse')
              GROUP BY u.User_Id
              ORDER BY consultation_count DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $staff_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lab technician performance
    $query = "SELECT 
                u.FirstName, u.LastName,
                COUNT(l.LabTest_Id) as test_count,
                SUM(CASE WHEN l.Status = 'Completed' THEN 1 ELSE 0 END) as completed_tests
              FROM Users u
              LEFT JOIN Laboratory l ON CONCAT(u.FirstName, ' ', u.LastName) = l.LabTechnician
                AND l.DateTested BETWEEN ? AND ?
              WHERE u.Role = 'lab_technician'
              GROUP BY u.User_Id
              ORDER BY test_count DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $lab_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Medical Staff Performance -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Medical Staff Consultations</h2>
            <div class="space-y-3">
                <?php if (empty($staff_consultations)): ?>
                    <p class="text-gray-500 text-center py-4">No consultation data available</p>
                <?php else: ?>
                    <?php foreach ($staff_consultations as $staff): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-800">
                                    <?php echo htmlspecialchars($staff['FirstName'] . ' ' . $staff['LastName']); ?>
                                </p>
                                <p class="text-sm text-gray-600 capitalize">
                                    <?php echo str_replace('_', ' ', $staff['Role']); ?>
                                </p>
                            </div>
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                <?php echo $staff['consultation_count']; ?> consultations
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lab Staff Performance -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Laboratory Staff Performance</h2>
            <div class="space-y-3">
                <?php if (empty($lab_performance)): ?>
                    <p class="text-gray-500 text-center py-4">No lab test data available</p>
                <?php else: ?>
                    <?php foreach ($lab_performance as $lab): ?>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <p class="font-medium text-gray-800">
                                    <?php echo htmlspecialchars($lab['FirstName'] . ' ' . $lab['LastName']); ?>
                                </p>
                                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                    <?php echo $lab['test_count']; ?> tests
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <?php 
                                $completion_rate = $lab['test_count'] > 0 ? ($lab['completed_tests'] / $lab['test_count']) * 100 : 0;
                                ?>
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $completion_rate; ?>%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">
                                <?php echo number_format($completion_rate, 1); ?>% completion rate
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- Export Options -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Export Report</h2>
        <div class="flex space-x-4">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                <i class="fas fa-print mr-2"></i>Print Report
            </button>
            <button onclick="exportToCSV()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors">
                <i class="fas fa-file-csv mr-2"></i>Export to CSV
            </button>
        </div>
    </div>
</div>

<script>
function exportToCSV() {
    // This would implement CSV export functionality
    alert('CSV export functionality would be implemented here');
}
</script>

<?php include 'includes/footer.php'; ?>
