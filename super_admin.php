<?php
// super_admin.php
require_once 'config/database.php';
require_once 'includes/auth.php';

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;

// Block non-super admins
if ($_SESSION['role'] !== 'super_admin') {
    $_SESSION['error'] = "Access Denied. Super Admin privileges required.";
    header("Location: index.php");
    exit;
}

// 1. Generate Referral Code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_code'])) {
    $usage_limit = (int)$_POST['usage_limit'];
    $new_code = 'REF-' . strtoupper(substr(md5(uniqid()), 0, 8)); // Generate random code
    
    $stmt = $pdo->prepare("INSERT INTO referral_codes (code, created_by, usage_limit) VALUES (?, ?, ?)");
    $stmt->execute([$new_code, $user_id, $usage_limit]);
    $_SESSION['success'] = "Generated new referral code: <b>{$new_code}</b>";
    header("Location: super_admin.php");
    exit;
}

// 2. Approve or Reject User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_user'])) {
    $target_user_id = (int)$_POST['target_user_id'];
    $action = $_POST['action_user']; // 'approve' or 'reject'
    $new_status = ($action === 'approve') ? 'active' : 'rejected';

    // Fetch user details for the email
    $uStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
    $uStmt->execute([$target_user_id]);
    $targetUser = $uStmt->fetch();

    if ($targetUser) {
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new_status, $target_user_id]);
        
        // Send Notification Email
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();                                            
            $mail->Host       = 'smtp.gmail.com';                       
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = 'godoyjp443@gmail.com';                 
            $mail->Password   = 'YOUR_APP_PASSWORD_HERE'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
            $mail->Port       = 587;                                    
            $mail->setFrom('godoyjp443@gmail.com', 'BlastMaster Admin');
            $mail->addAddress($targetUser['email'], $targetUser['name']);
            $mail->isHTML(true);

            if ($action === 'approve') {
                $mail->Subject = 'Account Approved - Welcome to BlastMaster!';
                $mail->Body    = "Hello {$targetUser['name']},<br><br>Great news! Your admin account has been <b>approved</b> by the Super Admin.<br><br>You can now log in and start using the system: <a href='http://yourdomain.com/login.php'>Login Here</a>.";
                $_SESSION['success'] = "User {$targetUser['name']} has been approved.";
            } else {
                $mail->Subject = 'Account Registration Declined';
                $mail->Body    = "Hello {$targetUser['name']},<br><br>We regret to inform you that your admin account registration has been declined by the Super Admin.<br><br>If you believe this is a mistake, please contact support.";
                $_SESSION['success'] = "User {$targetUser['name']} has been rejected.";
            }
            $mail->send();
        } catch (Exception $e) {
            error_log("Approval email failed: " . $mail->ErrorInfo);
        }
    }
    header("Location: super_admin.php");
    exit;
}

