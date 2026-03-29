<?php
// subscribers.php
require_once 'config/database.php';
require_once 'includes/auth.php'; // Protect page & get $company_id

// Handle Form Submission for new single subscriber
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subscriber']) && $pdo) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $token = bin2hex(random_bytes(16)); // Generate verification token

    try {
        // Auto-verifying manual additions (is_verified = 1) for usability.
        $stmt = $pdo->prepare("INSERT INTO subscribers (company_id, name, email, is_verified, verification_token) VALUES (?, ?, ?, 1, ?)");
        $stmt->execute([$company_id, $name, $email, $token]);
        $_SESSION['success'] = "Subscriber added successfully!";
        header("Location: subscribers.php");
        exit;
    } catch (\PDOException $e) {
        $_SESSION['error'] = "Failed to add. Email might already exist in your list.";
        header("Location: subscribers.php");
        exit;
    }
}

// CSV Import Logic (Updated for Multi-Tenant)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv']) && isset($_FILES['csv_file']) && $pdo) {
    $file = $_FILES['csv_file']['tmp_name'];
    if ($_FILES['csv_file']['size'] > 0) {
        if (($handle = fopen($file, "r")) !== FALSE) {
            $importedCount = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $name = isset($data[0]) ? trim($data[0]) : 'Subscriber';
                $email = isset($data[1]) ? trim($data[1]) : '';
                $token = bin2hex(random_bytes(16));
                
                if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO subscribers (company_id, name, email, is_verified, verification_token) VALUES (?, ?, ?, 1, ?)");
                        $stmt->execute([$company_id, $name, $email, $token]);
                        $importedCount++;
                    } catch (\PDOException $e) {} // Skip duplicates
                }
            }
            fclose($handle);
            $_SESSION['success'] = "CSV Uploaded! $importedCount users imported.";
            header("Location: subscribers.php");
            exit;
        }
    }
}

