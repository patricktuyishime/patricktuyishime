<?php
require_once 'config/database.php';
include 'includes/header.php';

if (!hasAnyRole(['admin', 'pharmacy_officer'])) {
    header('Location: dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$action = $_GET['action'] ?? 'list';
$pharmacy_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_medicine'])) {
        $medicine_name = trim($_POST['medicine_name']);
        $stock = $_POST['stock'];
        $unit_price = $_POST['unit_price'];
        $expiry_date = $_POST['expiry_date'];
        $supplier = trim($_POST['supplier']);

        $query = "INSERT INTO Pharmacy (MedicineName, Stock, UnitPrice, ExpiryDate, Supplier) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$medicine_name, $stock, $unit_price, $expiry_date, $supplier])) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Medicine added successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error adding medicine.</div>';
        }
    } elseif (isset($_POST['update_medicine'])) {
        $medicine_name = trim($_POST['medicine_name']);
        $stock = $_POST['stock'];
        $unit_price = $_POST['unit_price'];
        $expiry_date = $_POST['expiry_date'];
        $supplier = trim($_POST['supplier']);

        $query = "UPDATE Pharmacy SET MedicineName=?, Stock=?, UnitPrice=?, ExpiryDate=?, Supplier=? WHERE Pharmacy_Id=?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$medicine_name, $stock, $unit_price, $expiry_date, $supplier, $pharmacy_id])) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Medicine updated successfully!</div>';
            $action = 'list';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error updating medicine.</div>';
        }
    } elseif (isset($_POST['update_stock'])) {
        $pharmacy_id = $_POST['pharmacy_id'];
        $stock_change = $_POST['stock_change'];
        $current_stock = $_POST['current_stock'];
        
        $new_stock = $current_stock + $stock_change;
        if ($new_stock < 0) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error: Stock cannot be negative.</div>';
        } else {
            $query = "UPDATE Pharmacy SET Stock=? WHERE Pharmacy_Id=?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$new_stock, $pharmacy_id])) {
                $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Stock updated successfully!</div>';
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error updating stock.</div>';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete']) {
    $delete_id = $_GET['delete'];
    $query = "DELETE FROM Pharmacy WHERE Pharmacy_Id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$delete_id])) {
        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">Medicine deleted successfully!</div>';
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error deleting medicine.</div>';
    }
}

// Get medicine data for editing
$medicine_data = null;
if ($action == 'edit' && $pharmacy_id) {
    $query = "SELECT * FROM Pharmacy WHERE Pharmacy_Id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$pharmacy_id]);
    $medicine_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all medicines for listing
$search = $_GET['search'] ?? '';
$stock_filter = $_GET['stock_filter'] ?? '';
$query = "SELECT * FROM Pharmacy WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (MedicineName LIKE ? OR Supplier LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
}

