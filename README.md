# FITTRACK — Data-Driven Gym Management & Decision Support System

> **Data → Insight → Action**

FITTRACK is a full-stack gym management and decision-support system that combines everyday gym operations with analytics, machine learning, trainer intelligence, member engagement analysis, retention-risk prediction, and a role-aware AI Copilot.

The project started as a traditional PHP/MySQL gym management application and was extended into a data-driven analytics platform.

---

## 🚀 What FITTRACK Does

FITTRACK covers the complete gym workflow:

- Member management
- Trainer management
- Membership plans and memberships
- Payments and revenue tracking
- Attendance
- Workout plans and assignments
- Classes and bookings
- Diet plans
- Member progress tracking
- Trainer reviews and ratings
- Member engagement analytics
- Retention-risk prediction
- Trainer performance and workload analytics
- Gym health metrics
- AI-powered operational assistance

---

## 🧠 Intelligence Layer

FITTRACK is more than a CRUD application. Its analytics layer turns operational data into measurable insights.

### Member Engagement

The system evaluates member engagement using:

- Recent attendance
- Workout completion
- Fitness progress
- Goal-aware progress analysis

Members can be grouped into engagement levels such as **High, Medium, and Low**, with filtering and pagination available for operational follow-up.

### Retention Risk Prediction

FITTRACK uses a machine-learning pipeline to estimate membership retention risk.

The current model is a **Logistic Regression pipeline with feature standardization**.

The prediction workflow uses activity and engagement features including:

- Visits before prediction
- Late visits
- Payments
- Amount paid
- Workouts assigned
- Workouts completed
- Classes booked
- Classes attended
- Progress records
- Workout completion rate
- Class attendance rate
- Late-visit rate

Predictions are stored with:

- Risk percentage
- Risk level
- Risk indicators
- Recommended action
- Model version
- Prediction timestamp

> Retention risk is a model prediction, not a guaranteed outcome.

### Trainer Analytics

FITTRACK provides trainer-level analytics covering:

- Attendance performance
- Workout completion
- Progress tracking
- Member ratings
- Composite trainer performance
- Current workload
- Historical period comparison

Trainer workload is also surfaced to help identify trainers managing larger active-member loads.

### Gym Health

The dashboard combines operational indicators such as:

- Active members
- 30-day attendance
- Workout completion
- Class attendance
- Active memberships
- Expiring memberships
- Monthly revenue
- Retention risk
- Trainer workload

These are **FITTRACK application metrics and business rules**, not industry or clinical standards.

---

## 🤖 FITTRACK AI Copilot

FITTRACK includes a global AI Copilot designed to work as an operational assistant rather than a generic chatbot.

The Copilot can use controlled FITTRACK context to answer questions about relevant gym data.

### Role-aware access

| Role | AI Context |
|---|---|
| **Admin** | System-level operational and analytics context |
| **Trainer** | Assigned members and own trainer performance/workload |
| **Member** | Personal fitness, attendance, workout, progress, and membership information |

The backend follows a controlled flow:

```text
User Question
      ↓
Role & Page Context
      ↓
FITTRACK Analytics / Database
      ↓
Sanitized Context
      ↓
Gemini API
      ↓
AI Response
```

The AI does **not** receive arbitrary SQL execution access. The application prepares the relevant FITTRACK context before sending it to the model.

The Gemini API key remains server-side and is not sent to browser JavaScript.

---

## 🏗️ System Architecture

```text
                    FITTRACK
                       │
        ┌──────────────┼──────────────┐
        │              │              │
   Admin Portal   Trainer Portal  Member Portal
        │              │              │
        └──────────────┼──────────────┘
                       │
                 PHP Application
                       │
                 MySQL / MariaDB
                       │
        ┌──────────────┼──────────────┐
        │              │              │
    Analytics       ML Layer       AI Copilot
        │              │              │
        │        Retention Model    Gemini
        │              │              │
        └──────────────┼──────────────┘
                       │
                 Decision Support
```

---

## 🛠️ Tech Stack

### Application

