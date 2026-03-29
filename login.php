<?php
// login.php
require_once 'config/database.php';
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['status']) && $_SESSION['status'] === 'active') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'pending') {
            $error = "Your account is currently pending Super Admin approval.";
        } else if ($user['status'] === 'rejected') {
            $error = "Your registration application was declined.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['status'] = $user['status'];
            header("Location: index.php");
            exit;
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BlastMaster Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; overflow: hidden; }
        
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
            animation: float 6s ease-in-out infinite;
        }

        .input-glass {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        .input-glass:focus {
            background: rgba(0, 0, 0, 0.4);
            border-color: #8b5cf6;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.4), inset 0 0 10px rgba(139, 92, 246, 0.2);
            outline: none;
        }

        .btn-glow {
            background: linear-gradient(135deg, #4f46e5, #ec4899);
            background-size: 200% 200%;
            animation: gradientMove 4s ease infinite;
            box-shadow: 0 0 20px rgba(236, 72, 153, 0.4);
            transition: all 0.3s ease;
        }
        .btn-glow:hover {
            box-shadow: 0 0 30px rgba(236, 72, 153, 0.7), 0 0 60px rgba(79, 70, 229, 0.5);
            transform: translateY(-2px);
        }

        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-15px); } 100% { transform: translateY(0px); } }
        @keyframes floatOrb1 { 0% { transform: translate(0, 0) scale(1); } 50% { transform: translate(50px, -50px) scale(1.2); } 100% { transform: translate(0, 0) scale(1); } }
        @keyframes gradientMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative">
    
    <!-- 3D Glowing Orbs -->
    <div class="absolute inset-0 z-[-1]">
        <div class="absolute top-[10%] left-[20%] w-[400px] h-[400px] rounded-full bg-indigo-600/50 blur-[120px] mix-blend-screen animate-[floatOrb1_10s_ease-in-out_infinite]"></div>
        <div class="absolute bottom-[10%] right-[20%] w-[500px] h-[500px] rounded-full bg-pink-600/40 blur-[130px] mix-blend-screen animate-[floatOrb1_15s_ease-in-out_infinite_reverse]"></div>
        <div class="absolute top-[40%] left-[40%] w-[300px] h-[300px] rounded-full bg-cyan-500/30 blur-[100px] mix-blend-screen animate-[floatOrb1_12s_ease-in-out_infinite]"></div>
    </div>

    <div class="w-full max-w-md glass-panel p-10 relative z-10">
        <div class="text-center mb-10">
            <div class="inline-block bg-gradient-to-br from-indigo-500 to-pink-500 p-3 rounded-2xl shadow-[0_0_20px_rgba(236,72,153,0.5)] mb-4">
                <i class="fa-solid fa-paper-plane text-2xl text-white"></i>
            </div>
            <h1 class="text-3xl font-black text-white tracking-wider">BlastMaster</h1>
            <p class="text-indigo-200 font-medium text-sm mt-1 opacity-80">Sign in to your workspace</p>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 p-3 rounded-xl mb-6 text-sm font-bold text-center shadow-[0_0_10px_rgba(52,211,153,0.2)]">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="bg-red-500/20 border border-red-400/30 text-red-300 p-3 rounded-xl mb-6 text-sm font-bold text-center shadow-[0_0_10px_rgba(239,68,68,0.2)]">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-indigo-300 uppercase tracking-wider mb-2">Email Address</label>
                <input type="email" name="email" required class="w-full px-5 py-4 input-glass rounded-xl placeholder-gray-400" placeholder="admin@company.com">
            </div>
            <div>
                <label class="block text-xs font-bold text-indigo-300 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" required class="w-full px-5 py-4 input-glass rounded-xl placeholder-gray-400" placeholder="••••••••">
            </div>
            
            <button type="submit" name="login" class="w-full btn-glow text-white font-bold py-4 rounded-xl mt-6 uppercase tracking-wider text-sm">
                Authenticate
            </button>
        </form>
        <p class="text-center text-sm text-indigo-200 mt-8 font-medium opacity-80">Have an invite code? <a href="register.php" class="text-white hover:text-pink-400 transition-colors font-bold underline">Apply here</a></p>
    </div>
</body>
</html>