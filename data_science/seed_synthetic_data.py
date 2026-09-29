import pandas as pd
import mysql.connector
from datetime import datetime, timedelta
from decimal import Decimal


# =========================================================
# 1. LOAD SYNTHETIC CSV
# =========================================================

csv_path = "data_science/data/fittrack_100_pakistani_members_synthetic.csv"

df = pd.read_csv(csv_path)

print("CSV loaded successfully!")
print("Total synthetic members:", len(df))


# =========================================================
# 2. CONNECT TO MYSQL
# =========================================================

connection = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="fittrack_gym"
)

cursor = connection.cursor()

print("Database connected successfully!")


# =========================================================
# 3. SAFETY CHECK
# =========================================================
# Prevent running the seed twice accidentally.

cursor.execute("""
    SELECT COUNT(*)
    FROM users
    WHERE email LIKE 'synthetic.member%@fittrack.local'
""")

existing_count = cursor.fetchone()[0]

if existing_count > 0:
    print("\nSynthetic data already exists!")
    print("Existing synthetic users:", existing_count)
    print("Nothing was inserted.")
    cursor.close()
    connection.close()
    exit()


# =========================================================
# 4. FIXED VALUES FROM YOUR DATABASE
# =========================================================

MEMBER_ROLE_ID = 3
MEMBERSHIP_PLAN_ID = 1

WORKOUT_PLAN_IDS = [1, 2, 4, 5]
CLASS_IDS = [2, 3]

# Demo password hash.
# Synthetic users are for data/analytics purposes.
PASSWORD_HASH = (
    "$2b$10$N9qo8uLOickgx2ZMRZoMye"
    "IjZAgcfl7p92ldGxad68LJZdL17lhWy"
)


# =========================================================
# 5. INSERT DATA
# =========================================================