- PHP
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- XAMPP / Apache

### Data Science & Machine Learning

- Python
- pandas
- NumPy
- scikit-learn
- Logistic Regression
- Feature standardization
- Cross-validation
- Streamlit

### AI

- Google Gemini API
- Server-side API integration
- Role-aware context control

### Development

- Git
- GitHub
- phpMyAdmin
- VS Code / Cursor
- XAMPP

---

## 📁 Project Structure

```text
FitTrack-Gym-Complete/
│
├── admin/                  # Admin portal
├── trainer/                # Trainer portal
├── member/                 # Member portal
├── components/             # Shared components including AI Copilot
├── config/                 # Database and application configuration
├── database/               # SQL dump and database utilities
│
├── data_science/
│   ├── api/                # Analytics, retention and AI APIs
│   ├── data/               # Development datasets
│   ├── models/             # Machine-learning models
│   ├── train_retention_model.py
│   ├── predict_retention.py
│   ├── streamlit_app.py
│   └── database.py
│
├── assets/                 # CSS, JavaScript and UI assets
├── index.php               # Public entry point
├── login.php               # Authentication
├── logout.php
├── change-password.php
├── requirements.txt
├── .env.example
└── README.md
```

---

## ⚙️ Local Installation

### 1. Requirements

Install:

- XAMPP
- PHP 8+
- MySQL/MariaDB
- Python 3+
- Git

### 2. Clone the repository

```bash
git clone https://github.com/huzaifa19054/FITTRACK-Gym-Analytics.git
cd FITTRACK-Gym-Analytics
```

### 3. Place the project in XAMPP

Copy the project into:

```text
C:\xampp\htdocs\
```

For example:

```text
C:\xampp\htdocs\FitTrack-Gym-Complete
```

### 4. Start XAMPP

Start:

- Apache
- MySQL

### 5. Create the database

Open phpMyAdmin and create:

```text
fittrack_gym
```

Import:

```text
database/fittrack_gym.sql
```

### 6. Configure database connection

Update:

```text
config/db.php
```

for your local environment.

The standard XAMPP development configuration uses:

```text
Host: localhost
User: root
Password: empty
Database: fittrack_gym
```

### 7. Configure Gemini

Create your server environment variable:

```text
GEMINI_API_KEY=your_api_key_here
```

Use `.env.example` as the reference.

**Never commit a real API key to GitHub.**

For Windows/XAMPP, restart Apache after changing environment variables.

### 8. Open FITTRACK

```text
http://localhost/FitTrack-Gym-Complete/login.php
```

---

## 🐍 Python / Machine Learning Setup

From the project directory:

```bash
python -m venv .venv
```

Windows:

```bash
.venv\Scripts\activate
```

Install dependencies:

```bash
pip install -r requirements.txt
```

The `data_science/` directory contains the retention-model training, prediction, database, synthetic-data, and Streamlit workflows.

---

## 🔐 Security Notes

This repository is prepared for development and portfolio use.

- Do not commit `.env` files.
- Do not commit real API keys.
- Keep production database credentials outside source control.
- Use proper secret management for production deployment.
- Review authentication and authorization settings before production deployment.

---

## 📊 Data Notes

Development/testing data in this repository is project-generated or synthetic.

FITTRACK's analytical thresholds and workload rules are application-specific business metrics. They should not be interpreted as medical standards, clinical measurements, or universal industry benchmarks.

---

## 🎯 Project Goal

FITTRACK demonstrates how a conventional gym management application can be extended into a data-driven decision-support platform.

The progression is:

```text
Operational Data
       ↓
Data Processing
       ↓
Analytics
       ↓
Machine Learning
       ↓
AI Assistance
       ↓
Actionable Decisions
```

---

## 👨‍💻 Project

**FITTRACK — Data-Driven Gym Management & Decision Support System**

Built as a full-stack academic/portfolio project combining web development, database systems, data analytics, machine learning, and generative AI.

**GitHub:**  
https://github.com/huzaifa19054/FITTRACK-Gym-Analytics
