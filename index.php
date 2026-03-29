<?php
// index.php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Fetch standard metrics
$subStmt = $pdo->prepare("SELECT count(*) FROM subscribers WHERE company_id = ? AND status = 'active' AND is_verified = 1");
$subStmt->execute([$company_id]);
$activeSubscribers = $subStmt->fetchColumn();

$campStmt = $pdo->prepare("SELECT count(*) FROM campaigns WHERE company_id = ?");
$campStmt->execute([$company_id]);
$totalCampaigns = $campStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT monthly_sent_count FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$monthlySent = $stmt->fetchColumn();
$monthlyLimit = 60000;
$limitPercentage = ($monthlySent / $monthlyLimit) * 100;

include 'includes/header.php';
?>

<div class="mb-10 page-transition">
    <h1 class="text-4xl md:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-indigo-900 tracking-tight drop-shadow-sm">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
    <p class="mt-3 text-lg text-gray-600 font-medium">Your global email metrics and network status.</p>
</div>

<!-- 3D Glassmorphic Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
    <div class="glass-3d hover-3d p-8 relative overflow-hidden group">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-indigo-500/20 rounded-full blur-2xl group-hover:bg-indigo-500/40 transition-all duration-500"></div>
        <div class="flex items-center justify-between mb-4 relative z-10">
            <h3 class="text-sm font-black text-gray-500 uppercase tracking-widest">Active Audience</h3>
            <div class="p-3 bg-white/60 rounded-xl shadow-sm border border-white"><i class="fa-solid fa-users text-indigo-600 text-xl"></i></div>
        </div>
        <p class="text-5xl font-black text-gray-900 drop-shadow-sm relative z-10"><?php echo number_format($activeSubscribers); ?></p>
        <p class="text-sm text-emerald-600 font-bold mt-2 relative z-10"><i class="fa-solid fa-arrow-trend-up mr-1"></i> Verified & Ready</p>
    </div>
    
    <div class="glass-3d hover-3d p-8 relative overflow-hidden group">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-purple-500/20 rounded-full blur-2xl group-hover:bg-purple-500/40 transition-all duration-500"></div>
        <div class="flex items-center justify-between mb-4 relative z-10">
            <h3 class="text-sm font-black text-gray-500 uppercase tracking-widest">Campaigns Sent</h3>
            <div class="p-3 bg-white/60 rounded-xl shadow-sm border border-white"><i class="fa-solid fa-envelope-open-text text-purple-600 text-xl"></i></div>
        </div>
        <p class="text-5xl font-black text-gray-900 drop-shadow-sm relative z-10"><?php echo number_format($totalCampaigns); ?></p>
        <p class="text-sm text-gray-500 font-bold mt-2 relative z-10">Total historical blasts</p>
    </div>

    <div class="glass-3d hover-3d p-8 relative overflow-hidden group">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-pink-500/20 rounded-full blur-2xl group-hover:bg-pink-500/40 transition-all duration-500"></div>
        <div class="flex items-center justify-between mb-4 relative z-10">
            <h3 class="text-sm font-black text-gray-500 uppercase tracking-widest">Monthly Quota</h3>
            <div class="p-3 bg-white/60 rounded-xl shadow-sm border border-white"><i class="fa-solid fa-gauge-high text-pink-600 text-xl"></i></div>
        </div>
        <p class="text-5xl font-black text-gray-900 drop-shadow-sm relative z-10"><?php echo number_format($monthlySent); ?></p>
        
        <div class="mt-4 relative z-10">
            <div class="w-full bg-gray-200/50 rounded-full h-2.5 shadow-inner">
                <div class="bg-gradient-to-r from-pink-500 to-indigo-500 h-2.5 rounded-full shadow-[0_0_10px_rgba(236,72,153,0.5)]" style="width: <?php echo min(100, $limitPercentage); ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 font-bold mt-2 text-right"><?php echo number_format($monthlyLimit); ?> Limit</p>
        </div>
    </div>
</div>

<!-- Quick Actions Panel -->
<div class="glass-3d p-10 relative overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
    <div class="flex flex-col md:flex-row justify-between items-center gap-6 relative z-10">
        <div>
            <h2 class="text-2xl font-black text-gray-900">Ready to launch?</h2>
            <p class="text-gray-600 font-medium mt-1">Create a new template or compose a blast to your audience instantly.</p>
        </div>
        <div class="flex gap-4">
            <a href="templates.php" class="bg-white/60 hover:bg-white text-indigo-700 border border-white px-6 py-3 rounded-xl font-bold transition-all shadow-sm hover:shadow-md flex items-center">
                <i class="fa-solid fa-paintbrush mr-2"></i> Draft Template
            </a>
            <a href="campaigns.php" class="btn-glow px-8 py-3 rounded-xl font-bold flex items-center">
                <i class="fa-solid fa-paper-plane mr-2"></i> New Campaign
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>