<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
require_once "../vendor/autoload.php";

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if (!isset($_GET["id"])) {
    die("Member ID is required.");
}

$member_id = intval($_GET["id"]);

$stmt = $pdo->prepare("
    SELECT *
    FROM members
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$member_id]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    die("Member not found.");
}

$qrData = $member["qr_token"];

$qrCode = new QrCode(
    data: $qrData,
    size: 300,
    margin: 10
);

$writer = new PngWriter();

$result = $writer->write($qrCode);

$fileName = $member["member_code"] . ".png";

$filePath = "../assets/qr/" . $fileName;

$result->saveToFile($filePath);

header("Location: view.php?id=" . $member_id);

exit;