if ($stock_filter == 'low') {
    $query .= " AND Stock < 50";
} elseif ($stock_filter == 'out') {
    $query .= " AND Stock = 0";
} elseif ($stock_filter == 'expiring') {
    $query .= " AND ExpiryDate <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

$query .= " ORDER BY MedicineName";
$stmt = $db->prepare($query);
$stmt->execute($params);
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <?php echo $message; ?>

    <?php if ($action == 'list'): ?>
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Pharmacy Inventory</h1>
            <a href="?action=add" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Add New Medicine
            </a>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <input type="hidden" name="action" value="list">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Search medicines by name or supplier..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <select name="stock_filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Items</option>
                        <option value="low" <?php echo $stock_filter == 'low' ? 'selected' : ''; ?>>Low Stock (< 50)</option>
                        <option value="out" <?php echo $stock_filter == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                        <option value="expiring" <?php echo $stock_filter == 'expiring' ? 'selected' : ''; ?>>Expiring Soon</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <?php if ($search || $stock_filter): ?>
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Medicines List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($medicines)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <?php echo $search || $stock_filter ? 'No medicines found matching your criteria.' : 'No medicines in inventory yet.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($medicines as $medicine): ?>
                                <?php
                                $stock_status = '';
                                $stock_class = '';
                                if ($medicine['Stock'] == 0) {
                                    $stock_status = 'Out of Stock';
                                    $stock_class = 'bg-red-100 text-red-800';
                                } elseif ($medicine['Stock'] < 50) {
                                    $stock_status = 'Low Stock';
                                    $stock_class = 'bg-yellow-100 text-yellow-800';
                                } else {
                                    $stock_status = 'In Stock';
                                    $stock_class = 'bg-green-100 text-green-800';
                                }

                                $days_to_expiry = (strtotime($medicine['ExpiryDate']) - time()) / (60 * 60 * 24);
                                $expiry_class = '';
                                if ($days_to_expiry <= 30) {
                                    $expiry_class = 'text-red-600 font-medium';
                                } elseif ($days_to_expiry <= 90) {
                                    $expiry_class = 'text-yellow-600 font-medium';
                                }
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="bg-red-100 p-2 rounded-full mr-3">
                                                <i class="fas fa-pills text-red-600"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($medicine['MedicineName']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">ID: <?php echo $medicine['Pharmacy_Id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo number_format($medicine['Stock']); ?> units</div>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $stock_class; ?>">
                                            <?php echo $stock_status; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        RWF <?php echo number_format($medicine['UnitPrice'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $expiry_class ?: 'text-gray-900'; ?>">
                                        <?php echo date('M j, Y', strtotime($medicine['ExpiryDate'])); ?>
                                        <?php if ($days_to_expiry <= 30): ?>
                                            <br><span class="text-xs text-red-500">Expires in <?php echo ceil($days_to_expiry); ?> days</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($medicine['Supplier']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="openStockModal(<?php echo $medicine['Pharmacy_Id']; ?>, '<?php echo htmlspecialchars($medicine['MedicineName']); ?>', <?php echo $medicine['Stock']; ?>)" 
                                                    class="text-green-600 hover:text-green-900" title="Update Stock">
                                                <i class="fas fa-boxes"></i>
                                            </button>
                                            <a href="?action=edit&id=<?php echo $medicine['Pharmacy_Id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $medicine['Pharmacy_Id']; ?>" 
                                               class="text-red-600 hover:text-red-900" title="Delete"
                                               onclick="return confirmDelete('Are you sure you want to delete this medicine?')">
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
                <?php echo $action == 'add' ? 'Add New Medicine' : 'Edit Medicine'; ?>
            </h1>
            <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST" id="medicineForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="medicine_name" class="block text-sm font-medium text-gray-700 mb-2">Medicine Name *</label>
                        <input type="text" id="medicine_name" name="medicine_name" required
                               value="<?php echo htmlspecialchars($medicine_data['MedicineName'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
                        <input type="number" id="stock" name="stock" min="0" required
                               value="<?php echo htmlspecialchars($medicine_data['Stock'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-2">Unit Price (RWF) *</label>
                        <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" required
                               value="<?php echo htmlspecialchars($medicine_data['UnitPrice'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-2">Expiry Date *</label>
                        <input type="date" id="expiry_date" name="expiry_date" required
                               value="<?php echo htmlspecialchars($medicine_data['ExpiryDate'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="md:col-span-2">
                        <label for="supplier" class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                        <input type="text" id="supplier" name="supplier"
                               value="<?php echo htmlspecialchars($medicine_data['Supplier'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="?action=list" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" name="<?php echo $action == 'add' ? 'add_medicine' : 'update_medicine'; ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
                            onclick="return validateForm('medicineForm')">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $action == 'add' ? 'Add Medicine' : 'Update Medicine'; ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Stock Update Modal -->
<div id="stockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Update Stock</h3>
        <form method="POST">
            <input type="hidden" id="modal_pharmacy_id" name="pharmacy_id">
            <input type="hidden" id="modal_current_stock" name="current_stock">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Medicine</label>
                <p id="modal_medicine_name" class="text-sm text-gray-900 font-medium"></p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Stock</label>
                <p id="modal_current_stock_display" class="text-sm text-gray-900"></p>
            </div>
            
            <div class="mb-4">
                <label for="stock_change" class="block text-sm font-medium text-gray-700 mb-2">Stock Change</label>
                <input type="number" id="stock_change" name="stock_change" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Enter positive number to add, negative to subtract">
                <p class="text-xs text-gray-500 mt-1">Use positive numbers to add stock, negative to subtract</p>
            </div>
            
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeStockModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" name="update_stock" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Update Stock
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openStockModal(pharmacyId, medicineName, currentStock) {
    document.getElementById('modal_pharmacy_id').value = pharmacyId;
    document.getElementById('modal_current_stock').value = currentStock;
    document.getElementById('modal_medicine_name').textContent = medicineName;
    document.getElementById('modal_current_stock_display').textContent = currentStock + ' units';
    document.getElementById('stock_change').value = '';
    document.getElementById('stockModal').classList.remove('hidden');
    document.getElementById('stockModal').classList.add('flex');
}

function closeStockModal() {
    document.getElementById('stockModal').classList.add('hidden');
    document.getElementById('stockModal').classList.remove('flex');
}

// Close modal when clicking outside
document.getElementById('stockModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStockModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
