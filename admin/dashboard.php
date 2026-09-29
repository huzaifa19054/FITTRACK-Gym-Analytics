<?php

// Load authentication
require_once __DIR__ . '/../config/auth.php';

// Load database connection
require_once __DIR__ . '/../config/db.php';

// Allow only admin
require_role('admin');


// ==============================
// HELPER FUNCTION
// ==============================

function scalar($conn, $sql, $key = 'value')
{
    $r = $conn->query($sql);

    if (!$r) {
        return 0;
    }

    $row = $r->fetch_assoc();

    return $row[$key] ?? 0;
}


// ==============================
// DASHBOARD STATISTICS
// ==============================

$members = (int)scalar(
    $conn,
    "SELECT COUNT(*) value FROM members"
);

$activeMembers = (int)scalar(
    $conn,
    "SELECT COUNT(*) value FROM members WHERE status='active'"
);

$trainers = (int)scalar(
    $conn,
    "SELECT COUNT(*) value FROM trainers WHERE status='active'"
);

$plans = (int)scalar(
    $conn,
    "SELECT COUNT(*) value FROM membership_plans WHERE status='active'"
);

$activeMemberships = (int)scalar(
    $conn,
    "SELECT COUNT(*) value FROM memberships
     WHERE status='active'
     AND end_date>=CURDATE()"
);

$todayAttendance = (int)scalar(
    $conn,
    "SELECT COUNT(*) value FROM attendance
     WHERE DATE(check_in)=CURDATE()"
);

$revenue = (float)scalar(
    $conn,
    "SELECT COALESCE(SUM(amount),0) value
     FROM payments
     WHERE status='paid'"
);

$expiring = (int)scalar(
    $conn,
    "SELECT COUNT(*) value
     FROM memberships
     WHERE status='active'
     AND end_date BETWEEN CURDATE()
     AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)"
);

$pendingPayments = (int)scalar(
    $conn,
    "SELECT COUNT(*) value
     FROM payments
     WHERE status='pending'"
);

$newMessages = (int)scalar(
    $conn,
    "SELECT COUNT(*) value
     FROM contact_messages
     WHERE status='new'"
);


// ==============================
// MONTHLY DATA
// ==============================

$monthLabels = [];
$monthRevenue = [];
$monthMembers = [];
$monthAttendance = [];

for ($i = 5; $i >= 0; $i--) {

    $month = date(
        'Y-m',
        strtotime("first day of -$i month")
    );

    $monthLabels[] = date(
        'M',
        strtotime($month . '-01')
    );

    $monthRevenue[] = (float)scalar(
        $conn,
        "SELECT COALESCE(SUM(amount),0) value
         FROM payments
         WHERE status='paid'
         AND DATE_FORMAT(payment_date,'%Y-%m')='$month'"
    );

    $monthMembers[] = (int)scalar(
        $conn,
        "SELECT COUNT(*) value
         FROM members
         WHERE DATE_FORMAT(joined_date,'%Y-%m')='$month'"
    );

    $monthAttendance[] = (int)scalar(
        $conn,
        "SELECT COUNT(*) value
         FROM attendance
         WHERE DATE_FORMAT(check_in,'%Y-%m')='$month'"
    );
}


$maxRevenue = max(
    1,
    max($monthRevenue)
);

$maxMembers = max(
    1,
    max($monthMembers)
);

$maxAttendance = max(
    1,
    max($monthAttendance)
);


// ==============================
// RECENT DATA
// ==============================

$recentPayments = $conn->query(
    "SELECT
        p.payment_date,
        p.amount,
        p.payment_method,
        p.status,
        u.full_name
     FROM payments p
     JOIN members m
        ON m.id=p.member_id
     JOIN users u
        ON u.id=m.user_id
     ORDER BY p.id DESC
     LIMIT 5"
);


$recentMembers = $conn->query(
    "SELECT
        u.full_name,
        m.joined_date,
        m.status
     FROM members m
     JOIN users u
        ON u.id=m.user_id
     ORDER BY m.id DESC
     LIMIT 5"
);

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>FitTrack — Admin Dashboard</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

</head>


<body>

