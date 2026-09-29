import pandas as pd
import mysql.connector
import joblib

# ==============================
# DATABASE CONNECTION
# ==============================

db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="fittrack_gym"
)

cursor = db.cursor()

print("Database connected successfully.")

# ==============================
# LOAD DATA FROM MYSQL
# ==============================

query = "SELECT * FROM current_retention_features"

df = pd.read_sql(query, db)

print("\nData loaded from MySQL:", len(df), "rows")

# ==============================
# FEATURE ENGINEERING
# ==============================

df["workout_completion_rate"] = (
    df["workouts_completed"] /
    df["workouts_assigned"].replace(0, 1)
)

df["class_attendance_rate"] = (
    df["classes_attended"] /
    df["classes_booked"].replace(0, 1)
)

df["late_visit_rate"] = (
    df["late_visits"] /
    df["visits_before_prediction"].replace(0, 1)
)

# ==============================
# MODEL FEATURES
# ==============================

feature_columns = [
    "visits_before_prediction",
    "late_visits",
    "payments_before_prediction",
    "amount_paid_before_prediction",
    "workouts_assigned",
    "workouts_completed",
    "classes_booked",
    "classes_attended",
    "progress_records",
    "workout_completion_rate",
    "class_attendance_rate",
    "late_visit_rate"
]

X = df[feature_columns]

# ==============================
# LOAD SAVED MODEL
# ==============================

model_path = "data_science/models/retention_model.joblib"

model = joblib.load(model_path)

print("Saved model loaded successfully.")

# ==============================
# PREDICT RETENTION RISK
# ==============================

risk_probability = model.predict_proba(X)[:, 1]

df["risk_probability"] = risk_probability
df["risk_percentage"] = risk_probability * 100

# ==============================
# ASSIGN RISK LEVEL
# ==============================

def get_risk_level(probability):

    if probability < 0.30:
        return "LOW"

    elif probability < 0.60:
        return "MEDIUM"

    else:
        return "HIGH"


df["risk_level"] = df["risk_probability"].apply(get_risk_level)

# ==============================
# RISK EXPLANATION
# ==============================

def generate_risk_reasons(row):

    reasons = []

    visits = row["visits_before_prediction"]
    workout_rate = row["workout_completion_rate"]
    class_rate = row["class_attendance_rate"]
    late_rate = row["late_visit_rate"]
    progress = row["progress_records"]

    # Low visit frequency
    if visits <= 2:
        reasons.append("Low visit frequency")

    # Low workout completion
    if workout_rate < 0.50:
        reasons.append("Low workout completion")

    # Low class attendance
    if class_rate < 0.50 and row["classes_booked"] > 0:
        reasons.append("Low class attendance")

    # Frequent late visits
    if late_rate >= 0.30 and visits > 0:
        reasons.append("Frequent late visits")

    # No progress records
    if progress == 0:
        reasons.append("No progress records")

    # Keep maximum 3 reasons
    reasons = reasons[:3]

    # Fill empty reason slots
    while len(reasons) < 3:
        reasons.append("No major additional indicator")

    # Recommended action
    if row["risk_level"] == "HIGH":

        action = "Contact member and send a renewal follow-up"

    elif row["risk_level"] == "MEDIUM":

        action = "Send engagement reminder and monitor activity"

    else:

        action = "Continue regular member engagement"

    return (
        reasons[0],
        reasons[1],
        reasons[2],
        action
    )


# Generate explanations for every prediction
risk_details = df.apply(
    generate_risk_reasons,
    axis=1,
    result_type="expand"
)

df["risk_reason_1"] = risk_details[0]
df["risk_reason_2"] = risk_details[1]
df["risk_reason_3"] = risk_details[2]
df["recommended_action"] = risk_details[3]

# ==============================
# SAVE PREDICTIONS TO MYSQL
# ==============================

# Remove old current predictions
cursor.execute("DELETE FROM retention_predictions")

# ==============================
# INSERT QUERY
# ==============================

insert_query = """
INSERT INTO retention_predictions (
    member_id,
    membership_id,
    risk_probability,
    risk_percentage,
    risk_level,
    risk_reason_1,
    risk_reason_2,
    risk_reason_3,
    recommended_action,
    model_version
)
VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)

ON DUPLICATE KEY UPDATE
    member_id = VALUES(member_id),
    risk_probability = VALUES(risk_probability),
    risk_percentage = VALUES(risk_percentage),
    risk_level = VALUES(risk_level),
    risk_reason_1 = VALUES(risk_reason_1),
    risk_reason_2 = VALUES(risk_reason_2),
    risk_reason_3 = VALUES(risk_reason_3),
    recommended_action = VALUES(recommended_action),
    model_version = VALUES(model_version),
    prediction_date = CURRENT_TIMESTAMP
"""

model_version = "logistic_regression_v1"

# ==============================
# INSERT PREDICTIONS
# ==============================

for _, row in df.iterrows():

    cursor.execute(
        insert_query,
        (
            int(row["member_id"]),
            int(row["membership_id"]),
            float(row["risk_probability"]),
            float(row["risk_percentage"]),
            row["risk_level"],
            row["risk_reason_1"],
            row["risk_reason_2"],
            row["risk_reason_3"],
            row["recommended_action"],
            model_version
        )
    )

# Commit changes
db.commit()

print("\n===== PREDICTIONS SAVED =====")
print("Predictions inserted:", len(df))

# ==============================
# RISK SUMMARY
# ==============================

print("\n===== RISK SUMMARY =====")

print(
    df["risk_level"]
    .value_counts()
    .sort_index()
)

# ==============================
# SHOW RISK EXPLANATIONS
# ==============================

print("\n===== RISK EXPLANATIONS =====")

for _, row in df.iterrows():

    print(
        f"\nMember ID: {int(row['member_id'])}"
    )

    print(
        f"Risk: {row['risk_percentage']:.2f}% "
        f"({row['risk_level']})"
    )

    print(
        f"Reason 1: {row['risk_reason_1']}"
    )

    print(
        f"Reason 2: {row['risk_reason_2']}"
    )

    print(
        f"Reason 3: {row['risk_reason_3']}"
    )

    print(
        f"Recommended Action: "
        f"{row['recommended_action']}"
    )

# ==============================
# CLOSE DATABASE
# ==============================

cursor.close()
db.close()

print("\nDatabase connection closed.")