import os
import warnings

import joblib
import mysql.connector
import numpy as np
import pandas as pd
import streamlit as st
import matplotlib.pyplot as plt

from sklearn.model_selection import StratifiedKFold, cross_val_score
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.linear_model import LogisticRegression
from sklearn.ensemble import RandomForestClassifier
from sklearn.tree import DecisionTreeClassifier
from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    confusion_matrix,
)

warnings.filterwarnings("ignore")

# ============================================================
# FITTRACK — STREAMLIT ML ANALYTICS
# ============================================================

st.set_page_config(
    page_title="FITTRACK ML Analytics",
    page_icon="📊",
    layout="wide",
    initial_sidebar_state="expanded",
)

FEATURE_COLUMNS = [
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
    "late_visit_rate",
]

RAW_FEATURES = [
    "visits_before_prediction",
    "late_visits",
    "payments_before_prediction",
    "amount_paid_before_prediction",
    "workouts_assigned",
    "workouts_completed",
    "classes_booked",
    "classes_attended",
    "progress_records",
]

DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "fittrack_gym",
}

PROJECT_ROOT = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(
    PROJECT_ROOT, "models", "retention_model.joblib"
)


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


@st.cache_data(ttl=60)
def load_training_data():
    conn = get_connection()
    try:
        return pd.read_sql(
            "SELECT * FROM member_retention_features",
            conn,
        )
    finally:
        conn.close()


@st.cache_data(ttl=30)
def load_current_predictions():
    conn = get_connection()
    try:
        query = """
            SELECT
                rp.member_id,
                rp.membership_id,
                u.full_name AS member_name,
                rp.risk_probability,
                rp.risk_percentage,
                rp.risk_level,
                rp.model_version,
                rp.prediction_date
            FROM retention_predictions rp
            INNER JOIN members m ON m.id = rp.member_id
            INNER JOIN users u ON u.id = m.user_id
            ORDER BY rp.risk_percentage DESC
        """
        return pd.read_sql(query, conn)
    finally:
        conn.close()


@st.cache_resource
def load_saved_model():
    if not os.path.exists(MODEL_PATH):
        return None
    return joblib.load(MODEL_PATH)


def engineer_features(df):
    df = df.copy()

    df["workout_completion_rate"] = (
        df["workouts_completed"]
        / df["workouts_assigned"].replace(0, 1)
    )

    df["class_attendance_rate"] = (
        df["classes_attended"]
        / df["classes_booked"].replace(0, 1)
    )

    df["late_visit_rate"] = (
        df["late_visits"]
        / df["visits_before_prediction"].replace(0, 1)
    )

    return df


def train_models(df):
    data = engineer_features(df)

    X = data[FEATURE_COLUMNS]
    y = data["retention_churn"]

    split = int(len(data) * 0.80)
    # Keep the same random stratified split used by the project.
    from sklearn.model_selection import train_test_split

    X_train, X_test, y_train, y_test = train_test_split(
        X,
        y,
        test_size=0.20,
        random_state=42,
        stratify=y,
    )

    models = {
        "Logistic Regression": Pipeline(
            [
                ("scaler", StandardScaler()),
                (
                    "model",
                    LogisticRegression(
                        random_state=42,
                        max_iter=5000,
                    ),
                ),
            ]
        ),
        "Random Forest": RandomForestClassifier(
            n_estimators=200,
            random_state=42,
            class_weight="balanced",
        ),
        "Decision Tree": DecisionTreeClassifier(
            max_depth=5,
            random_state=42,
            class_weight="balanced",
        ),
    }

    rows = []
    fitted = {}

    for name, model in models.items():
        model.fit(X_train, y_train)
        pred = model.predict(X_test)

        rows.append(
            {
                "Model": name,
                "Accuracy": accuracy_score(y_test, pred),
                "Precision": precision_score(
                    y_test, pred, zero_division=0
                ),
                "Recall": recall_score(
                    y_test, pred, zero_division=0
                ),
                "F1 Score": f1_score(
                    y_test, pred, zero_division=0
                ),
            }
        )
        fitted[name] = model

    results = pd.DataFrame(rows)

    cv = StratifiedKFold(
        n_splits=5,
        shuffle=True,
        random_state=42,
    )

    cv_rows = []
    for name, model in models.items():
        scores = cross_val_score(
            model,
            X,
            y,
            cv=cv,
            scoring="f1",
        )
        cv_rows.append(
            {
                "Model": name,
                "Mean CV F1": scores.mean(),
                "Std CV F1": scores.std(),
            }
        )

    cv_results = pd.DataFrame(cv_rows)

    return (
        data,
        X_train,
        X_test,
        y_train,
        y_test,
        results,
        cv_results,
        fitted,
    )


