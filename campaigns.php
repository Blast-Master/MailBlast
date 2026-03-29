<?php
// campaigns.php
require_once 'config/database.php';
require_once 'includes/auth.php'; // Protect page & get $company_id

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_campaign']) && $pdo) {
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);
    $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
    $status = $scheduled_at ? 'scheduled' : 'draft';

    // Insert Campaign tied to Company
    $stmt = $pdo->prepare("INSERT INTO campaigns (company_id, subject, body, status, scheduled_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$company_id, $subject, $body, $status, $scheduled_at]);

    $campaignId = $pdo->lastInsertId();
    $pdo->query("INSERT INTO campaign_analytics (campaign_id) VALUES ($campaignId)");

    $_SESSION['success'] = "Campaign successfully saved as $status!";
    header("Location: campaigns.php");
    exit;
}

// DELETE CAMPAIGN ACTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_campaign']) && $pdo) {
    $del_id = (int)$_POST['campaign_id'];
    $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ? AND company_id = ?");
    $stmt->execute([$del_id, $company_id]);
    
    $_SESSION['success'] = "Campaign permanently deleted from history.";
    header("Location: campaigns.php");
    exit;
}

// Fetch Campaigns for this specific company
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE company_id = ? ORDER BY created_at DESC");
$stmt->execute([$company_id]);
$campaigns = $stmt->fetchAll();

// Fetch Pre-Made Templates
$tplStmt = $pdo->prepare("SELECT * FROM email_templates WHERE company_id = ? ORDER BY name ASC");
$tplStmt->execute([$company_id]);
$templatesList = $tplStmt->fetchAll();

include 'includes/header.php';
?>