<div class="app">


    <!-- =========================================
         SIDEBAR
    ========================================= -->

    <aside class="sidebar">

        <div class="logo">
            Fit<span>Track</span>
        </div>

        <div class="nav-label">
            MAIN
        </div>

        <a
            class="nav active"
            href="dashboard.php"
        >
            <span class="ico">⌂</span>
            Dashboard
        </a>

        <a
            class="nav"
            href="members.php"
        >
            <span class="ico">◎</span>
            Members
        </a>

        <a
            class="nav"
            href="trainers.php"
        >
            <span class="ico">✦</span>
            Trainers
        </a>

        <a
            class="nav"
            href="membership-plans.php"
        >
            <span class="ico">◇</span>
            Membership Plans
        </a>

        <a
            class="nav"
            href="payments.php"
        >
            <span class="ico">₨</span>
            Payments
        </a>

        <a
            class="nav"
            href="memberships.php"
        >
            <span class="ico">▱</span>
            Memberships
        </a>


        <div class="nav-label">
            MANAGEMENT
        </div>

        <a
            class="nav"
            href="attendance.php"
        >
            <span class="ico">◷</span>
            Attendance
        </a>

        <a
            class="nav"
            href="workout-plans.php"
        >
            <span class="ico">▣</span>
            Workout Plans
        </a>

        <a
            class="nav"
            href="diet-plans.php"
        >
            <span class="ico">◌</span>
            Diet Plans
        </a>

        <a
            class="nav"
            href="classes.php"
        >
            <span class="ico">▤</span>
            Classes
        </a>

        <a
            class="nav"
            href="progress.php"
        >
            <span class="ico">↗</span>
            Progress
        </a>

        <a
            class="nav"
            href="reports.php"
        >
            <span class="ico">▥</span>
            Reports
        </a>


        <div class="nav-label">
            ACCOUNT
        </div>

        <a
            class="nav"
            href="users.php"
        >
            <span class="ico">♙</span>
            User Management
        </a>

        <a
            class="nav"
            href="../change-password.php"
        >
            <span class="ico">⌁</span>
            Change Password
        </a>

        <a
            class="nav"
            href="../index.php"
        >
            <span class="ico">↗</span>
            Website
        </a>

        <a
            class="nav"
            href="../logout.php"
        >
            <span class="ico">⇥</span>
            Logout
        </a>

    </aside>


    <!-- =========================================
         MAIN CONTENT
    ========================================= -->

    <main class="main dashboard-main">


        <!-- HEADER -->

        <div class="top dashboard-top">

            <div>

                <div class="eyebrow">
                    FITTRACK / ADMIN
                </div>

                <h1>
                    Good morning,
                    <?php
                    echo htmlspecialchars(
                        explode(
                            ' ',
                            trim($_SESSION['full_name'])
                        )[0]
                    );
                    ?>.
                </h1>

                <p>
                    Here is what's happening across your gym today.
                </p>

            </div>


            <div class="dashboard-user-actions">

                <div class="user-pill">

                    <span class="online-dot"></span>

                    <?php
                    echo htmlspecialchars(
                        $_SESSION['full_name']
                    );
                    ?>

                    <b>
                        Administrator
                    </b>

                </div>

                <a
                    href="../logout.php"
                    class="logout-top"
                >
                    Logout
                    <span>⇥</span>
                </a>

            </div>

        </div>


        <!-- =========================================
             ALERT CARDS
        ========================================= -->

        <div
            class="dashboard-alerts"
            style="
                display:grid;
                grid-template-columns:repeat(3,1fr);
                gap:12px;
                margin-bottom:18px
            "
        >

            <a
                href="memberships.php"
                class="section"
                style="
                    margin:0;
                    padding:15px;
                    display:flex;
                    justify-content:space-between;
                    align-items:center
                "
            >

                <div>

                    <small style="color:#70817f">
                        EXPIRING SOON
                    </small>

                    <strong
                        style="
                            display:block;
                            margin-top:6px;
                            font-size:18px
                        "
                    >
                        <?php echo $expiring; ?>
                    </strong>

                    <span
                        style="
                            font-size:9px;
                            color:#71817f
                        "
                    >
                        Memberships in next 7 days
                    </span>

                </div>

                <i
                    style="
                        color:#16c7a4;
                        font-style:normal
                    "
                >
                    →
                </i>

            </a>


            <a
                href="payments.php?status=pending"
                class="section"
                style="
                    margin:0;
                    padding:15px;
                    display:flex;
                    justify-content:space-between;
                    align-items:center
                "
            >

                <div>

                    <small style="color:#70817f">
                        PENDING PAYMENTS
                    </small>

                    <strong
                        style="
                            display:block;
                            margin-top:6px;
                            font-size:18px
                        "
                    >
                        <?php echo $pendingPayments; ?>
                    </strong>

                    <span
                        style="
                            font-size:9px;
                            color:#71817f
                        "
                    >
                        Need review
                    </span>

                </div>

                <i
                    style="
                        color:#16c7a4;
                        font-style:normal
                    "
                >
                    →
                </i>

            </a>


            <a
                href="contact-messages.php"
                class="section"
                style="
                    margin:0;
                    padding:15px;
                    display:flex;
                    justify-content:space-between;
                    align-items:center
                "
            >

                <div>

                    <small style="color:#70817f">
                        NEW MESSAGES
                    </small>

                    <strong
                        style="
                            display:block;
                            margin-top:6px;
                            font-size:18px
                        "
                    >
                        <?php echo $newMessages; ?>
                    </strong>

                    <span
                        style="
                            font-size:9px;
                            color:#71817f
                        "
                    >
                        Website enquiries
                    </span>

                </div>

                <i
                    style="
                        color:#16c7a4;
                        font-style:normal
                    "
                >
                    →
                </i>

            </a>

        </div>


        <!-- =========================================
             STAT CARDS
        ========================================= -->

        <div class="dashboard-stats">

            <div class="stat stat-highlight">

                <div>

                    <small>
                        Total Members
                    </small>

                    <strong>
                        <?php echo number_format($members); ?>
                    </strong>

                    <span class="stat-sub">
                        All registered members
                    </span>

                </div>

                <i>◎</i>

            </div>


            <div class="stat">

                <div>

                    <small>
                        Active Members
                    </small>

                    <strong>
                        <?php echo number_format($activeMembers); ?>
                    </strong>

                    <span class="stat-sub">
                        Currently active
                    </span>

                </div>

                <i>◉</i>

            </div>


            <div class="stat">

                <div>

                    <small>
                        Active Trainers
                    </small>

                    <strong>
                        <?php echo number_format($trainers); ?>
                    </strong>

                    <span class="stat-sub">
                        Available trainers
                    </span>

                </div>

                <i>✦</i>

            </div>


            <div class="stat">

                <div>

                    <small>
                        Paid Revenue
                    </small>

                    <strong>
                        ₨ <?php echo number_format($revenue); ?>
                    </strong>

                    <span class="stat-sub">
                        All paid transactions
                    </span>

                </div>

                <i>₨</i>

            </div>

        </div>


        <!-- =========================================
             CHARTS
        ========================================= -->

        <div class="dashboard-grid two-col">


            <!-- REVENUE -->

            <section class="panel chart-panel">

                <div class="panel-head">

                    <div>

                        <span class="panel-kicker">
                            FINANCIAL OVERVIEW
                        </span>

                        <h2>
                            Revenue performance
                        </h2>

                    </div>

                    <span class="panel-badge">
                        Last 6 months
                    </span>

                </div>


                <div class="bar-chart">

                    <?php
                    foreach (
                        $monthRevenue
                        as $i => $value
                    ):

                        $height = max(
                            5,
                            ($value / $maxRevenue) * 100
                        );
                    ?>

                        <div class="bar-col">

                            <div class="bar-value">
                                ₨
                                <?php
                                echo number_format(
                                    $value / 1000,
                                    1
                                );
                                ?>k
                            </div>

                            <div class="bar-track">

                                <span
                                    style="
                                        height:<?php echo $height; ?>%
                                    "
                                ></span>

                            </div>

                            <small>
                                <?php echo $monthLabels[$i]; ?>
                            </small>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>


            <!-- MEMBER GROWTH -->

            <section class="panel chart-panel">

                <div class="panel-head">

                    <div>

                        <span class="panel-kicker">
                            MEMBER ACTIVITY
                        </span>

                        <h2>
                            New member growth
                        </h2>

                    </div>

                    <span class="panel-badge">
                        Monthly
                    </span>

                </div>


                <div class="line-chart-wrap">

                    <svg
                        class="line-chart"
                        viewBox="0 0 600 220"
                        preserveAspectRatio="none"
                        aria-label="Member growth chart"
                    >

                        <line
                            x1="25"
                            y1="190"
                            x2="580"
                            y2="190"
                            class="chart-grid-line"
                        />

                        <line
                            x1="25"
                            y1="135"
                            x2="580"
                            y2="135"
                            class="chart-grid-line"
                        />

                        <line
                            x1="25"
                            y1="80"
                            x2="580"
                            y2="80"
                            class="chart-grid-line"
                        />

                        <line
                            x1="25"
                            y1="25"
                            x2="580"
                            y2="25"
                            class="chart-grid-line"
                        />


                        <?php

                        $points = [];

                        foreach (
                            $monthMembers
                            as $i => $v
                        ) {

                            $x =
                                25 +
                                (
                                    $i *
                                    (555 / 5)
                                );

                            $y =
                                190 -
                                (
                                    ($v / $maxMembers) *
                                    165
                                );

                            $points[] =
                                round($x, 1) .
                                ',' .
                                round($y, 1);
                        }

                        $poly = implode(
                            ' ',
                            $points
                        );

                        ?>


                        <polyline
                            points="<?php echo $poly; ?>"
                            class="chart-line"
                        />


                        <?php

                        foreach (
                            $monthMembers
                            as $i => $v
                        ):

                            $x =
                                25 +
                                (
                                    $i *
                                    (555 / 5)
                                );

                            $y =
                                190 -
                                (
                                    ($v / $maxMembers) *
                                    165
                                );

                        ?>

                            <circle
                                cx="<?php echo $x; ?>"
                                cy="<?php echo $y; ?>"
                                r="4"
                                class="chart-point"
                            />

                            <text
                                x="<?php echo $x; ?>"
                                y="212"
                                class="chart-label"
                                text-anchor="middle"
                            >
                                <?php echo $monthLabels[$i]; ?>
                            </text>

                        <?php endforeach; ?>

                    </svg>


                    <div class="chart-total">

                        <strong>
                            <?php
                            echo array_sum(
                                $monthMembers
                            );
                            ?>
                        </strong>

                        <span>
                            new members in 6 months
                        </span>

                    </div>

                </div>

            </section>

        </div>


        <!-- =========================================
             MINI DASHBOARD
        ========================================= -->

        <div class="dashboard-grid three-col">


            <!-- ATTENDANCE -->

            <section class="panel mini-chart-panel">

                <div class="panel-head">

                    <div>

                        <span class="panel-kicker">
                            VISITS
                        </span>

                        <h2>
                            Attendance
                        </h2>

                    </div>

                    <strong class="panel-number">
                        <?php echo $todayAttendance; ?>
                        today
                    </strong>

                </div>


                <div class="mini-bars">

                    <?php
                    foreach (
                        $monthAttendance
                        as $i => $v
                    ):
                    ?>

                        <div>

                            <span
                                style="
                                    height:<?php
                                    echo max(
                                        6,
                                        ($v / $maxAttendance) *
                                        100
                                    );
                                    ?>%
                                "
                            ></span>

                            <small>
                                <?php
                                echo $monthLabels[$i];
                                ?>
                            </small>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>


            <!-- MEMBERSHIP STATUS -->

            <section class="panel">

                <div class="panel-head">

                    <div>

                        <span class="panel-kicker">
                            MEMBERSHIP
                        </span>

                        <h2>
                            Current status
                        </h2>

                    </div>

                </div>


                <div class="status-list">

                    <div>

                        <span class="status-dot green"></span>

                        <div>

                            <strong>
                                Active memberships
                            </strong>

                            <small>
                                Valid memberships
                            </small>

                        </div>

                        <b>
                            <?php echo $activeMemberships; ?>
                        </b>

                    </div>


                    <div>

                        <span class="status-dot teal"></span>

                        <div>

                            <strong>
                                Membership plans
                            </strong>

                            <small>
                                Active plans available
                            </small>

                        </div>

                        <b>
                            <?php echo $plans; ?>
                        </b>

                    </div>


                    <div>

                        <span class="status-dot gray"></span>

                        <div>

                            <strong>
                                Active trainers
                            </strong>

                            <small>
                                Trainer accounts
                            </small>

                        </div>

                        <b>
                            <?php echo $trainers; ?>
                        </b>

                    </div>

                </div>

            </section>


            <!-- QUICK ACTIONS -->

            <section class="panel quick-panel">

                <div class="panel-head">

                    <div>

                        <span class="panel-kicker">
                            QUICK ACTIONS
                        </span>

                        <h2>
                            Manage gym
                        </h2>

                    </div>

                </div>


                <div class="quick-actions">

                    <a href="members.php">
                        + Add member
                        <span>→</span>
                    </a>

                    <a href="payments.php">
                        + Record payment
                        <span>→</span>
                    </a>

                    <a href="attendance.php">
                        + Check attendance
                        <span>→</span>
                    </a>

                    <a href="workout-plans.php">
                        + Create workout
                        <span>→</span>
                    </a>

                </div>

            </section>

        </div>
