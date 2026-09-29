import mysql.connector
import pandas as pd

# ==============================
# DATABASE CONNECTION
# ==============================

connection = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="fittrack_gym"
)

print("Database connected successfully!")

# ==============================
# LOAD ML DATASET
# ==============================

query = """
SELECT *
FROM member_retention_features
"""

df = pd.read_sql(query, connection)

connection.close()

print("\nDataset loaded successfully!")

print("Rows:", len(df))
print("Columns:", len(df.columns))

print("\n===== COLUMNS =====")
print(df.columns.tolist())

print("\n===== FIRST 5 ROWS =====")
print(df.head())

print("\n===== TARGET DISTRIBUTION =====")
print(df["retention_churn"].value_counts())

print("\n===== TARGET PERCENTAGE =====")
print(
    df["retention_churn"]
    .value_counts(normalize=True)
    .mul(100)
    .round(2)
)

print("\n===== DATA TYPES =====")
print(df.dtypes)

print("\n===== MISSING VALUES =====")
print(df.isnull().sum())

print("\n===== DUPLICATE ROWS =====")
print("Duplicate rows:", df.duplicated().sum())

print("\n===== DUPLICATE MEMBERSHIPS =====")
print("Duplicate membership IDs:", df["membership_id"].duplicated().sum())

print("\n===== FEATURE STATISTICS =====")

features = [
    "visits_before_prediction",
    "late_visits",
    "payments_before_prediction",
    "amount_paid_before_prediction",
    "workouts_assigned",
    "workouts_completed",
    "classes_booked",
    "classes_attended",
    "progress_records"
]

print(df[features].describe().round(2))

print("\n===== FEATURES VS RETENTION =====")

print(
    df.groupby("retention_churn")[features]
      .mean()
      .round(2)
)

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

print("\n===== ENGINEERED FEATURES =====")

engineered_features = [
    "workout_completion_rate",
    "class_attendance_rate",
    "late_visit_rate"
]

print(
    df[engineered_features]
    .describe()
    .round(2)
)

# ==============================
# PREPARE DATA FOR MACHINE LEARNING
# ==============================

from sklearn.model_selection import train_test_split

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
y = df["retention_churn"]

X_train, X_test, y_train, y_test = train_test_split(
    X,
    y,
    test_size=0.20,
    random_state=42,
    stratify=y
)

print("\n===== TRAIN / TEST SPLIT =====")
print("Training rows:", len(X_train))
print("Testing rows:", len(X_test))

print("\nTraining target distribution:")
print(y_train.value_counts())

print("\nTesting target distribution:")
print(y_test.value_counts())

# ==============================
# LOGISTIC REGRESSION
# ==============================

from sklearn.linear_model import LogisticRegression
from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    confusion_matrix,
    classification_report
)
from sklearn.preprocessing import StandardScaler

# Scale features
scaler = StandardScaler()

X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)

# Train model
logistic_model = LogisticRegression(
    random_state=42,
    max_iter=1000
)

logistic_model.fit(X_train_scaled, y_train)

# Predictions
y_pred = logistic_model.predict(X_test_scaled)

# Probability of not-retained
y_probability = logistic_model.predict_proba(X_test_scaled)[:, 1]

print("\n===== LOGISTIC REGRESSION RESULTS =====")

print("Accuracy:", round(accuracy_score(y_test, y_pred), 4))
print("Precision:", round(precision_score(y_test, y_pred), 4))
print("Recall:", round(recall_score(y_test, y_pred), 4))
print("F1 Score:", round(f1_score(y_test, y_pred), 4))

print("\n===== CONFUSION MATRIX =====")
print(confusion_matrix(y_test, y_pred))

print("\n===== CLASSIFICATION REPORT =====")
print(classification_report(y_test, y_pred))

# ==============================
# RANDOM FOREST
# ==============================

from sklearn.ensemble import RandomForestClassifier

random_forest_model = RandomForestClassifier(
    n_estimators=200,
    random_state=42,
    class_weight="balanced"
)