def risk_level(probability):
    if probability < 0.30:
        return "LOW"
    if probability < 0.60:
        return "MEDIUM"
    return "HIGH"


# ============================================================
# PREMIUM FITTRACK STYLING
# ============================================================

st.set_page_config(
    page_title="FitTrack ML Analytics",
    page_icon="F",
    layout="wide",
    initial_sidebar_state="expanded",
)

st.markdown(
    """
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap');

:root {
    --bg: #061113;
    --bg2: #08181a;
    --card: #0b1b1d;
    --card2: #0e2324;
    --line: rgba(132, 178, 174, .16);
    --text: #f3f7f6;
    --muted: #829a97;
    --green: #19c6a0;
    --green2: #0e9f86;
    --danger: #ff6f68;
    --warning: #e9c46a;
}

html, body, [class*="css"] {
    font-family: 'DM Sans', sans-serif;
}

.stApp {
    background:
        radial-gradient(circle at 80% 0%, rgba(25,198,160,.08), transparent 28%),
        radial-gradient(circle at 0% 50%, rgba(25,198,160,.035), transparent 24%),
        var(--bg);
    color: var(--text);
}

[data-testid="stHeader"] {
    background: rgba(6,17,19,.86);
}

[data-testid="stSidebar"] {
    background: #071416;
    border-right: 1px solid var(--line);
}

[data-testid="stSidebar"] > div:first-child {
    padding-top: 1.2rem;
}

.block-container {
    max-width: 1420px;
    padding-top: 1.8rem;
    padding-bottom: 4rem;
}

/* Sidebar brand */
.ft-brand {
    padding: 8px 10px 22px 10px;
    border-bottom: 1px solid var(--line);
    margin-bottom: 18px;
}
.ft-brand-row {
    display:flex;
    align-items:center;
    gap:10px;
}
.ft-logo {
    width:32px;
    height:32px;
    border-radius:9px;
    background:var(--green);
    color:#041311;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family:'Space Grotesk',sans-serif;
    font-weight:700;
}
.ft-brand-name {
    font-family:'Space Grotesk',sans-serif;
    font-size:21px;
    font-weight:700;
}
.ft-brand-name span { color:var(--green); }
.ft-caption {
    color:var(--muted);
    font-size:11px;
    margin-top:8px;
    letter-spacing:.04em;
}

/* Hero */
.ml-hero {
    position:relative;
    overflow:hidden;
    border:1px solid var(--line);
    border-radius:24px;
    padding:30px 34px;
    margin-bottom:22px;
    background:
      linear-gradient(105deg, rgba(14,35,36,.98), rgba(8,24,26,.82)),
      radial-gradient(circle at 88% 30%, rgba(25,198,160,.18), transparent 25%);
}
.ml-hero:after {
    content:"";
    position:absolute;
    width:260px;
    height:260px;
    border:1px solid rgba(25,198,160,.10);
    border-radius:50%;
    right:-90px;
    top:-110px;
}
.eyebrow {
    color:var(--green);
    font-size:11px;
    font-weight:700;
    letter-spacing:.18em;
    text-transform:uppercase;
}
.ml-hero h1 {
    font-family:'Space Grotesk',sans-serif;
    font-size:42px;
    line-height:1.02;
    letter-spacing:-.04em;
    margin:10px 0 9px;
    color:var(--text);
}
.ml-hero h1 span { color:var(--green); }
.ml-hero p {
    color:#a3b7b4;
    font-size:15px;
    max-width:720px;
    margin:0;
}

/* Top status */
.top-status {
    display:flex;
    gap:9px;
    flex-wrap:wrap;
    margin-top:20px;
}
.pill {
    border:1px solid var(--line);
    border-radius:999px;
    padding:7px 11px;
    color:#9eb3b0;
    background:rgba(255,255,255,.02);
    font-size:11px;
}
.pill strong { color:var(--text); }

/* Metric cards */
.metric-grid {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
    margin-bottom:22px;
}
.ft-metric {
    background:linear-gradient(145deg, #0b1b1d, #0a1719);
    border:1px solid var(--line);
    border-radius:18px;
    padding:19px 20px;
    min-height:112px;
}
.ft-metric .label {
    color:var(--muted);
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.12em;
}
.ft-metric .value {
    font-family:'Space Grotesk',sans-serif;
    color:var(--text);
    font-size:31px;
    font-weight:700;
    margin-top:8px;
}
.ft-metric .sub {
    color:var(--green);
    font-size:11px;
    margin-top:4px;
}

/* Section heading */
.section-head {
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:20px;
    margin:28px 0 13px;
}
.section-head h2 {
    font-family:'Space Grotesk',sans-serif;
    font-size:24px;
    margin:0;
    letter-spacing:-.025em;
}
.section-head p {
    color:var(--muted);
    font-size:12px;
    margin:0;
    max-width:530px;
}

/* Cards */
.ft-card {
    border:1px solid var(--line);
    background:rgba(11,27,29,.88);
    border-radius:18px;
    padding:20px;
}
.card-kicker {
    color:var(--green);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.16em;
    font-weight:700;
}
.card-title {
    font-family:'Space Grotesk',sans-serif;
    font-size:20px;
    margin-top:6px;
    color:var(--text);
}
.card-copy {
    color:var(--muted);
    font-size:12px;
    line-height:1.6;
}

/* Streamlit widgets */
.stSelectbox label, .stRadio label, .stTextInput label {
    color:#9bb0ad !important;
    font-size:11px !important;
}
div[data-baseweb="select"] > div {
    background:#0a191b !important;
    border-color:var(--line) !important;
    border-radius:10px !important;
}
.stButton > button {
    background:var(--green) !important;
    color:#031310 !important;
    border:0 !important;
    border-radius:10px !important;
    font-weight:700 !important;
}
.stButton > button:hover {
    background:#2bd7b3 !important;
}
div[data-testid="stMetric"] {
    background:transparent;
}
div[data-testid="stMetricLabel"] {
    color:var(--muted) !important;
}
div[data-testid="stMetricValue"] {
    color:var(--text) !important;
}

/* Dataframes */
div[data-testid="stDataFrame"] {
    border:1px solid var(--line);
    border-radius:14px;
    overflow:hidden;
}

/* Alerts */
div[data-testid="stAlert"] {
    border-radius:13px;
    border:1px solid var(--line);
}

/* Footer */
.ft-footer {
    border-top:1px solid var(--line);
    margin-top:55px;
    padding-top:16px;
    display:flex;
    justify-content:space-between;
    color:#5f7774;
    font-size:10px;
    letter-spacing:.03em;
}

@media (max-width: 900px) {
    .metric-grid { grid-template-columns:repeat(2,1fr); }
    .ml-hero h1 { font-size:34px; }
}
</style>
""",
    unsafe_allow_html=True,
)

