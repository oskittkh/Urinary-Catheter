<?php

  $host = "localhost";
  $db   = "uc_db";
  $user = "root";
  $pass = "";

  $conn = new mysqli($host, $user, $pass, $db);
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }

  $pulseA = intval($_GET['pulseA'] ?? 0);
  $fwA    = floatval($_GET['flowA'] ?? 0);
  $logBy  = $_GET['logBy'] ?? "n/a";
  $machineKey = $_GET['machineKey'] ?? "n/a";
  $remark = "";

  date_default_timezone_set('Asia/Bangkok');
  $strDate = date('Y-m-d');
  $strTime = date('H:i:s');

  $stmt = $conn->prepare(
      "INSERT INTO tbltransaction 
      (pulse_avg, flow_avg, machinekey, logby, logdate, logtime, remark) 
      VALUES (?, ?, ?, ?, ?, ?, ?)"
  );

  $stmt->bind_param(
      "ddsssss",
      $pulseA,
      $fwA,
      $machineKey,
      $logBy,
      $strDate,
      $strTime,
      $remark
  );

  if ($stmt->execute()) {
      echo "OK";
  } else {
      echo "ERROR: " . $stmt->error;
  }

  $stmt->close();
  $conn->close();

?>