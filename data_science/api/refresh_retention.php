<?php

require_once "../../config/auth.php";
require_once "../../config/db.php";

header("Content-Type: application/json");

require_role('admin');

try {

    // Python executable
    $python = "C:\\Users\\Yousuf Traders\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";

    // Project paths
    $projectRoot = "C:\\xampp\\htdocs\\FitTrack-Gym-Complete";

    $pythonScript = $projectRoot . "\\data_science\\predict_retention.py";

    // Make sure files exist
    if (!file_exists($python)) {
        throw new Exception("Python executable not found.");
    }

    if (!file_exists($pythonScript)) {
        throw new Exception("Retention prediction script not found.");
    }

    // Run Python from project root because the script
    // uses relative paths such as data_science/models/...
    $command =
        'cd /d "' . $projectRoot . '" && ' .
        '"' . $python . '" "' . $pythonScript . '" 2>&1';

    $output = shell_exec($command);

    if ($output === null) {
        throw new Exception("Unable to execute Python prediction script.");
    }

    // Check whether prediction script completed successfully
    if (
        strpos($output, "Database connection closed.") === false
    ) {
        throw new Exception(
            "Prediction script did not complete successfully."
        );
    }

    // Get latest prediction count
    $result = $conn->query(
        "SELECT COUNT(*) AS total FROM retention_predictions"
    );

    if (!$result) {
        throw new Exception($conn->error);
    }

    $row = $result->fetch_assoc();

    echo json_encode([
        "success" => true,
        "message" => "Retention predictions refreshed successfully.",
        "prediction_count" => (int)$row["total"]
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}