# ============================================================
# LOAD DATA
# ============================================================

# ============================================================


# ============================================================
# NAVIGATION + HERO
# ============================================================

with st.sidebar:
    st.markdown(
        """
        <div class="ft-brand">
            <div class="ft-brand-row">
                <div class="ft-logo">F</div>
                <div class="ft-brand-name">Fit<span>Track</span></div>
            </div>
            <div class="ft-caption">PREDICTIVE GYM ANALYTICS</div>
        </div>
        """,
        unsafe_allow_html=True,
    )

    page = st.radio(
        "WORKSPACE",
        [
            "Overview",
            "Dataset & EDA",
            "Feature Engineering",
            "Model Evaluation",
            "Feature Importance",
            "Member Prediction",
        ],
    )

    st.markdown("---")
    st.caption("MODEL")
    st.markdown("**Logistic Regression v1**")
    st.caption("Development / portfolio environment")

st.markdown(
    """
    <div class="ml-hero">
        <div class="eyebrow">FITTRACK · MACHINE LEARNING</div>
        <h1>Turn gym data into <span>predictive insights.</span></h1>
        <p>
            Explore the data, engineer behavioral features, evaluate models,
            and inspect member retention risk — all inside one analytics workspace.
        </p>
        <div class="top-status">
            <div class="pill"><strong>12</strong>&nbsp; ML features</div>
            <div class="pill"><strong>3</strong>&nbsp; models evaluated</div>
            <div class="pill"><strong>5-fold</strong>&nbsp; cross-validation</div>
            <div class="pill"><strong>LOW · MEDIUM · HIGH</strong>&nbsp; risk bands</div>
        </div>
    </div>
    """,
    unsafe_allow_html=True,
)

