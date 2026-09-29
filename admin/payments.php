<?php
// Load the file needed by this page
require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('admin');
$message = '';
$error = '';
// Check if an action was requested
if (isset($_GET['delete'])) {
// Run the database query.
    $id = (int)$_GET['delete'];
    // Get data from the database
    $stmt = $conn->prepare("DELETE FROM payments WHERE id=?");
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    // Run the database query
    if ($stmt->execute()) $message = 'Payment deleted successfully.';
// Run the database query.
    else $error = 'Unable to delete payment.';
    $stmt->close();
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $member = (int)($_POST['member_id'] ?? 0);
    $membership = ($_POST['membership_id'] ?? '') !== '' ? (int)$_POST['membership_id'] : null;
    $amount = (float)($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? 'cash';
    $date = $_POST['payment_date'] ?? date('Y-m-d');
    $ref = trim($_POST['reference_no'] ?? '');
    $status = $_POST['status'] ?? 'paid';
    if (!$member || $amount <= 0 || !in_array($method, ['cash', 'card', 'bank', 'online'], true) || !in_array($status, ['paid', 'pending', 'refunded'], true)) $error = 'Member, valid amount, method and status are required.';
    elseif ($id) {
        // Get data from the database
        $stmt = $conn->prepare("UPDATE payments SET member_id=?,membership_id=?,amount=?,payment_method=?,payment_date=?,reference_no=?,status=? WHERE id=?");
        // Add values to the prepared query
        $stmt->bind_param('iidssssi', $member, $membership, $amount, $method, $date, $ref, $status, $id);
        // Run the database query
        if ($stmt->execute()) $message = 'Payment updated successfully.';
// Run the database query.
        else $error = 'Could not update payment.';
        $stmt->close();
    } else {
        // Get data from the database
        $stmt = $conn->prepare("INSERT INTO payments(member_id,membership_id,amount,payment_method,payment_date,reference_no,status) VALUES(?,?,?,?,?,?,?)");
        // Add values to the prepared query
        $stmt->bind_param('iidssss', $member, $membership, $amount, $method, $date, $ref, $status);
        // Run the database query
        if ($stmt->execute()) $message = 'Payment recorded successfully.';
        else $error = 'Could not record payment.';
        $stmt->close();
    }
}
// Get data from the database
$members = $conn->query("SELECT m.id,u.full_name FROM members m JOIN users u ON u.id=m.user_id ORDER BY u.full_name");
// Get data from the database
$memberships = $conn->query("SELECT ms.id,ms.member_id,mp.name,ms.start_date,ms.end_date FROM memberships ms JOIN membership_plans mp ON mp.id=ms.plan_id ORDER BY ms.id DESC");
$edit = null;
// Check if an action was requested
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // Get data from the database
    $stmt = $conn->prepare("SELECT * FROM payments WHERE id=?");
    // Add values to the prepared query
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
// Get data from the database
$list = $conn->query("SELECT p.*,u.full_name,mp.name plan_name FROM payments p JOIN members m ON m.id=p.member_id JOIN users u ON u.id=m.user_id LEFT JOIN memberships ms ON ms.id=p.membership_id LEFT JOIN membership_plans mp ON mp.id=ms.plan_id ORDER BY p.payment_date DESC,p.id DESC");
$paid = 0;
$pending = 0;
$refunded = 0;
// Get data from the database
$r = $conn->query("SELECT status,COALESCE(SUM(amount),0) total FROM payments GROUP BY status");
while ($x = $r->fetch_assoc()) {
    if ($x['status'] === 'paid') $paid = $x['total'];
    elseif ($x['status'] === 'pending') $pending = $x['total'];
    else $refunded = $x['total'];
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payments — FitTrack</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="app">
    <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">MAIN</div><a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav" href="members.php"><span class="ico">◎</span>Members</a><a class="nav" href="trainers.php"><span class="ico">✦</span>Trainers</a><a class="nav" href="membership-plans.php"><span class="ico">◇</span>Membership Plans</a><a class="nav active" href="payments.php"><span class="ico">₨</span>Payments</a><a class="nav" href="memberships.php"><span class="ico">▱</span>Memberships</a>
            <div class="nav-label">MANAGEMENT</div><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <h1>Payments</h1>
                    <p>Record and manage member payment history.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?> · Administrator</div>
            </div>
            <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <div class="grid">
                <div class="stat"><small>Paid Revenue</small><strong>PKR <?php echo number_format($paid); ?></strong><i>₨</i></div>
                <div class="stat"><small>Pending</small><strong>PKR <?php echo number_format($pending); ?></strong><i>◷</i></div>
                <div class="stat"><small>Refunded</small><strong>PKR <?php echo number_format($refunded); ?></strong><i>↩</i></div>
                <div class="stat"><small>Total Records</small><strong><?php echo $list->num_rows; ?></strong><i>▤</i></div>
            </div>
            <div class="section">
                <div class="section-head">
                    <h2><?php echo $edit ? 'Edit Payment' : 'Record Payment'; ?></h2><?php if ($edit): ?><a class="btn secondary" href="payments.php">Cancel</a><?php endif; ?>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="post" class="form-grid">
<!-- // Run the database query. -->
                    <?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)($edit['id'] ?? 0); ?>"><label>Member<select name="member_id" required>
<!-- // Run the database query. -->
                            <option value="">Select member</option><?php $members->data_seek(0);
                                                                    while ($m = $members->fetch_assoc()): ?><option value="<?php echo $m['id']; ?>" <?php echo (($edit['member_id'] ?? '') == $m['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['full_name']); ?></option><?php endwhile; ?>
                        </select></label>
<!-- // Run the database query. -->
                    <label>Membership (optional)<select name="membership_id">
                            <option value="">No linked membership</option><?php $memberships->data_seek(0);
                                                                            while ($ms = $memberships->fetch_assoc()): ?><option value="<?php echo $ms['id']; ?>" data-member="<?php echo $ms['member_id']; ?>" <?php echo (($edit['membership_id'] ?? '') == $ms['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($ms['name'] . ' · ' . $ms['start_date'] . ' to ' . $ms['end_date']); ?></option><?php endwhile; ?>
                        </select></label>
                    <label>Amount (PKR)<input type="number" name="amount" min="1" step="0.01" value="<?php echo htmlspecialchars($edit['amount'] ?? ''); ?>" required></label><label>Payment Date<input type="date" name="payment_date" value="<?php echo htmlspecialchars($edit['payment_date'] ?? date('Y-m-d')); ?>" required></label>
<!-- // Run the database query. -->
                    <label>Payment Method<select name="payment_method">
                            <option value="cash" <?php echo (($edit['payment_method'] ?? 'cash') === 'cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="card" <?php echo (($edit['payment_method'] ?? '') === 'card') ? 'selected' : ''; ?>>Card</option>
                            <option value="bank" <?php echo (($edit['payment_method'] ?? '') === 'bank') ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="online" <?php echo (($edit['payment_method'] ?? '') === 'online') ? 'selected' : ''; ?>>Online</option>
<!-- // Run the database query. -->
                        </select></label><label>Status<select name="status">
                            <option value="paid" <?php echo (($edit['status'] ?? 'paid') === 'paid') ? 'selected' : ''; ?>>Paid</option>
                            <option value="pending" <?php echo (($edit['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="refunded" <?php echo (($edit['status'] ?? '') === 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                        </select></label>
                    <label>Reference No. (optional)<input type="text" name="reference_no" maxlength="80" value="<?php echo htmlspecialchars($edit['reference_no'] ?? ''); ?>
?>">
                    </label>
<!-- // Run the database query. -->
                    <div style="display:flex;align-items:end"><button class="btn" type="submit"><?php echo $edit ? 'Update Payment' : 'Save Payment'; ?></button></div>
                </form>
            </div>
            <div class="section">
                <div class="section-head">
                    <h2>Payment History</h2><span style="font-size:10px;color:#637572"><?php echo $list->num_rows; ?> records</span>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Membership</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody><?php if ($list->num_rows): while ($row = $list->fetch_assoc()): ?><tr>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['plan_name'] ?? '—'); ?></td>
                                        <td>PKR <?php echo number_format($row['amount'], 2); ?></td>
                                        <td><?php echo ucfirst($row['payment_method']); ?></td>
                                        <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['reference_no'] ?: '—'); ?></td>
                                        <td><span class="badge"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td>
<!-- // Run the database query. -->
                                            <div class="actions"><a class="action" href="payments.php?edit=<?php echo $row['id']; ?>">Edit</a><a class="action" href="payments.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this payment record?')">Delete</a></div>
                                        </td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="8">
                                        <div class="empty">No payments recorded yet.</div>
                                    </td>
                                </tr><?php endif; ?></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script>
// Run the database query.
        const member = document.querySelector('select[name="member_id"]'),
// Run the database query.
            membership = document.querySelector('select[name="membership_id"]');

        function filterMemberships() {
            const id = member.value;
            [...membership.options].forEach(o => {
                if (!o.dataset.member) return;
                o.hidden = id !== o.dataset.member;
            });
            if (membership.selectedOptions[0]?.hidden) membership.value = '';
        }
        member.addEventListener('change', filterMemberships);
        filterMemberships();
    </script>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
