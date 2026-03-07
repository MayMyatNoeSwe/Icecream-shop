<?php
ob_start(); // buffer all output so stray notices don't break JSON

session_start();

// Clean any buffered junk before writing JSON
function jsonResponse(array $data): void {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Auth check
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized']);
}

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.']);
}

require_once '../config/database.php';

$id      = trim($_POST['id']      ?? '');
$to      = trim($_POST['to']      ?? '');
$toName  = trim($_POST['to_name'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$body    = trim($_POST['body']    ?? '');

if (!$id || !$to || !$subject || !$body) {
    jsonResponse(['success' => false, 'message' => 'Missing required fields.']);
}

if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Invalid recipient email address.']);
}

$adminName = $_SESSION['admin_name'] ?? 'Scoops Admin';
$fromEmail = 'noreply@scoops.com';

// Premium HTML email template
$htmlBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { margin:0; font-family:'Helvetica Neue',Arial,sans-serif; background:#f4f4f8; }
        .wrapper { max-width:600px; margin:40px auto; }
        .header  { background:linear-gradient(135deg,#2c296d 0%,#6c5dfc 100%); padding:40px 40px 30px; border-radius:24px 24px 0 0; text-align:center; }
        .header h1 { color:white; font-size:1.6rem; margin:0; letter-spacing:-0.5px; }
        .header p  { color:rgba(255,255,255,0.75); font-size:0.9rem; margin:8px 0 0; }
        .body { background:white; padding:36px 40px; }
        .greeting { font-size:1.05rem; color:#2c296d; font-weight:700; margin-bottom:20px; }
        .message-box { background:#f8f8fc; border-left:4px solid #6c5dfc; border-radius:0 14px 14px 0; padding:20px 24px; font-size:0.95rem; line-height:1.8; color:#555; margin-bottom:28px; white-space:pre-wrap; }
        .footer { background:#f4f4f8; padding:24px 40px; border-radius:0 0 24px 24px; text-align:center; }
        .footer p { font-size:0.8rem; color:#999; margin:0; }
        .footer strong { color:#6c5dfc; }
    </style>
</head>
<body>
<div class='wrapper'>
    <div class='header'>
        <h1>🍦 Scoops Premium</h1>
        <p>Reply to your inquiry</p>
    </div>
    <div class='body'>
        <div class='greeting'>Hi " . htmlspecialchars($toName) . ",</div>
        <div class='message-box'>" . nl2br(htmlspecialchars($body)) . "</div>
        <p style='font-size:0.85rem;color:#aaa;'>If you did not contact us, please disregard this email.</p>
    </div>
    <div class='footer'>
        <p>Warm regards, <strong>" . htmlspecialchars($adminName) . "</strong> &mdash; Scoops Admin Team</p>
        <p style='margin-top:6px;'>📍 123 Sweet Street, Yangon, Myanmar</p>
    </div>
</div>
</body>
</html>
";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: Scoops Admin <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$fromEmail}\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail($to, "Re: {$subject}", $htmlBody, $headers);

if ($sent) {
    try {
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE contact_messages SET status = 'replied' WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        // DB error is non-critical – email was still sent
    }
    jsonResponse(['success' => true]);
} else {
    jsonResponse([
        'success' => false,
        'message' => 'Email could not be sent. On local XAMPP, make sure sendmail is configured in php.ini (sendmail_path), or switch to PHPMailer with SMTP.'
    ]);
}

