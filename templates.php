<?php
// templates.php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $name = trim($_POST['name']);
    $html_content = trim($_POST['html_content']); // Storing plain text in this column now
    
    $stmt = $pdo->prepare("INSERT INTO email_templates (company_id, name, html_content) VALUES (?, ?, ?)");
    $stmt->execute([$company_id, $name, $html_content]);
    
    $_SESSION['success'] = "Template saved successfully!";
    header("Location: templates.php");
    exit;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_template'])) {
    $id = (int)$_POST['template_id'];
    $stmt = $pdo->prepare("DELETE FROM email_templates WHERE id = ? AND company_id = ?");
    $stmt->execute([$id, $company_id]);
    
    $_SESSION['success'] = "Template deleted.";
    header("Location: templates.php");
    exit;
}

// Fetch Pre-Made Templates
$tplStmt = $pdo->prepare("SELECT * FROM email_templates WHERE company_id = ? ORDER BY created_at DESC");
$tplStmt->execute([$company_id]);
$templatesList = $tplStmt->fetchAll();

include 'includes/header.php';
?>

<div class="mb-10">
    <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-indigo-800 tracking-tight">Email Templates</h1>
    <p class="mt-2 text-base text-gray-500 font-medium">Create and manage plain text templates for your campaigns.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
    <!-- Template Creator -->
    <div class="lg:col-span-6">
        <div class="glass shadow-2xl rounded-3xl p-8 border border-white/60 relative">
            <h3 class="text-xl font-black text-gray-900 mb-6 pb-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-3 bg-indigo-100 text-indigo-600 rounded-xl mr-3 shadow-inner"><i class="fa-solid fa-align-left"></i></div> 
                    Text Editor
                </div>
                
                <!-- Pre-made Template Loader -->
                <select id="premade_selector" onchange="loadPremadeTemplate()" class="text-sm font-bold bg-gray-50 border border-gray-200 text-gray-700 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                    <option value="">-- Load Pre-Made Template --</option>
                    <option value="welcome">👋 Welcome Email</option>
                    <option value="promo">🏷️ Promotional Sale</option>
                    <option value="newsletter">📰 Monthly Newsletter</option>
                </select>
            </h3>
            
            <form action="templates.php" method="POST" id="templateForm">
                <div class="mb-5 group">
                    <label class="block text-sm font-extrabold text-gray-700 mb-2 uppercase tracking-wider">Template Name</label>
                    <input type="text" name="name" placeholder="e.g. Welcome Series 1" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                
                <div class="mb-6 group">
                    <label class="block text-sm font-extrabold text-gray-700 mb-2 flex justify-between uppercase tracking-wider">
                        <span>Email Content</span>
                        <span class="text-xs font-bold text-indigo-600">Supports {name} tag</span>
                    </label>
                    <textarea name="html_content" id="html_content" rows="12" placeholder="Hi {name},..." required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none font-mono text-sm leading-relaxed bg-white/70 resize-none"></textarea>
                </div>

                <button type="submit" name="save_template" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-3.5 rounded-xl hover:shadow-lg hover:-translate-y-0.5 transition-all">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Save Template
                </button>
            </form>
        </div>
    </div>

    <!-- Template List -->
    <div class="lg:col-span-6 flex flex-col h-[700px]">
        <div class="glass shadow-2xl rounded-3xl overflow-hidden border border-white/60 flex flex-col h-full relative">
            <div class="px-8 py-6 border-b border-gray-100 bg-white/40 flex items-center justify-between">
                <h3 class="text-xl font-black text-gray-900">Your Saved Templates</h3>
                <span class="bg-indigo-50 text-indigo-700 text-xs font-bold px-3 py-1.5 rounded-full border border-indigo-100"><?php echo count($templatesList); ?> Saved</span>
            </div>
            
            <div class="overflow-y-auto flex-grow p-6 custom-scrollbar bg-white/20 space-y-4">
                <?php if(empty($templatesList)): ?>
                    <div class="text-center py-16 text-gray-400 font-medium">
                        <i class="fa-solid fa-box-open text-5xl mb-4 text-gray-300"></i>
                        <p>No templates created yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($templatesList as $tpl): ?>
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-3">
                            <h4 class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($tpl['name']); ?></h4>
                            <form action="templates.php" method="POST" onsubmit="return confirm('Delete this template permanently?');">
                                <input type="hidden" name="template_id" value="<?php echo $tpl['id']; ?>">
                                <button type="submit" name="delete_template" class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors border border-red-100 text-xs font-bold"><i class="fa-solid fa-trash mr-1"></i> Delete</button>
                            </form>
                        </div>
                        <div class="bg-gray-50 border border-gray-100 rounded-lg p-3 h-32 overflow-hidden relative text-sm text-gray-600 font-mono whitespace-pre-wrap">
                            <?php echo htmlspecialchars(substr($tpl['html_content'], 0, 300)) . '...'; ?>
                            <div class="absolute bottom-0 left-0 w-full h-12 bg-gradient-to-t from-gray-50 to-transparent"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Pre-made Text Templates Logic
    const premadeTemplates = {
        'welcome': `Welcome to the Family, {name}! 🎉

We are absolutely thrilled to have you on board. Our goal is to provide you with the best experience possible.

Here are a few things you can do to get started:
- Explore our features: Check out the dashboard to see what's new.
- Update your profile: Make sure your details are up to date.
- Reach out to support: We're here if you need any help!

Thanks again for joining us.

Best regards,
The Team`,

        'promo': `Huge Savings Just for You! 💸

Hi {name},

For a limited time only, we are offering an exclusive 20% discount on your next purchase!

Use the promo code below at checkout:
SAVE20NOW

Don't wait too long—this offer expires in 48 hours.

Happy Shopping!`,

        'newsletter': `Your Monthly Update 📰

Hello {name},

Welcome to this month's newsletter! We've been working hard behind the scenes and have some exciting updates to share.

🚀 What's New?
We just launched a highly requested feature! You can now manage your preferences directly from your account settings.

💡 Tip of the Month
Did you know you can automate your workflow? Check out our latest blog post to learn how to save hours every week.

Stay tuned for more updates next month.`
    };

    function loadPremadeTemplate() {
        var selector = document.getElementById('premade_selector');
        var selectedValue = selector.value;
        var textArea = document.getElementById('html_content');
        
        if (selectedValue && premadeTemplates[selectedValue]) {
            // Warn the user before overwriting their current work
            if(textArea.value.trim().length > 0) {
                if(!confirm("Loading this template will overwrite your current text. Proceed?")) {
                    selector.value = ""; // Reset dropdown
                    return;
                }
            }
            // Inject the plain text into the textarea
            textArea.value = premadeTemplates[selectedValue];
        }
        // Reset dropdown back to default
        selector.value = "";
    }
</script>

<?php include 'includes/footer.php'; ?>s