<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";


// ===============================
// ADD COACH
// ===============================

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $coach_code = trim($_POST["coach_code"]);
    $name = trim($_POST["name"]);
    $contact = trim($_POST["contact"]);
    $status = $_POST["status"];

    if (empty($coach_code) || empty($name)) {

        $message = "Please complete the required fields.";
        $message_type = "error";

    } else {

        try {

            // Check if coach code already exists
            $check = $pdo->prepare("
                SELECT id
                FROM coaches
                WHERE coach_code = ?
            ");

            $check->execute([$coach_code]);

            if ($check->fetch()) {

                $message = "Coach code already exists.";
                $message_type = "error";

            } else {

                // Insert coach
                $stmt = $pdo->prepare("
                    INSERT INTO coaches
                    (
                        coach_code,
                        name,
                        contact,
                        status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $coach_code,
                    $name,
                    $contact,
                    $status
                ]);

                $message = "Coach added successfully.";
                $message_type = "success";
            }

        } catch (PDOException $e) {

            $message = "Error adding coach: " . $e->getMessage();
            $message_type = "error";
        }
    }
}


// ===============================
// GET COACHES
// ===============================

$stmt = $pdo->query("
    SELECT
        id,
        coach_code,
        name,
        contact,
        status,
        created_at
    FROM coaches
    ORDER BY id DESC
");

$coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Coaches - Gym Management System
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
           MAIN CONTAINER
        =============================== */

        .container {

            max-width: 1200px;

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
        .form-group select {

            padding: 11px;

            border: 1px solid #d1d5db;

            border-radius: 6px;

            font-size: 14px;

            width: 100%;
        }


        .form-group input:focus,
        .form-group select:focus {

            outline: none;

            border-color: #21180f;
        }


        /* ===============================
           BUTTON
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
        }


        td {

            padding: 14px;

            border-bottom:
                1px solid #e5e7eb;
        }


        tr:hover {

            background: #fafafa;
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


        .inactive {

            background: #e5e7eb;

            color: #374151;
        }


        /* ===============================
           EMPTY
        =============================== */

        .empty {

            text-align: center;

            color: #6b7280;

            padding: 30px;
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
            Coach Management
        </h1>

        <p>
            Add and manage gym coaches.
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
         ADD COACH
    =============================== -->

    <div class="card">

        <h2>
            Add Coach
        </h2>


        <form method="POST">


            <div class="form-grid">


                <!-- COACH CODE -->

                <div class="form-group">

                    <label>
                        Coach Code
                    </label>

                    <input
                        type="text"
                        name="coach_code"
                        placeholder="Example: C001"
                        required
                    >

                </div>


                <!-- NAME -->

                <div class="form-group">

                    <label>
                        Coach Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Enter coach name"
                        required
                    >

                </div>


                <!-- CONTACT -->

                <div class="form-group">

                    <label>
                        Contact
                    </label>

                    <input
                        type="text"
                        name="contact"
                        placeholder="Enter contact number"
                    >

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
                            Inactive
                        </option>

                    </select>

                </div>


            </div>


            <button
                type="submit"
                class="submit-btn"
            >

                + Add Coach

            </button>


        </form>

    </div>


    <!-- ===============================
         COACH LIST
    =============================== -->

    <div class="card">

        <h2>
            Coaches
        </h2>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            Coach Code
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Contact
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Created
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($coaches) === 0): ?>

                    <tr>

                        <td
                            colspan="5"
                            class="empty"
                        >

                            No coaches found.

                        </td>

                    </tr>

                <?php else: ?>


                    <?php foreach ($coaches as $coach): ?>

                        <tr>


                            <td>

                                <?= htmlspecialchars(
                                    $coach["coach_code"]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $coach["name"]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $coach["contact"] ?? ""
                                ) ?>

                            </td>


                            <td>

                                <?php

                                $status = $coach["status"];

                                $class = strtolower($status);

                                ?>

                                <span
                                    class="status <?= htmlspecialchars($class) ?>"
                                >

                                    <?= htmlspecialchars($status) ?>

                                </span>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $coach["created_at"]
                                ) ?>

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