<!-- =========================================
     TRAINER PERFORMANCE & WORKLOAD
========================================= -->

<div
    class="panel"
    style="margin-top:20px;"
>

    <div class="panel-head">

        <div>

            <span class="panel-kicker">
                TRAINER ANALYTICS
            </span>

            <h2>
                Trainer Performance & Workload
            </h2>

        </div>

        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;">

            <label for="trainer-performance-period" style="font-size:11px;color:#71817f;font-weight:600;">
                PERIOD
            </label>

            <select
                id="trainer-performance-period"
                style="padding:8px 12px;border:1px solid rgba(255,255,255,.12);border-radius:8px;background:rgba(255,255,255,.04);color:inherit;font-size:12px;outline:none;cursor:pointer;"
            >
                <option value="2">Last 2 Days</option>
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="all">All Time</option>
            </select>

            <span
                id="trainer-performance-updated"
                class="panel-badge"
            >
                Loading...
            </span>

        </div>

    </div>


    <!-- SUMMARY CARDS -->

    <div
        style="
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:12px;
            margin:18px 0;
        "
    >

        <div
            class="section"
            style="
                margin:0;
                padding:18px;
            "
        >

            <small style="color:#70817f;">
                ACTIVE TRAINERS
            </small>

            <strong
                id="trainer-count"
                style="
                    display:block;
                    margin-top:6px;
                    font-size:26px;
                "
            >
                —
            </strong>

            <span
                style="
                    font-size:11px;
                    color:#71817f;
                "
            >
                Trainers being monitored
            </span>

        </div>


        <div
            class="section"
            style="
                margin:0;
                padding:18px;
            "
        >

            <small style="color:#70817f;">
                HIGH WORKLOAD
            </small>

            <strong
                id="high-workload-count"
                style="
                    display:block;
                    margin-top:6px;
                    font-size:26px;
                "
            >
                —
            </strong>

            <span
                style="
                    font-size:11px;
                    color:#71817f;
                "
            >
                Trainers with 9+ active members
            </span>

        </div>


        <div
            class="section"
            style="
                margin:0;
                padding:18px;
            "
        >

            <small style="color:#70817f;">
                AVERAGE PERFORMANCE
            </small>

            <strong
                id="average-trainer-performance"
                style="
                    display:block;
                    margin-top:6px;
                    font-size:26px;
                "
            >
                —
            </strong>

            <span
                style="
                    font-size:11px;
                    color:#71817f;
                "
            >
                Overall trainer performance
            </span>

        </div>

    </div>


    <!-- TRAINER TABLE -->

    <div style="margin-top:22px;">

        <div class="panel-head">

            <div>

                <span class="panel-kicker">
                    PERFORMANCE OVERVIEW
                </span>

                <h2>
                    Trainer Metrics
                </h2>

            </div>

            <span
                id="trainer-performance-status"
                class="panel-badge"
            >
                Loading...
            </span>

        </div>


        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>Trainer</th>

                        <th>Members</th>

                        <th>Attendance</th>

                        <th>Workout</th>

                        <th>Progress</th>

                        <th>Rating</th>

                        <th>Performance</th>

                        <th>Workload</th>

                    </tr>

                </thead>


                <tbody id="trainer-performance-table">

                    <tr>

                        <td
                            colspan="8"
                            class="empty"
                        >
                            Loading trainer analytics...
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</div>


<!-- =========================================
     GYM HEALTH ANALYTICS
========================================= -->
<div class="panel" style="margin-top:20px;">

    <div class="panel-head">
        <div>
            <span class="panel-kicker">GYM ANALYTICS</span>
            <h2>Gym Health Overview</h2>
        </div>
        <span id="gym-health-updated" class="panel-badge">Loading...</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:18px 0;">

        <div class="section" style="margin:0;padding:18px;">
            <small style="color:#70817f;">ACTIVE MEMBERS</small>
            <strong id="health-active-members" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span id="health-active-members-sub" style="font-size:11px;color:#71817f;">—</span>
        </div>

        <div class="section" style="margin:0;padding:18px;">
            <small style="color:#70817f;">30-DAY ATTENDANCE</small>
            <strong id="health-attendance" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span id="health-attendance-sub" style="font-size:11px;color:#71817f;">—</span>
        </div>

        <div class="section" style="margin:0;padding:18px;">
            <small style="color:#70817f;">WORKOUT COMPLETION</small>
            <strong id="health-workout" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span id="health-workout-sub" style="font-size:11px;color:#71817f;">—</span>
        </div>

        <div class="section" style="margin:0;padding:18px;">
            <small style="color:#70817f;">CLASS ATTENDANCE</small>
            <strong id="health-class" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span id="health-class-sub" style="font-size:11px;color:#71817f;">Last 30 days</span>
        </div>

        <div class="section" style="margin:0;padding:18px;">
            <small style="color:#70817f;">ACTIVE MEMBERSHIPS</small>
            <strong id="health-memberships" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span id="health-memberships-sub" style="font-size:11px;color:#71817f;">—</span>
        </div>

        <div class="section" style="margin:0;padding:18px;">
            <small style="color:#70817f;">EXPIRING SOON</small>
            <strong id="health-expiring" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span style="font-size:11px;color:#71817f;">Within 7 days</span>
        </div>

        <div class="section" style="margin:0;padding:18px;">
            <small style="color:#70817f;">MONTHLY REVENUE</small>
            <strong id="health-revenue" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span id="health-revenue-sub" style="font-size:11px;color:#71817f;">—</span>
        </div>

        <div class="section" style="margin:0;padding:18px;">
            <small style="color:#70817f;">HIGH RETENTION RISK</small>
            <strong id="health-risk" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span id="health-risk-sub" style="font-size:11px;color:#71817f;">—</span>
        </div>

    </div>

    <div style="margin-top:20px;padding:16px 18px;border:1px solid rgba(255,255,255,.06);border-radius:12px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <div>
                <span class="panel-kicker">OPERATIONAL SNAPSHOT</span>
                <h3 style="margin:5px 0 0;">What needs attention</h3>
            </div>
            <span id="health-attention-count" class="panel-badge">Loading...</span>
        </div>
        <div id="gym-health-attention" style="margin-top:14px;display:grid;gap:8px;">
            <div class="empty">Loading gym health...</div>
        </div>
    </div>

</div>


<!-- =========================================
     MEMBER ENGAGEMENT ANALYTICS