random_forest_model.fit(X_train, y_train)

rf_pred = random_forest_model.predict(X_test)

print("\n===== RANDOM FOREST RESULTS =====")

print("Accuracy:", round(accuracy_score(y_test, rf_pred), 4))
print("Precision:", round(precision_score(y_test, rf_pred), 4))
print("Recall:", round(recall_score(y_test, rf_pred), 4))
print("F1 Score:", round(f1_score(y_test, rf_pred), 4))

print("\n===== CONFUSION MATRIX =====")
print(confusion_matrix(y_test, rf_pred))

print("\n===== CLASSIFICATION REPORT =====")
print(classification_report(y_test, rf_pred))

# ==============================
# DECISION TREE
# ==============================

from sklearn.tree import DecisionTreeClassifier

decision_tree_model = DecisionTreeClassifier(
    max_depth=5,
    random_state=42,
    class_weight="balanced"
)

decision_tree_model.fit(X_train, y_train)

dt_pred = decision_tree_model.predict(X_test)

print("\n===== DECISION TREE RESULTS =====")

print("Accuracy:", round(accuracy_score(y_test, dt_pred), 4))
print("Precision:", round(precision_score(y_test, dt_pred), 4))
print("Recall:", round(recall_score(y_test, dt_pred), 4))
print("F1 Score:", round(f1_score(y_test, dt_pred), 4))

print("\n===== CONFUSION MATRIX =====")
print(confusion_matrix(y_test, dt_pred))

print("\n===== CLASSIFICATION REPORT =====")
print(classification_report(y_test, dt_pred))

# ==============================
# RANDOM FOREST FEATURE IMPORTANCE
# ==============================

feature_importance = pd.DataFrame({
    "feature": feature_columns,
    "importance": random_forest_model.feature_importances_
})

feature_importance = feature_importance.sort_values(
    by="importance",
    ascending=False
)

print("\n===== FEATURE IMPORTANCE =====")
print(feature_importance.to_string(index=False))

# ==============================
# RETENTION RISK PROBABILITIES
# ==============================

logistic_probabilities = logistic_model.predict_proba(
    X_test_scaled
)[:, 1]

risk_results = X_test.copy()

risk_results["actual_retention_churn"] = y_test.values
risk_results["risk_probability"] = logistic_probabilities.round(4)

risk_results["risk_percentage"] = (
    risk_results["risk_probability"] * 100
).round(2)

print("\n===== RETENTION RISK PREDICTIONS =====")

print(
    risk_results[
        [
            "risk_probability",
            "risk_percentage",
            "actual_retention_churn"
        ]
    ].head(10)
)

# ==============================
# RISK PROBABILITY ANALYSIS
# ==============================

risk_results["predicted_class"] = (
    risk_results["risk_probability"] >= 0.50
).astype(int)

print("\n===== RISK PROBABILITY SUMMARY =====")

print(
    risk_results["risk_probability"]
    .describe()
    .round(3)
)

print("\n===== PREDICTED CLASS DISTRIBUTION =====")

print(
    risk_results["predicted_class"]
    .value_counts()
)

print("\n===== ACTUAL VS PREDICTED =====")

print(
    pd.crosstab(
        risk_results["actual_retention_churn"],
        risk_results["predicted_class"],
        rownames=["Actual"],
        colnames=["Predicted"]
    )
)

# ==============================
# THRESHOLD ANALYSIS
# ==============================

from sklearn.metrics import precision_score, recall_score, f1_score

print("\n===== THRESHOLD ANALYSIS =====")

thresholds = [0.30, 0.40, 0.50, 0.60, 0.70]

for threshold in thresholds:

    threshold_pred = (
        logistic_probabilities >= threshold
    ).astype(int)

    precision = precision_score(
        y_test,
        threshold_pred,
        zero_division=0
    )

    recall = recall_score(
        y_test,
        threshold_pred,
        zero_division=0
    )

    f1 = f1_score(
        y_test,
        threshold_pred,
        zero_division=0
    )

    print(
        f"Threshold: {threshold:.2f} | "
        f"Precision: {precision:.2f} | "
        f"Recall: {recall:.2f} | "
        f"F1: {f1:.2f}"
    )

