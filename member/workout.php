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
$rows = $conn->query("SELECT mw.*,w.name,w.goal,w.description FROM member_workouts mw JOIN workout_plans w ON w.id=mw.workout_plan_id WHERE mw.member_id=$mid ORDER BY mw.id DESC"); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FitTrack — My Workout</title>
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
            <div class="nav-label">MAIN</div><a class="nav " href="dashboard.php"><span class="ico">⌂</span>Dashboard</a><a class="nav " href="membership.php"><span class="ico">▱</span>My Membership</a><a class="nav " href="attendance.php"><span class="ico">◷</span>My Attendance</a><a class="nav active" href="workout.php"><span class="ico">▣</span>My Workout</a><a class="nav " href="diet.php"><span class="ico">◌</span>My Diet</a><a class="nav " href="payments.php"><span class="ico">₨</span>My Payments</a><a class="nav " href="classes.php"><span class="ico">▤</span>Classes</a><a class="nav " href="progress.php"><span class="ico">↗</span>Progress</a>
            <div class="nav-label">ACCOUNT</div><a class="nav" href="profile.php"><span class="ico">♙</span>Profile</a><a class="nav" href="../change-password.php"><span class="ico">⌁</span>Change Password</a><a class="nav" href="../index.php"><span class="ico">↗</span>Website</a><a class="nav" href="../logout.php"><span class="ico">⇥</span>Logout</a>
        </aside>
    <!-- Main page content. -->
        <main class="main dashboard-main">
            <div class="top dashboard-top">
                <div>
                    <div class="eyebrow">MEMBER / WORKOUT</div>
                    <h1>My Workout Plan</h1>
                    <p>Follow the workout plans assigned by your trainer.</p>
                </div>
            </div><?php if ($rows && $rows->num_rows): while ($r = $rows->fetch_assoc()): ?><section class="panel" style="margin-bottom:13px">
                        <div class="panel-head">
                            <div><span class="panel-kicker">ASSIGNED <?php echo date('d M Y', strtotime($r['assigned_date'])); ?></span>
                                <h2><?php echo htmlspecialchars($r['name']); ?></h2>
                                <p style="color:#6f817f;font-size:9px;margin-top:6px"><?php echo htmlspecialchars($r['goal'] ?: 'Fitness goal'); ?></p>
                            </div><span class="badge"><?php echo ucfirst($r['status']); ?></span>
                        </div>
<!-- // Run the database query. -->
                        <p style="color:#849390;font-size:10px;line-height:1.8"><?php echo nl2br(htmlspecialchars($r['description'] ?: 'No plan description.')); ?></p><?php $ex = $conn->query("SELECT * FROM workout_exercises WHERE workout_plan_id=" . (int)$r['workout_plan_id'] . " ORDER BY id");
                                                                                                                                                                    if ($ex && $ex->num_rows): ?><div class="table-wrap">
    <!-- Table used to display records. -->
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Exercise</th>
                                            <th>Sets</th>
                                            <th>Reps</th>
                                            <th>Rest</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody><?php while ($e = $ex->fetch_assoc()): ?><tr>
                                                <td><strong><?php echo htmlspecialchars($e['exercise_name']); ?></strong></td>
                                                <td><?php echo (int)$e['sets_count']; ?></td>
                                                <td><?php echo htmlspecialchars($e['reps']); ?></td>
                                                <td><?php echo (int)$e['rest_seconds']; ?> sec</td>
                                                <td><?php echo htmlspecialchars($e['notes'] ?? ''); ?></td>
                                            </tr><?php endwhile; ?></tbody>
                                </table>
                            </div><?php endif; ?>
                    </section><?php endwhile;
                        else: ?><section class="panel">
                    <div class="empty">No workout plan assigned yet.</div>
                </section><?php endif; ?>
        </main>
    </div>
    <!-- JavaScript used on this page. -->
    <script src="../assets/js/admin.js"></script>

<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>
