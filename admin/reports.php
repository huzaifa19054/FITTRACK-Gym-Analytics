<?php
// Load the file needed by this page
require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('admin');
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
// Check if an action was requested
if (isset($_GET['export']) && in_array($_GET['export'], ['payments', 'members'], true)) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=fittrack-' . $_GET['export'] . '-' . date('Ymd-His') . '.csv');
    $out = fopen('php://output', 'w');
    if ($_GET['export'] === 'payments') {
        fputcsv($out, ['Payment Date', 'Member', 'Amount', 'Method', 'Status', 'Reference']);
        // Get data from the database
        $st = $conn->prepare("SELECT p.payment_date,u.full_name,p.amount,p.payment_method,p.status,p.reference_no FROM payments p JOIN members m ON m.id=p.member_id JOIN users u ON u.id=m.user_id WHERE p.payment_date BETWEEN ? AND ? ORDER BY p.payment_date DESC,p.id DESC");
        $st->bind_param('ss', $from, $to);
        $st->execute();
        $rs = $st->get_result();
        while ($r = $rs->fetch_assoc()) fputcsv($out, [$r['payment_date'], $r['full_name'], $r['amount'], $r['payment_method'], $r['status'], $r['reference_no']]);
        $st->close();
    } else {
        fputcsv($out, ['Member', 'Email', 'Status', 'Joined Date', 'Visits', 'Paid Amount']);
        // Get data from the database
        $st = $conn->prepare("SELECT u.full_name,u.email,m.status,m.joined_date,(SELECT COUNT(*) FROM attendance a WHERE a.member_id=m.id AND DATE(a.check_in) BETWEEN ? AND ?) visits,(SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE p.member_id=m.id AND p.status='paid' AND p.payment_date BETWEEN ? AND ?) paid FROM members m JOIN users u ON u.id=m.user_id ORDER BY u.full_name");
        $st->bind_param('ssss', $from, $to, $from, $to);
        $st->execute();
        $rs = $st->get_result();
        while ($r = $rs->fetch_assoc()) fputcsv($out, [$r['full_name'], $r['email'], $r['status'], $r['joined_date'], $r['visits'], $r['paid']]);
        $st->close();
    }
    fclose($out);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
function scalar($conn, $sql, $types = '', $vals = [])
{
    // Get data from the database
    $s = $conn->prepare($sql);
    if ($types) $s->bind_param($types, ...$vals);
    $s->execute();
    $r = $s->get_result()->fetch_assoc();
    $s->close();
    return $r;
}
// Run the database query.
$rev = scalar($conn, "SELECT COALESCE(SUM(amount),0) v FROM payments WHERE status='paid' AND payment_date BETWEEN ? AND ?", 'ss', [$from, $to])['v'];
// Run the database query.
$paid = scalar($conn, "SELECT COUNT(*) v FROM payments WHERE status='paid' AND payment_date BETWEEN ? AND ?", 'ss', [$from, $to])['v'];
// Run the database query.
$att = scalar($conn, "SELECT COUNT(*) v FROM attendance WHERE DATE(check_in) BETWEEN ? AND ?", 'ss', [$from, $to])['v'];
// Run the database query.
$newMembers = scalar($conn, "SELECT COUNT(*) v FROM members WHERE joined_date BETWEEN ? AND ?", 'ss', [$from, $to])['v'];
// Get data from the database
$members = $conn->query("SELECT m.id,u.full_name,m.status,m.joined_date,(SELECT COUNT(*) FROM attendance a WHERE a.member_id=m.id AND DATE(a.check_in) BETWEEN '$from' AND '$to') visits,(SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE p.member_id=m.id AND p.status='paid' AND p.payment_date BETWEEN '$from' AND '$to') paid FROM members m JOIN users u ON u.id=m.user_id ORDER BY visits DESC, u.full_name LIMIT 50");
// Get data from the database
$plans = $conn->query("SELECT mp.name,COUNT(ms.id) uses,COALESCE(SUM(CASE WHEN ms.status='active' THEN 1 ELSE 0 END),0) active_count FROM membership_plans mp LEFT JOIN memberships ms ON ms.plan_id=mp.id GROUP BY mp.id ORDER BY uses DESC");
// Get data from the database
$recentPayments = $conn->query("SELECT p.payment_date,p.amount,p.payment_method,p.status,u.full_name FROM payments p JOIN members m ON m.id=p.member_id JOIN users u ON u.id=m.user_id WHERE p.payment_date BETWEEN '$from' AND '$to' ORDER BY p.payment_date DESC,p.id DESC LIMIT 20");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reports · FitTrack</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .report-kicker {
            font-size: 9px;
            color: #607270;
            text-transform: uppercase;
            letter-spacing: 1px
        }

        .print-hide {}

        @media print {

            .sidebar,
            .top .user-pill,
            .print-hide,
            .btn {
                display: none !important
            }

            .main {
                margin: 0;
                width: 100%;
                padding: 15px
            }

            .section,
            .stat {
                break-inside: avoid
            }

            body {
                background: #fff;
                color: #111
            }

            .section,
            .stat {
                border: 1px solid #ddd;
                background: #fff
            }

            th,
            td {
                color: #222;
                border-color: #ddd
            }
        }
    </style>
