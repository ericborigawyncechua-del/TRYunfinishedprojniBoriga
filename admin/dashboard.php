<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";


// =====================================================
// DASHBOARD STATISTICS
// =====================================================

// TOTAL MEMBERS
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM members
");

$total_members = $stmt->fetchColumn();


// ACTIVE MEMBERS
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM members
    WHERE status = 'Active'
");

$active_members = $stmt->fetchColumn();


// TODAY'S ATTENDANCE
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM attendance
    WHERE attendance_date = CURDATE()
");

$today_attendance = $stmt->fetchColumn();


// ACTIVE MEMBERSHIPS
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM memberships
    WHERE status = 'Active'
");

$active_memberships = $stmt->fetchColumn();


// MEMBERS CURRENTLY INSIDE
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM attendance
    WHERE attendance_date = CURDATE()
    AND time_in IS NOT NULL
    AND time_out IS NULL
");

$current_inside = $stmt->fetchColumn();


// =====================================================
// RECENT ATTENDANCE
// =====================================================

$stmt = $pdo->query("
    SELECT
        attendance.id,
        members.member_code,
        members.name,
        attendance.attendance_date,
        attendance.time_in,
        attendance.time_out,
        attendance.duration_minutes,
        attendance.status

    FROM attendance

    INNER JOIN members
        ON attendance.member_id = members.id

    ORDER BY attendance.id DESC

    LIMIT 10
");

$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Gym System - Dashboard
    </title>


    <style>

        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }


        body {

            margin: 0;

            background: #f3f4f6;

            color: #111827;

        }


        /* ================================
           NAVBAR
        ================================= */

        .navbar {

            background: #271d11;

            color: white;

            padding: 18px 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }


        .navbar h2 {

            margin: 0;

            font-size: 21px;

        }


        .navbar-right {

            display: flex;

            align-items: center;

            gap: 15px;

        }


        .logout {

            color: white;

            text-decoration: none;

            background: #dc2626;

            padding: 8px 14px;

            border-radius: 6px;

        }


        .logout:hover {

            background: #b91c1c;

        }


        /* ================================
           CONTAINER
        ================================= */

        .container {

            padding: 30px;

            max-width: 1500px;

            margin: auto;

        }


        .welcome {

            margin-bottom: 25px;

        }


        .welcome h1 {

            margin-bottom: 8px;

        }


        .welcome p {

            margin: 0;

            color: #4b5563;

        }


        /* ================================
           STAT CARDS
        ================================= */

        .cards {

            display: grid;

            grid-template-columns:
                repeat(5, 1fr);

            gap: 18px;

        }


        .card {

            background: white;

            padding: 22px;

            border-radius: 10px;

            box-shadow:
                0 2px 10px rgba(0,0,0,0.05);

        }


        .card h3 {

            margin-top: 0;

            margin-bottom: 15px;

            color: #555;

            font-size: 15px;

        }


        .number {

            font-size: 34px;

            font-weight: bold;

        }


        /* ================================
           QUICK MENU
        ================================= */

        .menu {
             margin-top: 25px;

             display: grid;

             grid-template-columns:
             repeat(7, 1fr);

               gap: 15px;
        }


        .menu a {

            background: white;

            padding: 20px 10px;

            text-decoration: none;

            color: #271e00;

            border-radius: 10px;

            text-align: center;

            box-shadow:
                0 2px 10px rgba(0,0,0,0.05);

            transition: 0.2s;

        }


        .menu a:hover {

            background: #e5e7eb;

            transform: translateY(-2px);

        }


        .menu-icon {

            font-size: 25px;

            margin-bottom: 7px;

        }


        /* ================================
           RECENT ATTENDANCE
        ================================= */

        .attendance-card {

            margin-top: 30px;

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 2px 10px rgba(0,0,0,0.05);

        }


        .attendance-card h2 {

            margin-top: 0;

            margin-bottom: 20px;

        }


        .table-container {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

        }


        th {

            background: #f3f4f6;

            text-align: left;

            padding: 13px;

            font-size: 14px;

        }


        td {

            padding: 13px;

            border-bottom:
                1px solid #e5e7eb;

            font-size: 14px;

        }


        tr:hover {

            background: #f9fafb;

        }


        /* ================================
           STATUS
        ================================= */

        .status {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;

        }


        .status-in {

            background: #dcfce7;

            color: #166534;

        }


        .status-out {

            background: #e5e7eb;

            color: #374151;

        }


        /* ================================
           EMPTY
        ================================= */

        .empty {

            text-align: center;

            padding: 30px;

            color: #6b7280;

        }


        /* ================================
           RESPONSIVE
        ================================= */

        @media (max-width: 1100px) {

            .cards {

                grid-template-columns:
                    repeat(3, 1fr);

            }


            .menu {

                grid-template-columns:
                    repeat(3, 1fr);

            }

        }


        @media (max-width: 700px) {

            .navbar {

                flex-direction: column;

                gap: 12px;

                align-items: flex-start;

            }


            .cards {

                grid-template-columns:
                    1fr;

            }


            .menu {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .container {

                padding: 20px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================
     NAVBAR
========================================= -->

<div class="navbar">

    <h2>
        D'FITNESS MANAGEMENT SYSTEM
    </h2>


    <div class="navbar-right">

        <span>

            Welcome,
            <?= htmlspecialchars($_SESSION["username"]) ?>

        </span>


        <a
            class="logout"
            href="../logout.php"
        >
            Logout
        </a>

    </div>

</div>



<!-- =========================================
     MAIN CONTENT
========================================= -->

<div class="container">


    <!-- WELCOME -->

    <div class="welcome">

        <h1>
            Dashboard
        </h1>

        <p>
            Welcome to the Gym Management System.
        </p>

    </div>



    <!-- =========================================
         STATISTICS
    ========================================== -->

    <div class="cards">


        <!-- TOTAL MEMBERS -->

        <div class="card">

            <h3>
                Total Members
            </h3>

            <div class="number">

                <?= $total_members ?>

            </div>

        </div>


        <!-- ACTIVE MEMBERS -->

        <div class="card">

            <h3>
                Active Members
            </h3>

            <div class="number">

                <?= $active_members ?>

            </div>

        </div>


        <!-- TODAY ATTENDANCE -->

        <div class="card">

            <h3>
                Today's Attendance
            </h3>

            <div class="number">

                <?= $today_attendance ?>

            </div>

        </div>


        <!-- ACTIVE MEMBERSHIPS -->

        <div class="card">

            <h3>
                Active Memberships
            </h3>

            <div class="number">

                <?= $active_memberships ?>

            </div>

        </div>


        <!-- CURRENTLY INSIDE -->

        <div class="card">

            <h3>
                Currently Inside
            </h3>

            <div class="number">

                <?= $current_inside ?>

            </div>

        </div>


    </div>



    <!-- =========================================
         QUICK MENU
    ========================================== -->

    <div class="menu">


        <a href="../members/index.php">

            <div class="menu-icon">
                👤
            </div>

            Members

        </a>


        <a href="../members/add.php">

            <div class="menu-icon">
                ➕
            </div>

            Add Member

        </a>


        <a href="../attendance/scanner.php">

            <div class="menu-icon">
                📷
            </div>

            QR Scanner

        </a>


        <a href="../members/memberships.php">

            <div class="menu-icon">
                💳
            </div>

            Memberships

        </a>


        <a href="../coaches/index.php">

            <div class="menu-icon">
                🧑‍🏫
            </div>

            Coaches

        </a>


        <a href="training_sessions.php">

            <div class="menu-icon">
                🏋️
            </div>

            Training Sessions

        </a>


        <a href="../attendance/dashboard.php">

            <div class="menu-icon">
                📊
            </div>

            Attendance

        </a>


    </div>



    <!-- =========================================
         RECENT ATTENDANCE
    ========================================== -->

    <div class="attendance-card">

        <h2>
            Recent Attendance
        </h2>


        <div class="table-container">

            <table>


                <thead>

                    <tr>

                        <th>
                            Member Code
                        </th>

                        <th>
                            Member Name
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Time-In
                        </th>

                        <th>
                            Time-Out
                        </th>

                        <th>
                            Duration
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($recent_attendance) === 0): ?>


                    <tr>

                        <td
                            colspan="7"
                            class="empty"
                        >

                            No attendance records found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $recent_attendance
                        as $attendance
                    ): ?>


                        <tr>


                            <td>

                                <?= htmlspecialchars(
                                    $attendance["member_code"]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $attendance["name"]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $attendance["attendance_date"]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $attendance["time_in"]
                                ) ?>

                            </td>


                            <td>

                                <?php

                                if (
                                    empty(
                                        $attendance["time_out"]
                                    )
                                ) {

                                    echo "—";

                                } else {

                                    echo htmlspecialchars(
                                        $attendance["time_out"]
                                    );

                                }

                                ?>

                            </td>


                            <td>

                                <?php

                                if (
                                    $attendance[
                                        "duration_minutes"
                                    ] !== null
                                ) {

                                    echo
                                        htmlspecialchars(
                                            $attendance[
                                                "duration_minutes"
                                            ]
                                        )
                                        . " min";

                                } else {

                                    echo "—";

                                }

                                ?>

                            </td>


                            <td>


                                <?php

                                $status =
                                    $attendance["status"];

                                if (
                                    strtoupper($status)
                                    === "IN"
                                ) {

                                    $status_class =
                                        "status-in";

                                } else {

                                    $status_class =
                                        "status-out";

                                }

                                ?>


                                <span
                                    class="status
                                    <?= $status_class ?>"
                                >

                                    <?= htmlspecialchars(
                                        $status
                                    ) ?>

                                </span>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>


            </table>

        </div>

    </div>


</div>


</body>

</html>