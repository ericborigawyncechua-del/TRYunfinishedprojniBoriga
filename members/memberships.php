<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";


// ===============================
// ADD MEMBERSHIP
// ===============================

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $member_id = $_POST["member_id"];
    $membership_type = $_POST["membership_type"];
    $start_date = $_POST["start_date"];
    $expiration_date = $_POST["expiration_date"];
    $status = $_POST["status"];

    if (
        empty($member_id) ||
        empty($membership_type) ||
        empty($start_date) ||
        empty($expiration_date)
    ) {

        $message = "Please complete all required fields.";
        $message_type = "error";

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO memberships
            (
                member_id,
                membership_type,
                start_date,
                expiration_date,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");

        $stmt->execute([
            $member_id,
            $membership_type,
            $start_date,
            $expiration_date,
            $status
        ]);

        $message = "Membership added successfully.";
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
// GET MEMBERSHIPS
// ===============================

$stmt = $pdo->query("
    SELECT
        memberships.id,
        memberships.membership_type,
        memberships.start_date,
        memberships.expiration_date,
        memberships.status,
        members.member_code,
        members.name

    FROM memberships

    INNER JOIN members
        ON memberships.member_id = members.id

    ORDER BY memberships.id DESC
");

$memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Memberships - Gym Management System</title>


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

            padding: 40px 28px;

            max-width: 1400px;

            margin: auto;
        }

        .title h1 {

            margin: 0 0 8px;

            font-size: 32px;
        }

        .title p {

            margin: 0 0 25px;
        }


        /* CARD */

        .card {

            background: white;

            padding: 25px;

            border-radius: 10px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 10px rgba(0,0,0,0.08);
        }


        /* FORM */

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


        /* MESSAGE */

        .message {

            padding: 12px;

            border-radius: 6px;

            margin-bottom: 20px;
        }

        .success {

            background: #dcfce7;

            color: #166534;
        }

        .error {

            background: #fee2e2;

            color: #991b1b;
        }


        /* TABLE */

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


        /* STATUS */

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

        .cancelled {

            background: #e5e7eb;

            color: #374151;
        }


        /* RESPONSIVE */

        @media (max-width: 700px) {

            .form-grid {

                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- HEADER -->

<div class="header">

    <h2>
        GYM MANAGEMENT SYSTEM
    </h2>

    <a href="../admin/dashboard.php">
        ← Dashboard
    </a>

</div>


<div class="container">


    <div class="title">

        <h1>
            Membership Management
        </h1>

        <p>
            Add and manage gym memberships.
        </p>

    </div>


    <?php if ($message): ?>

        <div class="message <?= $message_type ?>">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- ADD MEMBERSHIP -->

    <div class="card">

        <h2>
            Add Membership
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


                <!-- MEMBERSHIP TYPE -->

                <div class="form-group">

                    <label>
                        Membership Type
                    </label>

                    <select
                        name="membership_type"
                        required
                    >

                        <option value="">
                            Select Membership
                        </option>

                        <option value="Monthly">
                            Monthly
                        </option>

                        <option value="3 Months">
                            3 Months
                        </option>

                        <option value="6 Months">
                            6 Months
                        </option>

                        <option value="1 Year">
                            1 Year
                        </option>

                    </select>

                </div>


                <!-- START DATE -->

                <div class="form-group">

                    <label>
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        required
                    >

                </div>


                <!-- EXPIRATION DATE -->

                <div class="form-group">

                    <label>
                        Expiration Date
                    </label>

                    <input
                        type="date"
                        name="expiration_date"
                        required
                    >

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select
                        name="status"
                    >

                        <option value="Active">
                            Active
                        </option>

                        <option value="Expired">
                            Expired
                        </option>

                        <option value="Cancelled">
                            Cancelled
                        </option>

                    </select>

                </div>


            </div>


            <button
                type="submit"
                class="submit-btn"
            >

                + Add Membership

            </button>


        </form>

    </div>


    <!-- MEMBERSHIP LIST -->

    <div class="card">

        <h2>
            Memberships
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
                            Membership
                        </th>

                        <th>
                            Start Date
                        </th>

                        <th>
                            Expiration
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (count($memberships) === 0): ?>

                    <tr>

                        <td
                            colspan="6"
                            style="text-align:center;"
                        >

                            No memberships found.

                        </td>

                    </tr>

                <?php else: ?>


                    <?php foreach (
                        $memberships as $membership
                    ): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $membership["member_code"]
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $membership["name"]
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $membership["membership_type"]
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $membership["start_date"]
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $membership["expiration_date"]
                                ) ?>
                            </td>


                            <td>

                                <?php

                                $status =
                                    $membership["status"];

                                $class =
                                    strtolower($status);

                                ?>

                                <span
                                    class="
                                        status
                                        <?= $class ?>
                                    "
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