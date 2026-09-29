<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../config/db.php';

try {

    /*
     * ============================================================
     * FITTRACK TRAINER PERFORMANCE ANALYTICS
     * ============================================================
     *
     * Period:
     *   2, 7, 30, 90 days or all time
     *   Custom dates also supported.
     *
     * Period-sensitive:
     *   Attendance = percentage of active members with at least
     *                one check-in during the selected period
     *   Workout completion
     *   Progress tracking
     *
     * Current-state:
     *   Active member workload
     *   Trainer rating
     *
     * Performance:
     *   Attendance 30%
     *   Workout    30%
     *   Progress   20%
     *   Rating     20%
     *
     * Workout ownership:
     *   trainer
     *      ↓
     *   workout_plans.trainer_id
     *      ↓
     *   member_workouts.workout_plan_id
     *
     * ============================================================
     */


    /* ============================================================
       PERIOD SETUP
    ============================================================ */

    $allowedPeriods = [2, 7, 30, 90];

    $periodParam =
        strtolower(
            trim(
                (string)($_GET['period'] ?? '30')
            )
        );

    $startDateParam =
        trim(
            (string)($_GET['start_date'] ?? '')
        );

    $endDateParam =
        trim(
            (string)($_GET['end_date'] ?? '')
        );


    $today =
        new DateTimeImmutable('today');


    $periodLabel =
        'Last 30 Days';


    $periodDays =
        30;


    $startDate =
        $today->modify('-29 days');


    $endDate =
        $today;


    $isAllTime =
        false;


    /* ============================================================
       PERIOD = ALL
    ============================================================ */

    if ($periodParam === 'all') {

        $isAllTime =
            true;

        $periodLabel =
            'All Time';

        $periodDays =
            0;

    }


    /* ============================================================
       PERIOD = CUSTOM
    ============================================================ */

    elseif ($periodParam === 'custom') {

        $start =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $startDateParam
            );

        $end =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $endDateParam
            );


        if (
            !$start ||
            !$end ||
            $start->format('Y-m-d') !== $startDateParam ||
            $end->format('Y-m-d') !== $endDateParam
        ) {

            throw new InvalidArgumentException(
                'Invalid custom date range. Use YYYY-MM-DD.'
            );

        }


        if ($start > $end) {

            throw new InvalidArgumentException(
                'Custom start date cannot be after custom end date.'
            );

        }


        $periodDays =
            $start->diff($end)->days + 1;


        if ($periodDays > 3660) {

            throw new InvalidArgumentException(
                'Custom date range cannot exceed 10 years.'
            );

        }


        $startDate =
            $start;


        $endDate =
            $end;


        $periodLabel =
            $start->format('d M Y')
            . ' – '
            . $end->format('d M Y');

    }


    /* ============================================================
       NORMAL PERIOD
    ============================================================ */

    else {

        $periodDays =
            (int)$periodParam;


        if (
            !in_array(
                $periodDays,
                $allowedPeriods,
                true
            )
        ) {

            $periodDays =
                30;

        }


        $startDate =
            $today->modify(
                '-' . ($periodDays - 1) . ' days'
            );


        $endDate =
            $today;


        $periodLabel =
            'Last '
            . $periodDays
            . ' Days';

    }


    $startSql =
        $startDate->format('Y-m-d')
        . ' 00:00:00';


    $endSql =
        $endDate->format('Y-m-d')
        . ' 23:59:59';


    $safeStartSql =
        $conn->real_escape_string(
            $startSql
        );


    $safeEndSql =
        $conn->real_escape_string(
            $endSql
        );


    /* ============================================================
       ALL-TIME HISTORY
       
       For All Time we determine the first activity date.
    ============================================================ */

    if ($isAllTime) {

        $historySql = "

            SELECT
                MIN(activity_date) AS first_activity

            FROM (

                SELECT
                    MIN(check_in) AS activity_date

                FROM attendance


                UNION ALL


                SELECT
                    MIN(assigned_date) AS activity_date

                FROM member_workouts


                UNION ALL


                SELECT
                    MIN(record_date) AS activity_date

                FROM progress_records

            ) history

            WHERE activity_date IS NOT NULL

        ";


        $historyResult =
            $conn->query(
                $historySql
            );


        if (
            $historyResult &&
            (
                $historyRow =
                    $historyResult->fetch_assoc()
            ) &&
            !empty(
                $historyRow['first_activity']
            )
        ) {

            $firstActivity =
                new DateTimeImmutable(
                    substr(
                        $historyRow['first_activity'],
                        0,
                        10
                    )
                );


            $periodDays =
                max(
                    1,
                    $firstActivity->diff($today)->days + 1
                );


            $startSql =
                $firstActivity->format('Y-m-d')
                . ' 00:00:00';


            $safeStartSql =
                $conn->real_escape_string(
                    $startSql
                );

        }
        else {

            $periodDays =
                30;


            $startSql =
                $today
                    ->modify('-29 days')
                    ->format('Y-m-d')
                . ' 00:00:00';


            $safeStartSql =
                $conn->real_escape_string(
                    $startSql
                );

        }


        $endSql =
            $today->format('Y-m-d')
            . ' 23:59:59';


        $safeEndSql =
            $conn->real_escape_string(
                $endSql
            );

    }


    /* ============================================================
       MAIN TRAINER ANALYTICS QUERY
    ============================================================ */

    $sql = "

        WITH

        /* --------------------------------------------------------
           CURRENT ACTIVE MEMBERS
        -------------------------------------------------------- */

        active_members AS (

            SELECT

                id AS member_id,

                trainer_id

            FROM members

            WHERE status = 'active'

              AND trainer_id IS NOT NULL

        ),


        /* --------------------------------------------------------
           ATTENDANCE PER ACTIVE MEMBER
        -------------------------------------------------------- */

        member_attendance AS (

            SELECT

                am.trainer_id,

                am.member_id,

                COUNT(a.id) AS period_checkins

            FROM active_members am

            LEFT JOIN attendance a

                ON a.member_id = am.member_id

               AND a.check_in BETWEEN
                    '{$safeStartSql}'
                    AND
                    '{$safeEndSql}'

            GROUP BY

                am.trainer_id,

                am.member_id

        ),


        /* --------------------------------------------------------
           ALL-TIME ATTENDANCE CONSISTENCY

           For All Time, a simple ever-attended percentage is
           misleading. Instead, for each active member we calculate:

           months with at least one check-in
           --------------------------------
           months since the member joined

           The trainer score is the average of those member-level
           consistency percentages.
        -------------------------------------------------------- */

        all_time_attendance_member AS (

            SELECT

                am.trainer_id,
                am.member_id,

                GREATEST(
                    1,
                    TIMESTAMPDIFF(
                        MONTH,
                        m.joined_date,
                        CURDATE()
                    ) + 1
                ) AS active_months,

                COUNT(
                    DISTINCT DATE_FORMAT(
                        a.check_in,
                        '%Y-%m'
                    )
                ) AS attended_months

            FROM active_members am

            INNER JOIN members m
                ON m.id = am.member_id

            LEFT JOIN attendance a
                ON a.member_id = am.member_id
               AND a.check_in <= '{$safeEndSql}'

            GROUP BY
                am.trainer_id,
                am.member_id,
                m.joined_date

        ),

        all_time_attendance_metrics AS (

            SELECT

                trainer_id,

                AVG(
                    LEAST(
                        (
                            attended_months
                            /
                            active_months
                        ) * 100.0,
                        100.0
                    )
                ) AS attendance_consistency_score

            FROM all_time_attendance_member

            GROUP BY trainer_id

        ),

        /* --------------------------------------------------------
           ATTENDANCE METRICS
        -------------------------------------------------------- */

        attendance_metrics AS (

            SELECT

                trainer_id,

                SUM(period_checkins)
                    AS total_checkins,

                SUM(
                    CASE
                        WHEN period_checkins > 0
                        THEN 1
                        ELSE 0
                    END
                ) AS members_attended

            FROM member_attendance

            GROUP BY trainer_id

        ),


        /* --------------------------------------------------------
           WORKOUT METRICS
           
           IMPORTANT:
           Workout ownership is determined through:

           trainers
              ↓
           workout_plans.trainer_id
              ↓
           member_workouts.workout_plan_id

           Only workouts belonging to the trainer's
           currently active members are counted.
        -------------------------------------------------------- */

        workout_metrics AS (

            SELECT

                am.trainer_id,

                COUNT(mw.id) AS total_workouts,

                SUM(
                    CASE
                        WHEN mw.status = 'completed'
                        THEN 1
                        ELSE 0
                    END
                ) AS completed_workouts

            FROM active_members am

            INNER JOIN workout_plans wp

                ON wp.trainer_id =
                    am.trainer_id

            INNER JOIN member_workouts mw

                ON mw.workout_plan_id =
                    wp.id

               AND mw.member_id =
                    am.member_id

               AND mw.assigned_date BETWEEN
                    '{$safeStartSql}'
                    AND
                    '{$safeEndSql}'

            WHERE mw.status IN (
                'assigned',
                'completed'
            )

            GROUP BY am.trainer_id

        ),


        /* --------------------------------------------------------
           PROGRESS METRICS
        -------------------------------------------------------- */

        progress_metrics AS (

            SELECT

                am.trainer_id,

                COUNT(
                    DISTINCT CASE
                        WHEN pr.member_id IS NOT NULL
                        THEN am.member_id
                        ELSE NULL
                    END
                ) AS members_with_progress

            FROM active_members am

            LEFT JOIN progress_records pr

                ON pr.member_id =
                    am.member_id

               AND pr.record_date BETWEEN
                    '{$safeStartSql}'
                    AND
                    '{$safeEndSql}'

            GROUP BY am.trainer_id

        ),


        /* --------------------------------------------------------
           ACTIVE MEMBER COUNTS
        -------------------------------------------------------- */

        member_counts AS (

            SELECT

                trainer_id,

                COUNT(*) AS active_members

            FROM active_members

            GROUP BY trainer_id

        )


        /* --------------------------------------------------------
           TRAINER MASTER DATA
        -------------------------------------------------------- */

        SELECT

            t.id AS trainer_id,

            u.full_name AS trainer_name,

            t.specialization,

            t.experience_years,

            t.joining_date,

            COALESCE(
                mc.active_members,
                0
            ) AS active_members,

            COALESCE(
                am.total_checkins,
                0
            ) AS total_checkins,

            COALESCE(
                am.members_attended,
                0
            ) AS members_attended,

            COALESCE(
                atam.attendance_consistency_score,
                0
            ) AS attendance_consistency_score,

            COALESCE(
                wm.total_workouts,
                0
            ) AS total_workouts,

            COALESCE(
                wm.completed_workouts,
                0
            ) AS completed_workouts,

            COALESCE(
                pm.members_with_progress,
                0
            ) AS members_with_progress

        FROM trainers t

        INNER JOIN users u

            ON u.id = t.user_id

        LEFT JOIN member_counts mc

            ON mc.trainer_id = t.id

        LEFT JOIN attendance_metrics am

            ON am.trainer_id = t.id

        LEFT JOIN all_time_attendance_metrics atam

            ON atam.trainer_id = t.id

        LEFT JOIN workout_metrics wm

            ON wm.trainer_id = t.id

        LEFT JOIN progress_metrics pm

            ON pm.trainer_id = t.id

        WHERE t.status = 'active'

          AND u.status = 'active'

        ORDER BY
            u.full_name ASC

    ";


    $result =
        $conn->query(
            $sql
        );


    if (!$result) {

        throw new RuntimeException(
            $conn->error
        );

    }


    /* ============================================================
       TRAINER RATINGS
       
       Ratings remain all-time.
    ============================================================ */

    $ratings = [];


    try {

        $ratingResult =
            $conn->query("

                SELECT

                    trainer_id,

                    AVG(rating)
                        AS average_rating,

                    COUNT(*)
                        AS review_count

                FROM trainer_reviews

                GROUP BY trainer_id

            ");


        if ($ratingResult) {

            while (
                $row =
                    $ratingResult->fetch_assoc()
            ) {

                $ratings[
                    (int)$row['trainer_id']
                ] = [

                    'average_rating' =>
                        (float)$row['average_rating'],

                    'review_count' =>
                        (int)$row['review_count']

                ];

            }

        }

    }
    catch (Throwable $ratingError) {

        $ratings = [];

    }


    /* ============================================================
       REAL MEMBER PHYSICAL PROGRESS
       ============================================================

       For every active member:
       - latest measurement is the newest record on/before the
         selected period end date
       - previous measurement is the record immediately before it
       - the latest measurement must fall inside the selected period
         to count as current progress
       - score starts at 50 (neutral)
       - positive percentage change moves the score above 50
       - negative percentage change moves it below 50
       - 5% positive change reaches 100
       - 5% negative change reaches 0
       - weight direction depends on the member's goal
       - body fat / waist: lower is positive
       - muscle mass: higher is positive
       - muscle-gain goals also use chest, arms and thighs
    ============================================================ */

    $trainerProgressScores = [];
    $trainerProgressMembers = [];

    $progressSql = "

        WITH ranked_progress AS (

            SELECT

                m.trainer_id,
                m.id AS member_id,

                pr.id AS progress_id,
                pr.record_date,

                pr.weight,
                pr.body_fat,
                pr.muscle_mass,
                pr.chest,
                pr.waist,
                pr.arms,
                pr.thighs,

                ROW_NUMBER() OVER (
                    PARTITION BY m.id
                    ORDER BY pr.record_date DESC, pr.id DESC
                ) AS rn

            FROM members m

            INNER JOIN progress_records pr
                ON pr.member_id = m.id

            WHERE m.status = 'active'
              AND m.trainer_id IS NOT NULL
              AND pr.record_date <= '{$safeEndSql}'

        ),

        latest_goals AS (

            SELECT

                mw.member_id,
                wp.goal,

                ROW_NUMBER() OVER (
                    PARTITION BY mw.member_id
                    ORDER BY mw.assigned_date DESC, mw.id DESC
                ) AS rn

            FROM member_workouts mw

            INNER JOIN workout_plans wp
                ON wp.id = mw.workout_plan_id

        )

        SELECT

            rp.trainer_id,
            rp.member_id,

            MAX(
                CASE
                    WHEN rp.rn = 1
                    THEN rp.record_date
                END
            ) AS latest_record_date,

            MAX(
                CASE
                    WHEN rp.rn = 1
                    THEN rp.weight
                END
            ) AS latest_weight,

            MAX(
                CASE
                    WHEN rp.rn = 1
                    THEN rp.body_fat
                END
            ) AS latest_body_fat,

            MAX(
                CASE
                    WHEN rp.rn = 1
                    THEN rp.muscle_mass
                END
            ) AS latest_muscle_mass,

            MAX(
                CASE
                    WHEN rp.rn = 1
                    THEN rp.chest
                END
            ) AS latest_chest,

            MAX(
                CASE
                    WHEN rp.rn = 1
                    THEN rp.waist
                END
            ) AS latest_waist,

            MAX(
                CASE
                    WHEN rp.rn = 1
                    THEN rp.arms
                END
            ) AS latest_arms,

            MAX(
                CASE
                    WHEN rp.rn = 1
                    THEN rp.thighs
                END
            ) AS latest_thighs,

            MAX(
                CASE
                    WHEN rp.rn = 2
                    THEN rp.weight
                END
            ) AS previous_weight,

            MAX(
                CASE
                    WHEN rp.rn = 2
                    THEN rp.body_fat
                END
            ) AS previous_body_fat,

            MAX(
                CASE
                    WHEN rp.rn = 2
                    THEN rp.muscle_mass
                END
            ) AS previous_muscle_mass,

            MAX(
                CASE
                    WHEN rp.rn = 2
                    THEN rp.chest
                END
            ) AS previous_chest,

            MAX(
                CASE
                    WHEN rp.rn = 2
                    THEN rp.waist
                END
            ) AS previous_waist,

            MAX(
                CASE
                    WHEN rp.rn = 2
                    THEN rp.arms
                END
            ) AS previous_arms,

            MAX(
                CASE
                    WHEN rp.rn = 2
                    THEN rp.thighs
                END
            ) AS previous_thighs,

            COALESCE(
                MAX(
                    CASE
                        WHEN lg.rn = 1
                        THEN lg.goal
                    END
                ),
                ''
            ) AS member_goal

        FROM ranked_progress rp

        LEFT JOIN latest_goals lg
            ON lg.member_id = rp.member_id
           AND lg.rn = 1

        WHERE rp.rn <= 2

        GROUP BY
            rp.trainer_id,
            rp.member_id

    ";

    $progressResult =
        $conn->query($progressSql);

    if (!$progressResult) {

        throw new RuntimeException(
            'Unable to calculate trainer member progress: '
            . $conn->error
        );

    }


    /*
     * Convert a percentage change into a 0–100 score.
     *
     * 0% change  = 50
     * +5% change = 100
     * -5% change = 0
     */
    $calculateMetricScore = static function (
        $previous,
        $latest,
        string $direction
    ): ?float {

        if (
            $previous === null
            ||
            $latest === null
            ||
            (float)$previous == 0.0
        ) {
            return null;
        }

        $previousValue = (float)$previous;
        $latestValue = (float)$latest;

        $changePercent =
            (
                ($latestValue - $previousValue)
                /
                abs($previousValue)
            ) * 100.0;

        if ($direction === 'lower') {
            $improvementPercent = -$changePercent;
        } else {
            $improvementPercent = $changePercent;
        }

        return max(
            0.0,
            min(
                100.0,
                50.0 + ($improvementPercent * 10.0)
            )
        );

    };


    while (
        $progressRow =
            $progressResult->fetch_assoc()
    ) {

        $trainerId =
            (int)$progressRow['trainer_id'];

        $latestDate =
            $progressRow['latest_record_date'] ?? null;

        /*
         * Only a measurement made during the selected period
         * counts as current trainer progress.
         */
        if (
            empty($latestDate)
            ||
            $latestDate < $startDate->format('Y-m-d')
            ||
            $latestDate > $endDate->format('Y-m-d')
        ) {
            continue;
        }


        /*
         * Two measurements are required for a real comparison.
         */
        if (
            $progressRow['previous_weight'] === null
            &&
            $progressRow['previous_body_fat'] === null
            &&
            $progressRow['previous_muscle_mass'] === null
            &&
            $progressRow['previous_chest'] === null
            &&
            $progressRow['previous_waist'] === null
            &&
            $progressRow['previous_arms'] === null
            &&
            $progressRow['previous_thighs'] === null
        ) {
            continue;
        }


        $goal =
            strtolower(
                trim(
                    (string)(
                        $progressRow['member_goal'] ?? ''
                    )
                )
            );

        $isFatLossGoal =
            strpos($goal, 'fat loss') !== false
            ||
            strpos($goal, 'weight loss') !== false
            ||
            strpos($goal, 'lose weight') !== false;

        $isMuscleGainGoal =
            strpos($goal, 'muscle gain') !== false
            ||
            strpos($goal, 'muscle building') !== false
            ||
            strpos($goal, 'bulking') !== false;


        $metricScores = [];


        /*
         * Weight is goal-dependent.
         */
        if ($isFatLossGoal) {

            $score =
                $calculateMetricScore(
                    $progressRow['previous_weight'],
                    $progressRow['latest_weight'],
                    'lower'
                );

            if ($score !== null) {
                $metricScores[] = $score;
            }

        } elseif ($isMuscleGainGoal) {

            $score =
                $calculateMetricScore(
                    $progressRow['previous_weight'],
                    $progressRow['latest_weight'],
                    'higher'
                );

            if ($score !== null) {
                $metricScores[] = $score;
            }
        }


        /*
         * Body composition.
         */
        $score =
            $calculateMetricScore(
                $progressRow['previous_body_fat'],
                $progressRow['latest_body_fat'],
                'lower'
            );

        if ($score !== null) {
            $metricScores[] = $score;
        }


        $score =
            $calculateMetricScore(
                $progressRow['previous_muscle_mass'],
                $progressRow['latest_muscle_mass'],
                'higher'
            );

        if ($score !== null) {
            $metricScores[] = $score;
        }


        /*
         * Waist is useful for fat-loss/general goals.
         */
        if (!$isMuscleGainGoal) {

            $score =
                $calculateMetricScore(
                    $progressRow['previous_waist'],
                    $progressRow['latest_waist'],
                    'lower'
                );

            if ($score !== null) {
                $metricScores[] = $score;
            }
        }


        /*
         * Muscle-gain body measurements.
         */
        if ($isMuscleGainGoal) {

            foreach (
                [
                    ['previous_chest', 'latest_chest'],
                    ['previous_arms', 'latest_arms'],
                    ['previous_thighs', 'latest_thighs']
                ]
                as $measurement
            ) {

                $score =
                    $calculateMetricScore(
                        $progressRow[$measurement[0]],
                        $progressRow[$measurement[1]],
                        'higher'
                    );

                if ($score !== null) {
                    $metricScores[] = $score;
                }

            }

        }


        /*
         * If no goal exists, use body-composition metrics.
         */
        if (
            !$isFatLossGoal
            &&
            !$isMuscleGainGoal
            &&
            count($metricScores) === 0
        ) {

            foreach (
                [
                    ['previous_body_fat', 'latest_body_fat', 'lower'],
                    ['previous_muscle_mass', 'latest_muscle_mass', 'higher'],
                    ['previous_waist', 'latest_waist', 'lower']
                ]
                as $measurement
            ) {

                $score =
                    $calculateMetricScore(
                        $progressRow[$measurement[0]],
                        $progressRow[$measurement[1]],
                        $measurement[2]
                    );

                if ($score !== null) {
                    $metricScores[] = $score;
                }

            }

        }


        if (count($metricScores) === 0) {
            continue;
        }


        $memberProgressScore =
            array_sum($metricScores)
            /
            count($metricScores);


        if (!isset($trainerProgressScores[$trainerId])) {

            $trainerProgressScores[$trainerId] = [
                'sum' => 0.0,
                'count' => 0
            ];

        }

        $trainerProgressScores[$trainerId]['sum']
            += $memberProgressScore;

        $trainerProgressScores[$trainerId]['count']++;

    }


    $trainerProgressMemberCounts = [];

    foreach (
        $trainerProgressScores
        as $trainerId => $progressSummary
    ) {

        $trainerProgressMemberCounts[$trainerId] =
            (int)$progressSummary['count'];

        if ($progressSummary['count'] > 0) {

            $trainerProgressScores[$trainerId] =
                $progressSummary['sum']
                /
                $progressSummary['count'];

        } else {

            $trainerProgressScores[$trainerId] =
                50.0;

        }

    }


    /* ============================================================
       BUILD RESPONSE
    ============================================================ */

    $data = [];


    while (
        $row =
            $result->fetch_assoc()
    ) {

        $trainerId =
            (int)$row['trainer_id'];


        $activeMembers =
            (int)$row['active_members'];


        $totalCheckins =
            (int)$row['total_checkins'];


        $membersAttended =
            (int)$row['members_attended'];


        $attendanceConsistencyScore =
            (float)$row['attendance_consistency_score'];


        $totalWorkouts =
            (int)$row['total_workouts'];


        $completedWorkouts =
            (int)$row['completed_workouts'];


        $membersWithProgress =
            (int)$row[
                'members_with_progress'
            ];


        /* ========================================================
           ATTENDANCE SCORE
        ========================================================

           For fixed periods:
           percentage of the trainer's current active members
           who attended at least once during the selected period.

           For All Time:
           average member-level monthly attendance consistency.
           This avoids giving 100% merely because every member
           attended once at some point in their history.
        ======================================================== */

        if ($isAllTime) {

            /*
             * All Time:
             * average percentage of months in which each active
             * member actually attended at least once.
             */
            $attendanceScore =
                max(
                    0.0,
                    min(
                        100.0,
                        $attendanceConsistencyScore
                    )
                );

        } else {

            /*
             * Fixed periods:
             * percentage of active members who attended at least once
             * during the selected period.
             */
            $attendanceScore =
                $activeMembers > 0

                    ? min(
                        (
                            $membersAttended
                            /
                            $activeMembers
                        ) * 100.0,
                        100.0
                    )

                    : 0.0;

        }


        /* ========================================================
           WORKOUT SCORE
           
           Completed / actionable workouts
           
           Paused workouts are excluded.
        ======================================================== */

        $workoutScore =
            $totalWorkouts > 0

                ? min(
                    (
                        $completedWorkouts
                        /
                        $totalWorkouts
                    ) * 100.0,
                    100.0
                )

                : 0.0;


        /* ========================================================
           REAL MEMBER PROGRESS SCORE
        ========================================================

           This is the average physical-progress score of the
           trainer's members who have a comparable latest/previous
           measurement in the selected period.

           50 = neutral
           >50 = improvement
           <50 = regression

           If no comparable member progress is available, 50 is
           used as a neutral score rather than falsely reporting
           0% physical progress.
        ======================================================== */

        $progressScore =
            $trainerProgressScores[$trainerId]
            ??
            50.0;

        $progressScore =
            max(
                0.0,
                min(
                    100.0,
                    (float)$progressScore
                )
            );


        /* ========================================================
           RATING SCORE
        ======================================================== */

        $averageRating =
            $ratings[$trainerId][
                'average_rating'
            ] ?? 0.0;


        $reviewCount =
            $ratings[$trainerId][
                'review_count'
            ] ?? 0;


        $ratingScore =
            min(
                (
                    $averageRating
                    /
                    5.0
                ) * 100.0,
                100.0
            );


        /* ========================================================
           COMPOSITE PERFORMANCE
        ======================================================== */

        $performanceScore =

            ($attendanceScore * 0.30)

            +

            ($workoutScore * 0.30)

            +

            ($progressScore * 0.20)

            +

            ($ratingScore * 0.20);


        $performanceScore =
            max(
                0.0,
                min(
                    100.0,
                    $performanceScore
                )
            );


        /* ========================================================
           WORKLOAD
        ======================================================== */

        if ($activeMembers >= 9) {

            $workload =
                'HIGH';

        }
        elseif ($activeMembers >= 6) {

            $workload =
                'MEDIUM';

        }
        else {

            $workload =
                'LOW';

        }


        /* ========================================================
           RESPONSE ROW
        ======================================================== */

        $data[] = [

            'trainer_id' =>
                $trainerId,

            'trainer_name' =>
                $row['trainer_name'],

            'specialization' =>
                $row['specialization'],

            'experience_years' =>
                (int)$row['experience_years'],

            'joining_date' =>
                $row['joining_date'],


            'active_members' =>
                $activeMembers,


            'total_checkins' =>
                $totalCheckins,


            'members_attended' =>
                $membersAttended,


            'attendance_score' =>
                round(
                    $attendanceScore,
                    2
                ),


            'total_workouts' =>
                $totalWorkouts,


            'completed_workouts' =>
                $completedWorkouts,


            'workout_score' =>
                round(
                    $workoutScore,
                    2
                ),


            'members_with_progress' =>
                $membersWithProgress,


            'progress_score' =>
                round(
                    $progressScore,
                    2
                ),

            'progress_members_compared' =>
                $trainerProgressMemberCounts[$trainerId]
                ?? 0,


            'average_rating' =>
                round(
                    $averageRating,
                    2
                ),


            'rating' =>
                round(
                    $averageRating,
                    2
                ),


            'review_count' =>
                $reviewCount,


            'rating_score' =>
                round(
                    $ratingScore,
                    2
                ),


            'performance_score' =>
                round(
                    $performanceScore,
                    2
                ),


            'workload' =>
                $workload

        ];

    }


    /* ============================================================
       SORT BY PERFORMANCE
    ============================================================ */

    usort(
        $data,
        static function (
            array $a,
            array $b
        ): int {

            return
                $b['performance_score']
                <=>
                $a['performance_score'];

        }
    );


    /* ============================================================
       JSON RESPONSE
    ============================================================ */

    echo json_encode(

        [

            'success' =>
                true,


            'generated_at' =>
                date(
                    'Y-m-d H:i:s'
                ),


            'period' => [

                'key' =>
                    $isAllTime

                        ? 'all'

                        : (
                            $periodParam === 'custom'

                                ? 'custom'

                                : (string)$periodDays
                        ),


                'label' =>
                    $periodLabel,


                'start_date' =>
                    $startDate->format(
                        'Y-m-d'
                    ),


                'end_date' =>
                    $endDate->format(
                        'Y-m-d'
                    ),


                'days' =>
                    $periodDays

            ],


            'rules' => [

                'attendance_method' =>
                    $isAllTime
                        ? 'average_member_monthly_attendance_consistency'
                        : 'percentage_of_active_members_with_at_least_one_checkin',

                'attendance_weight' =>
                    0.30,

                'workout_weight' =>
                    0.30,

                'progress_method' =>
                    'average_actual_member_measurement_improvement',

                'progress_neutral_score' =>
                    50,

                'progress_weight' =>
                    0.20,

                'rating_weight' =>
                    0.20,

                'high_workload_min_members' =>
                    9,

                'medium_workload_min_members' =>
                    6,

                'workload_is_current_state' =>
                    true,

                'rating_is_all_time' =>
                    true

            ],


            'data' =>
                $data

        ],

        JSON_PRETTY_PRINT

    );


}
catch (Throwable $e) {

    http_response_code(500);


    echo json_encode(

        [

            'success' =>
                false,


            'message' =>
                'Unable to load trainer performance analytics.',


            'error' =>
                $e->getMessage()

        ],

        JSON_PRETTY_PRINT

    );

}