<?php
// settings.php
require_once 'config/database.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_smtp'])) {
    $host = $_POST['host'];
    $port = $_POST['port'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $encryption = $_POST['encryption'];
    $from_email = $_POST['from_email'];
    $from_name = $_POST['from_name'];

    // Check if settings already exist
    $checkStmt = $pdo->prepare("SELECT id FROM smtp_settings WHERE company_id = ?");
    $checkStmt->execute([$company_id]);

    if ($checkStmt->rowCount() > 0) {
        $stmt = $pdo->prepare("UPDATE smtp_settings SET host=?, port=?, username=?, password=?, encryption=?, from_email=?, from_name=? WHERE company_id=?");
        $stmt->execute([$host, $port, $username, $password, $encryption, $from_email, $from_name, $company_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO smtp_settings (company_id, host, port, username, password, encryption, from_email, from_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $host, $port, $username, $password, $encryption, $from_email, $from_name]);
    }

    $_SESSION['success'] = "SMTP Settings saved successfully!";
    header("Location: settings.php");
    exit;
}

// Fetch current settings
$stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE company_id = ?");
$stmt->execute([$company_id]);
$smtp = $stmt->fetch();

include 'includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-indigo-800 tracking-tight">System Settings</h1>
    <p class="mt-2 text-base text-gray-500 font-medium">Configure your company's custom email sender.</p>
</div>

<div class="glass shadow-xl rounded-3xl p-8 border border-white/60 max-w-3xl">
    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center border-b pb-4">
        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl mr-3 shadow-sm border border-indigo-100"><i class="fa-solid fa-server"></i></div> 
        SMTP Configuration
    </h3>

    <form action="settings.php" method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Sender Name (From)</label>
                <input type="text" name="from_name" value="<?php echo htmlspecialchars($smtp['from_name'] ?? ''); ?>" placeholder="e.g. Acme Corp Marketing" required class="w-full px-4 py-3 bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Sender Email</label>
                <input type="email" name="from_email" value="<?php echo htmlspecialchars($smtp['from_email'] ?? ''); ?>" placeholder="e.g. marketing@acme.com" required class="w-full px-4 py-3 bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">SMTP Host</label>
                <input type="text" name="host" value="<?php echo htmlspecialchars($smtp['host'] ?? ''); ?>" placeholder="e.g. smtp.gmail.com" required class="w-full px-4 py-3 bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">SMTP Port</label>
                <input type="number" name="port" value="<?php echo htmlspecialchars($smtp['port'] ?? '587'); ?>" required class="w-full px-4 py-3 bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">SMTP Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($smtp['username'] ?? ''); ?>" required class="w-full px-4 py-3 bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">SMTP Password / App Password</label>
                <input type="password" name="password" value="<?php echo htmlspecialchars($smtp['password'] ?? ''); ?>" required class="w-full px-4 py-3 bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Encryption</label>
                <select name="encryption" class="w-full px-4 py-3 bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="tls" <?php echo (isset($smtp['encryption']) && $smtp['encryption'] === 'tls') ? 'selected' : ''; ?>>TLS</option>
                    <option value="ssl" <?php echo (isset($smtp['encryption']) && $smtp['encryption'] === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                    <option value="none" <?php echo (isset($smtp['encryption']) && $smtp['encryption'] === 'none') ? 'selected' : ''; ?>>None</option>
                </select>
            </div>
        </div>
        <button type="submit" name="save_smtp" class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold py-3 px-8 rounded-xl hover:shadow-lg hover:-translate-y-0.5 transition-all">
            <i class="fa-solid fa-floppy-disk mr-2"></i> Save Settings
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>