========================================= -->
<div class="panel" style="margin-top:20px;">

    <div class="panel-head">
        <div>
            <span class="panel-kicker">MEMBER ANALYTICS</span>
            <h2>Member Engagement Analytics</h2>
        </div>
        <span id="member-engagement-updated" class="panel-badge">Loading...</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:18px 0;">

        <div class="section" style="margin:0;padding:18px;">
            <small style="color:#70817f;">AVERAGE ENGAGEMENT</small>
            <strong id="engagement-average" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span style="font-size:11px;color:#71817f;">Across active members</span>
        </div>

        <div class="section" style="margin:0;padding:18px;border-left:4px solid #12c7a0;">
            <small style="color:#70817f;">HIGH ENGAGEMENT</small>
            <strong id="engagement-high" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span style="font-size:11px;color:#71817f;">Score 75–100</span>
        </div>

        <div class="section" style="margin:0;padding:18px;border-left:4px solid #f0b429;">
            <small style="color:#70817f;">MEDIUM ENGAGEMENT</small>
            <strong id="engagement-medium" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span style="font-size:11px;color:#71817f;">Score 50–74.99</span>
        </div>

        <div class="section" style="margin:0;padding:18px;border-left:4px solid #e5484d;">
            <small style="color:#70817f;">LOW ENGAGEMENT</small>
            <strong id="engagement-low" style="display:block;margin-top:6px;font-size:26px;">—</strong>
            <span style="font-size:11px;color:#71817f;">Score below 50</span>
        </div>

    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:18px;">

        <div style="padding:16px 18px;border:1px solid rgba(255,255,255,.06);border-radius:12px;">
            <div class="panel-head" style="margin-bottom:12px;">
                <div>
                    <span class="panel-kicker">ENGAGEMENT SCORES</span>
                    <h3>Top Engagement Scores</h3>
                </div>
                <span class="panel-badge">Top 5</span>
            </div>

            <div id="top-engaged-members" style="display:grid;gap:10px;">
                <div class="empty">Loading engagement analytics...</div>
            </div>
        </div>

        <div style="padding:16px 18px;border:1px solid rgba(255,255,255,.06);border-radius:12px;">
            <div class="panel-head" style="margin-bottom:12px;">
                <div>
                    <span class="panel-kicker">ACTION REQUIRED</span>
                    <h3>Members Needing Attention</h3>
                </div>
                <span id="engagement-attention-count" class="panel-badge">Loading...</span>
            </div>

            <div id="engagement-attention-members" style="display:grid;gap:10px;">
                <div class="empty">Loading engagement analytics...</div>
            </div>
        </div>

    </div>

    <div style="margin-top:18px;">
        <div class="panel-head" style="margin-bottom:12px;">
            <div>
                <span class="panel-kicker">MEMBER BREAKDOWN</span>
                <h3>Engagement Details</h3>
            </div>
            <span id="engagement-table-count" class="panel-badge">Active members</span>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:12px;">
            <div style="flex:1;min-width:220px;">
                <input
                    id="engagement-member-search"
                    type="search"
                    placeholder="Search member name..."
                    autocomplete="off"
                    style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.10);background:#0a1719;color:#fff;outline:none;font-size:11px;"
                >
            </div>

            <select
                id="engagement-level-filter"
                style="padding:10px 12px;border:1px solid rgba(255,255,255,.10);border-radius:8px;background:#0a1719;color:#fff;font-size:11px;outline:none;cursor:pointer;"
            >
                <option value="ALL">All Members</option>
                <option value="HIGH">High Engagement</option>
                <option value="MEDIUM">Medium Engagement</option>
                <option value="LOW">Low Engagement</option>
                <option value="ATTENTION">Needs Attention</option>
            </select>

            <select
                id="engagement-page-size"
                style="padding:10px 12px;border:1px solid rgba(255,255,255,.10);border-radius:8px;background:#0a1719;color:#fff;font-size:11px;outline:none;cursor:pointer;"
            >
                <option value="10" selected>10 per page</option>
                <option value="20">20 per page</option>
                <option value="50">50 per page</option>
            </select>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>30-Day Visits</th>
                        <th>Workout</th>
                        <th>Progress</th>
                        <th>Last Visit</th>
                        <th>Score</th>
                        <th>Level</th>
                    </tr>
                </thead>
                <tbody id="member-engagement-table">
                    <tr>
                        <td colspan="7" class="empty">Loading engagement details...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="engagement-pagination" style="display:flex;justify-content:center;align-items:center;gap:6px;margin-top:14px;flex-wrap:wrap;"></div>
    </div>

        <!-- =========================================
             AI RETENTION RISK ANALYTICS
        ========================================= -->

        <div
            class="panel"
            style="margin-top:20px;"
        >


            <!-- ML HEADER -->

            <div class="panel-head">

                <div>

                    <span class="panel-kicker">
                        AI / MACHINE LEARNING
                    </span>

                    <h2>
                        Member Retention Risk
                    </h2>

                </div>

                <span class="panel-badge">
                    Logistic Regression
                </span>

            </div>


            <!-- =========================================
                 RISK SUMMARY
            ========================================= -->

            <div
                id="retention-summary"
                style="
                    display:grid;
                    grid-template-columns:repeat(3,1fr);
                    gap:12px;
                    margin:18px 0;
                "
            >


                <!-- HIGH -->

                <div
                    class="section"
                    style="
                        margin:0;
                        padding:18px;
                        border-left:4px solid #e5484d;
                    "
                >

                    <small style="color:#70817f;">
                        HIGH RISK
                    </small>

                    <strong
                        id="high-risk-count"
                        style="
                            display:block;
                            margin-top:6px;
                            font-size:26px;
                        "
                    >
                        —
                    </strong>

                    <span
                        style="
                            font-size:11px;
                            color:#71817f;
                        "
                    >
                        Members requiring attention
                    </span>

                </div>


                <!-- MEDIUM -->

                <div
                    class="section"
                    style="
                        margin:0;
                        padding:18px;
                        border-left:4px solid #f2b94b;
                    "
                >

                    <small style="color:#70817f;">
                        MEDIUM RISK
                    </small>

                    <strong
                        id="medium-risk-count"
                        style="
                            display:block;
                            margin-top:6px;
                            font-size:26px;
                        "
                    >
                        —
                    </strong>

                    <span
                        style="
                            font-size:11px;
                            color:#71817f;
                        "
                    >
                        Members to monitor
                    </span>

                </div>


                <!-- LOW -->

                <div
                    class="section"
                    style="
                        margin:0;
                        padding:18px;
                        border-left:4px solid #16c7a4;
                    "
                >

                    <small style="color:#70817f;">
                        LOW RISK
                    </small>

                    <strong
                        id="low-risk-count"
                        style="
                            display:block;
                            margin-top:6px;
                            font-size:26px;
                        "
                    >
                        —
                    </strong>

                    <span
                        style="
                            font-size:11px;
                            color:#71817f;
                        "
                    >
                        Lower predicted retention risk
                    </span>

                </div>

            </div>


            <!-- =========================================
                 RETENTION PREDICTIONS
            ========================================= -->

            <div style="margin-top:22px;">

                <div class="panel-head">

                    <div>

                        <span class="panel-kicker">
                            PREDICTIONS
                        </span>

                        <h2>
                            Highest Retention Risk
                        </h2>

                    </div>

                    <span
                        id="retention-updated"
                        class="panel-badge"
                    >
                        Loading...
                    </span>

                </div>


                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Member
                                </th>

                                <th>
                                    Risk
                                </th>

                                <th>
                                    Risk Level
                                </th>

                                <th>
                                    Risk Indicators
                                </th>

                                <th>
                                    Recommended Action
                                </th>

                            </tr>

                        </thead>


                        <tbody id="retention-risk-table">

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty"
                                >
                                    Loading retention predictions...
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- =========================================
     FITTRACK AI ASSISTANT
========================================= -->

<div class="panel" style="margin-top:20px;" id="fittrack-ai-assistant">

    <div class="panel-head">
        <div>
            <span class="panel-kicker">AI ASSISTANT</span>
            <h2>FITTRACK AI Assistant</h2>
            <p style="margin:5px 0 0;color:#71817f;font-size:11px;">
                Ask questions about your gym analytics, retention risk, engagement, trainers and operations.
            </p>
        </div>

        <span id="ai-assistant-status" class="panel-badge">Ready</span>
    </div>

    <div style="margin-top:16px;display:grid;grid-template-columns:1fr;gap:12px;">

        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <button type="button" class="ai-quick-prompt"
                data-question="Who is currently at high retention risk and why?"
                style="padding:8px 11px;border:1px solid rgba(255,255,255,.08);border-radius:8px;background:rgba(255,255,255,.035);color:#b9c6c4;font-size:10px;cursor:pointer;">
                High retention risk
            </button>

            <button type="button" class="ai-quick-prompt"
                data-question="Give me a concise summary of the current gym health and the main areas needing attention."
                style="padding:8px 11px;border:1px solid rgba(255,255,255,.08);border-radius:8px;background:rgba(255,255,255,.035);color:#b9c6c4;font-size:10px;cursor:pointer;">
                Gym health summary
            </button>

            <button type="button" class="ai-quick-prompt"
                data-question="Which members currently need engagement attention and what are the observed reasons?"
                style="padding:8px 11px;border:1px solid rgba(255,255,255,.08);border-radius:8px;background:rgba(255,255,255,.035);color:#b9c6c4;font-size:10px;cursor:pointer;">
                Member attention
            </button>

            <button type="button" class="ai-quick-prompt"
                data-question="Summarize the current trainer performance and workload situation."
                style="padding:8px 11px;border:1px solid rgba(255,255,255,.08);border-radius:8px;background:rgba(255,255,255,.035);color:#b9c6c4;font-size:10px;cursor:pointer;">
                Trainer overview
            </button>
        </div>

        <textarea id="ai-assistant-question" rows="3"
            placeholder="Ask FITTRACK AI something about your gym data..."
            style="width:100%;box-sizing:border-box;resize:vertical;padding:12px 13px;border-radius:9px;border:1px solid rgba(255,255,255,.10);background:#0a1719;color:#fff;outline:none;font-size:12px;line-height:1.5;"></textarea>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <span id="ai-assistant-hint" style="font-size:10px;color:#71817f;">
                AI answers are based on the FITTRACK data supplied to the assistant.
            </span>

            <button type="button" id="ai-assistant-ask"
                style="padding:10px 16px;border:0;border-radius:8px;background:#12c7a0;color:#061412;font-size:11px;font-weight:800;cursor:pointer;">
                Ask FITTRACK AI
            </button>
        </div>

        <div id="ai-assistant-response"
            style="display:none;padding:15px 16px;border:1px solid rgba(255,255,255,.07);border-radius:10px;background:rgba(255,255,255,.025);">
        </div>

    </div>