try:
    training_df = load_training_data()
except Exception as e:
    st.error(
        "Could not connect to the FITTRACK MySQL database. "
        "Make sure XAMPP/MySQL is running and database `fittrack_gym` exists."
    )
    st.code(str(e))
    st.stop()

if training_df.empty:
    st.warning("No rows found in `member_retention_features`.")
    st.stop()

data = engineer_features(training_df)

# ============================================================
# OVERVIEW
# ============================================================

if page == "Overview":
    retained = int((data["retention_churn"] == 0).sum())
    not_retained = int((data["retention_churn"] == 1).sum())

    c1, c2, c3, c4 = st.columns(4)
    cards = [
        ("Membership Records", len(data), "Training records"),
        ("Retained", retained, f"{retained / len(data) * 100:.1f}%"),
        ("Not Retained", not_retained, f"{not_retained / len(data) * 100:.1f}%"),
        ("ML Features", len(FEATURE_COLUMNS), "Behavioral features"),
    ]

    for col, (title, value, sub) in zip([c1, c2, c3, c4], cards):
        with col:
            st.markdown(
                f"""
                <div class="metric-card">
                    <div class="metric-title">{title}</div>
                    <div class="metric-value">{value}</div>
                    <div class="metric-sub">{sub}</div>
                </div>
                """,
                unsafe_allow_html=True,
            )

    st.markdown('<div class="section-head"><div><div class="eyebrow">PREDICTIVE OBJECTIVE</div><h2>What FITTRACK predicts</h2></div><p>A defined non-retention outcome based on the project’s 30-day retention rule.</p></div>', unsafe_allow_html=True)

    st.write(
        "The model estimates the risk that a membership will not be "
        "followed by another membership within the project's 30-day "
        "retention definition."
    )

    chart_df = pd.DataFrame(
        {
            "Status": ["Retained", "Not Retained"],
            "Members": [retained, not_retained],
        }
    )
    st.bar_chart(chart_df.set_index("Status"))

    st.info(
        "Risk bands used in the project: LOW < 30%, "
        "MEDIUM 30%–<60%, HIGH ≥ 60%."
    )

# ============================================================
# DATASET & EDA
# ============================================================

elif page == "Dataset & EDA":
    st.markdown('<div class="section-head"><div><div class="eyebrow">DATA FOUNDATION</div><h2>Dataset overview</h2></div><p>Understand the training population before the model sees it.</p></div>', unsafe_allow_html=True)

    a, b, c = st.columns(3)
    a.metric("Rows", len(training_df))
    b.metric("Columns", len(training_df.columns))
    c.metric(
        "Missing Values",
        int(training_df.isna().sum().sum()),
    )

    st.dataframe(training_df.head(20), use_container_width=True)

    st.markdown(
        '<div class="section-title">Retention Distribution</div>',
        unsafe_allow_html=True,
    )

    dist = (
        training_df["retention_churn"]
        .value_counts()
        .rename(index={0: "Retained", 1: "Not Retained"})
        .sort_index()
    )
    st.bar_chart(dist)

    st.markdown(
        '<div class="section-title">Behavior by Retention Outcome</div>',
        unsafe_allow_html=True,
    )

    compare = (
        data.groupby("retention_churn")[RAW_FEATURES]
        .mean()
        .round(2)
        .T
    )
    compare.columns = ["Retained (0)", "Not Retained (1)"]
    st.dataframe(compare, use_container_width=True)

