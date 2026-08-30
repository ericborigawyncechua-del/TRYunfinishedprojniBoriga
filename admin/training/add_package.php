<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";


// ===============================
// MESSAGE
// ===============================

$message = "";
$message_type = "";


// ===============================
// ADD TRAINING PACKAGE
// ===============================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $member_id = $_POST["member_id"] ?? "";
    $coach_id = $_POST["coach_id"] ?? "";
    $total_sessions = $_POST["total_sessions"] ?? "";

    if (
        empty($member_id) ||
        empty($total_sessions)
    ) {

        $message = "Please complete all required fields.";
        $message_type = "error";

    } elseif (!is_numeric($total_sessions) || $total_sessions <= 0) {

        $message = "Total sessions must be greater than 0.";
        $message_type = "error";

    } else {

        // If no coach was selected, store NULL
        $coach_id_value = !empty($coach_id)
            ? (int)$coach_id
            : null;

        $total_sessions = (int)$total_sessions;

        $stmt = $pdo->prepare("
            INSERT INTO training_packages
            (
                member_id,
                coach_id,
                total_sessions,
                sessions_used,
                sessions_remaining
            )
            VALUES
            (
                ?,
                ?,
                ?,
                0,
                ?
            )
        ");

        $stmt->execute([
            (int)$member_id,
            $coach_id_value,
            $total_sessions,
            $total_sessions
        ]);

        $message = "Training package added successfully.";
        $message_type = "success";
    }
}


// ===============================
// GET MEMBERS
// ===============================

