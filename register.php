<?php
// register.php
require_once 'config/database.php';

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;

session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['status']) && $_SESSION['status'] === 'active') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $company_name = trim($_POST['company_name']);
    $user_name = trim($_POST['user_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $referral_code = trim($_POST['referral_code']);

    try {
        $pdo->beginTransaction();

        $refStmt = $pdo->prepare("SELECT id, used_count, usage_limit FROM referral_codes WHERE code = ? AND status = 'active'");
        $refStmt->execute([$referral_code]);
        $refData = $refStmt->fetch();

        if (!$refData || $refData['used_count'] >= $refData['usage_limit']) {
            throw new Exception("Invalid, expired, or maxed out Referral Code.");
        }

        $stmt = $pdo->prepare("INSERT INTO companies (name) VALUES (?)");
        $stmt->execute([$company_name]);
        $company_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO users (company_id, role, status, name, email, password, referral_code_used) VALUES (?, 'admin', 'pending', ?, ?, ?, ?)");
        $stmt->execute([$company_id, $user_name, $email, $password, $referral_code]);
        
        $pdo->prepare("UPDATE referral_codes SET used_count = used_count + 1 WHERE id = ?")->execute([$refData['id']]);

        $pdo->commit();

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();                                            
            $mail->Host       = 'smtp.gmail.com';                       
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = 'godoyjp443@gmail.com';                 
            $mail->Password   = 'YOUR_APP_PASSWORD_HERE'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
            $mail->Port       = 587;                                    
            $mail->setFrom('godoyjp443@gmail.com', 'BlastMaster System');
            $mail->addAddress($email, $user_name);
            $mail->Subject = 'Account Pending Approval';
            $mail->isHTML(true);
            $mail->Body    = "Hello {$user_name},<br><br>Your admin account for <b>{$company_name}</b> has been successfully registered using referral code <b>{$referral_code}</b>.<br><br>Your account is currently <b>pending approval</b> from a Super Admin. You will receive another email once your account is activated.<br><br>Thank you!";
            $mail->send();
        } catch (Exception $e) {}

        $_SESSION['success'] = "Registration successful! Your account is currently pending Super Admin approval.";
        header("Location: login.php");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage() !== "" ? $e->getMessage() : "Registration failed. Email might already be in use.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BlastMaster Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; overflow-x: hidden; }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3), inset 0 0 20px rgba(255,255,255,0.02);
            border-radius: 30px;
            transform-style: preserve-3d;
            animation: float 8s ease-in-out infinite;
        }

        .input-glass {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        .input-glass:focus {
            background: rgba(0, 0, 0, 0.4);
            border-color: #06b6d4;
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.4), inset 0 0 10px rgba(6, 182, 212, 0.2);
            outline: none;
        }

        .btn-glow {
            background: linear-gradient(135deg, #06b6d4, #3b82f6);
            background-size: 200% 200%;
            animation: gradientMove 4s ease infinite;
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.4);
            transition: all 0.3s ease;
        }
        .btn-glow:hover {
            box-shadow: 0 0 30px rgba(6, 182, 212, 0.7), 0 0 60px rgba(59, 130, 246, 0.5);
            transform: translateY(-2px);
        }

        @keyframes float { 0% { transform: translateY(0px) rotateX(1deg); } 50% { transform: translateY(-10px) rotateX(-1deg); } 100% { transform: translateY(0px) rotateX(1deg); } }
        @keyframes floatOrb { 0% { transform: translate(0, 0) scale(1); } 50% { transform: translate(-50px, 50px) scale(1.1); } 100% { transform: translate(0, 0) scale(1); } }
        @keyframes gradientMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative py-10">
    
    <!-- 3D Glowing Orbs -->
    <div class="absolute inset-0 z-[-1] overflow-hidden fixed">
        <div class="absolute top-[5%] right-[20%] w-[400px] h-[400px] rounded-full bg-cyan-600/50 blur-[120px] mix-blend-screen animate-[floatOrb_12s_ease-in-out_infinite]"></div>
        <div class="absolute bottom-[5%] left-[20%] w-[500px] h-[500px] rounded-full bg-blue-600/40 blur-[130px] mix-blend-screen animate-[floatOrb_18s_ease-in-out_infinite_reverse]"></div>
    </div>

    <div class="w-full max-w-md glass-panel p-10 relative z-10 my-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-white tracking-wider mb-2">Join the Network</h1>
            <p class="text-cyan-200 font-medium text-sm opacity-80">Referral Code Required</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-500/20 border border-red-400/30 text-red-300 p-3 rounded-xl mb-6 text-sm font-bold text-center shadow-[0_0_10px_rgba(239,68,68,0.2)]">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-cyan-300 uppercase tracking-wider mb-1">Company Name</label>
                <input type="text" name="company_name" required class="w-full px-4 py-3 input-glass rounded-xl">
            </div>
            <div>
                <label class="block text-xs font-bold text-cyan-300 uppercase tracking-wider mb-1">Your Name</label>
                <input type="text" name="user_name" required class="w-full px-4 py-3 input-glass rounded-xl">
            </div>
            <div>
                <label class="block text-xs font-bold text-cyan-300 uppercase tracking-wider mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full px-4 py-3 input-glass rounded-xl">
            </div>
            <div>
                <label class="block text-xs font-bold text-cyan-300 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 input-glass rounded-xl">
            </div>
            <div class="pt-2">
                <label class="block text-xs font-bold text-cyan-400 uppercase tracking-wider mb-1"><i class="fa-solid fa-ticket mr-1"></i> Referral Code</label>
                <input type="text" name="referral_code" required class="w-full px-4 py-3 input-glass border-cyan-500/50 rounded-xl text-cyan-100 font-bold uppercase tracking-wider shadow-[inset_0_0_10px_rgba(6,182,212,0.1)]" placeholder="REF-XXXXXX">
            </div>
            
            <button type="submit" name="register" class="w-full btn-glow text-white font-bold py-4 rounded-xl mt-6 uppercase tracking-wider text-sm">
                Submit Application
            </button>
        </form>
        <p class="text-center text-sm text-cyan-200 mt-6 font-medium opacity-80">Already approved? <a href="login.php" class="text-white hover:text-cyan-400 transition-colors font-bold underline">Sign in here</a></p>
    </div>
</body>
</html>