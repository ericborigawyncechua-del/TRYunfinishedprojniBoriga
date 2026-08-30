<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$stmt = $pdo->query("
    SELECT
        m.id,
        m.member_code,
        m.name,
        m.contact,
        m.status,

        ms.membership_type,
        ms.expiration_date,

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

    ORDER BY m.id DESC
");

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Members - Gym System</title>

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
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-button {
            background: #111827;
            color: white;
            padding: 12px 18px;
            text-decoration: none;
            border-radius: 6px;
        }

        .table-box {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f9fafb;
        }

        .active {
            color: #15803d;
            font-weight: bold;
        }

        .expired {
            color: #dc2626;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #666;
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

    <div class="header">

        <h1>Members</h1>

        <a
            class="add-button"
            href="add.php"
        >
            + Add Member
        </a>

    </div>

    <div class="table-box">

        <?php if (count($members) > 0): ?>

            <table>

                <thead>

                    <tr>

                        <th>Member ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Membership</th>
                        <th>Expiration</th>
                        <th>Coach</th>
                        <th>Sessions Left</th>
                        <th>Status</th>
                        <th>Action</th>
                        

                    </tr>
                    

                </thead>

                <tbody>

                    <?php foreach ($members as $member): ?>

                        <?php

                        $today = date("Y-m-d");

                        $membership_active =
                            $member["expiration_date"] >= $today;

                        ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($member["member_code"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($member["name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($member["contact"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($member["membership_type"] ?? "-"); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($member["expiration_date"] ?? "-"); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($member["coach_name"] ?? "No Coach"); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($member["sessions_remaining"] ?? "0"); ?>
                            </td>

                            <td>

                                <?php if ($membership_active): ?>

                                    <span class="active">
                                        ACTIVE
                                    </span>

                                <?php else: ?>

                                    <span class="expired">
                                        EXPIRED
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                              <a href="view.php?id=<?php echo $member["id"]; ?>">
                                       View
                             </a>

</td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty">

                No members registered yet.

                <br><br>

                Click
                <strong>+ Add Member</strong>
                to register your first member.

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>