</div>


<!-- =========================================
             RECENT ACTIVITY
        ========================================= -->

        <div class="dashboard-grid two-col bottom-grid">


            <!-- RECENT PAYMENTS -->

            <section class="panel">

                <div class="panel-head">

                    <div>

                        <span class="panel-kicker">
                            RECENT ACTIVITY
                        </span>

                        <h2>
                            Latest payments
                        </h2>

                    </div>

                    <a
                        class="panel-link"
                        href="payments.php"
                    >
                        View all →
                    </a>

                </div>


                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Member
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Method
                                </th>

                                <th>
                                    Amount
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php

                            if (
                                $recentPayments &&
                                $recentPayments->num_rows
                            ):

                                while (
                                    $row =
                                    $recentPayments->fetch_assoc()
                                ):

                            ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['full_name']
                                            );
                                            ?>
                                        </strong>

                                    </td>

                                    <td>

                                        <?php
                                        echo date(
                                            'd M Y',
                                            strtotime(
                                                $row['payment_date']
                                            )
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <span class="method-badge">

                                            <?php
                                            echo htmlspecialchars(
                                                ucfirst(
                                                    $row['payment_method']
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>

                                    <td class="amount">

                                        ₨
                                        <?php
                                        echo number_format(
                                            $row['amount']
                                        );
                                        ?>

                                    </td>

                                </tr>

                            <?php

                                endwhile;

                            else:

                            ?>

                                <tr>

                                    <td
                                        colspan="4"
                                        class="empty"
                                    >
                                        No payments recorded yet.
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>


            <!-- RECENT MEMBERS -->

            <section class="panel">

                <div class="panel-head">

                    <div>

                        <span class="panel-kicker">
                            NEW MEMBERS
                        </span>

                        <h2>
                            Recently joined
                        </h2>

                    </div>

                    <a
                        class="panel-link"
                        href="members.php"
                    >
                        View all →
                    </a>

                </div>


                <div class="member-list">

                    <?php

                    if (
                        $recentMembers &&
                        $recentMembers->num_rows
                    ):

                        while (
                            $row =
                            $recentMembers->fetch_assoc()
                        ):

                            $parts =
                                preg_split(
                                    '/\s+/',
                                    trim(
                                        $row['full_name']
                                    )
                                );

                            $initial =
                                strtoupper(
                                    substr(
                                        $parts[0],
                                        0,
                                        1
                                    ) .
                                    (
                                        count($parts) > 1
                                        ?
                                        substr(
                                            $parts[
                                                count($parts) - 1
                                            ],
                                            0,
                                            1
                                        )
                                        :
                                        ''
                                    )
                                );

                    ?>

                        <div class="member-row">

                            <span class="member-avatar">

                                <?php
                                echo htmlspecialchars(
                                    $initial
                                );
                                ?>

                            </span>


                            <div>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['full_name']
                                    );
                                    ?>

                                </strong>

                                <small>

                                    Joined
                                    <?php
                                    echo date(
                                        'd M Y',
                                        strtotime(
                                            $row['joined_date']
                                        )
                                    );
                                    ?>

                                </small>

                            </div>


                            <span
                                class="badge
                                <?php
                                echo
                                $row['status'] === 'active'
                                ? ''
                                : 'muted';
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $row['status']
                                    )
                                );
                                ?>

                            </span>

                        </div>

                    <?php

                        endwhile;

                    else:

                    ?>

                        <div class="empty">
                            No members found.
                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </div>

    </main>

</div>


<!-- =========================================
     TRAINER PERFORMANCE JAVASCRIPT
========================================= -->
<script>
async function loadTrainerPerformance() {
    const table = document.getElementById('trainer-performance-table');
    const updated = document.getElementById('trainer-performance-updated');
    const trainerCount = document.getElementById('trainer-count');
    const highWorkload = document.getElementById('high-workload-count');
    const avgPerformance = document.getElementById('average-trainer-performance');
    const status = document.getElementById('trainer-performance-status');
    const periodSelect = document.getElementById('trainer-performance-period');

    if (!table) return;

    const period = periodSelect ? periodSelect.value : '30';

    try {
        if (status) status.textContent = 'Loading...';
        if (updated) updated.textContent = 'Refreshing...';
        table.innerHTML = '<tr><td colspan="8" class="empty">Loading trainer analytics...</td></tr>';

        const response = await fetch(
            '../data_science/api/trainer_performance.php?period=' + encodeURIComponent(period),
            { cache: 'no-store' }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to load trainer analytics');
        }

        const trainers = Array.isArray(result.data) ? result.data : [];
        const periodLabel = result.period?.label || 'Selected period';

        trainerCount.textContent = trainers.length;

        // Workload is current-state based, so it does not change with the date filter.
        highWorkload.textContent = trainers.filter(
            t => Number(t.active_members ?? t.member_count ?? 0) >= 9
        ).length;

        const scores = trainers
            .map(t => Number(t.performance_score))
            .filter(Number.isFinite);

        const average = scores.length
            ? scores.reduce((sum, value) => sum + value, 0) / scores.length
            : 0;

        avgPerformance.textContent = average.toFixed(2) + '%';

        if (updated) updated.textContent = periodLabel;
        if (status) status.textContent = 'Updated just now';

        if (!trainers.length) {
            table.innerHTML = '<tr><td colspan="8" class="empty">No trainer analytics available.</td></tr>';
            return;
        }

        table.innerHTML = trainers.map(trainer => {
            const score = Math.max(0, Math.min(100, Number(trainer.performance_score) || 0));
            const activeMembers = Number(trainer.active_members ?? trainer.member_count ?? 0);

            // Workload reflects current active members, not the selected date period.
            const workload = activeMembers >= 9
                ? 'HIGH'
                : activeMembers >= 6
                    ? 'MEDIUM'
                    : 'LOW';

            const workloadClass = workload === 'HIGH'
                ? 'high'
                : workload === 'MEDIUM'
                    ? 'medium'
                    : 'low';

            const attendance = Number(trainer.attendance_score ?? 0);
            const workout = Number(trainer.workout_score ?? 0);
            const progress = Number(trainer.progress_score ?? 0);
            const rating = Number(trainer.rating ?? trainer.average_rating ?? 0);

            return `
                <tr>
                    <td><strong>${escapeHtml(trainer.trainer_name ?? trainer.name ?? 'Unknown Trainer')}</strong></td>
                    <td>${activeMembers}</td>
                    <td>${Number.isFinite(attendance) ? attendance.toFixed(1) + '%' : '—'}</td>
                    <td>${Number.isFinite(workout) ? workout.toFixed(1) + '%' : '—'}</td>
                    <td>${Number.isFinite(progress) ? progress.toFixed(1) + '%' : '—'}</td>
                    <td>${Number.isFinite(rating) ? rating.toFixed(2) + '/5' : '—'}</td>
                    <td style="min-width:150px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;height:7px;background:#e8efed;border-radius:99px;overflow:hidden;">
                                <span style="display:block;width:${score}%;height:100%;background:#16c7a4;border-radius:99px;"></span>
                            </div>
                            <strong style="font-size:12px;min-width:45px;text-align:right;">${score.toFixed(2)}%</strong>
                        </div>
                    </td>
                    <td>
                        <span style="display:inline-block;padding:5px 10px;border-radius:20px;font-size:11px;font-weight:700;${
                            workloadClass === 'high'
                                ? 'background:#fde8e8;color:#d63939;'
                                : workloadClass === 'medium'
                                    ? 'background:#fff4dc;color:#a66a00;'
                                    : 'background:#e8f7f3;color:#13866f;'
                        }">${escapeHtml(workload)}</span>
                    </td>
                </tr>
            `;
        }).join('');

    } catch (error) {
        console.error('Trainer analytics error:', error);
        if (updated) updated.textContent = 'Unavailable';
        if (status) status.textContent = 'Unable to load trainer analytics';
        table.innerHTML = `<tr><td colspan="8" class="empty">Unable to load trainer analytics. Check the trainer performance API.</td></tr>`;
    }
}