</head>

<body>
    <div class="app">
    <!-- Dashboard sidebar navigation. -->
        <aside class="sidebar">
            <div class="logo">Fit<span>Track</span></div>
            <div class="nav-label">MAIN</div><a class="nav" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav" href="members.php"><span class="ico">◎</span>Members</a><a class="nav" href="trainers.php"><span class="ico">✦</span>Trainers</a><a class="nav" href="membership-plans.php"><span class="ico">◇</span>Membership Plans</a><a class="nav" href="payments.php"><span class="ico">₨</span>Payments</a><a class="nav" href="memberships.php"><span class="ico">▱</span>Memberships</a>
            <div class="nav-label">MANAGEMENT</div><a class="nav" href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav" href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav" href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav" href="progress.php"><span class="ico">↗</span>Progress</a><a class="nav active" href="reports.php"><span class="ico">▥</span>Reports</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="users.php"><span class="ico">♙</span>User Management</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main">
            <div class="top">
                <div>
                    <h1>Reports</h1>
                    <p>Review revenue, attendance and member activity for a selected period.</p>
                </div>
                <div class="user-pill"><?php echo htmlspecialchars($_SESSION['full_name']); ?> · Administrator</div>
            </div>
            <div class="section print-hide">
                <div class="section-head">
                    <h2>Report Period</h2>
                    <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn secondary" type="button" onclick="window.print()">Print Report</button><a class="btn" href="?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=payments">Export Payments CSV</a><a class="btn" href="?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=members">Export Members CSV</a></div>
                </div>
    <!-- Form used to collect or submit data. -->
                <form method="get" class="form-grid">
                    <?php echo csrf_field(); ?><label>From<input class="form-control" type="date" name="from" value="<?php echo htmlspecialchars($from); ?>"></label><label>To<input class="form-control" type="date" name="to" value="<?php echo htmlspecialchars($to); ?>"></label>
                    <div style="display:flex;align-items:end"><button class="btn" type="submit">Apply Filters</button></div>
                </form>
            </div>
            <div class="grid">
                <div class="stat"><small>Paid Revenue</small><strong>PKR <?php echo number_format($rev); ?></strong><i>₨</i></div>
                <div class="stat"><small>Paid Transactions</small><strong><?php echo $paid; ?></strong><i>✓</i></div>
                <div class="stat"><small>Attendance Visits</small><strong><?php echo $att; ?></strong><i>◷</i></div>
                <div class="stat"><small>New Members</small><strong><?php echo $newMembers; ?></strong><i>◎</i></div>
            </div>
            <div class="section">
                <div class="section-head">
                    <div>
                        <h2>Member Activity</h2>
                        <p style="font-size:10px;color:#637572;margin:5px 0 0"><?php echo date('d M Y', strtotime($from)); ?> — <?php echo date('d M Y', strtotime($to)); ?></p>
                    </div>
                </div>
                <div class="table-wrap">
    <!-- Table used to display records. -->
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Status</th>
                                <th>Visits</th>
                                <th>Paid Amount</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody><?php if ($members && $members->num_rows): while ($m = $members->fetch_assoc()): ?><tr>
                                        <td><strong><?php echo htmlspecialchars($m['full_name']); ?></strong></td>
                                        <td><span class="badge"><?php echo ucfirst($m['status']); ?></span></td>
                                        <td><?php echo (int)$m['visits']; ?></td>
                                        <td>PKR <?php echo number_format($m['paid']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($m['joined_date'])); ?></td>
                                    </tr><?php endwhile;
                                    else: ?><tr>
                                    <td colspan="5">
                                        <div class="empty">No member activity in this period.</div>
                                    </td>
                                </tr><?php endif; ?></tbody>
                    </table>
                </div>
            </div>
            <div class="detail-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
                <div class="section">
                    <div class="section-head">
                        <h2>Membership Plan Usage</h2>
                    </div>
                    <div class="table-wrap">
    <!-- Table used to display records. -->
                        <table>
                            <thead>
                                <tr>
                                    <th>Plan</th>
                                    <th>Total</th>
                                    <th>Active</th>
                                </tr>
                            </thead>
                            <tbody><?php if ($plans): while ($p = $plans->fetch_assoc()): ?><tr>
                                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                                            <td><?php echo (int)$p['uses']; ?></td>
                                            <td><?php echo (int)$p['active_count']; ?></td>
                                        </tr><?php endwhile;
                                        endif; ?></tbody>
                        </table>
                    </div>
                </div>
                <div class="section">
                    <div class="section-head">
                        <h2>Recent Payments</h2>
                    </div>
                    <div class="table-wrap">
    <!-- Table used to display records. -->
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Member</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody><?php if ($recentPayments && $recentPayments->num_rows): while ($p = $recentPayments->fetch_assoc()): ?><tr>
                                            <td><?php echo date('d M', strtotime($p['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                                            <td>PKR <?php echo number_format($p['amount']); ?></td>
                                        </tr><?php endwhile;
                                        else: ?><tr>
                                        <td colspan="3">
                                            <div class="empty">No payments in this period.</div>
                                        </td>
                                    </tr><?php endif; ?></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
