<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";


// ========================================
// DASHBOARD COUNTS
// ========================================

// Total attendance today
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM attendance
    WHERE attendance_date = CURDATE()
");

$total_today = $stmt->fetchColumn();


// Currently inside
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM attendance
    WHERE attendance_date = CURDATE()
    AND status = 'IN'
");

$current_inside = $stmt->fetchColumn();


// Completed visits
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM attendance
    WHERE attendance_date = CURDATE()
    AND status = 'COMPLETED'
");

$completed_today = $stmt->fetchColumn();


// ========================================
// TODAY'S ATTENDANCE
// ========================================

$stmt = $pdo->query("
    SELECT
        attendance.id,
        members.member_code,
        members.name,
        attendance.time_in,
        attendance.time_out,
        attendance.duration_minutes,
        attendance.status

    FROM attendance

    INNER JOIN members
        ON attendance.member_id = members.id

    WHERE attendance.attendance_date = CURDATE()

    ORDER BY attendance.id DESC
");

$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Attendance - Gym Management System</title>

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


        /* HEADER */

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


        /* MAIN */

        .container {
            padding: 45px 28px;
        }

        .title {
            margin-bottom: 25px;
        }

        .title h1 {
            margin: 0 0 10px;
            font-size: 32px;
        }

        .title p {
            margin: 0;
            font-size: 16px;
        }


        /* STAT CARDS */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

            margin-bottom: 30px;
        }

        .stat-card {
            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 3px 10px rgba(0,0,0,0.08);
        }

        .stat-card h3 {
            margin: 0 0 15px;

            color: #4b5563;

            font-size: 17px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
        }


        /* TABLE CARD */

        .table-card {
            background: white;

            border-radius: 10px;

            padding: 25px;

            box-shadow:
                0 3px 10px rgba(0,0,0,0.08);
        }

        .table-card h2 {
            margin-top: 0;
        }


        table {
            width: 100%;

            border-collapse: collapse;

            margin-top: 20px;
        }

        th {
            background: #f3f4f6;

            text-align: left;

            padding: 14px;

            font-size: 14px;
        }

        td {
            padding: 14px;

            border-bottom:
                1px solid #e5e7eb;

            font-size: 14px;
        }


        /* STATUS */

        .status {
            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }

        .status-in {
            background: #dcfce7;
            color: #166534;
        }

        .status-completed {
            background: #e5e7eb;
            color: #374151;
        }

        .status-out {
            background: #fee2e2;
            color: #991b1b;
        }


        /* BUTTONS */

        .buttons {
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;

            padding: 10px 16px;

            background: #21180f;

            color: white;

            text-decoration: none;

            border-radius: 6px;

            margin-right: 8px;
        }

        .btn:hover {
            opacity: 0.9;
        }


        /* MOBILE */

        @media (max-width: 800px) {

            .stats {
                grid-template-columns: 1fr;
            }

            .table-card {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }

        }

    </style>

</head>


<body>


<!-- HEADER -->

<div class="header">

    <h2>
        D'FITNESS ATTENDANCE
    </h2>

    <a href="../admin/dashboard.php">
        ← Dashboard
    </a>

</div>


<!-- MAIN -->

<div class="container">


    <div class="title">

        <h1>
            Attendance
        </h1>

        <p>
            Today's gym attendance records
        </p>

    </div>


    <!-- BUTTONS -->

    <div class="buttons">

        <a
            href="scanner.php"
            class="btn"
        >
            📷 QR Scanner
        </a>

    </div>


    <!-- STATISTICS -->

    <div class="stats">


        <div class="stat-card">

            <h3>
                Today's Visits
            </h3>

            <div class="stat-number">
                <?= $total_today ?>
            </div>

        </div>


        <div class="stat-card">

            <h3>
                Currently Inside
            </h3>

            <div class="stat-number">
                <?= $current_inside ?>
            </div>

        </div>


        <div class="stat-card">

            <h3>
                Completed Visits
            </h3>

            <div class="stat-number">
                <?= $completed_today ?>
            </div>

        </div>


    </div>


    <!-- ATTENDANCE TABLE -->

    <div class="table-card">

        <h2>
            Today's Attendance
        </h2>


        <?php if (count($attendance) === 0): ?>

            <p>
                No attendance records yet today.
            </p>

        <?php else: ?>


        <table>

            <thead>

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Member Code
                    </th>

                    <th>
                        Member Name
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

            <?php foreach ($attendance as $row): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($row["id"]) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars($row["member_code"]) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars($row["name"]) ?>
                    </td>


                    <td>

                        <?php

                        if ($row["time_in"]) {

                            echo date(
                                "g:i A",
                                strtotime($row["time_in"])
                            );

                        } else {

                            echo "--";

                        }

                        ?>

                    </td>


                    <td>

                        <?php

                        if ($row["time_out"]) {

                            echo date(
                                "g:i A",
                                strtotime($row["time_out"])
                            );

                        } else {

                            echo "--";

                        }

                        ?>

                    </td>


                    <td>

                        <?php

                        if ($row["duration_minutes"] !== null) {

                            echo
                                htmlspecialchars(
                                    $row["duration_minutes"]
                                )
                                . " minutes";

                        } else {

                            echo "--";

                        }

                        ?>

                    </td>


                    <td>

                        <?php

                        $status = $row["status"];

                        if ($status === "IN") {

                            echo '
                                <span class="status status-in">
                                    IN
                                </span>
                            ';

                        }

                        elseif ($status === "COMPLETED") {

                            echo '
                                <span class="status status-completed">
                                    COMPLETED
                                </span>
                            ';

                        }

                        else {

                            echo '
                                <span class="status status-out">
                                    OUT
                                </span>
                            ';

                        }

                        ?>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>


        <?php endif; ?>


    </div>


</div>


</body>

</html>