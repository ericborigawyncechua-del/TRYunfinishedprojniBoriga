<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gym QR Scanner</title>

    <script src="https://unpkg.com/html5-qrcode"></script>

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
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
        }

        #reader {
            max-width: 500px;
            margin: 30px auto;
        }

        #result {
            margin-top: 20px;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
        }

    </style>

</head>

<body>

<div class="navbar">

    <strong>GYM MANAGEMENT SYSTEM</strong>

    <a href="../admin/dashboard.php">
        ← Dashboard
    </a>

</div>


<div class="container">

    <div class="card">

        <h1>📷 QR Attendance Scanner</h1>

        <p>
            Ask the member to present their QR code.
        </p>

        <div id="reader"></div>

        <div id="result">
            Waiting for QR code...
        </div>

    </div>

</div>


<script>

let processing = false;


function qrSuccess(decodedText, decodedResult) {

    // Prevent the same QR from being processed repeatedly
    if (processing) {
        return;
    }

    processing = true;


    const result = document.getElementById("result");

    result.innerHTML = "Processing QR code...";


    // Send QR token to PHP
    fetch("time_in.php", {

        method: "POST",

        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },

        body:
            "qr_token=" +
            encodeURIComponent(decodedText)

    })


    .then(response => response.json())


    .then(data => {

    if (data.success) {

        result.style.background = "#dcfce7";
        result.style.color = "#166534";


        // =========================
        // TIME-IN
        // =========================

        if (data.action === "TIME_IN") {

            result.innerHTML = `

    <h2>
        🟢 TIME-IN SUCCESSFUL
    </h2>

    <h3>
        ${data.member_name}
    </h3>

    <p>
        <strong>Member Code:</strong>
        ${data.member_code}
    </p>

    <p>
        <strong>Membership:</strong>
        ${data.membership_type}
    </p>

    <p>
        <strong>Expiration:</strong>
        ${data.expiration_date}
    </p>

    <p>
        <strong>Time-In:</strong>
        ${data.time_in}
    </p>

    <p>
        ✓ Attendance recorded successfully.
    </p>

`;
        }


        // =========================
        // TIME-OUT
        // =========================

        else if (data.action === "TIME_OUT") {

            result.style.background = "#fee2e2";
            result.style.color = "#991b1b";

            result.innerHTML = `

                <h2>
                    🔴 TIME-OUT SUCCESSFUL
                </h2>

                <h3>
                    ${data.member_name}
                </h3>

                <p>
                    <strong>Member Code:</strong>
                    ${data.member_code}
                </p>

                <p>
                    <strong>Time-In:</strong>
                    ${data.time_in}
                </p>

                <p>
                    <strong>Time-Out:</strong>
                    ${data.time_out}
                </p>

                <p>
                    <strong>Duration:</strong>
                    ${data.duration_minutes} minutes
                </p>

                <p>
                    ✓ Attendance completed.
                </p>

            `;
        }

    }

    else {

        result.style.background = "#fee2e2";
        result.style.color = "#991b1b";

        result.innerHTML = `

            <h3>
                ✕ Attendance Not Recorded
            </h3>

            <p>
                ${data.message}
            </p>

        `;
    }


    // Allow another scan
    setTimeout(() => {

        processing = false;

        result.style.background = "#f3f4f6";
        result.style.color = "#000";

        result.innerHTML =
            "Waiting for QR code...";

    }, 3000);

})
}


function qrError(errorMessage) {

    // Ignore scanning errors while searching for a QR code.

}


const scanner = new Html5QrcodeScanner(

    "reader",

    {
        fps: 10,

        qrbox: {
            width: 250,
            height: 250
        },

        rememberLastUsedCamera: true
    },

    false

);


scanner.render(
    qrSuccess,
    qrError
);

</script>

</body>

</html>