<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';


try {

    /*
    |--------------------------------------------------------------------------
    | MEMBER ENGAGEMENT ANALYTICS
    |--------------------------------------------------------------------------
    |
    | Engagement Score:
    |
    | Attendance          = 50%
    | Workout Completion  = 30%
    | Progress Tracking   = 20%
    |
    | Attendance benchmark:
    | 8 visits in 30 days = 100%
    |
    */


    $sql = "

        /* ================================================================
           ACTIVE MEMBERS
        ================================================================ */

        WITH active_members AS (

            SELECT
                m.id AS member_id,
                u.full_name AS member_name

            FROM members m

            INNER JOIN users u
                ON u.id = m.user_id

            WHERE m.status = 'active'
        ),


        /* ================================================================
           ATTENDANCE METRICS
        ================================================================ */

        attendance_metrics AS (

            SELECT

                m.id AS member_id,

                COUNT(
                    CASE
                        WHEN a.check_in >= DATE_SUB(
                            CURDATE(),
                            INTERVAL 30 DAY
                        )
                        THEN a.id
                    END
                ) AS visits_30_days,

                MAX(a.check_in) AS last_visit

            FROM members m

            LEFT JOIN attendance a
                ON a.member_id = m.id

            WHERE m.status = 'active'

            GROUP BY m.id
        ),


        /* ================================================================
           WORKOUT METRICS
        ================================================================ */

        workout_metrics AS (

            SELECT

                m.id AS member_id,

                COUNT(mw.id) AS total_workouts,

                SUM(
                    CASE
                        WHEN mw.status = 'completed'
                        THEN 1
                        ELSE 0
                    END
                ) AS completed_workouts

            FROM members m

            LEFT JOIN member_workouts mw
                ON mw.member_id = m.id

            WHERE m.status = 'active'

            GROUP BY m.id
        ),


        /* ================================================================
           PROGRESS METRICS
        ================================================================ */

        progress_metrics AS (

            SELECT

                m.id AS member_id,

                COUNT(pr.id) AS progress_records,

                (
                    SELECT pr1.weight
                    FROM progress_records pr1
                    WHERE pr1.member_id = m.id
                    ORDER BY pr1.record_date DESC, pr1.id DESC
                    LIMIT 1
                ) AS latest_weight,

                (
                    SELECT pr1.body_fat
                    FROM progress_records pr1
                    WHERE pr1.member_id = m.id
                    ORDER BY pr1.record_date DESC, pr1.id DESC
                    LIMIT 1
                ) AS latest_body_fat,

                (
                    SELECT pr1.muscle_mass
                    FROM progress_records pr1
                    WHERE pr1.member_id = m.id
                    ORDER BY pr1.record_date DESC, pr1.id DESC
                    LIMIT 1
                ) AS latest_muscle_mass,

                (
                    SELECT pr1.chest
                    FROM progress_records pr1
                    WHERE pr1.member_id = m.id
                    ORDER BY pr1.record_date DESC, pr1.id DESC
                    LIMIT 1
                ) AS latest_chest,

                (
                    SELECT pr1.waist
                    FROM progress_records pr1
                    WHERE pr1.member_id = m.id
                    ORDER BY pr1.record_date DESC, pr1.id DESC
                    LIMIT 1
                ) AS latest_waist,

                (
                    SELECT pr1.arms
                    FROM progress_records pr1
                    WHERE pr1.member_id = m.id
                    ORDER BY pr1.record_date DESC, pr1.id DESC
                    LIMIT 1
                ) AS latest_arms,

                (
                    SELECT pr1.thighs
                    FROM progress_records pr1
                    WHERE pr1.member_id = m.id
                    ORDER BY pr1.record_date DESC, pr1.id DESC
                    LIMIT 1
                ) AS latest_thighs,

                (
                    SELECT pr2.weight
                    FROM progress_records pr2
                    WHERE pr2.member_id = m.id
                    ORDER BY pr2.record_date DESC, pr2.id DESC
                    LIMIT 1 OFFSET 1
                ) AS previous_weight,

                (
                    SELECT pr2.body_fat
                    FROM progress_records pr2
                    WHERE pr2.member_id = m.id
                    ORDER BY pr2.record_date DESC, pr2.id DESC
                    LIMIT 1 OFFSET 1
                ) AS previous_body_fat,

                (
                    SELECT pr2.muscle_mass
                    FROM progress_records pr2
                    WHERE pr2.member_id = m.id
                    ORDER BY pr2.record_date DESC, pr2.id DESC
                    LIMIT 1 OFFSET 1
                ) AS previous_muscle_mass,

                (
                    SELECT pr2.chest
                    FROM progress_records pr2
                    WHERE pr2.member_id = m.id
                    ORDER BY pr2.record_date DESC, pr2.id DESC
                    LIMIT 1 OFFSET 1
                ) AS previous_chest,

                (
                    SELECT pr2.waist
                    FROM progress_records pr2
                    WHERE pr2.member_id = m.id
                    ORDER BY pr2.record_date DESC, pr2.id DESC
                    LIMIT 1 OFFSET 1
                ) AS previous_waist,

                (
                    SELECT pr2.arms
                    FROM progress_records pr2
                    WHERE pr2.member_id = m.id
                    ORDER BY pr2.record_date DESC, pr2.id DESC
                    LIMIT 1 OFFSET 1
                ) AS previous_arms,

                (
                    SELECT pr2.thighs
                    FROM progress_records pr2
                    WHERE pr2.member_id = m.id
                    ORDER BY pr2.record_date DESC, pr2.id DESC
                    LIMIT 1 OFFSET 1
                ) AS previous_thighs

            FROM members m

            LEFT JOIN progress_records pr
                ON pr.member_id = m.id

            WHERE m.status = 'active'

            GROUP BY m.id
        ),

        member_goals AS (

            SELECT

                m.id AS member_id,

                COALESCE(
                    m.fitness_goal,
                    ''
                ) AS member_goal

            FROM members m

            WHERE m.status = 'active'
        )


        /* ================================================================
           FINAL MEMBER DATA
        ================================================================ */

        SELECT

            am.member_id,

            am.member_name,


            /* Attendance */

            COALESCE(
                att.visits_30_days,
                0
            ) AS visits_30_days,


            att.last_visit,


            /* Workouts */

            COALESCE(
                w.total_workouts,
                0
            ) AS total_workouts,


            COALESCE(
                w.completed_workouts,
                0
            ) AS completed_workouts,


            /* Progress */

            COALESCE(
                p.progress_records,
                0
            ) AS progress_records,

            p.latest_weight,
            p.latest_body_fat,
            p.latest_muscle_mass,
            p.latest_chest,
            p.latest_waist,
            p.latest_arms,
            p.latest_thighs,

            p.previous_weight,
            p.previous_body_fat,
            p.previous_muscle_mass,
            p.previous_chest,
            p.previous_waist,
            p.previous_arms,
            p.previous_thighs,

            COALESCE(
                g.member_goal,
                ''
            ) AS member_goal


        FROM active_members am


        LEFT JOIN attendance_metrics att
            ON att.member_id = am.member_id


        LEFT JOIN workout_metrics w
            ON w.member_id = am.member_id


        LEFT JOIN progress_metrics p
            ON p.member_id = am.member_id

        LEFT JOIN member_goals g
            ON g.member_id = am.member_id


        ORDER BY am.member_name ASC

    ";


    $result = $conn->query($sql);


    if (!$result) {

        throw new Exception(
            $conn->error
        );

    }


    $members = [];


    $highEngagement = 0;

    $mediumEngagement = 0;

    $lowEngagement = 0;


    $totalEngagementScore = 0;


    /*
    |--------------------------------------------------------------------------
    | PROCESS MEMBERS
    |--------------------------------------------------------------------------
    */

    while ($row = $result->fetch_assoc()) {


        $memberId =
            (int) $row['member_id'];


        $memberName =
            $row['member_name'];


        $visits30Days =
            (int) $row['visits_30_days'];


        $totalWorkouts =
            (int) $row['total_workouts'];


        $completedWorkouts =
            (int) $row['completed_workouts'];


        $progressRecords =
            (int) $row['progress_records'];


        /*
        |--------------------------------------------------------------------------
        | REAL PROGRESS MEASUREMENTS
        |--------------------------------------------------------------------------
        */

        $latestWeight = $row['latest_weight'] !== null
            ? (float) $row['latest_weight']
            : null;

        $latestBodyFat = $row['latest_body_fat'] !== null
            ? (float) $row['latest_body_fat']
            : null;

        $latestMuscleMass = $row['latest_muscle_mass'] !== null
            ? (float) $row['latest_muscle_mass']
            : null;

        $latestChest = $row['latest_chest'] !== null
            ? (float) $row['latest_chest']
            : null;

        $latestWaist = $row['latest_waist'] !== null
            ? (float) $row['latest_waist']
            : null;

        $latestArms = $row['latest_arms'] !== null
            ? (float) $row['latest_arms']
            : null;

        $latestThighs = $row['latest_thighs'] !== null
            ? (float) $row['latest_thighs']
            : null;

        $previousWeight = $row['previous_weight'] !== null
            ? (float) $row['previous_weight']
            : null;

        $previousBodyFat = $row['previous_body_fat'] !== null
            ? (float) $row['previous_body_fat']
            : null;

        $previousMuscleMass = $row['previous_muscle_mass'] !== null
            ? (float) $row['previous_muscle_mass']
            : null;

        $previousChest = $row['previous_chest'] !== null
            ? (float) $row['previous_chest']
            : null;

        $previousWaist = $row['previous_waist'] !== null
            ? (float) $row['previous_waist']
            : null;

        $previousArms = $row['previous_arms'] !== null
            ? (float) $row['previous_arms']
            : null;

        $previousThighs = $row['previous_thighs'] !== null
            ? (float) $row['previous_thighs']
            : null;

        $memberGoal = strtolower(
            trim((string) ($row['member_goal'] ?? ''))
        );

        /*
         * Fitness goals come from members.fitness_goal.
         * Current supported values:
         * weight_loss, muscle_gain, general_fitness
         */
        $isFatLossGoal =
            $memberGoal === 'weight_loss'
            ||
            strpos($memberGoal, 'weight loss') !== false
            ||
            strpos($memberGoal, 'fat loss') !== false;

        $isMuscleGainGoal =
            $memberGoal === 'muscle_gain'
            ||
            strpos($memberGoal, 'muscle gain') !== false
            ||
            strpos($memberGoal, 'muscle building') !== false
            ||
            strpos($memberGoal, 'bulking') !== false;

        $isGeneralFitnessGoal =
            $memberGoal === 'general_fitness'
            ||
            strpos($memberGoal, 'general fitness') !== false;


        /*
         |--------------------------------------------------------------------------
         | ATTENDANCE SCORE
         |--------------------------------------------------------------------------
         |
         | 8 visits in 30 days = 100%
         |
         */

        $attendanceScore = min(

            ($visits30Days / 8) * 100,

            100

        );


        /*
        |--------------------------------------------------------------------------
        | WORKOUT COMPLETION
        |--------------------------------------------------------------------------
        */

        if ($totalWorkouts > 0) {

            $workoutScore = (

                $completedWorkouts
                /
                $totalWorkouts

            ) * 100;

        } else {

            $workoutScore = 0;

        }


        /*
        |--------------------------------------------------------------------------
        | PROGRESS SCORE
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Progress is NOT based on number of records.
        |
        | Each comparable measurement starts at 50 (neutral):
        |
        | 50 = no meaningful change
        | >50 = improvement
        | <50 = regression
        |
        | The score uses the SIZE of the change, so a tiny improvement
        | does not automatically become 100%.
        |
        | If there is no previous measurement, the metric is ignored.
        |
        */

        $metricScores = [];

        $addMetricScore = function (
            $previous,
            $latest,
            $direction,
            $sensitivity
        ) use (&$metricScores) {

            if (
                $previous === null
                ||
                $latest === null
                ||
                $previous == 0
            ) {
                return;
            }

            $changePercent =
                (($latest - $previous) / abs($previous)) * 100;

            if ($direction === 'lower_is_better') {
                $improvementPercent = -$changePercent;
            } else {
                $improvementPercent = $changePercent;
            }

            /*
             * 0% change = 50 score.
             * A 5% positive change reaches 100 when sensitivity=10.
             * A 5% negative change reaches 0.
             */
            $score =
                50 + ($improvementPercent * $sensitivity);

            $score = max(
                0,
                min(100, $score)
            );

            $metricScores[] = $score;
        };


        /*
         * Weight is goal-dependent.
         * We do NOT automatically assume weight loss is good.
         */
        if ($isFatLossGoal) {

            $addMetricScore(
                $previousWeight,
                $latestWeight,
                'lower_is_better',
                10
            );

        } elseif ($isMuscleGainGoal) {

            $addMetricScore(
                $previousWeight,
                $latestWeight,
                'higher_is_better',
                10
            );
        }


        /*
         * Goal-aware body composition.
         *
         * Weight is handled separately above because its direction
         * depends on the member's fitness goal.
         *
         * Body fat is useful for weight-loss and general-fitness goals.
         * For muscle gain, a stable/lower body-fat trend is still useful,
         * but it should not dominate the score.
         */
        if ($isFatLossGoal || $isGeneralFitnessGoal) {

            $addMetricScore(
                $previousBodyFat,
                $latestBodyFat,
                'lower_is_better',
                10
            );

        } elseif ($isMuscleGainGoal) {

            $addMetricScore(
                $previousBodyFat,
                $latestBodyFat,
                'lower_is_better',
                5
            );
        }


        /*
         * Muscle mass is especially important for muscle-gain and
         * general-fitness goals.
         */
        if ($isMuscleGainGoal || $isGeneralFitnessGoal) {

            $addMetricScore(
                $previousMuscleMass,
                $latestMuscleMass,
                'higher_is_better',
                10
            );

        } elseif ($isFatLossGoal) {

            /*
             * Muscle retention is still useful during weight loss,
             * but it carries less weight than the fat-loss indicators.
             */
            $addMetricScore(
                $previousMuscleMass,
                $latestMuscleMass,
                'higher_is_better',
                5
            );
        }


        /*
         * Waist measurement is most meaningful for weight-loss and
         * general-fitness goals.
         */
        if ($isFatLossGoal || $isGeneralFitnessGoal) {

            $addMetricScore(
                $previousWaist,
                $latestWaist,
                'lower_is_better',
                10
            );
        }


        /*
         * Muscle-gain measurements:
         * chest, arms and thighs increasing can support the goal.
         */
        if ($isMuscleGainGoal) {

            $addMetricScore(
                $previousChest,
                $latestChest,
                'higher_is_better',
                10
            );

            $addMetricScore(
                $previousArms,
                $latestArms,
                'higher_is_better',
                10
            );

            $addMetricScore(
                $previousThighs,
                $latestThighs,
                'higher_is_better',
                10
            );
        }


        /*
         * If no fitness goal has been set, do not pretend that a
         * physical change is goal-aligned.
         *
         * Comparable records are kept neutral at 50 so the member is
         * not unfairly penalized. The API also exposes the goal and
         * compared_metrics so the UI can show that goal setup is needed.
         */
        if (
            !$isFatLossGoal
            &&
            !$isMuscleGainGoal
            &&
            !$isGeneralFitnessGoal
        ) {
            $metricScores = [];
        }


        /*
         * No comparable measurements:
         * neutral score rather than falsely reporting 0% progress.
         *
         * Completely missing progress records still remain 0.
         */
        /*
         * A score is only displayed when we have a valid fitness goal and
         * at least one comparable measurement. Otherwise progress is
         * genuinely unknown, not 50%. We still use a neutral 50 internally
         * for the engagement formula so missing progress data does not
         * unfairly penalize the member.
         */
        $progressScore = null;
        $progressScoreForEngagement = 50;

        if ($memberGoal !== '' && count($metricScores) > 0) {

            $progressScore =
                array_sum($metricScores)
                /
                count($metricScores);

            $progressScoreForEngagement = $progressScore;

        }


        /*
        |--------------------------------------------------------------------------
        | FINAL ENGAGEMENT SCORE
        |--------------------------------------------------------------------------
        */

        $engagementScore =

            ($attendanceScore * 0.50)

            +

            ($workoutScore * 0.30)

            +

            ($progressScoreForEngagement * 0.20);


        /*
        |--------------------------------------------------------------------------
        | SAFETY LIMIT
        |--------------------------------------------------------------------------
        */

        $engagementScore = max(

            0,

            min(

                100,

                $engagementScore

            )

        );


        $engagementScore =
            round(
                $engagementScore,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | DAYS SINCE LAST VISIT
        |--------------------------------------------------------------------------
        */

        if (!empty($row['last_visit'])) {

            $lastVisitTimestamp =
                strtotime(
                    $row['last_visit']
                );


            $daysSinceLastVisit = max(

                0,

                floor(

                    (
                        time()
                        -
                        $lastVisitTimestamp

                    ) / 86400

                )

            );

        } else {

            $daysSinceLastVisit = null;

        }


        /*
        |--------------------------------------------------------------------------
        | ENGAGEMENT LEVEL
        |--------------------------------------------------------------------------
        */

        if ($engagementScore >= 75) {

            $engagementLevel =
                'HIGH';

            $highEngagement++;

        }

        elseif ($engagementScore >= 50) {

            $engagementLevel =
                'MEDIUM';

            $mediumEngagement++;

        }

        else {

            $engagementLevel =
                'LOW';

            $lowEngagement++;

        }


        /*
        |--------------------------------------------------------------------------
        | INDICATORS
        |--------------------------------------------------------------------------
        */

        $indicators = [];


        if ($visits30Days < 4) {

            $indicators[] =
                'Low visit frequency';

        }


        if ($totalWorkouts === 0) {

            $indicators[] =
                'No workouts assigned';

        }

        elseif ($workoutScore < 50) {

            $indicators[] =
                'Low workout completion';

        }


        if ($progressRecords === 0) {

            $indicators[] =
                'No progress records';

        }

        elseif ($memberGoal === '') {

            $indicators[] =
                'Fitness goal not set';

        }

        elseif ($progressRecords === 1) {

            $indicators[] =
                'Need another measurement to track progress';

        }

        elseif ($progressScore < 45) {

            $indicators[] =
                'Progress trend needs attention';

        }


        if (

            $daysSinceLastVisit !== null

            &&

            $daysSinceLastVisit > 14

        ) {

            $indicators[] =
                'No recent visit';

        }


        if (empty($indicators)) {

            $indicators[] =
                'No major engagement issue';

        }


        /*
        |--------------------------------------------------------------------------
        | RECOMMENDED ACTION
        |--------------------------------------------------------------------------
        */

        if ($engagementLevel === 'LOW') {

            $recommendedAction =
                'Contact member and encourage regular gym activity';

        }

        elseif ($engagementLevel === 'MEDIUM') {

            $recommendedAction =
                'Monitor engagement and encourage consistent activity';

        }

        else {

            $recommendedAction =
                'Maintain regular engagement';

        }


        /*
        |--------------------------------------------------------------------------
        | MEMBER DATA
        |--------------------------------------------------------------------------
        */

        $members[] = [

            'member_id' =>
                $memberId,

            'member_name' =>
                $memberName,

            'visits_30_days' =>
                $visits30Days,

            'total_workouts' =>
                $totalWorkouts,

            'completed_workouts' =>
                $completedWorkouts,

            'workout_completion_rate' =>
                round(
                    $workoutScore,
                    2
                ),

            'progress_records' =>
                $progressRecords,

            'last_visit' =>
                $row['last_visit'],

            'days_since_last_visit' =>
                $daysSinceLastVisit,

            'attendance_score' =>
                round(
                    $attendanceScore,
                    2
                ),

            'progress_score' =>
                $progressScore !== null
                    ? round($progressScore, 2)
                    : null,

            'progress_status' =>
                $progressScore !== null
                    ? 'Goal-based progress calculated'
                    : ($memberGoal === ''
                        ? 'Fitness goal not set'
                        : 'Not enough comparable measurements'),

            'progress_measurements' => [
                'latest_weight' => $latestWeight,
                'previous_weight' => $previousWeight,
                'latest_body_fat' => $latestBodyFat,
                'previous_body_fat' => $previousBodyFat,
                'latest_muscle_mass' => $latestMuscleMass,
                'previous_muscle_mass' => $previousMuscleMass,
                'latest_chest' => $latestChest,
                'previous_chest' => $previousChest,
                'latest_waist' => $latestWaist,
                'previous_waist' => $previousWaist,
                'latest_arms' => $latestArms,
                'previous_arms' => $previousArms,
                'latest_thighs' => $latestThighs,
                'previous_thighs' => $previousThighs,
                'goal' => $memberGoal,
                'compared_metrics' => count($metricScores)
            ],

            'engagement_score' =>
                $engagementScore,

            'engagement_level' =>
                $engagementLevel,

            'indicators' =>
                $indicators,

            'recommended_action' =>
                $recommendedAction
        ];


        $totalEngagementScore +=
            $engagementScore;

    }


    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    $totalMembers =
        count($members);


    $averageEngagement =

        $totalMembers > 0

        ?

        round(

            $totalEngagementScore
            /
            $totalMembers,

            2

        )

        :

        0;


    /*
    |--------------------------------------------------------------------------
    | SORT — HIGHEST ENGAGEMENT FIRST
    |--------------------------------------------------------------------------
    */

    usort(

        $members,

        function ($a, $b) {

            return
                $b['engagement_score']
                <=>
                $a['engagement_score'];

        }

    );


    /*
    |--------------------------------------------------------------------------
    | TOP 5
    |--------------------------------------------------------------------------
    */

    $topEngaged =
        array_slice(
            $members,
            0,
            5
        );


    /*
    |--------------------------------------------------------------------------
    | LOW ENGAGEMENT MEMBERS
    |--------------------------------------------------------------------------
    */

    $attentionMembers =
        array_values(

            array_filter(

                $members,

                function ($member) {

                    return
                        $member['engagement_level']
                        === 'LOW';

                }

            )

        );


    /*
    |--------------------------------------------------------------------------
    | LIMIT ATTENTION LIST
    |--------------------------------------------------------------------------
    */

    $attentionMembers =
        array_slice(

            $attentionMembers,

            0,

            10

        );


    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode(

        [

            'success' => true,

            'data' => [

                'summary' => [

                    'total_active_members' =>
                        $totalMembers,

                    'high_engagement' =>
                        $highEngagement,

                    'medium_engagement' =>
                        $mediumEngagement,

                    'low_engagement' =>
                        $lowEngagement,

                    'average_engagement_score' =>
                        $averageEngagement

                ],

                'top_engaged_members' =>
                    $topEngaged,

                'attention_members' =>
                    $attentionMembers,

                'all_members' =>
                    $members

            ]

        ],

        JSON_PRETTY_PRINT

    );


}
catch (Throwable $e) {


    http_response_code(500);


    echo json_encode(

        [

            'success' => false,

            'error' =>
                $e->getMessage()

        ],

        JSON_PRETTY_PRINT

    );

}


$conn->close();