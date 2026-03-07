<?php
session_start();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$db = Database::getInstance()->getConnection();
$successMsg = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $status = $_POST['status'];

    if ($type === 'message') {
        $stmt = $db->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
    } else {
        $stmt = $db->prepare("UPDATE catering_inquiries SET status = ? WHERE id = ?");
    }
    $stmt->execute([$status, $id]);
    header('Location: contact_messages.php?tab=' . ($type === 'message' ? 'messages' : 'catering'));
    exit;
}

// Fetch data
$activeTab = $_GET['tab'] ?? 'messages';
$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
$catering = $db->query("SELECT ci.*, u.name as user_name FROM catering_inquiries ci LEFT JOIN users u ON ci.user_id = u.id ORDER BY ci.created_at DESC")->fetchAll();
$pendingMessages = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'pending'")->fetchColumn();
$pendingCatering = $db->query("SELECT COUNT(*) FROM catering_inquiries WHERE status = 'pending'")->fetchColumn();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Inbox | Scoops Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        /* Component Specific Styles */
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .tab-btn {
            padding: 10px 24px;
            border-radius: 50px;
            border: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-muted);
            background: rgba(255,255,255,0.8);
            box-shadow: var(--card-shadow);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-btn .tab-count {
            background: rgba(255,255,255,0.25);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .tab-btn:not(.active) .tab-count {
            background: rgba(108,93,252,0.1);
            color: var(--primary);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(20px);
            padding: 0.8rem 1.5rem;
            border-radius: 18px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255,255,255,0.8);
        }

        .page-title { font-size: 1.4rem; font-weight: 800; color: var(--text-main); }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--surface);
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .admin-profile i {
            background: var(--primary);
            color: white;
            width: 32px; height: 32px;
            display: flex; justify-content: center; align-items: center;
            border-radius: 50%; font-size: 0.8rem;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .tab-btn {
            padding: 10px 24px;
            border-radius: 50px;
            border: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-muted);
            background: rgba(255,255,255,0.8);
            box-shadow: var(--card-shadow);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-btn .tab-count {
            background: rgba(255,255,255,0.25);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .tab-btn:not(.active) .tab-count {
            background: rgba(108,93,252,0.1);
            color: var(--primary);
        }

        /* Panel */
        .panel {
            background: var(--surface);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        /* Table */
        .premium-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }

        .premium-table th {
            text-align: left;
            padding: 0 1.25rem 0.5rem;
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid rgba(44,41,109,0.05);
        }

        .premium-table td {
            padding: 1rem 1.25rem;
            background: rgba(241,239,233,0.3);
            color: var(--text-main);
            font-size: 0.9rem;
            font-weight: 600;
            vertical-align: middle;
        }

        .premium-table tr td:first-child { border-radius: 14px 0 0 14px; }
        .premium-table tr td:last-child { border-radius: 0 14px 14px 0; }
        .premium-table tr:hover td { background: rgba(108,93,252,0.04); }

        .status-badge {
            padding: 5px 12px;
            border-radius: 100px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-pending   { background: rgba(245,158,11,0.15);  color: #d97706; }
        .status-read      { background: rgba(59,130,246,0.15);   color: #2563eb; }
        .status-replied   { background: rgba(16,185,129,0.15);   color: #059669; }
        .status-archived  { background: rgba(107,114,128,0.15);  color: #6b7280; }
        .status-contacted { background: rgba(59,130,246,0.15);   color: #2563eb; }
        .status-completed { background: rgba(16,185,129,0.15);   color: #059669; }
        .status-cancelled { background: rgba(239,68,68,0.15);    color: #dc2626; }

        .action-btn {
            padding: 6px 14px;
            border-radius: 10px;
            border: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-view { background: rgba(108,93,252,0.1); color: var(--primary); }
        .btn-view:hover { background: var(--primary); color: white; }

        .message-preview { 
            max-width: 250px; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
            font-weight: 500;
            color: var(--text-muted);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .empty-state i { font-size: 3rem; opacity: 0.3; margin-bottom: 1rem; display: block; }
        .empty-state p { font-weight: 600; font-size: 1rem; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">📬 Contact Inbox</h1>
            <div class="admin-profile">
                <span><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></span>
                <i class="fas fa-user-shield"></i>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <a href="?tab=messages" class="tab-btn <?= $activeTab === 'messages' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i>
                Contact Messages
                <span class="tab-count"><?= count($messages) ?></span>
            </a>
            <a href="?tab=catering" class="tab-btn <?= $activeTab === 'catering' ? 'active' : '' ?>">
                <i class="fas fa-utensils"></i>
                Catering Inquiries
                <span class="tab-count"><?= count($catering) ?></span>
            </a>
        </div>

        <!-- Contact Messages Tab -->
        <?php if ($activeTab === 'messages'): ?>
        <div class="panel">
            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No contact messages yet.</p>
                </div>
            <?php else: ?>
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $i => $msg): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($msg['name']) ?></td>
                        <td><a href="mailto:<?= htmlspecialchars($msg['email']) ?>" style="color: var(--primary); text-decoration: none;"><?= htmlspecialchars($msg['email']) ?></a></td>
                        <td><?= htmlspecialchars($msg['subject']) ?></td>
                        <td><div class="message-preview"><?= htmlspecialchars($msg['message']) ?></div></td>
                        <td><span class="status-badge status-<?= $msg['status'] ?>"><?= ucfirst($msg['status']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($msg['created_at'])) ?></td>
                        <td>
                            <button class="action-btn btn-view" onclick="viewMessage(<?= htmlspecialchars(json_encode($msg)) ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Catering Inquiries Tab -->
        <?php else: ?>
        <div class="panel">
            <?php if (empty($catering)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>No catering inquiries yet.</p>
                </div>
            <?php else: ?>
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Event Type</th>
                        <th>Guests</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($catering as $i => $ctr): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($ctr['name']) ?></td>
                        <td><a href="mailto:<?= htmlspecialchars($ctr['email']) ?>" style="color: var(--primary); text-decoration: none;"><?= htmlspecialchars($ctr['email']) ?></a></td>
                        <td><?= htmlspecialchars($ctr['event_type']) ?></td>
                        <td><?= number_format($ctr['guests']) ?> pax</td>
                        <td><span class="status-badge status-<?= $ctr['status'] ?>"><?= ucfirst($ctr['status']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($ctr['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display: flex; gap: 6px; align-items: center;">
                                <input type="hidden" name="id" value="<?= $ctr['id'] ?>">
                                <input type="hidden" name="type" value="catering">
                                <select name="status" style="padding: 6px 10px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.8rem; font-weight: 600; background: #f8f9fa; color: var(--text-main);">
                                    <option value="pending" <?= $ctr['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="contacted" <?= $ctr['status'] === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                                    <option value="completed" <?= $ctr['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $ctr['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="action-btn btn-view">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <script>
    function viewMessage(msg) {
        Swal.fire({
            title: `<div style="font-family:'Playfair Display',serif; font-size:1.5rem; font-weight:900; text-align:left; line-height:1.3;">${msg.subject}</div>`,
            html: `
                <div style="text-align:left; font-family:'Plus Jakarta Sans',sans-serif;">
                    <!-- Sender Info -->
                    <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid rgba(0,0,0,0.07);">
                        <div style="width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,#6c5dfc,#a78bfa); display:flex; align-items:center; justify-content:center; color:white; font-weight:800; font-size:1rem; flex-shrink:0;">${msg.name.charAt(0).toUpperCase()}</div>
                        <div>
                            <div style="font-weight:800; font-size:0.95rem; color:#2c296d;">${msg.name}</div>
                            <a href="mailto:${msg.email}" style="color:#6c5dfc; font-size:0.82rem; text-decoration:none;">${msg.email}</a>
                        </div>
                        <div style="margin-left:auto; font-size:0.75rem; color:#9ca3af;">${new Date(msg.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</div>
                    </div>

                    <!-- Original Message -->
                    <div style="font-size:0.72rem; font-weight:800; text-transform:uppercase; letter-spacing:1.2px; color:#9ca3af; margin-bottom:10px;">Original Message</div>
                    <div style="background:#f8f8fc; border-radius:14px; padding:16px 20px; font-size:0.9rem; line-height:1.8; color:#374151; max-height:160px; overflow-y:auto; margin-bottom:24px;">
                        ${msg.message.replace(/\n/g, '<br>')}
                    </div>

                    <!-- Reply Composer -->
                    <div style="background:linear-gradient(135deg,rgba(108,93,252,0.04),rgba(167,139,250,0.04)); border:1px solid rgba(108,93,252,0.12); border-radius:18px; padding:20px;">
                        <div style="font-size:0.72rem; font-weight:800; text-transform:uppercase; letter-spacing:1.2px; color:#6c5dfc; margin-bottom:12px; display:flex; align-items:center; gap:6px;">
                            <i class="fas fa-reply"></i> Compose Reply
                        </div>
                        <div style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap;">
                            <button type="button" onclick="insertTemplate(1)" style="padding:5px 12px; border-radius:20px; border:1px solid rgba(108,93,252,0.2); background:white; color:#6c5dfc; font-size:0.75rem; font-weight:700; cursor:pointer;">👋 Greeting</button>
                            <button type="button" onclick="insertTemplate(2)" style="padding:5px 12px; border-radius:20px; border:1px solid rgba(108,93,252,0.2); background:white; color:#6c5dfc; font-size:0.75rem; font-weight:700; cursor:pointer;">✅ Order Confirmed</button>
                            <button type="button" onclick="insertTemplate(3)" style="padding:5px 12px; border-radius:20px; border:1px solid rgba(108,93,252,0.2); background:white; color:#6c5dfc; font-size:0.75rem; font-weight:700; cursor:pointer;">🗓 Catering Follow-up</button>
                        </div>
                        <textarea id="replyBody" placeholder="Type your reply here..." style="width:100%; min-height:130px; padding:14px 16px; border-radius:12px; border:1px solid rgba(108,93,252,0.2); font-family:'Plus Jakarta Sans',sans-serif; font-size:0.9rem; line-height:1.7; resize:vertical; outline:none; background:white; color:#2c296d;"></textarea>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
                            <span style="font-size:0.78rem; color:#9ca3af;">→ Sending to: <strong style="color:#6c5dfc;">${msg.email}</strong></span>
                            <button id="sendReplyBtn" onclick="sendReply(${msg.id}, '${msg.email.replace(/'/g,"\\'")}', '${msg.name.replace(/'/g,"\\'")}', '${msg.subject.replace(/'/g,"\\'")}', ${msg.id})"
                                style="padding:10px 24px; background:linear-gradient(135deg,#6c5dfc,#a78bfa); color:white; border:none; border-radius:12px; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:0.88rem; cursor:pointer; display:flex; align-items:center; gap:8px;">
                                <i class="fas fa-paper-plane"></i> Send Reply
                            </button>
                        </div>
                    </div>

                    <!-- Status Update -->
                    <form method="POST" id="statusForm" style="margin-top:16px; display:flex; gap:10px; align-items:center;">
                        <input type="hidden" name="id" value="${msg.id}">
                        <input type="hidden" name="type" value="message">
                        <label style="font-weight:700; font-size:0.8rem; color:#6b6b8d; white-space:nowrap;">Update Status:</label>
                        <select name="status" style="padding:8px 12px; border-radius:10px; border:1px solid rgba(0,0,0,0.1); font-family:'Plus Jakarta Sans',sans-serif; font-size:0.85rem; font-weight:600; flex:1; background:#f8f9fa; color:#2c296d;">
                            <option value="pending" ${msg.status==='pending'?'selected':''}>Pending</option>
                            <option value="read" ${msg.status==='read'?'selected':''}>Read</option>
                            <option value="replied" ${msg.status==='replied'?'selected':''}>Replied</option>
                            <option value="archived" ${msg.status==='archived'?'selected':''}>Archived</option>
                        </select>
                        <button type="submit" name="update_status" style="padding:8px 18px; background:#6c5dfc; color:white; border:none; border-radius:10px; font-weight:700; cursor:pointer; white-space:nowrap;">Save</button>
                    </form>
                </div>
            `,
            showConfirmButton: false,
            showCloseButton: true,
            width: '620px',
            padding: '2rem',
            background: '#ffffff',
            didOpen: () => {
                Swal.getPopup().style.borderRadius = '28px';
            }
        });
    }

    function insertTemplate(tpl) {
        const ta = document.getElementById('replyBody');
        const templates = {
            1: "Hi there,\n\nThank you for reaching out to Scoops! We appreciate you contacting us and will get back to you as soon as possible.\n\nWarm regards,\nScoops Admin Team",
            2: "Hi there,\n\nGreat news! Your order has been confirmed and is being prepared fresh for you. We'll notify you once it's ready.\n\nThank you for choosing Scoops! 🍦\n\nWarm regards,\nScoops Admin Team",
            3: "Hi there,\n\nThank you for your catering inquiry! We'd love to make your event extra special with our premium artisan ice cream experience.\n\nOur events coordinator will be in touch within 24 hours to discuss the details of your event.\n\nWarm regards,\nScoops Admin Team"
        };
        ta.value = templates[tpl] || '';
        ta.focus();
    }

    async function sendReply(id, email, name, subject) {
        const body = document.getElementById('replyBody')?.value;
        if (!body || !body.trim()) {
            Swal.showValidationMessage && Swal.showValidationMessage('Please write a reply first.');
            const ta = document.getElementById('replyBody');
            if (ta) { ta.style.border = '1px solid #ef4444'; ta.focus(); }
            return;
        }

        const btn = document.getElementById('sendReplyBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...'; }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('to', email);
        formData.append('to_name', name);
        formData.append('subject', subject);
        formData.append('body', body);

        try {
            const res = await fetch('send_reply.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                Swal.fire({
                    title: 'Reply Sent!',
                    text: `Your reply has been sent to ${email}.`,
                    icon: 'success',
                    confirmButtonColor: '#6c5dfc',
                    didOpen: () => { Swal.getPopup().style.borderRadius = '24px'; }
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: 'Failed to Send',
                    html: `<div style="font-size:0.9rem; color:#6b6b8d; line-height:1.6;">${data.message}<br><br><strong>Tip:</strong> On local XAMPP, configure <code style="background:#f3f4f6; padding:2px 6px; border-radius:4px;">sendmail_path</code> in <code style="background:#f3f4f6; padding:2px 6px; border-radius:4px;">php.ini</code> or use an SMTP library like PHPMailer.</div>`,
                    icon: 'error',
                    confirmButtonColor: '#6c5dfc',
                    didOpen: () => { Swal.getPopup().style.borderRadius = '24px'; }
                });
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply'; }
            }
        } catch (e) {
            Swal.fire('Network Error', 'Could not connect to the server.', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply'; }
        }
    }
    </script>
</body>
</html>