const trainerPerformancePeriod = document.getElementById('trainer-performance-period');
if (trainerPerformancePeriod) {
    trainerPerformancePeriod.addEventListener('change', loadTrainerPerformance);
}

loadTrainerPerformance();
</script>


<!-- =========================================
     GYM HEALTH ANALYTICS JAVASCRIPT
========================================= -->
<script>
async function loadGymHealth() {
    const updated = document.getElementById('gym-health-updated');
    const attention = document.getElementById('gym-health-attention');
    const attentionCount = document.getElementById('health-attention-count');

    if (!updated) return;

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    const money = value => new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        maximumFractionDigits: 0
    }).format(Number(value) || 0);

    try {
        const response = await fetch('../data_science/api/gym_health.php', {
            cache: 'no-store'
        });

        const result = await response.json();

        if (!response.ok || !result.success || !result.data) {
            throw new Error(result.error || 'Failed to load gym health analytics');
        }

        const d = result.data;

        setText('health-active-members', `${d.members.active} / ${d.members.total}`);
        setText('health-active-members-sub', `${Number(d.members.active_percentage).toFixed(1)}% of all members`);

        setText('health-attendance', d.attendance.last_30_days);
        setText('health-attendance-sub', `${d.attendance.unique_members} unique members checked in`);

        setText('health-workout', `${Number(d.workouts.completion_rate).toFixed(1)}%`);
        setText('health-workout-sub', `${d.workouts.completed} completed / ${d.workouts.total} total`);

        setText('health-class', `${Number(d.classes.attendance_rate).toFixed(1)}%`);
        setText('health-class-sub', `${d.classes.attended_last_30_days} attended / ${d.classes.bookings_last_30_days} booked`);

        setText('health-memberships', d.memberships.active);
        setText('health-memberships-sub', `${d.memberships.expired} expired overall`);

        setText('health-expiring', d.memberships.expiring_7_days);

        setText('health-revenue', money(d.revenue.current_month));
        setText('health-revenue-sub', `Previous month: ${money(d.revenue.previous_month)}`);

        setText('health-risk', d.retention.high_risk);
        setText('health-risk-sub', `${d.retention.medium_risk} medium · ${d.retention.low_risk} low`);

        const issues = [];

        if (Number(d.retention.high_risk) > 0) {
            issues.push(`🔴 ${d.retention.high_risk} member${d.retention.high_risk == 1 ? '' : 's'} currently have high retention risk.`);
        }

        if (Number(d.memberships.expiring_7_days) > 0) {
            issues.push(`🟡 ${d.memberships.expiring_7_days} membership${d.memberships.expiring_7_days == 1 ? '' : 's'} expire within 7 days.`);
        }

        if (Number(d.memberships.expired) > 0) {
            issues.push(`🟠 ${d.memberships.expired} memberships are currently recorded as expired.`);
        }

        if (Number(d.classes.attendance_rate) === 0 && Number(d.classes.bookings_last_30_days) > 0) {
            issues.push('🟡 Class bookings exist in the last 30 days, but no attendance was recorded.');
        }

        if (Number(d.trainers.high_workload) > 0) {
            issues.push(`🟠 ${d.trainers.high_workload} trainer${d.trainers.high_workload == 1 ? '' : 's'} have 9+ active members.`);
        }

        if (!issues.length) {
            issues.push('🟢 No immediate operational attention items detected.');
        }

        if (attention) {
            attention.innerHTML = issues.map(item => `
                <div style="padding:10px 12px;border-radius:8px;background:rgba(255,255,255,.025);font-size:12px;">
                    ${escapeHtml(item)}
                </div>
            `).join('');
        }

        if (attentionCount) {
            attentionCount.textContent = `${issues.length} item${issues.length === 1 ? '' : 's'}`;
        }

        updated.textContent = 'Live analytics';

    } catch (error) {
        console.error('Gym health analytics error:', error);
        updated.textContent = 'Unavailable';
        if (attention) {
            attention.innerHTML = '<div class="empty">Unable to load gym health analytics. Check the gym health API.</div>';
        }
        if (attentionCount) attentionCount.textContent = 'Unavailable';
    }
}

loadGymHealth();
</script>


<!-- =========================================
     MEMBER ENGAGEMENT ANALYTICS JAVASCRIPT
