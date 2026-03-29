<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlastMaster - Pro Email System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f1f5f9;
            overflow-x: hidden;
        }

        /* --- 3D & Glassmorphism Core --- */
        .glass, .glass-3d { 
            background: rgba(255, 255, 255, 0.4); 
            backdrop-filter: blur(24px); 
            -webkit-backdrop-filter: blur(24px); 
            border: 1px solid rgba(255, 255, 255, 0.8); 
            border-top: 1px solid rgba(255, 255, 255, 1);
            border-left: 1px solid rgba(255, 255, 255, 1);
            box-shadow: 
                0 10px 40px -10px rgba(31, 38, 135, 0.15), 
                inset 0 0 20px rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            transform-style: preserve-3d;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* --- Hover Effects --- */
        .hover-3d:hover { 
            transform: perspective(1000px) translateY(-8px) rotateX(2deg) rotateY(-2deg) translateZ(10px); 
            box-shadow: 
                0 25px 50px -12px rgba(99, 102, 241, 0.3), 
                inset 0 0 25px rgba(255, 255, 255, 0.8),
                0 0 15px rgba(168, 85, 247, 0.2); 
            border-color: rgba(255, 255, 255, 1);
        }

        /* --- Glowing Buttons --- */
        .btn-glow {
            position: relative;
            background: linear-gradient(135deg, #4f46e5, #9333ea, #ec4899);
            background-size: 200% 200%;
            animation: gradientMove 4s ease infinite;
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            box-shadow: 0 4px 15px rgba(147, 51, 234, 0.4);
            transition: all 0.3s ease;
            transform-style: preserve-3d;
        }
        .btn-glow:hover {
            box-shadow: 
                0 0 25px rgba(168, 85, 247, 0.7), 
                0 0 50px rgba(99, 102, 241, 0.4);
            transform: translateY(-3px) scale(1.02);
        }

        /* --- Glowing Inputs --- */
        .input-glass {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }
        .input-glass:focus {
            background: rgba(255, 255, 255, 0.8);
            border-color: #8b5cf6;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.3), inset 0 2px 5px rgba(0,0,0,0.02);
            transform: translateZ(5px);
            outline: none;
        }

        /* --- Animations --- */
        @keyframes gradientMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        @keyframes floatOrb1 { 0% { transform: translate(0, 0) scale(1); } 33% { transform: translate(30px, -50px) scale(1.1); } 66% { transform: translate(-20px, 20px) scale(0.9); } 100% { transform: translate(0, 0) scale(1); } }
        @keyframes floatOrb2 { 0% { transform: translate(0, 0) scale(1); } 33% { transform: translate(-40px, 40px) scale(0.95); } 66% { transform: translate(30px, -20px) scale(1.05); } 100% { transform: translate(0, 0) scale(1); } }
        
        .page-transition { animation: fadeSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; perspective: 1000px; }
        @keyframes fadeSlideUp { from { opacity: 0; transform: translateY(40px) rotateX(-5deg); } to { opacity: 1; transform: translateY(0) rotateX(0); } }

        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.02); border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(139, 92, 246, 0.3); border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(139, 92, 246, 0.6); }
    </style>
</head>
<body class="min-h-screen flex flex-col relative text-gray-800">

<!-- Animated 3D Moving Background -->
<div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none">
    <div class="absolute top-[-10%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-indigo-400/40 blur-[120px] mix-blend-multiply animate-[floatOrb1_15s_ease-in-out_infinite]"></div>
    <div class="absolute bottom-[-10%] right-[-5%] w-[60vw] h-[60vw] rounded-full bg-fuchsia-400/40 blur-[120px] mix-blend-multiply animate-[floatOrb2_20s_ease-in-out_infinite]"></div>
    <div class="absolute top-[30%] left-[30%] w-[40vw] h-[40vw] rounded-full bg-cyan-300/30 blur-[100px] mix-blend-multiply animate-[floatOrb1_18s_ease-in-out_infinite_reverse]"></div>
