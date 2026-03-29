<?php
// send_campaign.php
require_once 'config/database.php';
session_start();

if (!isset($_GET['id']) || !$pdo) {
    header("Location: campaigns.php");
    exit;
}

// Security Check: Ensure user is actively approved
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'active') {
    $_SESSION['error'] = "You do not have permission to execute campaigns.";
    header("Location: campaigns.php");
    exit;
}

$campaignId = (int)$_GET['id'];

// 1. Fetch the campaign and its associated company details
$stmt = $pdo->prepare("
    SELECT c.*, comp.monthly_sent_count, comp.id as company_id 
    FROM campaigns c 
    JOIN companies comp ON c.company_id = comp.id 
    WHERE c.id = ?
");
$stmt->execute([$campaignId]);
$campaign = $stmt->fetch();

if (!$campaign || $campaign['status'] === 'sent') {
    $_SESSION['error'] = "Error: Campaign not found or already sent.";
    header("Location: campaigns.php");
    exit;
}

$companyId = $campaign['company_id'];

// 2. Fetch ONLY 'active' AND 'verified' subscribers for this company.
// Hard cap at 10,000 emails per blast to manage server load.
$subStmt = $pdo->prepare("
    SELECT * FROM subscribers 
    WHERE status = 'active' 
    AND is_verified = 1 
    AND company_id = ? 
    LIMIT 10000
");
$subStmt->execute([$companyId]);
$subscribers = $subStmt->fetchAll();

$targetCount = count($subscribers);

if ($targetCount === 0) {
    $_SESSION['error'] = "Error: No verified and active subscribers found for this campaign.";
    header("Location: campaigns.php");
    exit;
}

// 3. Enforce the 60,000 Monthly Sending Limit
if (($campaign['monthly_sent_count'] + $targetCount) > 60000) {
    $_SESSION['error'] = "Limit Exceeded: Sending this campaign requires $targetCount emails, but you only have " . (60000 - $campaign['monthly_sent_count']) . " left this month.";
    header("Location: campaigns.php");
    exit;
}

// Mark campaign as sending to prevent duplicate triggers
$pdo->prepare("UPDATE campaigns SET status = 'sending' WHERE id = ?")->execute([$campaignId]);

// --- ADVANCED SMTP CONFIGURATION USING PHPMAILER ---
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
$sentCount = 0;
$failedCount = 0;

try {
    // GLOBAL HARDCODED SERVER SETTINGS (Bypassing individual company settings)
    $mail->isSMTP();                                            
    $mail->Host       = 'smtp.gmail.com';                       
    $mail->SMTPAuth   = true;                                   
    $mail->Username   = 'godoyjp443@gmail.com';                 
    $mail->Password   = 'nofz ngrn camy vigl'; // <-- IMPORTANT: Put your Google App Password here!
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
    $mail->Port       = 587;                                    

    $mail->setFrom('godoyjp443@gmail.com', 'BlastMaster');      
    $mail->isHTML(true);                                        

    // Prepare statement for granular delivery tracking
    $logStmt = $pdo->prepare("INSERT INTO email_logs (campaign_id, subscriber_id, status, error_message) VALUES (?, ?, ?, ?)");

    // 4. Execution Loop
    foreach ($subscribers as $sub) {
        try {
            $mail->addAddress($sub['email'], $sub['name']);
            $mail->Subject = htmlspecialchars($campaign['subject']);
            
            // Personalization
            $body = str_replace('{name}', htmlspecialchars($sub['name']), $campaign['body']);
            $bodyHtml = nl2br($body);
            
            // Unsubscribe and Compliance Logic
            $unsubscribeLink = "http://yourdomain.com/unsubscribe.php?token=" . urlencode($sub['verification_token']);
            $bodyHtml .= "<br><br><hr style='border:none; border-top:1px solid #eee;'/>";
            $bodyHtml .= "<p style='font-size:12px; color:#999; font-family:sans-serif;'>";
            $bodyHtml .= "You are receiving this email because you opted in at BlastMaster.<br>";
            $bodyHtml .= "<a href='{$unsubscribeLink}' style='color:#4f46e5; text-decoration:underline;'>Manage Preferences or Unsubscribe</a>";
            $bodyHtml .= "</p>";

            $mail->Body = $bodyHtml;
            $mail->AltBody = strip_tags($bodyHtml);

            // Send payload
            $mail->send();
            $sentCount++;
            
            // Log successful delivery
            $logStmt->execute([$campaignId, $sub['id'], 'sent', null]);
            
            $mail->clearAddresses(); 
            
            // RATE LIMITING: Add a 100ms micro-sleep (10 emails/sec) to avoid aggressive spam blocking
            usleep(100000); 
            
        } catch (Exception $e) {
            // Log bounce/failure
            $logStmt->execute([$campaignId, $sub['id'], 'failed', $mail->ErrorInfo]);
            $failedCount++;
            error_log("Delivery failed for {$sub['email']}. Reason: {$mail->ErrorInfo}");
            $mail->clearAddresses();
        }
    }
} catch (Exception $e) {
    // Critical SMTP Connection Error
    $_SESSION['error'] = "Critical SMTP Error: " . $mail->ErrorInfo;
    $pdo->prepare("UPDATE campaigns SET status = 'draft' WHERE id = ?")->execute([$campaignId]); // Revert to draft
    header("Location: campaigns.php");
    exit;
}

// 5. Finalize Campaign & Analytics
$pdo->prepare("UPDATE campaigns SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$campaignId]);

// Deduct from monthly limits
$pdo->prepare("UPDATE companies SET monthly_sent_count = monthly_sent_count + ? WHERE id = ?")->execute([$sentCount, $companyId]);

// Update standard analytics table
$checkAnalytics = $pdo->prepare("SELECT id FROM campaign_analytics WHERE campaign_id = ?");
$checkAnalytics->execute([$campaignId]);
if ($checkAnalytics->rowCount() > 0) {
    $pdo->prepare("UPDATE campaign_analytics SET sent_count = ? WHERE campaign_id = ?")->execute([$sentCount, $campaignId]);
} else {
    $pdo->prepare("INSERT INTO campaign_analytics (campaign_id, sent_count) VALUES (?, ?)")->execute([$campaignId, $sentCount]);
}

$_SESSION['success'] = "Campaign Completed! Delivered: " . number_format($sentCount) . " | Bounced/Failed: " . number_format($failedCount);
header("Location: index.php");
exit;