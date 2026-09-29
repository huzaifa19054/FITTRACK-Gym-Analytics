<?php
// Load the file needed by this page
require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('admin');

$message = '';
$type = 'success';
// Check if an action was requested
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    if ($id > 0 && in_array($action, ['read', 'replied', 'new'], true)) {
        // Get data from the database
        $stmt = $conn->prepare("UPDATE contact_messages SET status=? WHERE id=?");
        // Add values to the prepared query
        $stmt->bind_param('si', $action, $id);
        $stmt->execute();
        $stmt->close();
        $message = 'Message status updated.';
// Run the database query.
    } elseif ($id > 0 && $action === 'delete') {
        // Get data from the database
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id=?");
        // Add values to the prepared query
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $message = 'Message deleted.';
    }
}

$newCount = 0;
$totalCount = 0;
// Get data from the database
$r = $conn->query("SELECT COUNT(*) total FROM contact_messages");
if ($r) $totalCount = (int)$r->fetch_assoc()['total'];
// Get data from the database
$r = $conn->query("SELECT COUNT(*) total FROM contact_messages WHERE status='new'");
if ($r) $newCount = (int)$r->fetch_assoc()['total'];
// Get data from the database
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC");
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — Contact Messages</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="app">
    <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">MAIN</div><a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav" href="members.php"><span class="ico">◎</span>Members</a><a class="nav" href="trainers.php"><span class="ico">✦</span>Trainers</a><a class="nav" href="membership-plans.php"><span class="ico">◇</span>Membership Plans</a><a class="nav" href="payments.php"><span class="ico">₨</span>Payments</a><a class="nav" href="memberships.php"><span class="ico">▱</span>Memberships</a>
            <div class="nav-label">MANAGEMENT</div><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav" href="progress.php"><span class="ico">↗</span>Progress</a><a class="nav" href="reports.php"><span class="ico">▥</span>Reports</a><a class="nav active" href="contact-messages.php"><span class="ico">✉</span>Contact Messages</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="users.php"><span class="ico">♙</span>User Management</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <div class="eyebrow">FITTRACK / ADMIN</div>
                    <h1>Contact Messages</h1>
                    <p>Messages submitted through the public FitTrack website.</p>
                </div>
                <div class="dashboard-user-actions">
                    <div class="user-pill"><span class="online-dot"></span><?php echo htmlspecialchars($_SESSION['full_name']); ?><b>Administrator</b></div><a href="../logout.php" class="logout-top">Logout <span>⇥</span></a>
                </div>
            </div>
            <?php if ($message): ?><div class="alert <?php echo $type; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <div class="dashboard-stats" style="grid-template-columns:repeat(2,1fr);max-width:520px">
                <div class="stat stat-highlight">
                    <div><small>New Messages</small><strong><?php echo $newCount; ?></strong><span class="stat-sub">Need attention</span></div><i>✉</i>
                </div>
                <div class="stat">
                    <div><small>Total Messages</small><strong><?php echo $totalCount; ?></strong><span class="stat-sub">All website enquiries</span></div><i>◎</i>
                </div>
            </div>
    <!-- Main page section. -->
            <section class="section">
                <div class="section-head">
                    <div>
                        <h2>Message Inbox</h2>
                    </div>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Sender</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($messages && $messages->num_rows): while ($row = $messages->fetch_assoc()): ?><tr>
                                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong><br><small><?php echo htmlspecialchars($row['email']); ?></small></td>
                                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                        <td style="min-width:260px;white-space:normal;line-height:1.6"><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                                        <td><span class="badge <?php echo $row['status'] === 'new' ? 'new-status' : ''; ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span></td>
                                        <td>
<!-- // Run the database query. -->
                                            <div class="actions"><a class="action" href="?action=<?php echo $row['status'] === 'new' ? 'read' : 'replied'; ?>&id=<?php echo (int)$row['id']; ?>"><?php echo $row['status'] === 'new' ? 'Mark Read' : 'Mark Replied'; ?></a><?php if ($row['status'] !== 'new'): ?><a class="action" href="?action=new&id=<?php echo (int)$row['id']; ?>">New</a><?php endif; ?><a class="action" href="?action=delete&id=<?php echo (int)$row['id']; ?>" onclick="return confirm('Delete this message?')">Delete</a></div>
                                        </td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="6" class="empty">No contact messages yet.</td>
                                </tr><?php endif; ?></tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