<div class="mb-10">
    <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-indigo-800 tracking-tight">Campaign Studio</h1>
    <p class="mt-2 text-base text-gray-500 font-medium">Design rich templates, insert dynamic tags, and orchestrate delivery.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
    <!-- Campaign Creator -->
    <div class="lg:col-span-7">
        <div class="glass shadow-2xl rounded-3xl p-10 border border-white/60 relative">
            <h3 class="text-2xl font-black text-gray-900 mb-8 pb-4 border-b border-gray-100 flex items-center">
                <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-2xl mr-4 shadow-lg shadow-indigo-200"><i class="fa-solid fa-pen-nib"></i></div> 
                Compose Email Blast
            </h3>
            
            <form action="campaigns.php" method="POST">
                <div class="mb-6 group">
                    <label class="block text-sm font-extrabold text-gray-700 mb-2 group-focus-within:text-indigo-600 transition-colors uppercase tracking-wider">Email Subject Line</label>
                    <input type="text" name="subject" placeholder="e.g. Huge Holiday Sale Inside! 🎁" required class="w-full px-5 py-4 text-lg font-bold border border-gray-200 rounded-2xl shadow-inner focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all bg-white/70">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-extrabold text-gray-700 mb-2 uppercase tracking-wider"><i class="fa-solid fa-wand-magic-sparkles text-amber-500 mr-1"></i> Load Pre-Made Template</label>
                    <select onchange="loadTemplate(this)" class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold bg-gray-50 text-gray-700">
                        <option value="">-- Start from Scratch --</option>
                        <?php foreach($templatesList as $tpl): ?>
                            <option value="<?php echo $tpl['id']; ?>"><?php echo htmlspecialchars($tpl['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-6 group">
                    <label class="block text-sm font-extrabold text-gray-700 mb-2 flex justify-between items-end uppercase tracking-wider group-focus-within:text-indigo-600 transition-colors">
                        <span>Message Body</span>
                        <span class="text-xs font-bold text-white bg-indigo-500 px-3 py-1.5 rounded-lg shadow-sm tracking-normal">Tag: <b class="font-mono bg-indigo-700 px-1 rounded">{name}</b></span>
                    </label>
                    <div class="relative">
                        <!-- Fake Editor Toolbar -->
                        <div class="absolute top-0 left-0 w-full h-10 bg-gray-100 rounded-t-2xl border-b border-gray-200 flex items-center px-4 gap-3 text-gray-400">
                            <i class="fa-solid fa-bold hover:text-gray-700 cursor-pointer"></i>
                            <i class="fa-solid fa-italic hover:text-gray-700 cursor-pointer"></i>
                            <i class="fa-solid fa-link hover:text-gray-700 cursor-pointer"></i>
                            <div class="w-px h-4 bg-gray-300 mx-1"></div>
                            <i class="fa-solid fa-list hover:text-gray-700 cursor-pointer"></i>
                        </div>
                        <textarea name="body" id="campaign_body" rows="8" placeholder="Hi {name},&#10;&#10;Type your message here..." required class="w-full pt-14 px-5 py-4 border border-gray-200 rounded-2xl shadow-inner focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm leading-relaxed bg-white/70 resize-none"></textarea>
                    </div>
                    <p class="mt-3 text-xs text-emerald-600 font-bold flex items-center bg-emerald-50 p-3 rounded-xl border border-emerald-100"><i class="fa-solid fa-shield-check text-lg mr-2"></i> GDPR & CAN-SPAM safe. Unsubscribe footer applied automatically.</p>
                </div>

                <div class="mb-8 p-6 bg-gradient-to-br from-gray-50 to-white rounded-2xl border border-gray-200 shadow-sm relative overflow-hidden group/schedule">
                    <div class="absolute -right-4 -bottom-4 opacity-[0.03] transform group-hover/schedule:scale-110 group-hover/schedule:-rotate-6 transition-transform duration-500"><i class="fa-solid fa-calendar-alt text-9xl text-indigo-900"></i></div>
                    <label class="block text-sm font-extrabold text-gray-700 mb-3 relative z-10 uppercase tracking-wider"><i class="fa-regular fa-clock text-indigo-500 mr-2 text-lg"></i>Schedule Delivery</label>
                    <input type="datetime-local" name="scheduled_at" class="w-full px-5 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-bold relative z-10 bg-white shadow-inner">
                    <p class="mt-2 text-xs text-gray-400 font-bold relative z-10">Leave blank to save as a Draft.</p>
                </div>

                <button type="submit" name="create_campaign" class="w-full bg-gradient-to-r from-gray-900 to-gray-800 text-white font-black py-4 px-4 rounded-2xl hover:from-black hover:to-gray-900 hover-lift transition-all focus:outline-none shadow-[0_8px_20px_-6px_rgba(0,0,0,0.5)] flex items-center justify-center text-lg gap-2">
                    <i class="fa-solid fa-layer-group"></i> Create Campaign
                </button>
            </form>
        </div>
    </div>

    <!-- Campaign History -->
    <div class="lg:col-span-5 flex flex-col h-[850px]">
        <div class="glass shadow-2xl rounded-3xl overflow-hidden border border-white/60 flex flex-col h-full relative">
            <div class="px-8 py-6 border-b border-gray-100 bg-white/40 flex items-center justify-between z-10">
                <h3 class="text-xl font-black text-gray-900">Archive Log</h3>
                <span class="px-4 py-1.5 bg-gray-100 border border-gray-200 rounded-full text-xs font-bold text-gray-600 shadow-sm">
                    <?php echo count($campaigns); ?> Total
                </span>
            </div>
            
            <div class="overflow-y-auto flex-grow p-6 custom-scrollbar bg-white/20 z-10 space-y-5">
                <?php if(empty($campaigns)): ?>
                    <div class="text-center py-20">
                        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-white mb-6 shadow-sm border border-gray-100">
                            <i class="fa-solid fa-box-open text-4xl text-gray-300"></i>
                        </div>
                        <p class="text-gray-500 font-bold text-lg">No history available.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($campaigns as $camp): ?>
                    <div class="bg-white rounded-2xl p-6 hover:shadow-xl hover:-translate-y-1 hover:border-indigo-200 transition-all border border-gray-100 group relative overflow-hidden shadow-sm">
                        <!-- Status Accent line -->
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 transition-colors
                            <?php echo $camp['status'] === 'sent' ? 'bg-emerald-400' : ($camp['status'] === 'scheduled' ? 'bg-blue-400' : 'bg-gray-300'); ?>
                        "></div>
                        
                        <div class="flex justify-between items-start mb-3 pl-2">
                            <h4 class="font-black text-gray-900 text-lg group-hover:text-indigo-600 transition-colors leading-tight pr-4"><?php echo htmlspecialchars($camp['subject']); ?></h4>
                            <span class="px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-full shadow-sm border whitespace-nowrap
                                <?php echo $camp['status'] === 'draft' ? 'bg-gray-50 text-gray-600 border-gray-200' : ($camp['status'] === 'scheduled' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'); ?>">
                                <?php echo $camp['status']; ?>
                            </span>
                        </div>
                        <p class="text-gray-500 text-sm truncate mb-5 pl-2 font-mono bg-gray-50 p-3 rounded-xl border border-gray-100 shadow-inner"><?php echo htmlspecialchars($camp['body']); ?></p>
                        
                        <div class="flex justify-between items-center pt-4 border-t border-gray-100 pl-2">
                            <span class="text-gray-400 text-xs font-bold flex items-center">
                                <i class="fa-solid fa-calendar-check mr-2 opacity-60"></i> 
                                <?php echo $camp['scheduled_at'] ? date('M d, Y', strtotime($camp['scheduled_at'])) : 'Unscheduled'; ?>
                            </span>
                            
                            <div class="flex items-center gap-2">
                                <?php if($camp['status'] !== 'sent'): ?>
                                    <a href="send_campaign.php?id=<?php echo $camp['id']; ?>" onclick="return confirm('Launch this campaign immediately to all active subscribers?');" class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-4 py-2 rounded-xl hover:from-emerald-600 hover:to-teal-600 hover:shadow-[0_4px_15px_-3px_rgba(16,185,129,0.5)] transition-all font-bold flex items-center text-xs">
                                        <i class="fa-solid fa-paper-plane mr-2"></i> Launch
                                    </a>
                                <?php else: ?>
                                    <span class="text-emerald-600 font-bold px-4 py-2 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center text-xs">
                                        <i class="fa-solid fa-check-double mr-2"></i> Sent
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Delete Action Form -->
                                <form action="campaigns.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this campaign?');">
                                    <input type="hidden" name="campaign_id" value="<?php echo $camp['id']; ?>">
                                    <button type="submit" name="delete_campaign" class="bg-red-50 text-red-600 hover:bg-red-500 hover:text-white px-3 py-2 rounded-xl transition-all font-bold flex items-center text-xs border border-red-100" title="Delete Campaign">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Template Loading JS -->
<script>
    const emailTemplates = <?php echo json_encode($templatesList); ?>;
    function loadTemplate(selectElement) {
        const id = selectElement.value;
        const bodyBox = document.getElementById('campaign_body');
        if (!id) {
            bodyBox.value = '';
            return;
        }
        const template = emailTemplates.find(t => t.id == id);
        if (template) {
            bodyBox.value = template.html_content;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>