========================================= -->
<script>
async function loadMemberEngagement() {
    const updated = document.getElementById('member-engagement-updated');
    const top = document.getElementById('top-engaged-members');
    const attention = document.getElementById('engagement-attention-members');
    const attentionCount = document.getElementById('engagement-attention-count');
    const table = document.getElementById('member-engagement-table');
    const searchInput = document.getElementById('engagement-member-search');
    const levelFilter = document.getElementById('engagement-level-filter');
    const pageSizeSelect = document.getElementById('engagement-page-size');
    const pagination = document.getElementById('engagement-pagination');
    const tableCount = document.getElementById('engagement-table-count');

    let allMembersForTable = [];
    let currentPage = 1;
    let currentPageSize = 10;

    if (!updated) return;

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    const formatDate = value => {
        if (!value) return 'No visit recorded';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return String(value);
        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    };

    const levelBadge = level => {
        const safe = String(level || 'LOW').toUpperCase();
        const styles = {
            HIGH: 'background:rgba(18,199,160,.14);color:#12c7a0;',
            MEDIUM: 'background:rgba(240,180,41,.14);color:#f0b429;',
            LOW: 'background:rgba(229,72,77,.14);color:#e5484d;'
        };
        return `<span style="display:inline-block;padding:4px 8px;border-radius:999px;font-size:10px;font-weight:700;${styles[safe] || styles.LOW}">${escapeHtml(safe)}</span>`;
    };

    const scoreBar = score => {
        const value = Math.max(0, Math.min(100, Number(score) || 0));
        return `
            <div style="display:flex;align-items:center;gap:8px;min-width:120px;">
                <div style="height:5px;flex:1;background:rgba(255,255,255,.08);border-radius:999px;overflow:hidden;">
                    <div style="height:100%;width:${value}%;background:#12c7a0;border-radius:999px;"></div>
                </div>
                <strong style="font-size:11px;min-width:42px;text-align:right;">${value.toFixed(1)}%</strong>
            </div>
        `;
    };

    try {
        const response = await fetch('../data_science/api/member_engagement.php', {
            cache: 'no-store'
        });

        const result = await response.json();

        if (!response.ok || !result.success || !result.data) {
            throw new Error(result.error || 'Failed to load member engagement analytics');
        }

        const d = result.data;
        const summary = d.summary || {};
        const members = Array.isArray(d.all_members) ? d.all_members : [];
        const topMembers = Array.isArray(d.top_engaged_members) ? d.top_engaged_members : [];
        const attentionMembers = Array.isArray(d.attention_members) ? d.attention_members : [];

        allMembersForTable = members.slice();
        currentPage = 1;

        setText('engagement-average', `${Number(summary.average_engagement_score || 0).toFixed(1)}%`);
        setText('engagement-high', summary.high_engagement || 0);
        setText('engagement-medium', summary.medium_engagement || 0);
        setText('engagement-low', summary.low_engagement || 0);
        setText('engagement-attention-count', `${attentionMembers.length} member${attentionMembers.length === 1 ? '' : 's'}`);

        if (top) {
            top.innerHTML = topMembers.length
                ? topMembers.map(member => `
                    <div style="padding:11px 12px;border-radius:9px;background:rgba(255,255,255,.025);">
                        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;">
                            <div>
                                <strong style="font-size:12px;">${escapeHtml(member.member_name)}</strong>
                                <div style="font-size:10px;color:#71817f;margin-top:3px;">
                                    ${Number(member.visits_30_days || 0)} visits · ${Number(member.workout_completion_rate || 0).toFixed(1)}% workout completion
                                </div>
                            </div>
                            <div style="min-width:130px;">${scoreBar(member.engagement_score)}</div>
                        </div>
                    </div>
                `).join('')
                : '<div class="empty">No engagement records available.</div>';
        }

        if (attention) {
            attention.innerHTML = attentionMembers.length
                ? attentionMembers.map(member => `
                    <div style="padding:11px 12px;border-radius:9px;background:rgba(255,255,255,.025);">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <div>
                                <strong style="font-size:12px;">${escapeHtml(member.member_name)}</strong>
                                <div style="font-size:10px;color:#71817f;margin-top:4px;">
                                    ${escapeHtml((member.indicators || []).join(' · '))}
                                </div>
                            </div>
                            <strong style="font-size:11px;">${Number(member.engagement_score || 0).toFixed(1)}%</strong>
                        </div>
                        <div style="font-size:10px;color:#8c9a98;margin-top:7px;">
                            ${escapeHtml(member.recommended_action || 'Follow up with member')}
                        </div>
                    </div>
                `).join('')
                : '<div class="empty">No low-engagement members found.</div>';
        }

        const renderEngagementTable = () => {
            if (!table) return;

            const query = String(searchInput?.value || '').trim().toLowerCase();
            const selectedLevel = String(levelFilter?.value || 'ALL').toUpperCase();

            const filtered = allMembersForTable.filter(member => {
                const name = String(member.member_name || '').toLowerCase();
                const level = String(member.engagement_level || 'LOW').toUpperCase();

                const matchesSearch = !query || name.includes(query);
                const matchesLevel =
                    selectedLevel === 'ALL'
                    || selectedLevel === level
                    || (selectedLevel === 'ATTENTION' && level === 'LOW');

                return matchesSearch && matchesLevel;
            });

            const totalFiltered = filtered.length;
            const totalPages = Math.max(1, Math.ceil(totalFiltered / currentPageSize));

            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * currentPageSize;
            const pageMembers = filtered.slice(start, start + currentPageSize);

            if (tableCount) {
                tableCount.textContent =
                    `${totalFiltered} member${totalFiltered === 1 ? '' : 's'}`;
            }

            table.innerHTML = pageMembers.length
                ? pageMembers.map(member => `
                    <tr>
                        <td>
                            <strong>${escapeHtml(member.member_name)}</strong>
                            <div style="font-size:10px;color:#71817f;">Member #${Number(member.member_id)}</div>
                        </td>
                        <td>${Number(member.visits_30_days || 0)}</td>
                        <td>${Number(member.workout_completion_rate || 0).toFixed(1)}%</td>
                        <td>${member.progress_score === null || member.progress_score === undefined ? 'N/A' : Number(member.progress_score).toFixed(0) + '%'}</td>
                        <td>${escapeHtml(formatDate(member.last_visit))}</td>
                        <td>${scoreBar(member.engagement_score)}</td>
                        <td>${levelBadge(member.engagement_level)}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="7" class="empty">No members match the current filter.</td></tr>';

            if (!pagination || totalFiltered === 0) {
                if (pagination) pagination.innerHTML = '';
                return;
            }

            const buttonStyle = (disabled = false, active = false) =>
                `padding:6px 9px;border-radius:7px;border:1px solid rgba(255,255,255,.10);background:${active ? '#12c7a0' : '#0a1719'};color:${active ? '#061412' : '#b9c6c4'};font-size:10px;font-weight:700;cursor:${disabled ? 'not-allowed' : 'pointer'};opacity:${disabled ? '.45' : '1'};`;

            const visiblePages = Math.min(totalPages, 7);
            let firstPage = Math.max(1, currentPage - 3);
            if (firstPage + visiblePages - 1 > totalPages) {
                firstPage = Math.max(1, totalPages - visiblePages + 1);
            }

            const pageButtons = [
                `<button type="button" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''} style="${buttonStyle(currentPage === 1)}">←</button>`
            ];

            for (let page = firstPage; page < firstPage + visiblePages; page++) {
                pageButtons.push(
                    `<button type="button" data-page="${page}" style="${buttonStyle(false, page === currentPage)}">${page}</button>`
                );
            }

            pageButtons.push(
                `<button type="button" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''} style="${buttonStyle(currentPage === totalPages)}">→</button>`
            );

            pagination.innerHTML = `
                <span style="font-size:10px;color:#71817f;margin-right:5px;">
                    Page ${currentPage} of ${totalPages}
                </span>
                ${pageButtons.join('')}
            `;

            pagination.querySelectorAll('button[data-page]').forEach(button => {
                button.addEventListener('click', () => {
                    const targetPage = Number(button.dataset.page);
                    if (!Number.isFinite(targetPage) || targetPage < 1 || targetPage > totalPages) return;
                    currentPage = targetPage;
                    renderEngagementTable();
                });
            });
        };

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                currentPage = 1;
                renderEngagementTable();
            });
        }

        if (levelFilter) {
            levelFilter.addEventListener('change', () => {
                currentPage = 1;
                renderEngagementTable();
            });
        }

        if (pageSizeSelect) {
            pageSizeSelect.addEventListener('change', () => {
                currentPageSize = Math.max(1, Number(pageSizeSelect.value) || 10);
                currentPage = 1;
                renderEngagementTable();
            });
        }

        renderEngagementTable();

        updated.textContent = 'Live analytics';

    } catch (error) {
        console.error('Member engagement analytics error:', error);
        updated.textContent = 'Unavailable';

        if (top) top.innerHTML = '<div class="empty">Unable to load engagement analytics. Check the member engagement API.</div>';
        if (attention) attention.innerHTML = '<div class="empty">Unable to load engagement analytics.</div>';
        if (table) table.innerHTML = '<tr><td colspan="7" class="empty">Unable to load engagement details.</td></tr>';
        if (attentionCount) attentionCount.textContent = 'Unavailable';
    }
}

loadMemberEngagement();
</script>

<!-- =========================================
     RETENTION RISK JAVASCRIPT
========================================= -->

<script>

async function loadRetentionRisk() {

    /*
     * First refresh the ML predictions.
     * This runs the Python prediction pipeline.
     */

    try {

        const refreshResponse = await fetch(
            "../data_science/api/refresh_retention.php"
        );

        const refreshResult =
            await refreshResponse.json();

        if (!refreshResult.success) {

            console.error(
                "ML refresh failed:",
                refreshResult.message
            );

        }

    } catch (error) {

        console.error(
            "Unable to refresh retention predictions:",
            error
        );

    }


    const table =
        document.getElementById(
            "retention-risk-table"
        );


    try {

        /*
         * Load fresh predictions from PHP API
         */

        const response = await fetch(
            "../data_science/api/retention_predictions.php"
        );


        const result =
            await response.json();


        if (!result.success) {

            throw new Error(
                result.message ||
                "Failed to load predictions"
            );

        }


        const predictions =
            result.data || [];


        // =========================================
        // RISK SUMMARY
        // =========================================

        let high = 0;
        let medium = 0;
        let low = 0;


        predictions.forEach(
            prediction => {

                if (
                    prediction.risk_level === "HIGH"
                ) {

                    high++;

                } else if (
                    prediction.risk_level === "MEDIUM"
                ) {

                    medium++;

                } else if (
                    prediction.risk_level === "LOW"
                ) {

                    low++;

                }

            }
        );


        document.getElementById(
            "high-risk-count"
        ).textContent = high;


        document.getElementById(
            "medium-risk-count"
        ).textContent = medium;


        document.getElementById(
            "low-risk-count"
        ).textContent = low;


        // =========================================
        // SORT BY HIGHEST RISK
        // =========================================

        const topPredictions =
            predictions
                .sort(
                    (a, b) =>
                        Number(
                            b.risk_percentage
                        ) -
                        Number(
                            a.risk_percentage
                        )
                )
                .slice(0, 10);


        // =========================================
        // NO DATA
        // =========================================

        if (
            topPredictions.length === 0
        ) {

            table.innerHTML = `

                <tr>

                    <td
                        colspan="5"
                        class="empty"
                    >
                        No retention predictions available.
                    </td>

                </tr>

            `;

            return;
        }


        // =========================================
        // BUILD TABLE
        // =========================================

        table.innerHTML =

            topPredictions
                .map(
                    prediction => {

                        let riskClass = "";


                        if (
                            prediction.risk_level ===
                            "HIGH"
                        ) {

                            riskClass = "high";

                        } else if (
                            prediction.risk_level ===
                            "MEDIUM"
                        ) {

                            riskClass = "medium";

                        } else {

                            riskClass = "low";

                        }


                        /*
                         * Risk indicators
                         */

                        const reasons = [

                            prediction.risk_reason_1,
                            prediction.risk_reason_2,
                            prediction.risk_reason_3

                        ].filter(
                            reason =>
                                reason &&
                                reason !==
                                "No major additional indicator"
                        );


                        const reasonHtml =
                            reasons.length > 0

                            ?

                            reasons
                                .map(
                                    reason => `
                                        <div
                                            style="
                                                margin-bottom:4px;
                                                font-size:11px;
                                                color:#536360;
                                            "
                                        >
                                            • ${reason}
                                        </div>
                                    `
                                )
                                .join("")

                            :

                            `
                                <span
                                    style="
                                        font-size:11px;
                                        color:#71817f;
                                    "
                                >
                                    No major additional indicator
                                </span>
                            `;


                        return `

                            <tr>

                                <!-- MEMBER -->

                                <td>

                                    <strong>
                                        ${escapeHtml(
                                            prediction.member_name ||
                                            "Unknown Member"
                                        )}
                                    </strong>

                                    <small
                                        style="
                                            display:block;
                                            color:#71817f;
                                            margin-top:3px;
                                        "
                                    >
                                        Member #
                                        ${escapeHtml(
                                            String(
                                                prediction.member_id
                                            )
                                        )}
                                    </small>

                                </td>


                                <!-- RISK -->

                                <td>

                                    <strong>

                                        ${Number(
                                            prediction.risk_percentage
                                        ).toFixed(2)}%

                                    </strong>

                                </td>


                                <!-- RISK LEVEL -->

                                <td>

                                    <span
                                        style="
                                            display:inline-block;
                                            padding:5px 10px;
                                            border-radius:20px;
                                            font-size:11px;
                                            font-weight:700;

                                            ${
                                                riskClass === "high"

                                                ?

                                                "background:#fde8e8;color:#d63939;"

                                                :

                                                riskClass === "medium"

                                                ?

                                                "background:#fff4dc;color:#b7791f;"

                                                :

                                                "background:#e6f8f3;color:#138a72;"
                                            }
                                        "
                                    >

                                        ${escapeHtml(
                                            prediction.risk_level
                                        )}

                                    </span>

                                </td>


                                <!-- RISK INDICATORS -->

                                <td>

                                    ${reasonHtml}

                                </td>


                                <!-- RECOMMENDED ACTION -->

                                <td>

                                    <div
                                        style="
                                            font-size:11px;
                                            line-height:1.5;
                                            color:#536360;
                                            max-width:260px;
                                        "
                                    >

                                        ${escapeHtml(
                                            prediction.recommended_action ||
                                            "Continue regular member engagement"
                                        )}

                                    </div>

                                </td>

                            </tr>

                        `;

                    }
                )
                .join("");


        // =========================================
        // UPDATED COUNT
        // =========================================

        document.getElementById(
            "retention-updated"
        ).textContent =
            `${result.count} predictions`;


    } catch (error) {

        console.error(
            "Retention prediction error:",
            error
        );


        table.innerHTML = `

            <tr>

                <td
                    colspan="5"
                    class="empty"
                >
                    Unable to load retention predictions.
                </td>

            </tr>

        `;

    }

}


// =========================================
// HTML ESCAPE
// =========================================

function escapeHtml(value) {

    return String(value)

        .replace(
            /&/g,
            "&amp;"
        )

        .replace(
            /</g,
            "&lt;"
        )

        .replace(
            />/g,
            "&gt;"
        )

        .replace(
            /"/g,
            "&quot;"
        )

        .replace(
            /'/g,
            "&#039;"
        );

}


// =========================================
// FORMAT DATE
// =========================================

function formatPredictionDate(
    dateString
) {

    if (!dateString) {

        return "—";

    }


    const date =
        new Date(
            dateString.replace(
                " ",
                "T"
            )
        );


    if (isNaN(date)) {

        return dateString;

    }


    return date.toLocaleDateString(
        "en-GB",
        {
            day: "2-digit",
            month: "short",
            year: "numeric"
        }
    );

}


// =========================================
// LOAD DASHBOARD
// =========================================

loadRetentionRisk();

</script>


<!-- =========================================
     FITTRACK AI ASSISTANT JAVASCRIPT
========================================= -->

<script>
(function () {

    const questionInput = document.getElementById("ai-assistant-question");
    const askButton = document.getElementById("ai-assistant-ask");
    const responseBox = document.getElementById("ai-assistant-response");
    const status = document.getElementById("ai-assistant-status");
    const hint = document.getElementById("ai-assistant-hint");
    const quickPrompts = document.querySelectorAll(".ai-quick-prompt");

    if (!questionInput || !askButton || !responseBox || !status) {
        return;
    }

    function setBusy(isBusy) {
        askButton.disabled = isBusy;
        askButton.style.opacity = isBusy ? "0.65" : "1";
        askButton.style.cursor = isBusy ? "not-allowed" : "pointer";
        status.textContent = isBusy ? "Thinking..." : "Ready";
    }

    function showResponse(message, isError = false) {
        responseBox.style.display = "block";
        responseBox.style.borderColor = isError
            ? "rgba(229,72,77,.25)"
            : "rgba(18,199,160,.15)";
        responseBox.style.background = isError
            ? "rgba(229,72,77,.05)"
            : "rgba(255,255,255,.025)";

        responseBox.innerHTML = `
            <div style="
                font-size:10px;
                font-weight:800;
                letter-spacing:.08em;
                text-transform:uppercase;
                color:${isError ? "#e5484d" : "#12c7a0"};
                margin-bottom:8px;
            ">
                ${isError ? "AI Assistant" : "FITTRACK AI"}
            </div>

            <div style="
                color:#c7d1cf;
                font-size:12px;
                line-height:1.7;
                white-space:pre-wrap;
            ">
                ${escapeHtml(message)}
            </div>
        `;
    }

    async function askAI() {

        const question = questionInput.value.trim();

        if (!question) {
            showResponse("Please enter a question first.", true);
            questionInput.focus();
            return;
        }

        setBusy(true);

        responseBox.style.display = "block";
        responseBox.innerHTML = `
            <div style="color:#71817f;font-size:12px;">
                FITTRACK AI is analyzing the current gym data...
            </div>
        `;

        try {

            const response = await fetch(
                "../data_science/api/ai_assistant.php",
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        question: question
                    })
                }
            );

            let result = null;

            try {
                result = await response.json();
            } catch (jsonError) {
                throw new Error("The AI service returned an invalid response.");
            }

            if (!response.ok || !result || !result.success) {

                let message = result && (
                    result.error ||
                    result.message
                );

                if (!message) {
                    message = response.status === 429
                        ? "AI usage quota is temporarily exhausted. Please try again after the quota resets."
                        : "FITTRACK AI could not complete the request right now.";
                }

                throw new Error(message);
            }

            showResponse(
                result.answer || "FITTRACK AI returned no answer."
            );

            status.textContent = "Answered";

            if (hint) {
                hint.textContent =
                    "Generated from the current FITTRACK analytics context.";
            }

        } catch (error) {

            console.error("FITTRACK AI error:", error);

            let message = error && error.message
                ? error.message
                : "FITTRACK AI is temporarily unavailable.";

            if (
                message.includes("429") ||
                message.toLowerCase().includes("quota")
            ) {
                message =
                    "AI usage quota is temporarily exhausted. The dashboard is working normally; try the AI Assistant again after the Gemini quota resets.";
            }

            showResponse(message, true);
            status.textContent = "Unavailable";

        } finally {
            setBusy(false);
        }
    }

    askButton.addEventListener("click", askAI);

    questionInput.addEventListener("keydown", event => {
        if (
            event.key === "Enter" &&
            (event.ctrlKey || event.metaKey)
        ) {
            event.preventDefault();
            askAI();
        }
    });

    quickPrompts.forEach(button => {
        button.addEventListener("click", () => {
            questionInput.value = button.dataset.question || "";
            questionInput.focus();
        });
    });

})();
</script>


<!-- Existing Admin JavaScript -->

<script
    src="../assets/js/admin.js"
></script>



<?php include_once __DIR__ . '/../components/ai_copilot.php'; ?>
</body>

</html>