# ==============================
# PROPER CROSS-VALIDATION
# ==============================

from sklearn.pipeline import Pipeline
from sklearn.model_selection import StratifiedKFold, cross_val_score

logistic_pipeline = Pipeline([
    ("scaler", StandardScaler()),
    ("model", LogisticRegression(
        random_state=42,
        max_iter=5000
    ))
])

cv = StratifiedKFold(
    n_splits=5,
    shuffle=True,
    random_state=42
)

cv_scores = cross_val_score(
    logistic_pipeline,
    X,
    y,
    cv=cv,
    scoring="f1"
)

print("\n===== PROPER 5-FOLD CROSS-VALIDATION =====")
print("F1 scores:", cv_scores.round(4))
print("Mean F1:", round(cv_scores.mean(), 4))
print("Std F1:", round(cv_scores.std(), 4))

# ==============================
# RANDOM FOREST CROSS-VALIDATION
# ==============================

from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import cross_val_score

random_forest = RandomForestClassifier(
    n_estimators=200,
    random_state=42,
    class_weight="balanced"
)

rf_cv_scores = cross_val_score(
    random_forest,
    X,
    y,
    cv=cv,
    scoring="f1"
)

print("\n===== RANDOM FOREST 5-FOLD CROSS-VALIDATION =====")
print("F1 scores:", rf_cv_scores.round(4))
print("Mean F1:", round(rf_cv_scores.mean(), 4))
print("Std F1:", round(rf_cv_scores.std(), 4))

# ==============================
# DECISION TREE CROSS-VALIDATION
# ==============================

from sklearn.tree import DecisionTreeClassifier

decision_tree = DecisionTreeClassifier(
    max_depth=5,
    random_state=42,
    class_weight="balanced"
)

dt_cv_scores = cross_val_score(
    decision_tree,
    X,
    y,
    cv=cv,
    scoring="f1"
)

print("\n===== DECISION TREE 5-FOLD CROSS-VALIDATION =====")
print("F1 scores:", dt_cv_scores.round(4))
print("Mean F1:", round(dt_cv_scores.mean(), 4))
print("Std F1:", round(dt_cv_scores.std(), 4))

# ==============================
# TRAIN FINAL MODEL ON ALL DATA
# ==============================

from sklearn.pipeline import Pipeline
import joblib

final_model = Pipeline([
    ("scaler", StandardScaler()),
    ("model", LogisticRegression(
        random_state=42,
        max_iter=5000
    ))
])

# Train on all available training data
final_model.fit(X, y)

# Save model
model_path = "data_science/models/retention_model.joblib"

joblib.dump(final_model, model_path)

print("\n===== FINAL MODEL SAVED =====")
print("Model:", model_path)
print("Training rows:", len(X))
print("Features:", len(feature_columns))

# ==============================
# THRESHOLD EVALUATION
# ==============================

from sklearn.model_selection import cross_val_predict
from sklearn.metrics import precision_score, recall_score, f1_score

# Get out-of-fold probabilities
cv_probabilities = cross_val_predict(
    logistic_pipeline,
    X,
    y,
    cv=cv,
    method="predict_proba"
)[:, 1]

thresholds = [0.30, 0.40, 0.50, 0.60, 0.70]

print("\n===== 5-FOLD THRESHOLD EVALUATION =====")

for threshold in thresholds:

    predictions = (cv_probabilities >= threshold).astype(int)

    precision = precision_score(y, predictions, zero_division=0)
    recall = recall_score(y, predictions, zero_division=0)
    f1 = f1_score(y, predictions, zero_division=0)

    print(
        f"Threshold {threshold:.2f} | "
        f"Precision: {precision:.4f} | "
        f"Recall: {recall:.4f} | "
        f"F1: {f1:.4f}"
    )