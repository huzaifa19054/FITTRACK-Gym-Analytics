<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';


/*
|--------------------------------------------------------------------------
| FITTRACK — GYM HEALTH ANALYTICS API
|--------------------------------------------------------------------------
|
| Provides:
| - Member health
| - Attendance engagement
| - Workout engagement
| - Class engagement
| - Membership status
| - Revenue
| - Retention risk
|
*/


$response = [
    'success' => false,
    'data' => null
];


try {

    /*
    |--------------------------------------------------------------------------
    | MEMBER METRICS
    |--------------------------------------------------------------------------
    */

    $memberSql = "
        SELECT

            COUNT(*) AS total_members,

            SUM(
                CASE
                    WHEN status = 'active'
                    THEN 1
                    ELSE 0
                END
            ) AS active_members,

            SUM(
                CASE
                    WHEN status != 'active'
                    THEN 1
                    ELSE 0
                END
            ) AS inactive_members

        FROM members
    ";

    $memberResult = $conn->query($memberSql);

    if (!$memberResult) {
        throw new Exception($conn->error);
    }

    $members = $memberResult->fetch_assoc();


    $totalMembers = (int) ($members['total_members'] ?? 0);
    $activeMembers = (int) ($members['active_members'] ?? 0);
    $inactiveMembers = (int) ($members['inactive_members'] ?? 0);


    $activeMemberPercentage = $totalMembers > 0
        ? ($activeMembers / $totalMembers) * 100
        : 0;


    /*
    |--------------------------------------------------------------------------
    | ATTENDANCE — LAST 30 DAYS
    |--------------------------------------------------------------------------
    */

    $attendanceSql = "
        SELECT

            COUNT(*) AS total_checkins,

            COUNT(
                DISTINCT member_id
            ) AS unique_members,

            COUNT(
                DISTINCT CASE
                    WHEN members.status = 'active'
                    THEN attendance.member_id
                END
            ) AS active_unique_members

        FROM attendance

        LEFT JOIN members
            ON members.id = attendance.member_id

        WHERE check_in >= DATE_SUB(
            CURDATE(),
            INTERVAL 30 DAY
        )
    ";

    $attendanceResult = $conn->query($attendanceSql);

    if (!$attendanceResult) {
        throw new Exception($conn->error);
    }

    $attendance = $attendanceResult->fetch_assoc();


    $totalCheckins = (int) ($attendance['total_checkins'] ?? 0);

    $uniqueAttendanceMembers =
        (int) ($attendance['unique_members'] ?? 0);

    $activeAttendanceMembers =
        (int) ($attendance['active_unique_members'] ?? 0);

    $attendanceRate =
        $activeMembers > 0
        ? ($activeAttendanceMembers / $activeMembers) * 100
        : 0;


    $averageCheckinsPerActiveMember =
        $activeMembers > 0
        ? $totalCheckins / $activeMembers
        : 0;


    /*
    |--------------------------------------------------------------------------
    | WORKOUT ENGAGEMENT
    |--------------------------------------------------------------------------
    */

    $workoutSql = "
        SELECT

            SUM(
                CASE
                    WHEN status = 'completed'
                    THEN 1
                    ELSE 0
                END
            ) AS completed_workouts,

            SUM(
                CASE
                    WHEN status = 'assigned'
                    THEN 1
                    ELSE 0
                END
            ) AS assigned_workouts,

            SUM(
                CASE
                    WHEN status = 'paused'
                    THEN 1
                    ELSE 0
                END
            ) AS paused_workouts

        FROM member_workouts

        WHERE assigned_date >= DATE_SUB(
            CURDATE(),
            INTERVAL 30 DAY
        )
    ";

    $workoutResult = $conn->query($workoutSql);

    if (!$workoutResult) {
        throw new Exception($conn->error);
    }

    $workouts = $workoutResult->fetch_assoc();


    $totalWorkouts =
        (int) ($workouts['completed_workouts'] ?? 0)
        + (int) ($workouts['assigned_workouts'] ?? 0);

    $completedWorkouts =
        (int) ($workouts['completed_workouts'] ?? 0);

    $assignedWorkouts =
        (int) ($workouts['assigned_workouts'] ?? 0);

    $pausedWorkouts =
        (int) ($workouts['paused_workouts'] ?? 0);


    $workoutCompletionRate =
        $totalWorkouts > 0
        ? ($completedWorkouts / $totalWorkouts) * 100
        : 0;


    /*
    |--------------------------------------------------------------------------
    | CLASS ENGAGEMENT — LAST 30 DAYS
    |--------------------------------------------------------------------------
    */

    $classSql = "
        SELECT

            SUM(
                CASE
                    WHEN cb.status IN ('booked', 'attended')
                    THEN 1
                    ELSE 0
                END
            ) AS total_bookings,

            SUM(
                CASE
                    WHEN cb.status = 'attended'
                    THEN 1
                    ELSE 0
                END
            ) AS attended_classes

        FROM class_bookings cb

        INNER JOIN classes c
            ON c.id = cb.class_id

        WHERE c.class_date >= DATE_SUB(
            CURDATE(),
            INTERVAL 30 DAY
        )
        AND c.class_date <= CURDATE()
    ";

    $classResult = $conn->query($classSql);

    if (!$classResult) {
        throw new Exception($conn->error);
    }

    $classes = $classResult->fetch_assoc();


    $totalBookings =
        (int) ($classes['total_bookings'] ?? 0);

    $attendedClasses =
        (int) ($classes['attended_classes'] ?? 0);


    $classAttendanceRate =
        $totalBookings > 0
        ? ($attendedClasses / $totalBookings) * 100
        : 0;


    /*
    |--------------------------------------------------------------------------
    | MEMBERSHIP HEALTH
    |--------------------------------------------------------------------------
    */

    $membershipSql = "
        SELECT

            SUM(
                CASE
                    WHEN status = 'active'
                         AND end_date >= CURDATE()
                    THEN 1
                    ELSE 0
                END
            ) AS active_memberships,

            SUM(
                CASE
                    WHEN status = 'active'
                         AND end_date BETWEEN CURDATE()
                         AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    THEN 1
                    ELSE 0
                END
            ) AS expiring_7_days,

            SUM(
                CASE
                    WHEN end_date < CURDATE()
                         AND status != 'cancelled'
                    THEN 1
                    ELSE 0
                END
            ) AS expired_memberships,

            SUM(
                CASE
                    WHEN status = 'cancelled'
                    THEN 1
                    ELSE 0
                END
            ) AS cancelled_memberships

        FROM memberships
    ";

    $membershipResult =
        $conn->query($membershipSql);

    if (!$membershipResult) {
        throw new Exception($conn->error);
    }

    $membership =
        $membershipResult->fetch_assoc();


    $activeMemberships =
        (int) ($membership['active_memberships'] ?? 0);

    $expiringMemberships =
        (int) ($membership['expiring_7_days'] ?? 0);

    $expiredMemberships =
        (int) ($membership['expired_memberships'] ?? 0);

    $cancelledMemberships =
        (int) ($membership['cancelled_memberships'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | REVENUE
    |--------------------------------------------------------------------------
    */

    $revenueSql = "
        SELECT

            COALESCE(
                SUM(
                    CASE
                        WHEN status = 'paid'
                        AND payment_date <= CURDATE()
                        THEN amount
                        ELSE 0
                    END
                ),
                0
            ) AS total_revenue,

            COALESCE(
                SUM(
                    CASE
                        WHEN status = 'paid'
                        AND payment_date >=
                            DATE_FORMAT(
                                CURDATE(),
                                '%Y-%m-01'
                            )
                        AND payment_date <= CURDATE()
                        THEN amount
                        ELSE 0
                    END
                ),
                0
            ) AS current_month_revenue,

            COALESCE(
                SUM(
                    CASE
                        WHEN status = 'paid'
                        AND payment_date >=
                            DATE_FORMAT(
                                DATE_SUB(
                                    CURDATE(),
                                    INTERVAL 1 MONTH
                                ),
                                '%Y-%m-01'
                            )
                        AND payment_date <
                            DATE_FORMAT(
                                CURDATE(),
                                '%Y-%m-01'
                            )
                        THEN amount
                        ELSE 0
                    END
                ),
                0
            ) AS previous_month_revenue

        FROM payments
    ";

    $revenueResult =
        $conn->query($revenueSql);

    if (!$revenueResult) {
        throw new Exception($conn->error);
    }

    $revenue =
        $revenueResult->fetch_assoc();


    $totalRevenue =
        (float) ($revenue['total_revenue'] ?? 0);

    $currentMonthRevenue =
        (float) ($revenue['current_month_revenue'] ?? 0);

    $previousMonthRevenue =
        (float) ($revenue['previous_month_revenue'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | RETENTION RISK
    |--------------------------------------------------------------------------
    */

    $retentionSql = "
        SELECT

            SUM(
                CASE
                    WHEN rp.risk_level = 'HIGH'
                    THEN 1
                    ELSE 0
                END
            ) AS high_risk,

            SUM(
                CASE
                    WHEN rp.risk_level = 'MEDIUM'
                    THEN 1
                    ELSE 0
                END
            ) AS medium_risk,

            SUM(
                CASE
                    WHEN rp.risk_level = 'LOW'
                    THEN 1
                    ELSE 0
                END
            ) AS low_risk

        FROM retention_predictions rp

        INNER JOIN memberships ms
            ON ms.id = rp.membership_id
            AND ms.member_id = rp.member_id
            AND ms.status = 'active'
            AND ms.end_date >= CURDATE()

        INNER JOIN members m
            ON m.id = rp.member_id
            AND m.status = 'active'

        WHERE NOT EXISTS (
            SELECT 1
            FROM memberships newer_ms
            WHERE newer_ms.member_id = ms.member_id
              AND newer_ms.status = 'active'
              AND newer_ms.end_date >= CURDATE()
              AND (
                    newer_ms.end_date > ms.end_date
                    OR (
                        newer_ms.end_date = ms.end_date
                        AND newer_ms.id > ms.id
                    )
              )
        )

        AND NOT EXISTS (
            SELECT 1
            FROM retention_predictions newer_rp
            WHERE newer_rp.member_id = rp.member_id
              AND newer_rp.membership_id = rp.membership_id
              AND (
                    newer_rp.prediction_date > rp.prediction_date
                    OR (
                        newer_rp.prediction_date = rp.prediction_date
                        AND newer_rp.id > rp.id
                    )
              )
        )
    ";


    $retentionResult =
        $conn->query($retentionSql);

    if (!$retentionResult) {
        throw new Exception($conn->error);
    }

    $retention =
        $retentionResult->fetch_assoc();


    $highRisk =
        (int) ($retention['high_risk'] ?? 0);

    $mediumRisk =
        (int) ($retention['medium_risk'] ?? 0);

    $lowRisk =
        (int) ($retention['low_risk'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | TRAINER OVERVIEW
    |--------------------------------------------------------------------------
    */

    $trainerSql = "
        SELECT

            COUNT(*) AS total_trainers,

            SUM(
                CASE
                    WHEN active_members >= 9
                    THEN 1
                    ELSE 0
                END
            ) AS high_workload_trainers

        FROM (

            SELECT

                t.id,

                COUNT(
                    DISTINCT CASE
                        WHEN m.status = 'active'
                        THEN m.id
                    END
                ) AS active_members

            FROM trainers t

            LEFT JOIN members m
                ON m.trainer_id = t.id

            WHERE t.status = 'active'

            GROUP BY t.id

        ) AS trainer_data
    ";

    /*
    |--------------------------------------------------------------------------
    | NOTE:
    | The query above depends on the trainer table's
    | active member relationship. If your current
    | trainer API is already working, this follows
    | the same relationship.
    |--------------------------------------------------------------------------
    */

    $trainerResult =
        $conn->query($trainerSql);

    if (!$trainerResult) {
        throw new Exception($conn->error);
    }

    $trainer =
        $trainerResult->fetch_assoc();


    $totalTrainers =
        (int) ($trainer['total_trainers'] ?? 0);

    $highWorkloadTrainers =
        (int) ($trainer['high_workload_trainers'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | BUILD RESPONSE
    |--------------------------------------------------------------------------
    */

    $response['success'] = true;

    $response['data'] = [

        'members' => [

            'total' => $totalMembers,

            'active' => $activeMembers,

            'inactive' => $inactiveMembers,

            'active_percentage' =>
                round($activeMemberPercentage, 2)
        ],


        'attendance' => [

            'last_30_days' => $totalCheckins,

            'unique_members' =>
                $uniqueAttendanceMembers,

            'active_members_checked_in' =>
                $activeAttendanceMembers,

            'attendance_rate' =>
                round($attendanceRate, 2),

            'average_checkins_per_active_member' =>
                round(
                    $averageCheckinsPerActiveMember,
                    2
                )
        ],


        'workouts' => [

            'total' => $totalWorkouts,

            'completed' => $completedWorkouts,

            'assigned' => $assignedWorkouts,

            'paused' => $pausedWorkouts,

            'completion_rate' =>
                round(
                    $workoutCompletionRate,
                    2
                )
        ],


        'classes' => [

            'bookings_last_30_days' =>
                $totalBookings,

            'attended_last_30_days' =>
                $attendedClasses,

            'attendance_rate' =>
                round(
                    $classAttendanceRate,
                    2
                )
        ],


        'memberships' => [

            'active' =>
                $activeMemberships,

            'expiring_7_days' =>
                $expiringMemberships,

            'expired' =>
                $expiredMemberships,

            'cancelled' =>
                $cancelledMemberships
        ],


        'revenue' => [

            'total' =>
                round($totalRevenue, 2),

            'current_month' =>
                round($currentMonthRevenue, 2),

            'previous_month' =>
                round($previousMonthRevenue, 2)
        ],


        'retention' => [

            'high_risk' =>
                $highRisk,

            'medium_risk' =>
                $mediumRisk,

            'low_risk' =>
                $lowRisk
        ],


        'trainers' => [

            'total' =>
                $totalTrainers,

            'high_workload' =>
                $highWorkloadTrainers
        ]

    ];


} catch (Throwable $e) {

    http_response_code(500);

    $response = [

        'success' => false,

        'error' => $e->getMessage()

    ];
}


echo json_encode(
    $response,
    JSON_PRETTY_PRINT
);


$conn->close();