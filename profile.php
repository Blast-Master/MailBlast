<?php
// profile.php
require_once 'config/database.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];

    try {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $hashed, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user_id]);
        }
        
        // Update session
        $_SESSION['name'] = $name;
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit;
    } catch (\PDOException $e) {
        $_SESSION['error'] = "Failed to update profile. Email may already be taken.";
        header("Location: profile.php");
        exit;
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT u.*, c.name as company_name FROM users u JOIN companies c ON u.company_id = c.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch();

include 'includes/header.php';
?>

<div class="mb-10">
    <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-indigo-800 tracking-tight">My Profile</h1>
    <p class="mt-2 text-base text-gray-500 font-medium">Manage your personal account settings and security.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-12 gap-10">
    <div class="md:col-span-4">
        <div class="glass shadow-2xl rounded-3xl p-8 border border-white/60 text-center">
            <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full mx-auto flex items-center justify-center text-white text-4xl font-black shadow-lg shadow-indigo-200 mb-4 border-4 border-white">
                <?php echo strtoupper(substr($userData['name'], 0, 1)); ?>
            </div>
            <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($userData['name']); ?></h2>
            <p class="text-indigo-600 font-bold text-sm mb-4"><?php echo strtoupper($userData['role']); ?></p>
            
            <div class="bg-gray-50 rounded-xl p-4 text-left border border-gray-100">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Assigned Company</p>
                <p class="text-sm font-bold text-gray-800"><i class="fa-solid fa-building text-gray-400 mr-2"></i> <?php echo htmlspecialchars($userData['company_name']); ?></p>
            </div>
        </div>
    </div>

    <div class="md:col-span-8">
        <div class="glass shadow-2xl rounded-3xl p-8 border border-white/60">
            <h3 class="text-xl font-black text-gray-900 mb-6 pb-4 border-b border-gray-100 flex items-center">
                <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl mr-3 border border-indigo-100"><i class="fa-solid fa-user-pen"></i></div> 
                Edit Account Details
            </h3>
            
            <form action="profile.php" method="POST" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required class="w-full px-4 py-3 bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required class="w-full px-4 py-3 bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="pt-4 mt-2 border-t border-gray-100">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2"><i class="fa-solid fa-lock text-gray-400 mr-1"></i> Change Password</label>
                    <input type="password" name="new_password" placeholder="Leave blank to keep current password" class="w-full px-4 py-3 bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <button type="submit" name="update_profile" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-3.5 px-8 rounded-xl hover:shadow-lg hover:-translate-y-0.5 transition-all mt-4 inline-block">
                    Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>