// Fetch Subscribers for this specific company
$stmt = $pdo->prepare("SELECT * FROM subscribers WHERE company_id = ? ORDER BY created_at DESC");
$stmt->execute([$company_id]);
$subscribers = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="mb-8 flex justify-between items-center flex-wrap gap-4">
    <div>
        <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-indigo-800 tracking-tight">Audience Manager</h1>
        <p class="mt-2 text-base text-gray-500 font-medium">Manage your contacts, handle opt-ins, and perform bulk operations.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column: Actions -->
    <div class="lg:col-span-1 space-y-6">
        
        <!-- Add Single Subscriber Form -->
        <div class="glass shadow-xl rounded-3xl p-8 border border-white/60 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 text-indigo-100/50 group-hover:text-indigo-100 transition-colors duration-500"><i class="fa-solid fa-user-plus text-8xl"></i></div>
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center relative z-10">
                <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl mr-3 shadow-sm border border-indigo-100"><i class="fa-solid fa-user"></i></div> 
                Quick Add
            </h3>
            
            <form action="subscribers.php" method="POST" class="relative z-10">
                <div class="mb-5 group/input">
                    <label class="block text-sm font-bold text-gray-700 mb-2 group-focus-within/input:text-indigo-600 transition-colors">Full Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 border border-gray-200 rounded-xl shadow-sm focus:outline-none focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all bg-white/80">
                </div>
                <div class="mb-6 group/input">
                    <label class="block text-sm font-bold text-gray-700 mb-2 group-focus-within/input:text-indigo-600 transition-colors">Email Address</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-200 rounded-xl shadow-sm focus:outline-none focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all bg-white/80">
                </div>
                <button type="submit" name="add_subscriber" class="w-full bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 animate-gradient-text text-white font-bold py-3.5 px-4 rounded-xl hover-lift shadow-[0_8px_20px_-6px_rgba(79,70,229,0.5)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex justify-center items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Subscribe
                </button>
            </form>
        </div>

        <!-- Bulk CSV Import -->
        <div class="glass shadow-xl rounded-3xl p-8 border border-white/60 relative overflow-hidden group">
            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center relative z-10">
                <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl mr-3 shadow-sm border border-emerald-100"><i class="fa-solid fa-file-csv"></i></div> 
                Bulk Import CSV
            </h3>
            <p class="text-xs text-gray-500 mb-5 font-medium relative z-10">Upload a CSV file with columns: <b>Name, Email</b>.</p>
            
            <form action="subscribers.php" method="POST" enctype="multipart/form-data" class="relative z-10">
                <div class="mb-5 border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center hover:border-emerald-400 hover:bg-emerald-50/30 transition-all group/dropzone cursor-pointer relative">
                    <input type="file" name="csv_file" accept=".csv" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
                    <i class="fa-solid fa-cloud-arrow-up text-4xl text-gray-400 group-hover/dropzone:text-emerald-500 mb-3 transition-colors"></i>
                    <p class="text-sm font-bold text-gray-700">Click or Drag CSV here</p>
                    <p class="text-xs text-gray-400 mt-1">Max file size 5MB</p>
                </div>
                <button type="submit" name="import_csv" class="w-full bg-white text-gray-800 border-2 border-gray-200 font-bold py-3.5 px-4 rounded-xl hover:border-emerald-500 hover:text-emerald-600 hover-lift transition-all focus:outline-none flex justify-center items-center gap-2">
                    <i class="fa-solid fa-upload"></i> Process Import
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Subscriber List -->
    <div class="lg:col-span-2">
        <div class="glass shadow-xl rounded-3xl overflow-hidden border border-white/60 flex flex-col h-[850px]">
            <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-white/40">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">Your List</h3>
                    <p class="text-sm text-gray-500 font-medium">Viewing all active and inactive contacts.</p>
                </div>
                <div class="bg-gradient-to-br from-indigo-100 to-purple-100 text-indigo-900 px-5 py-2 rounded-2xl shadow-inner border border-white flex flex-col items-center">
                    <span class="text-xs font-bold uppercase tracking-wide opacity-70">Total Users</span>
                    <span class="text-xl font-black"><?php echo number_format(count($subscribers)); ?></span>
                </div>
            </div>
            
            <div class="overflow-x-auto flex-grow overflow-y-auto custom-scrollbar bg-white/20">
                <table class="min-w-full divide-y divide-gray-100/50">
                    <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-md z-10 shadow-sm border-b border-gray-200">
                        <tr>
                            <th class="px-8 py-5 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Contact Info</th>
                            <th class="px-8 py-5 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-8 py-5 text-left text-xs font-extrabold text-gray-500 uppercase tracking-wider">Added On</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100/50">
                        <?php foreach($subscribers as $sub): ?>
                        <tr class="hover:bg-white/80 transition-colors group">
                            <td class="px-8 py-5 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12 bg-gradient-to-br from-gray-100 to-gray-200 group-hover:from-indigo-100 group-hover:to-purple-100 transition-colors rounded-2xl flex items-center justify-center text-gray-600 group-hover:text-indigo-700 font-black shadow-inner border border-white text-lg">
                                        <?php echo strtoupper(substr($sub['name'], 0, 1)); ?>
                                    </div>
                                    <div class="ml-5">
                                        <div class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($sub['name']); ?></div>
                                        <div class="text-sm font-medium text-gray-500 group-hover:text-indigo-500 transition-colors"><?php echo htmlspecialchars($sub['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap">
                                <?php if($sub['status'] === 'active'): ?>
                                    <span class="px-4 py-1.5 inline-flex text-xs leading-5 font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm"><i class="fa-solid fa-circle text-[8px] mt-[5px] mr-1.5"></i> Subscribed</span>
                                <?php else: ?>
                                    <span class="px-4 py-1.5 inline-flex text-xs leading-5 font-bold rounded-full bg-red-50 text-red-700 border border-red-200 shadow-sm"><i class="fa-solid fa-circle text-[8px] mt-[5px] mr-1.5"></i> Unsubscribed</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap text-sm font-medium text-gray-500">
                                <i class="fa-regular fa-calendar mr-1 opacity-50"></i> <?php echo date('M d, Y', strtotime($sub['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($subscribers)): ?>
                            <tr><td colspan="3" class="px-6 py-24 text-center">
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-indigo-50 mb-6 border-8 border-white shadow-sm">
                                    <i class="fa-solid fa-users text-4xl text-indigo-200"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">No Subscribers Yet</h3>
                                <p class="text-gray-500 font-medium max-w-sm mx-auto">Start building your audience by adding a user manually or uploading a bulk CSV file.</p>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>