FITTRACK — Data-Driven Gym Management & Decision Support System

FITTRACK is a full-stack gym management and decision-support system that combines day-to-day gym operations with analytics, machine learning, trainer insights, and an AI Copilot.

Data → Insight → Action

📸 Screenshots

Admin Dashboard



The centralized dashboard provides an overview of members, trainers, revenue, attendance, memberships, growth, and operational KPIs.

Member Engagement



Member engagement analytics combine attendance, workout completion, and fitness progress to identify engagement levels and members who may need attention.

Retention Risk



FITTRACK uses a machine-learning retention model to identify members with elevated churn risk and provide supporting reasons and recommended follow-up actions.

Trainer Performance



Trainer analytics provide attendance, workout completion, progress tracking, member ratings, composite performance, and workload information.

AI Copilot



The AI Copilot provides role-aware answers using relevant FITTRACK business context while keeping access to sensitive information controlled.

🚀 Core Features

Management

Member management

Trainer management

Membership plans and memberships

Payments and revenue tracking

Attendance tracking

Workout plans and assignments

Classes and bookings

Progress tracking

Diet plans

User and admin management

Intelligence & Analytics

Member Engagement

FITTRACK calculates an engagement score using:

Recent attendance

Workout completion

Fitness progress

Members are grouped into engagement levels to help identify members who may require attention.

Retention Risk Prediction

The retention model predicts the likelihood of membership churn using historical membership behavior.

Features include:

Visits before prediction

Late visits

Payments

Amount paid

Workouts assigned

Workouts completed

Class bookings

Class attendance

Progress records

Workout completion rate

Class attendance rate

Late visit rate

The final model uses a Logistic Regression pipeline with feature scaling.

Retention predictions are decision-support signals, not guarantees of future member behavior.

Trainer Analytics

Trainer analytics provide:

Attendance performance

Workout completion

Member progress tracking

Member ratings

Composite performance score

Trainer workload classification

Gym Health

FITTRACK provides operational health indicators covering:

Attendance

Workout completion

Class attendance

Membership status

Revenue

Retention risk

Trainer workload

AI Copilot

The AI Copilot connects natural-language questions with controlled FITTRACK business context.

Its architecture is:

User Question
      ↓
Role & Page Context
      ↓
FITTRACK Context Service
      ↓
Relevant Database Data
      ↓
AI Assistant
      ↓
Actionable Response

The AI does not receive arbitrary SQL execution access.

🏗️ System Architecture

                         FITTRACK
                            │
             ┌──────────────┴──────────────┐
             │                             │
       Management System             Intelligence Layer
             │                             │
    ┌────────┼────────┐          ┌─────────┼─────────┐
    │        │        │          │         │         │
 Members  Payments  Attendance  Engagement Retention Trainer
    │        │        │          │         │         │
    └────────┴────────┴──────────┴─────────┴─────────┘
                            │
                         MySQL
                            │
                 ┌──────────┴──────────┐
                 │                     │
            Python / ML            AI Copilot
                 │                     │
        Scikit-learn Model       Gemini API

🧠 Data Science

FITTRACK extends traditional CRUD-based gym management with a data-driven decision-support layer.

The system uses historical operational data to generate insights around:

Member engagement

Retention/churn risk

Trainer performance

Trainer workload

Gym operational health

The retention model was evaluated using cross-validation before the final model was selected.

🛠️ Technology Stack

Backend

PHP

MySQL / MariaDB

REST-style API endpoints

Frontend

HTML

CSS

JavaScript

Bootstrap

Data Science

Python

Pandas

NumPy

Scikit-learn

Joblib

AI

Google Gemini API

Development Tools

XAMPP

phpMyAdmin

Git

GitHub

Postman

Google Colab

📁 Project Structure

FITTRACK-Gym-Analytics/
│
├── admin/
├── member/
├── trainer/
├── components/
├── assets/
├── config/
├── data_science/
│   ├── api/
│   ├── models/
│   └── ...
├── database/
├── screenshots/
├── index.php
├── login.php
└── README.md

⚙️ Local Installation

1. Clone the repository

git clone https://github.com/huzaifa19054/FITTRACK-Gym-Analytics.git
cd FITTRACK-Gym-Analytics

2. Configure XAMPP

Place the project inside:

C:\xampp\htdocs\

Start Apache and MySQL.

3. Create the database

Open phpMyAdmin and create:

fittrack_gym

Then import the SQL database file from the project's database/ directory.

4. Configure database credentials

Update the database configuration according to your local XAMPP/MySQL setup.

Typical local configuration:

Host: localhost
User: root
Password: empty
Database: fittrack_gym

5. Open FITTRACK

http://localhost/FitTrack-Gym-Complete/

🤖 AI Configuration

The AI Copilot requires a Gemini API key.

For local development, configure the key as an environment variable rather than hard-coding it into source files.

GEMINI_API_KEY=your_api_key_here

Do not commit real API keys to GitHub.

🔐 Security Notes

API keys should be stored outside source code.

.env files should remain untracked.

Role-based access should be enforced for AI context.

Sensitive database information should not be exposed to the AI model.

Retention predictions should be treated as decision-support outputs rather than guaranteed outcomes.

📊 Data Notes

FITTRACK's analytics and machine-learning features depend on the operational data available in the database.

Some development/testing records may be synthetic. Production deployments should use properly collected and validated business data.

🎯 Project Goal

FITTRACK aims to demonstrate how a traditional management system can evolve into a data-driven decision-support platform.

DATA
  ↓
ANALYSIS
  ↓
INSIGHT
  ↓
DECISION
  ↓
ACTION

🔗 Repository

GitHub: https://github.com/huzaifa19054/FITTRACK-Gym-Analytics