# ============================================================
# FEATURE ENGINEERING
# ============================================================

elif page == "Feature Engineering":
    st.markdown('<div class="section-head"><div><div class="eyebrow">FEATURE ENGINEERING</div><h2>From raw activity to model signals</h2></div><p>Derived behavioral rates make the member activity measurable.</p></div>', unsafe_allow_html=True)

    st.write(
        "The project derives three behavioral rates from the raw activity "
        "counts before passing the data to the ML model."
    )

    feature_map = pd.DataFrame(
        [
            ["workout_completion_rate", "workouts_completed / workouts_assigned"],
            ["class_attendance_rate", "classes_attended / classes_booked"],
            ["late_visit_rate", "late_visits / visits_before_prediction"],
        ],
        columns=["Engineered Feature", "Formula"],
    )
    st.dataframe(feature_map, hide_index=True, use_container_width=True)

    st.markdown(
        '<div class="section-title">12 Model Features</div>',
        unsafe_allow_html=True,
    )

    feature_df = pd.DataFrame(
        {
            "Feature": FEATURE_COLUMNS,
            "Type": [
                "Behavior",
                "Behavior",
                "Payment",
                "Payment",
                "Workout",
                "Workout",
                "Class",
                "Class",
                "Progress",
                "Engineered",
                "Engineered",
                "Engineered",
            ],
        }
    )
    st.dataframe(feature_df, hide_index=True, use_container_width=True)

    st.markdown(
        '<div class="section-title">Feature Statistics</div>',
        unsafe_allow_html=True,
    )
    st.dataframe(
        data[FEATURE_COLUMNS].describe().T.round(2),
        use_container_width=True,
    )

# ============================================================
# MODEL EVALUATION
# ============================================================

elif page == "Model Evaluation":
    st.markdown('<div class="section-head"><div><div class="eyebrow">MODEL LAB</div><h2>Compare and validate the models</h2></div><p>Use holdout metrics and stratified 5-fold cross-validation to evaluate generalization.</p></div>', unsafe_allow_html=True)

    with st.spinner("Training evaluation models..."):
        (
            evaluated_data,
            X_train,
            X_test,
            y_train,
            y_test,
            results,
            cv_results,
            fitted,
        ) = train_models(training_df)

    display_results = results.copy()
    for col in ["Accuracy", "Precision", "Recall", "F1 Score"]:
        display_results[col] = display_results[col].round(4)

    st.subheader("Holdout Test Results")
    st.dataframe(
        display_results,
        hide_index=True,
        use_container_width=True,
    )

    st.subheader("5-Fold Cross-Validation — F1")
    display_cv = cv_results.copy()
    display_cv["Mean CV F1"] = display_cv["Mean CV F1"].round(4)
    display_cv["Std CV F1"] = display_cv["Std CV F1"].round(4)
    st.dataframe(
        display_cv,
        hide_index=True,
        use_container_width=True,
    )

    chart = cv_results.set_index("Model")[["Mean CV F1"]]
    st.bar_chart(chart)

    st.caption(
        "The comparison is calculated from the current `member_retention_features` "
        "training view using the project's evaluation setup."
    )

    st.subheader("Confusion Matrix — Logistic Regression")
    lr = fitted["Logistic Regression"]
    pred = lr.predict(X_test)
    cm = confusion_matrix(y_test, pred)
    cm_df = pd.DataFrame(
        cm,
        index=["Actual Retained", "Actual Not Retained"],
        columns=["Predicted Retained", "Predicted Not Retained"],
    )
    st.dataframe(cm_df, use_container_width=True)

# ============================================================
# FEATURE IMPORTANCE
# ============================================================