$stmt = $pdo->query("
    SELECT
        id,
        member_code,
        name
    FROM members
    ORDER BY name ASC
");

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ===============================
// GET COACHES
// ===============================

$stmt = $pdo->query("
    SELECT
        id,
        coach_code,
        name
    FROM coaches
    WHERE status = 'Active'
    ORDER BY name ASC
");

$coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ===============================
// GET TRAINING PACKAGES
// ===============================

$stmt = $pdo->query("
    SELECT
        training_packages.id,
        training_packages.total_sessions,
        training_packages.sessions_used,
        training_packages.sessions_remaining,
        members.member_code,
        members.name AS member_name,
        coaches.coach_code,
        coaches.name AS coach_name

    FROM training_packages

    INNER JOIN members
        ON training_packages.member_id = members.id

    LEFT JOIN coaches
        ON training_packages.coach_id = coaches.id

    ORDER BY training_packages.id DESC
");

$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Training Packages - Gym Management System
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

        .title h1 {

            margin: 0 0 8px;

            font-size: 32px;
        }

        .title p {

            margin: 0 0 25px;

            color: #4b5563;
        }

        /* ===============================
           CARD
        =============================== */

        .card {

            background: white;

            padding: 25px;

            border-radius: 10px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 10px rgba(0,0,0,0.08);
        }

        .card h2 {

            margin-top: 0;

            margin-bottom: 20px;
        }

        /* ===============================
           MESSAGE
        =============================== */

        .message {

            padding: 12px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

            font-weight: bold;
        }

        .success {

            background: #dcfce7;

            color: #166534;
        }

        .error {

            background: #fee2e2;

            color: #991b1b;
        }

        /* ===============================
           FORM
        =============================== */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }

        .form-group {

            display: flex;

            flex-direction: column;
        }

        .form-group label {

            font-weight: bold;

            margin-bottom: 7px;
        }

        .form-group select,
        .form-group input {

            padding: 11px;

            border: 1px solid #d1d5db;

            border-radius: 6px;

            font-size: 14px;
        }

        .submit-btn {

            margin-top: 20px;

            padding: 11px 20px;

            border: none;

            border-radius: 6px;

            background: #21180f;

            color: white;

            font-size: 15px;

            cursor: pointer;
        }

        .submit-btn:hover {

            opacity: 0.9;
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

            padding: 14px;

            text-align: left;

            white-space: nowrap;
        }

        td {

            padding: 14px;

            border-bottom:
                1px solid #e5e7eb;

            white-space: nowrap;
        }

        /* ===============================
           SESSION BADGES
        =============================== */

        .remaining {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            background: #dcfce7;

            color: #166534;

            font-weight: bold;

            font-size: 12px;
        }

        .used {

            color: #6b7280;

            font-weight: bold;
        }

        .none {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            background: #fee2e2;

            color: #991b1b;

            font-weight: bold;

            font-size: 12px;
        }

        /* ===============================
           RESPONSIVE
        =============================== */

        @media (max-width: 700px) {

            .form-grid {

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

    <a href="../admin/dashboard.php">
        ← Dashboard
    </a>

</div>


<!-- ===============================
     MAIN
=============================== -->

<div class="container">


    <div class="title">

        <h1>
            Training Packages
        </h1>

        <p>
            Assign personal training sessions to members.
        </p>

    </div>


    <!-- ===============================
         MESSAGE
    =============================== -->

    <?php if ($message): ?>

        <div class="message <?= htmlspecialchars($message_type) ?>">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- ===============================
         ADD PACKAGE
    =============================== -->

    <div class="card">

        <h2>
            Add Training Package
        </h2>


        <form method="POST">


            <div class="form-grid">


                <!-- MEMBER -->

                <div class="form-group">

                    <label>
                        Member
                    </label>

                    <select
                        name="member_id"
                        required
                    >

                        <option value="">
                            Select Member
                        </option>

                        <?php foreach ($members as $member): ?>

                            <option
                                value="<?= $member["id"] ?>"
                            >

                                <?= htmlspecialchars(
                                    $member["name"]
                                ) ?>

                                -
                                <?= htmlspecialchars(
                                    $member["member_code"]
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- COACH -->

                <div class="form-group">

                    <label>
                        Coach
                    </label>

                    <select
                        name="coach_id"
                    >

                        <option value="">
                            No Coach
                        </option>

                        <?php foreach ($coaches as $coach): ?>

                            <option
                                value="<?= $coach["id"] ?>"
                            >

                                <?= htmlspecialchars(
                                    $coach["name"]
                                ) ?>

                                -
                                <?= htmlspecialchars(
                                    $coach["coach_code"]
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- TOTAL SESSIONS -->

                <div class="form-group">

                    <label>
                        Total Sessions
                    </label>

                    <input
                        type="number"
                        name="total_sessions"
                        min="1"
                        placeholder="Example: 10"
                        required
                    >

                </div>


            </div>


            <button
                type="submit"
                class="submit-btn"
            >

                + Add Training Package

            </button>


        </form>

    </div>


    <!-- ===============================
         PACKAGE LIST
    =============================== -->

    <div class="card">

        <h2>
            Training Packages
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
                            Coach
                        </th>

                        <th>
                            Total Sessions
                        </th>

                        <th>
                            Sessions Used
                        </th>

                        <th>
                            Sessions Remaining
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($packages) === 0): ?>


                    <tr>

                        <td
                            colspan="6"
                            style="text-align:center;"
                        >

                            No training packages found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($packages as $package): ?>


                        <tr>


                            <td>

                                <?= htmlspecialchars(
                                    $package["member_code"]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $package["member_name"]
                                ) ?>

                            </td>


                            <td>

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

                                    No Coach

                                <?php endif; ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $package["total_sessions"]
                                ) ?>

                            </td>


                            <td>

                                <span class="used">

                                    <?= htmlspecialchars(
                                        $package["sessions_used"]
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?php if (
                                    $package["sessions_remaining"] > 0
                                ): ?>

                                    <span class="remaining">

                                        <?= htmlspecialchars(
                                            $package["sessions_remaining"]
                                        ) ?>

                                        Remaining

                                    </span>

                                <?php else: ?>

                                    <span class="none">

                                        No Sessions Left

                                    </span>

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


</body>

</html>