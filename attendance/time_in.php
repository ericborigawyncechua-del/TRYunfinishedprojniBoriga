<?php

session_start();

header("Content-Type: application/json");


// Check login
if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "You are not logged in."
    ]);

    exit;
}


// Database connection
require_once "../config/database.php";


// Check QR token
if (
    !isset($_POST["qr_token"]) ||
    trim($_POST["qr_token"]) === ""
) {

    echo json_encode([
        "success" => false,
        "message" => "No QR code was received."
    ]);

    exit;
}


$qr_token = trim($_POST["qr_token"]);


// Find member
$stmt = $pdo->prepare("
    SELECT
        id,
        member_code,
        name,
        status
    FROM members
    WHERE qr_token = ?
    LIMIT 1
");

$stmt->execute([$qr_token]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);


// Member not found
if (!$member) {

    echo json_encode([
        "success" => false,
        "message" => "Member not found."
    ]);

    exit;
}


// Check member status
if ($member["status"] !== "Active") {

    echo json_encode([
        "success" => false,
        "message" => "This member is inactive."
    ]);

    exit;
}


$member_id = $member["id"];

// ========================================
// CHECK MEMBERSHIP
// ========================================

$stmt = $pdo->prepare("
    SELECT
        id,
        membership_type,
        start_date,
        expiration_date,
        status
    FROM memberships
    WHERE member_id = ?
    AND status = 'Active'
    AND start_date <= CURDATE()
    AND expiration_date >= CURDATE()
    ORDER BY expiration_date DESC
    LIMIT 1
");

$stmt->execute([
    $member_id
]);

$membership = $stmt->fetch(PDO::FETCH_ASSOC);


// No valid membership
if (!$membership) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Access denied. This member does not have an active membership."
    ]);

    exit;
}


// Find today's latest attendance
$stmt = $pdo->prepare("
    SELECT
        id,
        time_in,
        time_out,
        duration_minutes,
        status
    FROM attendance
    WHERE member_id = ?
      AND attendance_date = CURDATE()
    ORDER BY id DESC
    LIMIT 1
");

$stmt->execute([$member_id]);

$attendance = $stmt->fetch(PDO::FETCH_ASSOC);


// =====================================================
// TIME-OUT
// =====================================================

if (
    $attendance &&
    $attendance["status"] === "IN" &&
    $attendance["time_out"] === null
) {

    $attendance_id = $attendance["id"];


    // Calculate duration in minutes
    $stmt = $pdo->prepare("
        UPDATE attendance

        SET
            time_out = CURTIME(),
            duration_minutes =
                TIME_TO_SEC(
                    TIMEDIFF(
                        CURTIME(),
                        time_in
                    )
                ) DIV 60,
            status = 'COMPLETED'

        WHERE id = ?
    ");

    $stmt->execute([
        $attendance_id
    ]);


    // Get updated record
    $stmt = $pdo->prepare("
        SELECT
            time_in,
            time_out,
            duration_minutes
        FROM attendance
        WHERE id = ?
    ");

    $stmt->execute([
        $attendance_id
    ]);

    $updated = $stmt->fetch(PDO::FETCH_ASSOC);


    echo json_encode([

        "success" => true,

        "action" => "TIME_OUT",

        "member_id" =>
            $member["id"],

        "member_code" =>
            $member["member_code"],

        "member_name" =>
            $member["name"],

        "status" =>
            $member["status"],

        "time_in" =>
            date(
                "g:i A",
                strtotime($updated["time_in"])
            ),

        "time_out" =>
            date(
                "g:i A",
                strtotime($updated["time_out"])
            ),

        "duration_minutes" =>
            $updated["duration_minutes"],

        "message" =>
            "Time-Out recorded successfully."

    ]);

    exit;
}


// =====================================================
// TIME-IN
// =====================================================


// Create new attendance
$stmt = $pdo->prepare("
    INSERT INTO attendance
    (
        member_id,
        attendance_date,
        time_in,
        status
    )

    VALUES
    (
        ?,
        CURDATE(),
        CURTIME(),
        'IN'
    )
");

$stmt->execute([
    $member_id
]);


$attendance_id = $pdo->lastInsertId();


// Get Time-In
$stmt = $pdo->prepare("
    SELECT
        time_in
    FROM attendance
    WHERE id = ?
");

$stmt->execute([
    $attendance_id
]);

$new_attendance = $stmt->fetch(PDO::FETCH_ASSOC);


// Return Time-In result
echo json_encode([

    "success" => true,

    "action" => "TIME_IN",

    "member_id" =>
        $member["id"],

    "member_code" =>
        $member["member_code"],

    "member_name" =>
        $member["name"],

    "status" =>
        $member["status"],

    "membership_type" =>
        $membership["membership_type"],

    "expiration_date" =>
        $membership["expiration_date"],

    "time_in" =>
        date(
            "g:i A",
            strtotime($new_attendance["time_in"])
        ),

    "message" =>
        "Time-In recorded successfully."

]);

exit;