<?php

/* ================= LINE CONFIG ================= */
$access_token = "KYrAhuWFzsjZnJHpyuMk/MFq29ooEFn7exNeujgnbcnMpNH1QVygLeB+6+V4JtyAN8dkvW4CrsVyR4ynJVBlG72kufmtFn1+SL/j/50KdR2H0NOw9i+REqg5G/5c4xj+MR40+tm5mduY20hO+ndBtQdB04t89/1O/w1cDnyilFU=";
$userId = "Ubb918bdfb5c9b475f081e19595da0722";

/* ================= MYSQL CONFIG ================= */
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "uc_db";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database connection failed");
}

/* ================= GET PARAM ================= */
$param = strtolower($_GET['param'] ?? 'unknown');

/* ================= GET PATIENT INFO ================= */
$sql = "SELECT hnno, fullname FROM tblaccount WHERE acc_code = ?";
$stmt = $conn->prepare($sql);

$acc_code = "arduino_r4_wifi";
$stmt->bind_param("s", $acc_code);
$stmt->execute();
$result = $stmt->get_result();

$hnno = "N/A";
$fullname = "Unknown";

if ($row = $result->fetch_assoc()) {
    $hnno = $row['hnno'];
    $fullname = $row['fullname'];
}

$stmt->close();
$conn->close();

/* ================= CHECK PARAM ================= */
if ($param === "blockage") {
    $statusText = "Critical: Likely blockage";
    $statusLogo = "🔴";
} elseif ($param === "low") {
    $statusText = "Warning: Low Flow Rate";
    $statusLogo = "🟠";
} else {
    $statusText = "Status: $param";
    $statusLogo = "ℹ️";
}

/* ================= LINE MESSAGE ================= */
$message = [
    "type" => "text",
    "text" =>
        $statusLogo . " Urinary Catheter Alert\n" .
        "HN: " . $hnno . "\n" .
        "Patient: " . $fullname . "\n" .
        "Status: " . $statusText . "\n" .
        "Please check patient immediately."
];

$data = [
    //"to" => $userId,
    "messages" => [$message]
];

/* ================= SEND TO LINE ================= */
//$ch = curl_init("https://api.line.me/v2/bot/message/push");
$ch = curl_init("https://api.line.me/v2/bot/message/broadcast");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $access_token"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);
curl_close($ch);

echo "LINE message sent";

?>