import mysql.connector
import pandas as pd
import matplotlib.pyplot as plt


# ==========================================
# 1. CONNECT TO FITTRACK DATABASE
# ==========================================

connection = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="fittrack_gym"
)

print("Database connected successfully!")


# ==========================================
# 2. LOAD REAL DATA FROM MYSQL
# ==========================================

query = "SELECT * FROM member_churn_dataset"

df = pd.read_sql(query, connection)

connection.close()

print("\nDataset loaded successfully!")


# ==========================================
# 3. BASIC DATA INFORMATION
# ==========================================

print("\nNumber of rows:", len(df))
print("Number of columns:", len(df.columns))


print("\n===== FIRST 5 ROWS =====")

print(df.head())


print("\n===== COLUMNS =====")

print(df.columns.tolist())


# ==========================================
# 4. DATA TYPES
# ==========================================

print("\n===== DATA TYPES =====")

print(df.dtypes)


# ==========================================
# 5. MISSING VALUES
# ==========================================

print("\n===== MISSING VALUES =====")

print(df.isnull().sum())


# ==========================================
# 6. BASIC STATISTICS
# ==========================================

print("\n===== BASIC STATISTICS =====")

print(df.describe())


# ==========================================
# 7. CHURN DISTRIBUTION
# ==========================================

print("\n===== CHURN DISTRIBUTION =====")

print(df["churn"].value_counts())


print("\n===== CHURN PERCENTAGE =====")

print(
    df["churn"].value_counts(normalize=True) * 100
)


# ==========================================
# 8. ATTENDANCE VS CHURN
# ==========================================

print("\n===== ATTENDANCE VS CHURN =====")

attendance_analysis = (
    df.groupby("churn")["total_visits"]
      .agg(["count", "mean", "min", "max"])
)

print(attendance_analysis)


# ==========================================
# 9. ATTENDANCE GRAPH
# ==========================================

attendance_by_churn = (
    df.groupby("churn")["total_visits"].mean()
)

attendance_by_churn.plot(
    kind="bar",
    title="Average Gym Visits by Churn Status",
    xlabel="Churn (0 = No, 1 = Yes)",
    ylabel="Average Visits"
)

plt.tight_layout()
plt.show()


# ==========================================
# 10. DAYS SINCE LAST VISIT
# ==========================================

print("\n===== DAYS SINCE LAST VISIT VS CHURN =====")

days_analysis = (
    df.groupby("churn")["days_since_last_visit"]
      .agg(["count", "mean", "min", "max"])
)

print(days_analysis)


# ==========================================
# 11. DAYS SINCE LAST VISIT GRAPH
# ==========================================

days_by_churn = (
    df.groupby("churn")["days_since_last_visit"].mean()
)

days_by_churn.plot(
    kind="bar",
    title="Average Days Since Last Visit by Churn Status",
    xlabel="Churn (0 = No, 1 = Yes)",
    ylabel="Average Days Since Last Visit"
)

plt.tight_layout()
plt.show()

# ==========================================
# DUPLICATE CHECK
# ==========================================

print("\n===== DUPLICATE CHECK =====")

print("Duplicate rows:", df.duplicated().sum())

print("Duplicate member IDs:", df["member_id"].duplicated().sum())

# ==========================================
# VALIDITY CHECK
# ==========================================

print("\n===== VALIDITY CHECK =====")

print("\nHeight:")
print(df["height"].describe())

print("\nWeight:")
print(df["weight"].describe())

print("\nTotal Visits:")
print(df["total_visits"].describe())

print("\nTotal Payments:")
print(df["total_payments"].describe())

print("\nTotal Amount Paid:")
print(df["total_amount_paid"].describe())

print("\nMembership Days:")
print(df["total_membership_days"].describe())

# ==========================================
# EDA - CHURN VS MEMBER BEHAVIOR
# ==========================================

print("\n===== CHURN VS KEY FEATURES =====")

features = [
    "total_visits",
    "total_payments",
    "total_amount_paid",
    "total_membership_days",
    "total_workout_assignments",
    "completed_workouts",
    "total_class_bookings",
    "attended_classes",
    "progress_records"
]

print(
    df.groupby("churn")[features].mean().round(2)
)

# ==========================================
# FEATURE CORRELATION WITH CHURN
# ==========================================

print("\n===== FEATURE CORRELATION WITH CHURN =====")

numeric_features = [
    "height",
    "weight",
    "total_memberships",
    "total_membership_days",
    "avg_membership_duration_days",
    "total_payments",
    "total_amount_paid",
    "total_visits",
    "late_attendance_count",
    "total_workout_assignments",
    "completed_workouts",
    "total_class_bookings",
    "attended_classes",
    "progress_records"
]

correlations = (
    df[numeric_features + ["churn"]]
    .corr()["churn"]
    .drop("churn")
    .sort_values(ascending=False)
)

print(correlations)

# ==========================================
# CHURN VS MEMBERSHIP STATUS
# ==========================================

print("\n===== CHURN VS MEMBERSHIP STATUS =====")

print(
    pd.crosstab(
        df["latest_membership_status"],
        df["churn"]
    )
)