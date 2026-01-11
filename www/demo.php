<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Urine Flow Obstruction Detector</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    body {
      background-color: #fff6cc;
      font-family: 'Poppins', sans-serif;
    }
    .header {
      background-color: #ff8157;
      color: white;
      text-align: center;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .card-custom {
      background-color: #ffcc66;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      margin-bottom: 20px;
    }
    .status-box {
      background-color: #ffe6a1;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .log-box {
      background-color: #fff;
      border-radius: 12px;
      padding: 15px;
      height: 220px;
      overflow-y: auto;
      box-shadow: 0px 2px 8px rgba(0,0,0,0.1);
    }
    .footer {
      text-align: center;
      margin-top: 30px;
      color: #666;
      font-size: 0.9rem;
    }
    .text-green { color: #007a2b; font-weight: 600; }
    .text-yellow { color: #e6b800; font-weight: 600; }
    .text-red { color: #d80000; font-weight: 600; }
    .icon-box {
      background-color: #ffcc66;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      font-size: 1.8rem;
      color: #ff6b00;
      transition: all 0.3s ease-in-out;
    }
    .icon-box:hover {
      background-color: #ffdb88;
      transform: scale(1.05);
    }
    .status-dot {
      font-size: 1.5rem;
      vertical-align: middle;
    }
    @media (max-width: 768px) {
      .header h2 { font-size: 1.3rem; }
      .card-custom h4 { font-size: 1.1rem; }
      .status-box p { font-size: 0.95rem; }
    }
  </style>
</head>
<body>

  <div class="container py-4">
    <!-- Header -->
    <div class="header">
      <h2>Blockage Monitoring System</h2>
    </div>

    <!-- Patient Info -->
    <div class="card-custom">
      <h4><i class="bi bi-person-circle"></i> Bobbie Coleman</h4>
      <p>HN: 1245</p>
    </div>

    <!-- Monitoring Section -->
    <div class="status-box">
      <h5 id="monitorStatus"><span class="status-dot" id="statusDot">●</span> Monitoring</h5>
      <div class="row">
        <div class="col-md-4 col-6">
          <p>Urine Flow rate: <span id="flowRate" class="text-green">43 mL/h</span></p>
        </div>
        <div class="col-md-4 col-6">
          <p>Urine Volume: <span id="urineVol" class="text-green">52 mL</span></p>
        </div>
        <div class="col-md-4 col-12">
          <p>Status: <span id="statusText" class="text-green">Normal</span></p>
        </div>
      </div>
      <small class="text-muted">Last updated: 12 minutes ago</small>
    </div>

    <!-- Icons -->
    <div class="row text-center mb-4 g-3">
      <div class="col-4">
        <div class="icon-box"><i class="bi bi-gear-fill"></i></div>
      </div>
      <div class="col-4">
        <div class="icon-box"><i class="bi bi-bar-chart-line-fill"></i></div>
      </div>
      <div class="col-4">
        <div class="icon-box"><i class="bi bi-telephone-fill"></i></div>
      </div>
    </div>

    <!-- Log Section -->
    <div class="log-box">
      <h6><strong>Log</strong></h6>
      <p><strong>19:00 - [CRITICAL]</strong> Blockage Detected<br>
      Details: Outflow sensor = 0 mL/hr. Inflow sensor = 25 mL/hr. High-pressure differential detected.</p>
      <hr>
      <p><strong>17:45 - [ALERT]</strong> Reverse Flow Detected<br>
      Details: Outflow sensor registered -5 mL/hr for 30 seconds.<br>
      Action: “Flow Suction” system activated automatically.</p>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>© 2025 Urine Flow Obstruction Detector | Smart Catheter Monitoring System</p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Function to update status (Normal, Warning, Blockage)
    function updateStatus(status) {
      const statusText = document.getElementById("statusText");
      const statusDot = document.getElementById("statusDot");

      // Reset styles
      statusText.className = '';
      statusDot.style.color = '';

      if (status === "Normal") {
        statusText.textContent = "Normal";
        statusText.classList.add("text-green");
        statusDot.style.color = "#28a745"; // green dot
      } 
      else if (status === "Warning") {
        statusText.textContent = "Warning: Low Volume";
        statusText.classList.add("text-yellow");
        statusDot.style.color = "#ffc107"; // yellow dot
      } 
      else if (status === "Blockage") {
        statusText.textContent = "Critical: Blockage Detected";
        statusText.classList.add("text-red");
        statusDot.style.color = "#dc3545"; // red dot
      }
    }

    // Example: change status after few seconds (for demo)
    setTimeout(() => updateStatus("Warning"), 4000);
    setTimeout(() => updateStatus("Blockage"), 8000);
  </script>
</body>
</html>
