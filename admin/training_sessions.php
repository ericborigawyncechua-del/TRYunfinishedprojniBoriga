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
// COMPLETE TRAINING SESSION
// ===============================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["complete_session"])
) {

    $session_id = (int) $_POST["session_id"];

    try {

        // Get the training session
        $stmt = $pdo->prepare("
            SELECT
                member_id,
                coach_id,
                status
            FROM training_sessions
            WHERE id = ?
        ");

        $stmt->execute([$session_id]);

        $session = $stmt->fetch(PDO::FETCH_ASSOC);


        // Check if session exists
        if (!$session) {

            $message = "Training session not found.";
            $message_type = "error";

        // Check if already completed
        } elseif ($session["status"] === "Completed") {

            $message =
                "This training session has already been completed.";

            $message_type = "error";

        // Check if cancelled
        } elseif ($session["status"] === "Cancelled") {

            $message =
                "This training session has been cancelled.";

            $message_type = "error";

        } else {

            // ===============================
            // GET MEMBER TRAINING PACKAGE
            // ===============================

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    total_sessions,
                    sessions_used,
                    sessions_remaining
                FROM training_packages
                WHERE member_id = ?
                ORDER BY id DESC
                LIMIT 1
            ");

            $stmt->execute([
                $session["member_id"]
            ]);

            $package = $stmt->fetch(PDO::FETCH_ASSOC);


            // No package
            if (!$package) {

                $message =
                    "This member does not have a training package.";

                $message_type = "error";

            // No remaining sessions
            } elseif ($package["sessions_remaining"] <= 0) {

                $message =
                    "This member has no remaining training sessions.";

                $message_type = "error";

            } else {

                // ===============================
                // START TRANSACTION
                // ===============================

                $pdo->beginTransaction();


                try {

                    // ===============================
                    // DEDUCT ONE SESSION
                    // ===============================

                    $stmt = $pdo->prepare("
                        UPDATE training_packages
                        SET
                            sessions_used = sessions_used + 1,
                            sessions_remaining = sessions_remaining - 1
                        WHERE id = ?
                        AND sessions_remaining > 0
                    ");

                    $stmt->execute([
                        $package["id"]
                    ]);


                    // Make sure the package was updated
                    if ($stmt->rowCount() !== 1) {

                        throw new Exception(
                            "Unable to deduct training session."
                        );
                    }


                    // ===============================
                    // MARK SESSION AS COMPLETED
                    // ===============================

                    $stmt = $pdo->prepare("
                        UPDATE training_sessions
                        SET status = 'Completed'
                        WHERE id = ?
                        AND status = 'Scheduled'
                    ");

                    $stmt->execute([
                        $session_id
                    ]);


                    // Make sure the session was updated
                    if ($stmt->rowCount() !== 1) {

                        throw new Exception(
                            "Unable to mark training session as completed."
                        );
                    }


                    // ===============================
                    // SAVE CHANGES
                    // ===============================

                    $pdo->commit();


                    $message =
                        "Training session completed successfully. One session has been deducted.";

                    $message_type = "success";


                } catch (Exception $e) {

                    // Undo changes if something failed
                    $pdo->rollBack();

                    $message =
                        "Error completing session: " . $e->getMessage();

                    $message_type = "error";
                }
            }
        }

    } catch (PDOException $e) {

        $message =
            "Database error: " . $e->getMessage();

        $message_type = "error";
    }
}


// ===============================
// SCHEDULE TRAINING SESSION
// ===============================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["schedule_session"])
) {

    $member_id = (int) $_POST["member_id"];

    $coach_id = !empty($_POST["coach_id"])
        ? (int) $_POST["coach_id"]
        : null;

    $session_date = $_POST["session_date"];

    $session_time = $_POST["session_time"];

    $notes = trim($_POST["notes"]);


    // ===============================
    // VALIDATE INPUT
    // ===============================

    if (
        empty($member_id) ||
        empty($session_date) ||
        empty($session_time)
    ) {

        $message =
            "Please complete all required fields.";

        $message_type = "error";

    } else {

        try {

            // ===============================
            // CHECK TRAINING PACKAGE
            // ===============================

            $check = $pdo->prepare("
                SELECT
                    id,
                    coach_id,
                    sessions_remaining
                FROM training_packages
                WHERE member_id = ?
                AND sessions_remaining > 0
                ORDER BY id DESC
                LIMIT 1
            ");

            $check->execute([
                $member_id
            ]);

            $package = $check->fetch(PDO::FETCH_ASSOC);


            // No available package
            if (!$package) {

                $message =
                    "This member does not have any remaining training sessions.";

                $message_type = "error";

            } else {

                // ===============================
                // USE ASSIGNED COACH
                // ===============================

                if (
                    $coach_id === null &&
                    $package["coach_id"] !== null
                ) {

                    $coach_id = (int) $package["coach_id"];
                }


                // ===============================
                // INSERT SESSION
                // ===============================

                $stmt = $pdo->prepare("
                    INSERT INTO training_sessions
                    (
                        member_id,
                        coach_id,
                        session_date,
                        session_time,
                        notes,
                        status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Scheduled'
                    )
                ");


                $stmt->execute([
                    $member_id,
                    $coach_id,
                    $session_date,
                    $session_time,
                    $notes
                ]);


                $message =
                    "Training session scheduled successfully.";

                $message_type = "success";
            }

        } catch (PDOException $e) {

            $message =
                "Error scheduling session: " . $e->getMessage();

            $message_type = "error";
        }
    }
}


// ===============================
// GET ACTIVE MEMBERS
// ===============================

$stmt = $pdo->query("
    SELECT
        id,
        member_code,
        name
    FROM members
    WHERE status = 'Active'
    ORDER BY name ASC
");

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ===============================
// GET ACTIVE COACHES
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
// GET TRAINING SESSIONS
// ===============================

$stmt = $pdo->query("
    SELECT

        training_sessions.id,

        training_sessions.session_date,

        training_sessions.session_time,

        training_sessions.notes,

        training_sessions.status,

        members.member_code,

        members.name AS member_name,

        coaches.coach_code,

        coaches.name AS coach_name

    FROM training_sessions

    INNER JOIN members
        ON training_sessions.member_id = members.id

    LEFT JOIN coaches
        ON training_sessions.coach_id = coaches.id

    ORDER BY
        training_sessions.session_date DESC,
        training_sessions.session_time DESC,
        training_sessions.id DESC
");

$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Training Sessions - Gym Management System
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
           MAIN
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
                0 3px 10px rgba(0, 0, 0, 0.08);
        }


        .card h2 {

            margin-top: 0;

            margin-bottom: 20px;
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


        .form-group input,
        .form-group select,
        .form-group textarea {

            padding: 11px;

            border: 1px solid #d1d5db;

            border-radius: 6px;

            font-size: 14px;

            width: 100%;
        }


        .form-group textarea {

            min-height: 90px;

            resize: vertical;
        }


        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {

            outline: none;

            border-color: #21180f;
        }


        .full-width {

            grid-column: 1 / -1;
        }


        /* ===============================
           SCHEDULE BUTTON
        =============================== */

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
           COMPLETE BUTTON
        =============================== */

        .complete-btn {

            padding: 8px 12px;

            border: none;

            border-radius: 6px;

            background: #166534;

            color: white;

            font-size: 13px;

            cursor: pointer;
        }


        .complete-btn:hover {

            opacity: 0.9;
        }


        /* ===============================
           MESSAGE
        =============================== */

        .message {

            padding: 12px;

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
           STATUS
        =============================== */

        .status {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }


        .status-scheduled {

            background: #fef3c7;

            color: #92400e;
        }


        .status-completed {

            background: #dcfce7;

            color: #166534;
        }


        .status-cancelled {

            background: #fee2e2;

            color: #991b1b;
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

            text-align: left;

            padding: 14px;

            white-space: nowrap;
        }


        td {

            padding: 14px;

            border-bottom:
                1px solid #e5e7eb;

            white-space: nowrap;
        }


        tr:hover {

            background: #fafafa;
        }


        .no-coach {

            color: #6b7280;
        }


        .notes {

            max-width: 250px;

            white-space: normal;
        }


        .completed-text {

            font-weight: bold;

            color: #166534;
        }


        .cancelled-text {

            font-weight: bold;

            color: #991b1b;
        }


        /* ===============================
           RESPONSIVE
        =============================== */

        @media (max-width: 700px) {

            .form-grid {

                grid-template-columns: 1fr;
            }


            .full-width {

                grid-column: auto;
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


    <a href="dashboard.php">
        ← Dashboard
    </a>

</div>


<!-- ===============================
     MAIN
=============================== -->

<div class="container">


    <div class="title">

        <h1>
            Training Session Management
        </h1>


        <p>
            Schedule and manage training sessions.
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
         SCHEDULE SESSION
    =============================== -->

    <div class="card">

        <h2>
            Schedule Training Session
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


                    <select name="coach_id">

                        <option value="">
                            Use Assigned Coach
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


                <!-- DATE -->

                <div class="form-group">

                    <label>
                        Session Date
                    </label>


                    <input
                        type="date"
                        name="session_date"
                        required
                    >

                </div>


                <!-- TIME -->

                <div class="form-group">

                    <label>
                        Session Time
                    </label>


                    <input
                        type="time"
                        name="session_time"
                        required
                    >

                </div>


                <!-- NOTES -->

                <div class="form-group full-width">

                    <label>
                        Notes
                    </label>


                    <textarea
                        name="notes"
                        placeholder="Optional notes..."
                    ></textarea>

                </div>


            </div>


            <button
                type="submit"
                name="schedule_session"
                class="submit-btn"
            >

                + Schedule Session

            </button>


        </form>

    </div>


    <!-- ===============================
         SESSION LIST
    =============================== -->

    <div class="card">

        <h2>
            Training Sessions
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
                            Date
                        </th>

                        <th>
                            Time
                        </th>

                        <th>
                            Notes
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($sessions) === 0): ?>


                    <tr>

                        <td
                            colspan="8"
                            style="text-align:center; padding:30px;"
                        >

                            No training sessions found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($sessions as $session): ?>


                        <tr>


                            <!-- MEMBER CODE -->

                            <td>

                                <?= htmlspecialchars(
                                    $session["member_code"]
                                ) ?>

                            </td>


                            <!-- MEMBER NAME -->

                            <td>

                                <?= htmlspecialchars(
                                    $session["member_name"]
                                ) ?>

                            </td>


                            <!-- COACH -->

                            <td>

                                <?php if ($session["coach_name"]): ?>

                                    <?= htmlspecialchars(
                                        $session["coach_name"]
                                    ) ?>

                                    -
                                    <?= htmlspecialchars(
                                        $session["coach_code"]
                                    ) ?>

                                <?php else: ?>

                                    <span class="no-coach">
                                        No Coach
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- DATE -->

                            <td>

                                <?= htmlspecialchars(
                                    $session["session_date"]
                                ) ?>

                            </td>


                            <!-- TIME -->

                            <td>

                                <?= htmlspecialchars(
                                    $session["session_time"]
                                ) ?>

                            </td>


                            <!-- NOTES -->

                            <td class="notes">

                                <?= htmlspecialchars(
                                    $session["notes"] ?? ""
                                ) ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php

                                $status =
                                    $session["status"];

                                $status_class =
                                    "status-" .
                                    strtolower($status);

                                ?>


                                <span
                                    class="status <?= htmlspecialchars($status_class) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $status
                                    ) ?>

                                </span>

                            </td>


                            <!-- ACTION -->

                            <td>


                                <?php if (
                                    $session["status"] === "Scheduled"
                                ): ?>


                                    <form
                                        method="POST"
                                        style="margin:0;"
                                    >

                                        <input
                                            type="hidden"
                                            name="session_id"
                                            value="<?= $session["id"] ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="complete_session"
                                            class="complete-btn"
                                            onclick="return confirm('Mark this training session as completed? One session will be deducted.');"
                                        >

                                            ✓ Complete

                                        </button>

                                    </form>


                                <?php elseif (
                                    $session["status"] === "Completed"
                                ): ?>


                                    <span class="completed-text">

                                        ✓ Completed

                                    </span>


                                <?php elseif (
                                    $session["status"] === "Cancelled"
                                ): ?>


                                    <span class="cancelled-text">

                                        Cancelled

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