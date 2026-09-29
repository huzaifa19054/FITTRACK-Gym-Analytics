<?php require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('member');
// Get the logged-in user ID
$uid = (int)$_SESSION['user_id'];
// Get data from the database
$m = $conn->query("SELECT id FROM members WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$mid = (int)($m['id'] ?? 0);
// Get data from the database
$rows = $conn->query("SELECT p.*,ms.id membership_id FROM payments p LEFT JOIN memberships ms ON ms.id=p.membership_id WHERE p.member_id=$mid ORDER BY p.id DESC"); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — My Payments</title>
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
            <div class="nav-label">MAIN</div><a class="nav " href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav " href="membership.php"><span class="ico">▱</span>My Membership</a><a class="nav " href="attendance.php"><span class="ico">◷</span>My Attendance</a><a class="nav " href="workout.php"><span class="ico">▣</span>My Workout</a><a class="nav " href="diet.php"><span class="ico">◌</span>My Diet</a><a class="nav active" href="payments.php"><span class="ico">₨</span>My Payments</a><a class="nav " href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav " href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">MEMBER / PAYMENTS</div>
                    <h1>My Payments</h1>
                    <p>Your payment transaction history.</p>
                </div>
            </div>
    <!-- Main page section. -->
            <section class="panel">
                <div class="panel-head">
                    <div><span class="panel-kicker">TRANSACTIONS</span>
                        <h2>Payment history</h2>
                    </div>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody><?php if ($rows && $rows->num_rows): while ($r = $rows->fetch_assoc()): ?><tr>
                                        <td><?php echo date('d M Y', strtotime($r['payment_date'])); ?></td>
                                        <td class="amount">₨ <?php echo number_format($r['amount']); ?></td>
                                        <td><?php echo ucfirst($r['payment_method']); ?></td>
                                        <td><?php echo htmlspecialchars($r['reference_no'] ?: '—'); ?></td>
                                        <td><?php echo ucfirst($r['status']); ?></td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="5" class="empty">No payment records.</td>
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