</div>

<div class="p-4">
    <nav class="glass mx-auto max-w-7xl px-6 py-3 rounded-full flex items-center justify-between shadow-xl relative z-50">
        <div class="flex items-center">
            <a href="index.php" class="flex-shrink-0 font-black text-2xl flex items-center gap-2 group hover-3d transition-transform">
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-2 rounded-xl shadow-lg shadow-indigo-300/50 group-hover:shadow-indigo-400/80 transition-shadow">
                    <i class="fa-solid fa-paper-plane text-sm transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5 transition-transform"></i>
                </div>
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-700 to-purple-700 drop-shadow-sm">BlastMaster</span>
            </a>
            <div class="hidden md:block ml-8">
                <div class="flex items-baseline space-x-1">
                    <a href="index.php" class="text-gray-700 hover:bg-white/60 hover:text-indigo-700 hover:shadow-md rounded-xl px-4 py-2 text-sm font-bold transition-all"><i class="fa-solid fa-chart-line mr-1.5 text-indigo-400"></i> Dashboard</a>
                    <a href="subscribers.php" class="text-gray-700 hover:bg-white/60 hover:text-indigo-700 hover:shadow-md rounded-xl px-4 py-2 text-sm font-bold transition-all"><i class="fa-solid fa-users mr-1.5 text-indigo-400"></i> Audience</a>
                    <a href="templates.php" class="text-gray-700 hover:bg-white/60 hover:text-indigo-700 hover:shadow-md rounded-xl px-4 py-2 text-sm font-bold transition-all"><i class="fa-solid fa-paintbrush mr-1.5 text-indigo-400"></i> Templates</a>
                    <a href="campaigns.php" class="text-gray-700 hover:bg-white/60 hover:text-indigo-700 hover:shadow-md rounded-xl px-4 py-2 text-sm font-bold transition-all"><i class="fa-solid fa-envelope-open-text mr-1.5 text-indigo-400"></i> Campaigns</a>
                    
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                        <a href="super_admin.php" class="text-fuchsia-700 bg-fuchsia-100/50 hover:bg-fuchsia-100 border border-fuchsia-200 shadow-sm rounded-xl px-4 py-2 text-sm font-bold transition-all ml-2"><i class="fa-solid fa-shield-halved mr-1.5"></i> Super Admin</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if(isset($_SESSION['name'])): ?>
        <div class="flex items-center gap-4">
            <a href="profile.php" class="hidden md:flex items-center text-sm font-bold text-gray-600 hover:text-indigo-700 hover:bg-white/50 px-3 py-1.5 rounded-xl transition-all">
                <div class="w-6 h-6 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 text-white flex items-center justify-center text-xs mr-2 shadow-sm">
                    <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                </div>
                <?php echo htmlspecialchars($_SESSION['name']); ?>
            </a>
            <a href="logout.php" class="text-red-600 hover:text-red-700 text-sm font-bold bg-white/50 hover:bg-red-50 hover:shadow-md px-4 py-2 rounded-xl transition-all border border-red-100/50">Logout</a>
        </div>
        <?php endif; ?>
    </nav>
</div>

<main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 page-transition">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="glass-3d bg-emerald-50/60 border-emerald-200 text-emerald-800 px-6 py-4 mb-8 rounded-2xl flex items-center">
            <div class="bg-emerald-400 p-2 rounded-full mr-4 shadow-[0_0_15px_rgba(52,211,153,0.5)]"><i class="fa-solid fa-check text-white"></i></div>
            <p class="font-bold"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="glass-3d bg-red-50/60 border-red-200 text-red-800 px-6 py-4 mb-8 rounded-2xl flex items-center">
            <div class="bg-red-500 p-2 rounded-full mr-4 shadow-[0_0_15px_rgba(239,68,68,0.5)]"><i class="fa-solid fa-triangle-exclamation text-white"></i></div>
            <p class="font-bold"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        </div>
    <?php endif; ?>