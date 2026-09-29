<?php
// Load the file needed by this page
require_once __DIR__ . '/../config/auth.php';
// Load the file needed by this page
require_once __DIR__ . '/../config/db.php';
// Allow only the correct user role
require_role('member');
// Get the logged-in user ID
$uid = (int)$_SESSION['user_id'];
// Get data from the database
$m = $conn->query("SELECT m.*,t.id trainer_id,tu.full_name trainer_name FROM members m LEFT JOIN trainers t ON t.id=m.trainer_id LEFT JOIN users tu ON tu.id=t.user_id WHERE m.user_id=$uid LIMIT 1")->fetch_assoc();
$mid = (int)($m['id'] ?? 0);
// Get data from the database
$mem = $conn->query("SELECT ms.*,p.name plan_name,p.price FROM memberships ms JOIN membership_plans p ON p.id=ms.plan_id WHERE ms.member_id=$mid ORDER BY ms.id DESC LIMIT 1")->fetch_assoc();
// Run the database query.
$att = (int)(($conn->query("SELECT COUNT(*) v FROM attendance WHERE member_id=$mid")->fetch_assoc()['v'] ?? 0));
// Run the database query.
$monthAtt = (int)(($conn->query("SELECT COUNT(*) v FROM attendance WHERE member_id=$mid AND MONTH(check_in)=MONTH(CURDATE()) AND YEAR(check_in)=YEAR(CURDATE())")->fetch_assoc()['v'] ?? 0));
// Get data from the database
$wp = $conn->query("SELECT mw.*,w.name,w.goal FROM member_workouts mw JOIN workout_plans w ON w.id=mw.workout_plan_id WHERE mw.member_id=$mid ORDER BY mw.id DESC LIMIT 1")->fetch_assoc();
// Get data from the database
$diet = $conn->query("SELECT md.*,d.name,d.calories,d.goal FROM member_diets md JOIN diet_plans d ON d.id=md.diet_plan_id WHERE md.member_id=$mid ORDER BY md.id DESC LIMIT 1")->fetch_assoc();
// Get data from the database
$latestProgress = $conn->query("SELECT * FROM progress_records WHERE member_id=$mid ORDER BY record_date DESC,id DESC LIMIT 2");
$progressRows = [];
if ($latestProgress) while ($r = $latestProgress->fetch_assoc()) $progressRows[] = $r;
// Get data from the database
$upcoming = $conn->query("SELECT c.name,c.class_date,c.start_time,c.end_time,cb.status booking_status FROM classes c LEFT JOIN class_bookings cb ON cb.class_id=c.id AND cb.member_id=$mid WHERE c.class_date>=CURDATE() AND c.status='scheduled' ORDER BY c.class_date,c.start_time LIMIT 3");
$daysLeft = null;
if ($mem && $mem['status'] === 'active') $daysLeft = max(0, (int)floor((strtotime($mem['end_date']) - strtotime(date('Y-m-d'))) / 86400));
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — Member Dashboard</title>
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
            <div class="nav-label">MAIN</div><a class="nav active" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav" href="membership.php"><span class="ico">▱</span>My Membership</a><a class="nav" href="attendance.php"><span class="ico">◷</span>My Attendance</a><a class="nav" href="workout.php"><span class="ico">▣</span>My Workout</a><a class="nav" href="diet.php"><span class="ico">◌</span>My Diet</a><a class="nav" href="payments.php"><span class="ico">₨</span>My Payments</a><a class="nav" href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav" href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">FITTRACK / MEMBER</div>
                    <h1>Welcome, <?php echo htmlspecialchars(explode(' ', trim($_SESSION['full_name']))[0]); ?>.</h1>
                    <p>Your fitness journey, organized in one place.</p>
                </div>
                <div class="dashboard-user-actions">
                    <div class="user-pill"><span class="online-dot"></span><?php echo htmlspecialchars($_SESSION['full_name']); ?><b>Member</b></div><a href="../logout.php" class="logout-top">Logout <span>⇥</span></a>
                </div>
            </div>
            <div class="dashboard-stats">
                <div class="stat stat-highlight">
                    <div><small>Membership</small><strong><?php echo htmlspecialchars($mem['plan_name'] ?? 'None'); ?></strong><span class="stat-sub"><?php echo $mem ? ($daysLeft === 0 ? 'Expires today' : $daysLeft . ' days remaining') : 'No active plan'; ?></span></div><i>▱</i>
                </div>
                <div class="stat">
                    <div><small>Gym Visits</small><strong><?php echo $att; ?></strong><span class="stat-sub"><?php echo $monthAtt; ?> visits this month</span></div><i>◷</i>
                </div>
                <div class="stat">
                    <div><small>Workout</small><strong><?php echo htmlspecialchars($wp['name'] ?? '—'); ?></strong><span class="stat-sub"><?php echo htmlspecialchars($wp['status'] ?? 'Not assigned'); ?></span></div><i>▣</i>
                </div>
                <div class="stat">
                    <div><small>Trainer</small><strong><?php echo htmlspecialchars($m['trainer_name'] ?? 'Not assigned'); ?></strong><span class="stat-sub">Your assigned trainer</span></div><i>✦</i>
                </div>
            </div>
            <div class="dashboard-grid two-col">
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">MY WORKOUT</span>
                            <h2>Current plan</h2>
                        </div><a class="panel-link" href="workout.php">Open →</a>
                    </div><?php if ($wp): ?><div class="status-list">
                            <div><span class="status-dot teal"></span>
                                <div><strong><?php echo htmlspecialchars($wp['name']); ?></strong><small><?php echo htmlspecialchars($wp['goal'] ?: 'Fitness goal'); ?></small></div><b><?php echo ucfirst($wp['status']); ?></b>
                            </div>
                        </div><?php else: ?><div class="empty">No workout plan assigned.</div><?php endif; ?>
                </section>
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">MY DIET</span>
                            <h2>Nutrition plan</h2>
                        </div><a class="panel-link" href="diet.php">Open →</a>
                    </div><?php if ($diet): ?><div class="status-list">
                            <div><span class="status-dot green"></span>
                                <div><strong><?php echo htmlspecialchars($diet['name']); ?></strong><small><?php echo htmlspecialchars($diet['goal'] ?: 'Nutrition goal'); ?></small></div><b><?php echo $diet['calories'] ? (int)$diet['calories'] . ' kcal' : '—'; ?></b>
                            </div>
                        </div><?php else: ?><div class="empty">No diet plan assigned.</div><?php endif; ?>
                </section>
            </div>
            <div class="dashboard-grid three-col">
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">PROGRESS</span>
                            <h2>Latest measurements</h2>
                        </div><a class="panel-link" href="progress.php">View →</a>
                    </div><?php if (count($progressRows)): $r = $progressRows[0]; ?><div class="status-list">
                            <div><span class="status-dot teal"></span>
                                <div><strong><?php echo htmlspecialchars($r['weight']); ?> kg</strong><small><?php echo date('d M Y', strtotime($r['record_date'])); ?> · Body measurements</small></div><b><?php echo $r['body_fat'] !== null ? htmlspecialchars($r['body_fat']) . '%' : '—'; ?></b>
                            </div><?php if (isset($progressRows[1])): $prev = $progressRows[1];
                                        $diff = (float)$r['weight'] - (float)$prev['weight']; ?><div><span class="status-dot gray"></span>
                                    <div><strong>Weight change</strong><small>Compared with previous record</small></div><b><?php echo ($diff > 0 ? '+' : '') . number_format($diff, 1); ?> kg</b>
                                </div><?php endif; ?>
                        </div><?php else: ?><div class="empty">No progress records yet.</div><?php endif; ?>
                </section>
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">UPCOMING</span>
                            <h2>My classes</h2>
                        </div><a class="panel-link" href="classes.php">Browse →</a>
                    </div>
                    <div class="member-list"><?php if ($upcoming && $upcoming->num_rows): while ($r = $upcoming->fetch_assoc()): ?><div class="member-row"><span class="member-avatar">▤</span>
                                    <div><strong><?php echo htmlspecialchars($r['name']); ?></strong><small><?php echo date('d M Y', strtotime($r['class_date'])); ?> · <?php echo date('h:i A', strtotime($r['start_time'])); ?></small></div><span class="badge"><?php echo $r['booking_status'] ? ucfirst($r['booking_status']) : 'Open'; ?></span>
                                </div><?php endwhile;
                                                else: ?><div class="empty">No upcoming classes.</div><?php endif; ?></div>
                </section>
    <!-- Main page section. -->
                <section class="panel quick-panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">QUICK ACCESS</span>
                            <h2>My fitness</h2>
                        </div>
                    </div>
                    <div class="quick-actions"><a href="membership.php">Membership details <span>→</span></a><a href="attendance.php">Attendance history <span>→</span></a><a href="payments.php">Payment history <span>→</span></a><a href="progress.php">Progress records <span>→</span></a></div>
                </section>
            </div>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
