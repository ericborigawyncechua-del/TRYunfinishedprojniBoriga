<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";


// ===============================
// GET MEMBER ID
// ===============================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid member ID.");
}

$member_id = (int) $_GET["id"];


// ===============================
// GET MEMBER
// ===============================

$stmt = $pdo->prepare("
    SELECT
        id,
        member_code,
        name,
        email,
        contact,
        address,
        status,
        created_at
    FROM members
    WHERE id = ?
");

$stmt->execute([$member_id]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$member) {
    die("Member not found.");
}


// ===============================
// GET MEMBERSHIP
// ===============================

$stmt = $pdo->prepare("
    SELECT
        id,
        membership_type,
        start_date,
        expiration_date,
        status
    FROM memberships
    WHERE member_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$stmt->execute([$member_id]);

$membership = $stmt->fetch(PDO::FETCH_ASSOC);


// ===============================
// GET TRAINING PACKAGE
// ===============================

$stmt = $pdo->prepare("
    SELECT
        training_packages.id,
        training_packages.total_sessions,
        training_packages.sessions_used,
        training_packages.sessions_remaining,
        coaches.name AS coach_name,
        coaches.coach_code
    FROM training_packages

    LEFT JOIN coaches
        ON training_packages.coach_id = coaches.id

    WHERE training_packages.member_id = ?

    ORDER BY training_packages.id DESC

    LIMIT 1
");

$stmt->execute([$member_id]);

$package = $stmt->fetch(PDO::FETCH_ASSOC);


// ===============================
// GET TRAINING HISTORY
// ===============================

$stmt = $pdo->prepare("
    SELECT
        training_sessions.session_date,
        training_sessions.session_time,
        training_sessions.status,
        training_sessions.notes,
        coaches.name AS coach_name,
        coaches.coach_code
    FROM training_sessions

    LEFT JOIN coaches
        ON training_sessions.coach_id = coaches.id

    WHERE training_sessions.member_id = ?

    ORDER BY
        training_sessions.session_date DESC,
        training_sessions.session_time DESC
");

$stmt->execute([$member_id]);

$training_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ===============================
// GET ATTENDANCE HISTORY
// ===============================

$stmt = $pdo->prepare("
    SELECT
        attendance_date,
        time_in,
        time_out
    FROM attendance
    WHERE member_id = ?
    ORDER BY attendance_date DESC, time_in DESC
");

$stmt->execute([$member_id]);

$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Member Profile - Gym Management System
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family: Arial, sans-serif;

            background: #f3f4f6;

            color: #111827;
        }


        /* ===============================
           HEADER
        =============================== */

        .header {

            background: #21180f;

            color: white;

            padding: 18px 28px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .header h2 {

            margin: 0;

            font-size: 20px;
        }


        .header a {

            color: white;

            text-decoration: none;

            font-size: 15px;
        }


        /* ===============================
           CONTAINER
        =============================== */

        .container {

            max-width: 1400px;

            margin: auto;

            padding: 40px 28px;
        }


        /* ===============================
           PROFILE HEADER
        =============================== */

        .profile-header {

            background: white;

            padding: 30px;

            border-radius: 10px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.08);
        }


        .profile-header h1 {

            margin: 0 0 8px;

            font-size: 32px;
        }


        .member-code {

            color: #6b7280;

            font-size: 16px;
        }


        /* ===============================
           GRID
        =============================== */

        .grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 25px;
        }


        /* ===============================
           CARD
        =============================== */

        .card {

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.08);
        }


        .card h2 {

            margin-top: 0;

            margin-bottom: 20px;

            font-size: 21px;
        }


        /* ===============================
           INFORMATION
        =============================== */

        .info-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 12px 0;

            border-bottom:
                1px solid #e5e7eb;
        }


        .info-row:last-child {

            border-bottom: none;
        }


        .label {

            color: #6b7280;

            font-weight: bold;
        }


        .value {

            text-align: right;

            font-weight: 500;
        }


        /* ===============================
           STATUS
        =============================== */

        .status {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }


        .active {

            background: #dcfce7;

            color: #166534;
        }


        .expired {

            background: #fee2e2;

            color: #991b1b;
        }


        .inactive {

            background: #e5e7eb;

            color: #374151;
        }


        /* ===============================
           SESSION COUNT
        =============================== */

        .session-box {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 12px;

            margin-top: 20px;
        }


        .session-item {

            background: #f3f4f6;

            padding: 18px;

            text-align: center;

            border-radius: 8px;
        }


        .session-number {

            display: block;

            font-size: 28px;

            font-weight: bold;

            margin-bottom: 5px;
        }


        .session-label {

            font-size: 13px;

            color: #6b7280;
        }


        /* ===============================
           TABLE
        =============================== */

        .table-container {

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;
        }


        th {

            background: #f3f4f6;

            padding: 13px;

            text-align: left;

            white-space: nowrap;
        }


        td {

            padding: 13px;

            border-bottom:
                1px solid #e5e7eb;

            white-space: nowrap;
        }


        .completed {

            color: #166534;

            font-weight: bold;
        }


        .scheduled {

            color: #92400e;

            font-weight: bold;
        }


        .cancelled {

            color: #991b1b;

            font-weight: bold;
        }


        .full-width {

            grid-column: 1 / -1;
        }


        .empty {

            text-align: center;

            color: #6b7280;

            padding: 25px;
        }


        /* ===============================
           RESPONSIVE
        =============================== */

        @media (max-width: 800px) {

            .grid {

                grid-template-columns: 1fr;
            }


            .full-width {

                grid-column: auto;
            }


            .session-box {

                grid-template-columns: 1fr;
            }


            .container {

                padding: 25px 15px;
            }

        }

    </style>

</head>


<body>


<!-- ===============================
     HEADER
=============================== -->

<div class="header">

    <h2>
        GYM MANAGEMENT SYSTEM
    </h2>


    <a href="members.php">
        ← Members
    </a>

</div>


<!-- ===============================
     MAIN
=============================== -->

<div class="container">


    <!-- ===============================
         PROFILE HEADER
    =============================== -->

    <div class="profile-header">

        <h1>

            <?= htmlspecialchars(
                $member["name"]
            ) ?>

        </h1>


        <div class="member-code">

            Member Code:

            <strong>

                <?= htmlspecialchars(
                    $member["member_code"]
                ) ?>

            </strong>

        </div>

    </div>


    <!-- ===============================
         INFORMATION GRID
    =============================== -->

    <div class="grid">


        <!-- ===============================
             MEMBER INFORMATION
        =============================== -->

        <div class="card">

            <h2>
                Member Information
            </h2>


            <div class="info-row">

                <span class="label">
                    Name
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $member["name"]
                    ) ?>

                </span>

            </div>


            <div class="info-row">

                <span class="label">
                    Member Code
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $member["member_code"]
                    ) ?>

                </span>

            </div>


            <div class="info-row">

                <span class="label">
                    Email
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $member["email"] ?? "N/A"
                    ) ?>

                </span>

            </div>


            <div class="info-row">

                <span class="label">
                    Phone
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $member["contact"] ?? "N/A"
                    ) ?>

                </span>

            </div>


            <div class="info-row">

                <span class="label">
                    Address
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $member["address"] ?? "N/A"
                    ) ?>

                </span>

            </div>


            <div class="info-row">

                <span class="label">
                    Status
                </span>

                <span class="value">

                    <?php

                    $member_status =
                        $member["status"];

                    $member_status_class =
                        strtolower(
                            $member_status
                        );

                    ?>


                    <span
                        class="status <?= htmlspecialchars(
                            $member_status_class
                        ) ?>"
                    >

                        <?= htmlspecialchars(
                            $member_status
                        ) ?>

                    </span>

                </span>

            </div>

        </div>


        <!-- ===============================
             MEMBERSHIP
        =============================== -->

        <div class="card">

            <h2>
                Membership
            </h2>


            <?php if ($membership): ?>


                <div class="info-row">

                    <span class="label">
                        Membership Type
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $membership["membership_type"]
                        ) ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="label">
                        Start Date
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $membership["start_date"]
                        ) ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="label">
                        Expiration Date
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $membership["expiration_date"]
                        ) ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="label">
                        Status
                    </span>

                    <span class="value">


                        <?php

                        $membership_status =
                            $membership["status"];

                        $membership_class =
                            strtolower(
                                $membership_status
                            );

                        ?>


                        <span
                            class="status <?= htmlspecialchars(
                                $membership_class
                            ) ?>"
                        >

                            <?= htmlspecialchars(
                                $membership_status
                            ) ?>

                        </span>


                    </span>

                </div>


            <?php else: ?>


                <div class="empty">

                    No membership found.

                </div>


            <?php endif; ?>


        </div>


        <!-- ===============================
             TRAINING PACKAGE
        =============================== -->

        <div class="card">

            <h2>
                Personal Training
            </h2>


            <?php if ($package): ?>


                <div class="info-row">

                    <span class="label">
                        Coach
                    </span>

                    <span class="value">

                        <?php if (
                            $package["coach_name"]
                        ): ?>

                            <?= htmlspecialchars(
                                $package["coach_name"]
                            ) ?>

                            -
                            <?= htmlspecialchars(
                                $package["coach_code"]
                            ) ?>

                        <?php else: ?>

                            No Coach Assigned

                        <?php endif; ?>

                    </span>

                </div>


                <div class="session-box">


                    <div class="session-item">

                        <span class="session-number">

                            <?= htmlspecialchars(
                                $package["total_sessions"]
                            ) ?>

                        </span>


                        <span class="session-label">

                            Total Sessions

                        </span>

                    </div>


                    <div class="session-item">

                        <span class="session-number">

                            <?= htmlspecialchars(
                                $package["sessions_used"]
                            ) ?>

                        </span>


                        <span class="session-label">

                            Sessions Used

                        </span>

                    </div>


                    <div class="session-item">

                        <span class="session-number">

                            <?= htmlspecialchars(
                                $package["sessions_remaining"]
                            ) ?>

                        </span>


                        <span class="session-label">

                            Sessions Remaining

                        </span>

                    </div>


                </div>


            <?php else: ?>


                <div class="empty">

                    No personal training package found.

                </div>


            <?php endif; ?>


        </div>


        <!-- ===============================
             TRAINING HISTORY
        =============================== -->

        <div class="card">

            <h2>
                Training History
            </h2>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Date
                            </th>

                            <th>
                                Time
                            </th>

                            <th>
                                Coach
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Notes
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        count($training_sessions) === 0
                    ): ?>


                        <tr>

                            <td
                                colspan="5"
                                class="empty"
                            >

                                No training sessions found.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $training_sessions
                            as $training
                        ): ?>


                            <tr>


                                <td>

                                    <?= htmlspecialchars(
                                        $training["session_date"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $training["session_time"]
                                    ) ?>

                                </td>


                                <td>

                                    <?php if (
                                        $training["coach_name"]
                                    ): ?>

                                        <?= htmlspecialchars(
                                            $training["coach_name"]
                                        ) ?>

                                    <?php else: ?>

                                        No Coach

                                    <?php endif; ?>

                                </td>


                                <td>


                                    <?php

                                    $training_status =
                                        $training["status"];

                                    $training_class =
                                        strtolower(
                                            $training_status
                                        );

                                    ?>


                                    <span
                                        class="<?= htmlspecialchars(
                                            $training_class
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $training_status
                                        ) ?>

                                    </span>


                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $training["notes"] ?? ""
                                    ) ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>


        <!-- ===============================
             ATTENDANCE HISTORY
        =============================== -->

        <div class="card full-width">

            <h2>
                Attendance History
            </h2>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Date
                            </th>

                            <th>
                                Time-In
                            </th>

                            <th>
                                Time-Out
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        count($attendance) === 0
                    ): ?>


                        <tr>

                            <td
                                colspan="3"
                                class="empty"
                            >

                                No attendance records found.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $attendance
                            as $record
                        ): ?>


                            <tr>


                                <td>

                                    <?= htmlspecialchars(
                                        $record["attendance_date"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $record["time_in"]
                                    ) ?>

                                </td>


                                <td>

                                    <?php if (
                                        $record["time_out"]
                                    ): ?>

                                        <?= htmlspecialchars(
                                            $record["time_out"]
                                        ) ?>

                                    <?php else: ?>

                                        <strong>
                                            Still Inside
                                        </strong>

                                    <?php endif; ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>


    </div>


</div>


</body>

</html>