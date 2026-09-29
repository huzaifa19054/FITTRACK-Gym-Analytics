<?php

header("Content-Type: application/json");

require_once "../../config/db.php";

try {

    $sql = "
        SELECT
            rp.id,
            rp.member_id,
            rp.membership_id,
            u.full_name AS member_name,

            rp.risk_probability,
            rp.risk_percentage,
            rp.risk_level,

            rp.risk_reason_1,
            rp.risk_reason_2,
            rp.risk_reason_3,
            rp.recommended_action,

            rp.model_version,
            rp.prediction_date

        FROM retention_predictions rp

        INNER JOIN memberships ms
            ON ms.id = rp.membership_id
            AND ms.member_id = rp.member_id
            AND ms.status = 'active'
            AND ms.end_date >= CURDATE()

        INNER JOIN members m
            ON m.id = rp.member_id
            AND m.status = 'active'

        INNER JOIN users u
            ON u.id = m.user_id
            AND u.status = 'active'

        /*
         * Keep only the latest active membership
         * for each member.
         */
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

        /*
         * Keep only the latest prediction
         * for that member + membership.
         */
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

        ORDER BY rp.risk_percentage DESC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $predictions = [];

    while ($row = $result->fetch_assoc()) {

        $predictions[] = [

            "id" => (int) $row["id"],

            "member_id" => (int) $row["member_id"],

            "membership_id" => (int) $row["membership_id"],

            "member_name" => $row["member_name"],

            "risk_probability" =>
                (float) $row["risk_probability"],

            "risk_percentage" =>
                (float) $row["risk_percentage"],

            "risk_level" =>
                $row["risk_level"],

            "risk_reason_1" =>
                $row["risk_reason_1"],

            "risk_reason_2" =>
                $row["risk_reason_2"],

            "risk_reason_3" =>
                $row["risk_reason_3"],

            "recommended_action" =>
                $row["recommended_action"],

            "model_version" =>
                $row["model_version"],

            "prediction_date" =>
                $row["prediction_date"]
        ];
    }

    echo json_encode([

        "success" => true,

        "count" => count($predictions),

        "data" => $predictions

    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" => $e->getMessage()

    ]);
}