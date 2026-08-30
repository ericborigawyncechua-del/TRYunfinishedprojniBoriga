<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $contact = trim($_POST["contact"]);
    $email = trim($_POST["email"]);
    $address = trim($_POST["address"]);

    $membership_type = trim($_POST["membership_type"]);
    $start_date = $_POST["start_date"];
    $expiration_date = $_POST["expiration_date"];

    $coach_id = !empty($_POST["coach_id"])
        ? $_POST["coach_id"]
        : null;

    $total_sessions = intval($_POST["total_sessions"]);

    if ($name === "" || $contact === "" || $membership_type === "") {

        $error = "Please fill in all required fields.";

    } else {

        try {

            $pdo->beginTransaction();

            /*
             * Generate temporary member code.
             * The actual ID will come from MySQL.
             */

            $temp_code = "TEMP-" . uniqid();

            /*
             * Generate a random QR token.
             */

            $qr_token = bin2hex(random_bytes(16));

            /*
             * Insert member.
             */

            $stmt = $pdo->prepare("
                INSERT INTO members
                (
                    member_code,
                    name,
                    contact,
                    email,
                    address,
                    qr_token,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'Active'
                )
            ");

            $stmt->execute([
                $temp_code,
                $name,
                $contact,
                $email,
                $address,
                $qr_token
            ]);

            $member_id = $pdo->lastInsertId();

            /*
             * Create the final member code.
             */

            $member_code = "GYM-" . str_pad(
                $member_id,
                4,
                "0",
                STR_PAD_LEFT
            );

            $stmt = $pdo->prepare("
                UPDATE members
                SET member_code = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $member_code,
                $member_id
            ]);

            /*
             * Insert membership.
             */

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
                    'Active'
                )
            ");

            $stmt->execute([
                $member_id,
                $membership_type,
                $start_date,
                $expiration_date
            ]);

            /*
             * Insert training package.
             */

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
                $member_id,
                $coach_id,
                $total_sessions,
                $total_sessions
            ]);

            $pdo->commit();

            $message = "Member successfully added! Member ID: " . $member_code;

        } catch (Exception $e) {

            $pdo->rollBack();

            $error = "Error adding member: " . $e->getMessage();
        }
    }
}

/*
 * Get active coaches.
 */

$coach_stmt = $pdo->query("
    SELECT id, coach_code, name
    FROM coaches
    WHERE status = 'Active'
    ORDER BY name ASC
");

$coaches = $coach_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Member - Gym System</title>

    <style>

        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            background: #f3f4f6;
        }

        .navbar {
            background: #111827;
            color: white;
            padding: 18px 30px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar a {
            color: white;
            text-decoration: none;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .form-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
        }

        .section-title {
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .full {
            grid-column: span 2;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .buttons {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }

        button,
        .back {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        button {
            background: #111827;
            color: white;
        }

        .back {
            background: #e5e7eb;
            color: #111827;
        }

    </style>

</head>

<body>

<div class="navbar">

    <strong>GYM MANAGEMENT SYSTEM</strong>

    <a href="../admin/dashboard.php">
        Dashboard
    </a>

</div>

<div class="container">

    <div class="form-box">

        <h1>Add New Member</h1>

        <?php if ($message): ?>

            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <h3 class="section-title">
                Member Information
            </h3>

            <div class="form-grid">

                <div class="form-group full">

                    <label>
                        Full Name *
                    </label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Enter member's full name"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Contact Number *
                    </label>

                    <input
                        type="text"
                        name="contact"
                        placeholder="09XXXXXXXXX"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        placeholder="member@email.com"
                    >

                </div>

                <div class="form-group full">

                    <label>
                        Address
                    </label>

                    <textarea
                        name="address"
                        placeholder="Enter member's address"
                    ></textarea>

                </div>

            </div>


            <h3 class="section-title">
                Membership Information
            </h3>

            <div class="form-grid">

                <div class="form-group">

                    <label>
                        Membership Type *
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

                        <option value="Yearly">
                            Yearly
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label>
                        Start Date *
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        value="<?php echo date('Y-m-d'); ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Expiration Date *
                    </label>

                    <input
                        type="date"
                        name="expiration_date"
                        required
                    >

                </div>

            </div>


            <h3 class="section-title">
                Coach & Training
            </h3>

            <div class="form-grid">

                <div class="form-group">

                    <label>
                        Assigned Coach
                    </label>

                    <select name="coach_id">

                        <option value="">
                            No Coach
                        </option>

                        <option value="Coach Eric">
                            Coach Eric
                        </option>

                        <option value="Coach Dave">
                            Coach Dave
                        </option>

                    </select>

                        <?php foreach ($coaches as $coach): ?>

                            <option value="<?php echo $coach["id"]; ?>">

                                <?php echo htmlspecialchars($coach["name"]); ?>

                                -
                                <?php echo htmlspecialchars($coach["coach_code"]); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>
                        Total Training Sessions
                    </label>

                    <input
                        type="number"
                        name="total_sessions"
                        value="0"
                        min="0"
                    >

                </div>

            </div>


            <div class="buttons">

                <a
                    class="back"
                    href="index.php"
                >
                    Cancel
                </a>

                <button type="submit">
                    Add Member
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>