try:

    for index, row in df.iterrows():

        # -------------------------------------------------
        # Basic values
        # -------------------------------------------------

        name = str(row["name"])

        email = f"synthetic.member{index + 1:03d}@fittrack.local"

        username = f"synthetic_member_{index + 1:03d}"

        phone = f"0300{index + 1:07d}"

        date_of_birth = pd.to_datetime(
            row["date_of_birth"]
        ).date()

        joined_date = pd.to_datetime(
            row["joined_date"]
        ).date()

        height = float(row["height"])
        weight = float(row["weight"])

        gender = str(row["gender"]).lower()

        status = str(row["member_status"]).lower()


        # -------------------------------------------------
        # 6. INSERT USER
        # -------------------------------------------------

        cursor.execute("""
            INSERT INTO users
            (
                role_id,
                full_name,
                email,
                username,
                password,
                phone,
                status
            )
            VALUES (%s, %s, %s, %s, %s, %s, %s)
        """, (
            MEMBER_ROLE_ID,
            name,
            email,
            username,
            PASSWORD_HASH,
            phone,
            "active"
        ))

        user_id = cursor.lastrowid


        # -------------------------------------------------
        # 7. INSERT MEMBER
        # -------------------------------------------------

        cursor.execute("""
            INSERT INTO members
            (
                user_id,
                trainer_id,
                date_of_birth,
                gender,
                address,
                emergency_contact,
                emergency_phone,
                joined_date,
                height,
                weight,
                status
            )
            VALUES
            (
                %s, NULL, %s, %s, %s, %s, %s,
                %s, %s, %s, %s
            )
        """, (
            user_id,
            date_of_birth,
            gender,
            "Karachi, Pakistan",
            "Emergency Contact",
            phone,
            joined_date,
            height,
            weight,
            status
        ))

        member_id = cursor.lastrowid


        # =================================================
        # 8. MEMBERSHIPS
        # =================================================

        total_memberships = int(row["total_memberships"])

        total_membership_days = int(
            row["total_membership_days"]
        )

        latest_status = str(
            row["latest_membership_status"]
        ).lower()

        last_end = pd.to_datetime(
            row["last_membership_end_date"]
        ).date()

        # Split total membership days across memberships
        base_days = total_membership_days // total_memberships
        remainder = total_membership_days % total_memberships

        current_end = last_end

        membership_ids = []

        for m in range(total_memberships):

            duration = base_days

            if m < remainder:
                duration += 1

            start_date = current_end - timedelta(days=duration)

            if m == total_memberships - 1:
                membership_status = latest_status
            else:
                membership_status = "expired"

            cursor.execute("""
                INSERT INTO memberships
                (
                    member_id,
                    plan_id,
                    start_date,
                    end_date,
                    status
                )
                VALUES (%s, %s, %s, %s, %s)
            """, (
                member_id,
                MEMBERSHIP_PLAN_ID,
                start_date,
                current_end,
                membership_status
            ))

            membership_ids.append(cursor.lastrowid)

            current_end = start_date - timedelta(days=1)


        # =================================================
        # 9. PAYMENTS
        # =================================================

        total_payments = int(row["total_payments"])

        total_amount = Decimal(
            str(row["total_amount_paid"])
        )

        if total_payments > 0:

            amount_each = (
                total_amount / total_payments
            ).quantize(Decimal("0.01"))

            for p in range(total_payments):

                payment_amount = amount_each

                # Make last payment exactly match
                # total_amount
                if p == total_payments - 1:

                    previous_amount = (
                        amount_each * (total_payments - 1)
                    )

                    payment_amount = (
                        total_amount - previous_amount
                    )

                payment_date = joined_date + timedelta(
                    days=max(
                        0,
                        int(
                            (
                                last_end - joined_date
                            ).days
                            * (p + 1)
                            / total_payments
                        )
                    )
                )

                payment_methods = [
                    "cash",
                    "card",
                    "bank",
                    "online"
                ]

                payment_method = payment_methods[
                    p % len(payment_methods)
                ]

                reference_no = (
                    f"SYN-{index + 1:03d}-"
                    f"PAY-{p + 1:02d}"
                )

                cursor.execute("""
                    INSERT INTO payments
                    (
                        member_id,
                        membership_id,
                        amount,
                        payment_method,
                        payment_date,
                        reference_no,
                        status
                    )
                    VALUES
                    (%s, %s, %s, %s, %s, %s, %s)
                """, (
                    member_id,
                    membership_ids[-1],
                    payment_amount,
                    payment_method,
                    payment_date,
                    reference_no,
                    "paid"
                ))


        # =================================================
        # 10. ATTENDANCE
        # =================================================

        total_visits = int(row["total_visits"])
        late_visits = int(row["late_attendance_count"])

        for a in range(total_visits):

            attendance_date = joined_date + timedelta(
                days=max(
                    1,
                    int(
                        (
                            last_end - joined_date
                        ).days
                        * (a + 1)
                        / (total_visits + 1)
                    )
                )
            )

            check_in = datetime.combine(
                attendance_date,
                datetime.min.time()
            ) + timedelta(
                hours=8 + (a % 4),
                minutes=(a * 7) % 60
            )

            check_out = check_in + timedelta(
                minutes=60 + (a % 45)
            )

            if a >= total_visits - late_visits:
                attendance_status = "late"
            else:
                attendance_status = "present"

            cursor.execute("""
                INSERT INTO attendance
                (
                    member_id,
                    check_in,
                    check_out,
                    status
                )
                VALUES (%s, %s, %s, %s)
            """, (
                member_id,
                check_in,
                check_out,
                attendance_status
            ))


        # =================================================
        # 11. WORKOUT ASSIGNMENTS
        # =================================================

        total_workouts = int(
            row["total_workout_assignments"]
        )

        completed_workouts = int(
            row["completed_workouts"]
        )

        for w in range(total_workouts):

            workout_plan_id = WORKOUT_PLAN_IDS[
                w % len(WORKOUT_PLAN_IDS)
            ]

            assigned_date = joined_date + timedelta(
                days=w * 7
            )

            if w < completed_workouts:
                workout_status = "completed"
            else:
                workout_status = "assigned"

            cursor.execute("""
                INSERT INTO member_workouts
                (
                    member_id,
                    workout_plan_id,
                    assigned_date,
                    status
                )
                VALUES (%s, %s, %s, %s)
            """, (
                member_id,
                workout_plan_id,
                assigned_date,
                workout_status
            ))


         # =================================================
        # 12. CLASS BOOKINGS
        # =================================================

        total_classes = int(
            row["total_class_bookings"]
        )

        attended_classes = int(
            row["attended_classes"]
        )

        # Database currently has only 2 classes.
        # Each member can book each class only once.

        bookings_to_insert = min(
            total_classes,
            len(CLASS_IDS)
        )

        attended_to_insert = min(
            attended_classes,
            bookings_to_insert
        )

        for c in range(bookings_to_insert):

            class_id = CLASS_IDS[c]

            booked_at = datetime.combine(
                joined_date + timedelta(days=c * 5),
                datetime.min.time()
            ) + timedelta(hours=10)

            if c < attended_to_insert:
                booking_status = "attended"
            else:
                booking_status = "booked"

            cursor.execute("""
                INSERT INTO class_bookings
                (
                    class_id,
                    member_id,
                    booked_at,
                    status
                )
                VALUES (%s, %s, %s, %s)
            """, (
                class_id,
                member_id,
                booked_at,
                booking_status
            ))

        # =================================================
        # 13. PROGRESS RECORDS
        # =================================================

        progress_count = int(
            row["progress_records"]
        )

        for p in range(progress_count):

            record_date = joined_date + timedelta(
                days=p * 14
            )

            # Small realistic variation in weight
            progress_weight = max(
                40,
                weight - (p * 0.3)
            )

            progress_height = height

            cursor.execute("""
                INSERT INTO progress_records
                (
                    member_id,
                    record_date,
                    weight,
                    height
                )
                VALUES (%s, %s, %s, %s)
            """, (
                member_id,
                record_date,
                round(progress_weight, 2),
                round(progress_height, 2)
            ))


        print(
            f"Inserted member {index + 1}/"
            f"{len(df)}: {name}"
        )


    # =====================================================
    # 14. COMMIT EVERYTHING
    # =====================================================

    connection.commit()

    print("\n========================================")
    print("SYNTHETIC DATA INSERTED SUCCESSFULLY!")
    print("========================================")

    print(
        f"Total synthetic members inserted: {len(df)}"
    )


except Exception as error:

    connection.rollback()

    print("\nERROR OCCURRED!")
    print(error)

    print("\nAll changes have been rolled back.")


finally:

    cursor.close()
    connection.close()

    print("\nDatabase connection closed.")