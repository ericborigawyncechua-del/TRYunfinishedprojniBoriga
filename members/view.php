<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

if (!isset($_GET["id"])) {
    die("Member ID is required.");
}

$member_id = intval($_GET["id"]);

$stmt = $pdo->prepare("
    SELECT
        m.*,

        ms.membership_type,
        ms.start_date,
        ms.expiration_date,
        ms.status AS membership_status,

        c.name AS coach_name,

        tp.total_sessions,
        tp.sessions_used,
        tp.sessions_remaining

    FROM members m

    LEFT JOIN memberships ms
        ON m.id = ms.member_id

    LEFT JOIN training_packages tp
        ON m.id = tp.member_id

    LEFT JOIN coaches c
        ON tp.coach_id = c.id

    WHERE m.id = ?

    LIMIT 1
");

$stmt->execute([$member_id]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    die("Member not found.");
}

$qrFile = "../assets/qr/" . $member["member_code"] . ".png";

$qrExists = file_exists($qrFile);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($member["name"]); ?>
        - Gym System
    </title>

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
        }

        .navbar a {
            color: white;
            text-decoration: none;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .profile {
            background: white;
            padding: 30px;
            border-radius: 12px;

            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 40px;

            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .info h1 {
            margin-top: 0;
        }

        .info-row {
            margin: 12px 0;
        }

        .label {
            font-weight: bold;
        }

        .qr-section {
            text-align: center;
            border-left: 1px solid #ddd;
            padding-left: 30px;
        }

        .qr-section img {
            width: 250px;
            height: 250px;
        }

        .generate {
            display: inline-block;
            background: #111827;
            color: white;
            padding: 12px 18px;
            border-radius: 6px;
            text-decoration: none;
        }

        .print-button {
            margin-top: 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .status-active {
            color: #15803d;
            font-weight: bold;
        }

        .status-expired {
            color: #dc2626;
            font-weight: bold;
        }

        @media print {

            .navbar,
            .print-button,
            .generate {
                display: none;
            }

            body {
                background: white;
            }

            .profile {
                box-shadow: none;
            }

        }

    </style>

</head>

<body>

<div class="navbar">

    <strong>GYM MANAGEMENT SYSTEM</strong>

    <a href="index.php">
        ← Back to Members
    </a>

</div>

<div class="container">

    <div class="profile">

        <div class="info">

            <h1>
                <?php echo htmlspecialchars($member["name"]); ?>
            </h1>

            <div class="info-row">

                <span class="label">
                    Member ID:
                </span>

                <?php echo htmlspecialchars($member["member_code"]); ?>

            </div>

            <div class="info-row">

                <span class="label">
                    Contact:
                </span>

                <?php echo htmlspecialchars($member["contact"]); ?>

            </div>

            <div class="info-row">

                <span class="label">
                    Email:
                </span>

                <?php echo htmlspecialchars($member["email"] ?? "-"); ?>

            </div>

            <div class="info-row">

                <span class="label">
                    Address:
                </span>

                <?php echo htmlspecialchars($member["address"] ?? "-"); ?>

            </div>


            <h3>
                Membership
            </h3>

            <div class="info-row">

                <span class="label">
                    Type:
                </span>

                <?php echo htmlspecialchars($member["membership_type"] ?? "-"); ?>

            </div>

            <div class="info-row">

                <span class="label">
                    Start:
                </span>

                <?php echo htmlspecialchars($member["start_date"] ?? "-"); ?>

            </div>

            <div class="info-row">

                <span class="label">
                    Expires:
                </span>

                <?php echo htmlspecialchars($member["expiration_date"] ?? "-"); ?>

            </div>


            <h3>
                Coaching
            </h3>

            <div class="info-row">

                <span class="label">
                    Coach:
                </span>

                <?php echo htmlspecialchars($member["coach_name"] ?? "No Coach"); ?>

            </div>

            <div class="info-row">

                <span class="label">
                    Total Sessions:
                </span>

                <?php echo htmlspecialchars($member["total_sessions"] ?? "0"); ?>

            </div>

            <div class="info-row">

                <span class="label">
                    Sessions Used:
                </span>

                <?php echo htmlspecialchars($member["sessions_used"] ?? "0"); ?>

            </div>

            <div class="info-row">

                <span class="label">
                    Sessions Remaining:
                </span>

                <?php echo htmlspecialchars($member["sessions_remaining"] ?? "0"); ?>

            </div>

        </div>


        <div class="qr-section">

            <h2>
                Member QR Code
            </h2>

            <?php if ($qrExists): ?>

                <img
                    src="<?php echo htmlspecialchars($qrFile); ?>"
                    alt="Member QR Code"
                >

                <p>

                    <strong>
                        <?php echo htmlspecialchars($member["member_code"]); ?>
                    </strong>

                </p>

                <button
                    class="print-button"
                    onclick="window.print()"
                >
                    Print QR
                </button>

            <?php else: ?>

                <p>
                    QR code has not been generated yet.
                </p>

                <a
                    class="generate"
                    href="generate_qr.php?id=<?php echo $member["id"]; ?>"
                >
                    Generate QR Code
                </a>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>