<?php require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_role('trainer');
function s($c, $q)
{
    $r = $c->query($q);
    if (!$r) return 0;
    $x = $r->fetch_assoc();
    return $x['v'] ?? 0;
}
// Get the logged-in user ID
$uid = (int)$_SESSION['user_id'];
// Run the database query.
$tr = $conn->query("SELECT id,specialization,experience_years FROM trainers WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$tid = (int)($tr['id'] ?? 0);
// Run the database query.
$members = (int)s($conn, "SELECT COUNT(*) v FROM members WHERE trainer_id=$tid");
// Run the database query.
$workouts = (int)s($conn, "SELECT COUNT(*) v FROM workout_plans WHERE trainer_id=$tid");
// Run the database query.
$diets = (int)s($conn, "SELECT COUNT(*) v FROM diet_plans WHERE trainer_id=$tid");
// Run the database query.
$classes = (int)s($conn, "SELECT COUNT(*) v FROM classes WHERE trainer_id=$tid AND class_date>=CURDATE() AND status='scheduled'");
// Get data from the database
$recent = $conn->query("SELECT u.full_name,m.status,m.joined_date FROM members m JOIN users u ON u.id=m.user_id WHERE m.trainer_id=$tid ORDER BY m.id DESC LIMIT 6");
// Get data from the database
$upcoming = $conn->query("SELECT name,class_date,start_time,end_time,capacity FROM classes WHERE trainer_id=$tid AND class_date>=CURDATE() AND status='scheduled' ORDER BY class_date,start_time LIMIT 5");
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — Trainer Dashboard</title>
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
            <div class="nav-label">MAIN</div><a class="nav active" href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav " href="members.php"><span class="ico">◎</span>My Members</a><a class="nav " href="workout-plans.php"><span class="ico">▣</span>Workout Plans</a><a class="nav " href="diet-plans.php"><span class="ico">◌</span>Diet Plans</a><a class="nav " href="attendance.php"><span class="ico">◷</span>Attendance</a><a class="nav " href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav " href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">FITTRACK / TRAINER</div>
                    <h1>Welcome, <?php echo htmlspecialchars(explode(' ', trim($_SESSION['full_name']))[0]); ?>.</h1>
                    <p>Your training workspace at a glance.</p>
                </div>
                <div class="dashboard-user-actions">
                    <div class="user-pill"><span class="online-dot"></span><?php echo htmlspecialchars($_SESSION['full_name']); ?><b>Trainer</b></div><a href="../logout.php" class="logout-top">Logout <span>⇥</span></a>
                </div>
            </div>
            <div class="dashboard-stats">
                <div class="stat stat-highlight">
                    <div><small>My Members</small><strong><?php echo $members; ?></strong><span class="stat-sub">Assigned to you</span></div><i>◎</i>
                </div>
                <div class="stat">
                    <div><small>Workout Plans</small><strong><?php echo $workouts; ?></strong><span class="stat-sub">Created by you</span></div><i>▣</i>
                </div>
                <div class="stat">
                    <div><small>Diet Plans</small><strong><?php echo $diets; ?></strong><span class="stat-sub">Nutrition plans</span></div><i>◌</i>
                </div>
                <div class="stat">
                    <div><small>Upcoming Classes</small><strong><?php echo $classes; ?></strong><span class="stat-sub">Scheduled sessions</span></div><i>▤</i>
                </div>
            </div>
            <div class="dashboard-grid two-col">
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">MY MEMBERS</span>
                            <h2>Recently assigned</h2>
                        </div><a class="panel-link" href="members.php">View all →</a>
                    </div>
                    <div class="member-list"><?php if ($recent && $recent->num_rows): while ($r = $recent->fetch_assoc()): $p = preg_split('/\s+/', trim($r['full_name']));
                                                        $in = strtoupper(substr($p[0], 0, 1) . (count($p) > 1 ? substr($p[count($p) - 1], 0, 1) : '')); ?><div class="member-row"><span class="member-avatar"><?php echo $in; ?></span>
                                    <div><strong><?php echo htmlspecialchars($r['full_name']); ?></strong><small>Joined <?php echo date('d M Y', strtotime($r['joined_date'])); ?></small></div><span class="badge <?php echo $r['status'] === 'active' ? '' : 'muted'; ?>"><?php echo ucfirst($r['status']); ?></span>
                                </div><?php endwhile;
                                                else: ?><div class="empty">No members assigned yet.</div><?php endif; ?></div>
                </section>
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">SCHEDULE</span>
                            <h2>Upcoming classes</h2>
                        </div><a class="panel-link" href="classes.php">Manage →</a>
                    </div>
                    <div class="member-list"><?php if ($upcoming && $upcoming->num_rows): while ($r = $upcoming->fetch_assoc()): ?><div class="member-row"><span class="member-avatar">▤</span>
                                    <div><strong><?php echo htmlspecialchars($r['name']); ?></strong><small><?php echo date('d M Y', strtotime($r['class_date'])); ?> · <?php echo date('h:i A', strtotime($r['start_time'])); ?></small></div><span class="badge"><?php echo (int)$r['capacity']; ?> seats</span>
                                </div><?php endwhile;
                                                else: ?><div class="empty">No upcoming classes.</div><?php endif; ?></div>
                </section>
            </div>
            <div class="dashboard-grid three-col">
    <!-- Main page section. -->
                <section class="panel quick-panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">QUICK ACTIONS</span>
                            <h2>Training tools</h2>
                        </div>
                    </div>
<!-- // Run the database query. -->
                    <div class="quick-actions"><a href="workout-plans.php">Create workout plan <span>→</span></a><a href="diet-plans.php">Create diet plan <span>→</span></a><a href="attendance.php">Record attendance <span>→</span></a><a href="progress.php">Update progress <span>→</span></a></div>
                </section>
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">SPECIALIZATION</span>
                            <h2>Your profile</h2>
                        </div>
                    </div>
                    <div class="status-list">
                        <div><span class="status-dot teal"></span>
                            <div><strong>Specialization</strong><small>Training focus</small></div><b><?php echo htmlspecialchars($tr['specialization'] ?? 'General'); ?></b>
                        </div>
                        <div><span class="status-dot green"></span>
                            <div><strong>Experience</strong><small>Professional years</small></div><b><?php echo (int)($tr['experience_years'] ?? 0); ?>y</b>
                        </div>
                    </div>
                </section>
    <!-- Main page section. -->
                <section class="panel">
                    <div class="panel-head">
                        <div><span class="panel-kicker">ACCOUNT</span>
                            <h2>Stay organized</h2>
                        </div>
                    </div>
                    <p style="font-size:10px;line-height:1.8;color:#70817f">Use your portal to manage assigned members and keep workout, diet, attendance and progress records up to date.</p>
                </section>
            </div>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