// Fetch Pending Users
$pendingUsers = $pdo->query("
    SELECT u.*, c.name as company_name 
    FROM users u 
    LEFT JOIN companies c ON u.company_id = c.id 
    WHERE u.status = 'pending' AND u.role = 'admin'
")->fetchAll();

// Fetch Referral Codes
$referralCodes = $pdo->query("SELECT * FROM referral_codes ORDER BY created_at DESC")->fetchAll();

include 'includes/header.php';
?>

<div class="mb-10 page-transition">
    <h1 class="text-4xl md:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-fuchsia-600 to-indigo-700 tracking-tight drop-shadow-sm flex items-center">
        <i class="fa-solid fa-shield-halved mr-4 text-fuchsia-500 drop-shadow-md"></i> Super Admin Center
    </h1>
    <p class="mt-3 text-lg text-gray-600 font-medium ml-1">Manage pending admin approvals and referral invitations.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-10 page-transition" style="animation-delay: 0.1s;">
    
    <!-- Pending Approvals -->
    <div class="glass-3d hover-3d flex flex-col relative overflow-hidden group h-[700px]">
        <!-- Glowing Orb Background -->
        <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-emerald-400/20 rounded-full blur-3xl group-hover:bg-emerald-400/40 transition-all duration-700 z-0 pointer-events-none"></div>
        
        <div class="px-8 py-6 border-b border-white/40 bg-white/20 flex justify-between items-center relative z-10 backdrop-blur-md">
            <h3 class="text-xl font-black text-gray-800 tracking-wide uppercase">Pending Approvals</h3>
            <span class="bg-amber-100/80 text-amber-800 border border-amber-200 shadow-sm text-xs font-bold px-4 py-1.5 rounded-full backdrop-blur-sm">
                <?php echo count($pendingUsers); ?> Action(s) Needed
            </span>
        </div>
        
        <div class="p-6 overflow-y-auto flex-grow custom-scrollbar relative z-10 space-y-4">
            <?php if(empty($pendingUsers)): ?>
                <div class="text-center py-20 text-gray-500 font-medium flex flex-col items-center justify-center h-full">
                    <div class="w-20 h-20 bg-emerald-100/50 rounded-full flex items-center justify-center mb-4 shadow-[0_0_20px_rgba(52,211,153,0.3)] border border-emerald-200/50">
                        <i class="fa-solid fa-check-double text-4xl text-emerald-500"></i>
                    </div>
                    <p class="text-lg">All caught up! No pending applications.</p>
                </div>
            <?php else: ?>
                <?php foreach($pendingUsers as $u): ?>
                <div class="bg-white/50 backdrop-blur-md border border-white rounded-2xl p-5 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between group/card hover:-translate-y-1">
                    <div class="mb-5">
                        <h4 class="font-black text-gray-900 text-xl mb-1"><?php echo htmlspecialchars($u['name']); ?></h4>
                        <p class="text-sm text-gray-600 font-medium"><i class="fa-solid fa-envelope mr-1.5 text-indigo-400"></i> <?php echo htmlspecialchars($u['email']); ?></p>
                        <p class="text-sm text-gray-600 font-medium mt-1.5"><i class="fa-solid fa-building mr-1.5 text-indigo-400"></i> Company: <b class="text-gray-800"><?php echo htmlspecialchars($u['company_name']); ?></b></p>
                        
                        <div class="mt-3 inline-block">
                            <span class="text-xs text-indigo-700 font-bold bg-indigo-100/80 border border-indigo-200 px-3 py-1.5 rounded-lg shadow-sm">
                                <i class="fa-solid fa-ticket mr-1"></i> Used Code: <?php echo htmlspecialchars($u['referral_code_used']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <form action="super_admin.php" method="POST" class="flex-1">
                            <input type="hidden" name="target_user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" name="action_user" value="approve" class="w-full bg-gradient-to-r from-emerald-400 to-emerald-600 hover:from-emerald-500 hover:to-emerald-700 text-white font-bold py-2.5 rounded-xl transition-all shadow-[0_4px_15px_rgba(16,185,129,0.3)] hover:shadow-[0_0_20px_rgba(16,185,129,0.6)] transform hover:-translate-y-0.5 border border-emerald-300/50">
                                <i class="fa-solid fa-check mr-1.5"></i> Approve
                            </button>
                        </form>
                        <form action="super_admin.php" method="POST" class="flex-1">
                            <input type="hidden" name="target_user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" name="action_user" value="reject" class="w-full bg-gradient-to-r from-red-400 to-red-600 hover:from-red-500 hover:to-red-700 text-white font-bold py-2.5 rounded-xl transition-all shadow-[0_4px_15px_rgba(239,68,68,0.3)] hover:shadow-[0_0_20px_rgba(239,68,68,0.6)] transform hover:-translate-y-0.5 border border-red-300/50" onclick="return confirm('Are you sure you want to decline this user?');">
                                <i class="fa-solid fa-xmark mr-1.5"></i> Reject
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Referral Code Management -->
    <div class="glass-3d hover-3d flex flex-col relative overflow-hidden group h-[700px]">
        <!-- Glowing Orb Background -->
        <div class="absolute -right-10 -top-10 w-48 h-48 bg-purple-400/20 rounded-full blur-3xl group-hover:bg-fuchsia-400/40 transition-all duration-700 z-0 pointer-events-none"></div>
        
        <div class="px-8 py-6 border-b border-white/40 bg-white/20 relative z-10 backdrop-blur-md">
            <h3 class="text-xl font-black text-gray-800 tracking-wide uppercase">Manage Referral Codes</h3>
        </div>
        
        <div class="p-6 bg-white/10 border-b border-white/30 relative z-10 backdrop-blur-sm">
            <form action="super_admin.php" method="POST" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Max Usages per Code</label>
                    <input type="number" name="usage_limit" value="5" min="1" required class="w-full px-5 py-3 input-glass rounded-xl text-gray-800 font-bold placeholder-gray-400">
                </div>
                <button type="submit" name="generate_code" class="btn-glow font-bold py-3 px-8 rounded-xl uppercase tracking-wider text-sm shadow-lg">
                    <i class="fa-solid fa-plus mr-1.5"></i> Generate Link
                </button>
            </form>
        </div>
        
        <div class="p-6 overflow-y-auto flex-grow custom-scrollbar relative z-10 space-y-3">
            <?php foreach($referralCodes as $code): ?>
            <div class="bg-white/60 backdrop-blur-md rounded-2xl p-5 border border-white shadow-sm hover:shadow-md transition-all flex justify-between items-center group/item hover:bg-white/80">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="font-mono font-black text-xl text-indigo-700 select-all tracking-wider drop-shadow-sm" id="ref-<?php echo $code['id']; ?>"><?php echo $code['code']; ?></span>
                        <button type="button" onclick="navigator.clipboard.writeText('<?php echo $code['code']; ?>'); alert('Referral code copied to clipboard!');" class="text-indigo-400 hover:text-fuchsia-600 hover:bg-white p-2 rounded-lg transition-all shadow-sm border border-transparent hover:border-indigo-100" title="Copy Code">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 font-bold mt-1.5 uppercase tracking-wider">Created: <?php echo date('M d, Y', strtotime($code['created_at'])); ?></p>
                </div>
                <div class="text-right flex flex-col items-end">
                    <?php if($code['status'] === 'active' && $code['used_count'] < $code['usage_limit']): ?>
                        <span class="text-xs font-black bg-emerald-100/80 text-emerald-700 px-3 py-1.5 rounded-lg border border-emerald-200 shadow-sm uppercase tracking-wider">Active</span>
                    <?php else: ?>
                        <span class="text-xs font-black bg-red-100/80 text-red-700 px-3 py-1.5 rounded-lg border border-red-200 shadow-sm uppercase tracking-wider">Maxed/Expired</span>
                    <?php endif; ?>
                    <p class="text-sm font-black text-gray-700 mt-2 bg-white/50 px-3 py-1 rounded-lg border border-white shadow-sm"><i class="fa-solid fa-users text-indigo-400 mr-1"></i> <?php echo $code['used_count']; ?> / <?php echo $code['usage_limit']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>