elif page == "Feature Importance":
    st.markdown('<div class="section-head"><div><div class="eyebrow">MODEL INTERPRETABILITY</div><h2>What signals the model uses</h2></div><p>Feature importance helps explain which behavioral variables contribute most to the tree-based model.</p></div>', unsafe_allow_html=True)

    with st.spinner("Training Random Forest for feature importance..."):
        engineered = engineer_features(training_df)
        X = engineered[FEATURE_COLUMNS]
        y = engineered["retention_churn"]

        rf = RandomForestClassifier(
            n_estimators=200,
            random_state=42,
            class_weight="balanced",
        )
        rf.fit(X, y)

    importance = (
        pd.DataFrame(
            {
                "Feature": FEATURE_COLUMNS,
                "Importance": rf.feature_importances_,
            }
        )
        .sort_values("Importance", ascending=False)
        .set_index("Feature")
    )

    st.bar_chart(importance)

    st.dataframe(
        importance.round(4),
        use_container_width=True,
    )

    st.caption(
        "Feature importance indicates how much the Random Forest used each "
        "feature in its splits; it does not establish causation."
    )

# ============================================================
# MEMBER PREDICTION
# ============================================================

elif page == "Member Prediction":
    st.markdown('<div class="section-head"><div><div class="eyebrow">PREDICTION CENTER</div><h2>Member retention risk</h2></div><p>Review current predictions and turn risk signals into follow-up actions.</p></div>', unsafe_allow_html=True)

    try:
        predictions = load_current_predictions()
    except Exception as e:
        st.error("Could not load `retention_predictions`.")
        st.code(str(e))
        st.stop()

    if predictions.empty:
        st.warning(
            "No current predictions found. Open the FITTRACK admin dashboard "
            "or run `predict_retention.py` first."
        )
        st.stop()

    high = int((predictions["risk_level"] == "HIGH").sum())
    medium = int((predictions["risk_level"] == "MEDIUM").sum())
    low = int((predictions["risk_level"] == "LOW").sum())

    c1, c2, c3 = st.columns(3)
    c1.metric("HIGH Risk", high)
    c2.metric("MEDIUM Risk", medium)
    c3.metric("LOW Risk", low)

    st.subheader("Risk Overview")
    chart = (
        predictions["risk_level"]
        .value_counts()
        .reindex(["HIGH", "MEDIUM", "LOW"])
        .fillna(0)
    )
    st.bar_chart(chart)

    st.subheader("Prediction Table")

    table_cols = [
        "member_name",
        "member_id",
        "risk_percentage",
        "risk_level",
    ]

    display = predictions[table_cols].copy()
    display["risk_percentage"] = display["risk_percentage"].round(2)
    display.columns = [
        "Member",
        "Member ID",
        "Risk %",
        "Risk Level",
        "Recommended Action",
    ]

    st.dataframe(
        display,
        hide_index=True,
        use_container_width=True,
    )

    selected = st.selectbox(
        "Inspect a member",
        predictions["member_name"].tolist(),
    )

    row = predictions[predictions["member_name"] == selected].iloc[0]

    st.markdown(
        f"""
        <div class="metric-card">
            <div class="metric-title">Selected Member</div>
            <div class="metric-value">{row["member_name"]}</div>
            <div class="metric-sub">
                Risk: {row["risk_percentage"]:.2f}% — {row["risk_level"]}
            </div>
        </div>
        """,
        unsafe_allow_html=True,
    )

    a, b = st.columns(2)

    with a:
        st.subheader("Risk Interpretation")
        if row["risk_level"] == "HIGH":
            st.error("High retention-risk signal. Review the member's recent activity and consider proactive follow-up.")
        elif row["risk_level"] == "MEDIUM":
            st.warning("Moderate retention-risk signal. Monitor recent engagement and consider a follow-up.")
        else:
            st.success("Low retention-risk signal. Continue normal member engagement.")

    with b:
        st.subheader("Suggested Action")
        if row["risk_level"] == "HIGH":
            st.info("Contact member → review engagement → discuss renewal / retention support.")
        elif row["risk_level"] == "MEDIUM":
            st.info("Monitor activity → encourage attendance → review progress.")
        else:
            st.info("Continue regular engagement and monitor activity.")

    st.caption(
        f'Model version: {row["model_version"]} | '
        f'Prediction date: {row["prediction_date"]}'
    )
