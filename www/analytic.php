<?php
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "uc_db";

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $acc_code = $_GET['acc_code'] ?? '';

    $fullname = '';
    $hnno = '';

    if ($acc_code !== '') {

        // GET VALUE >> HN + FULLNAME
        $stmt = $conn->prepare(
            "SELECT hnno, fullname FROM tblaccount WHERE acc_code = ?"
        );
        $stmt->bind_param("s", $acc_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $hnno = $row['hnno'];
            $fullname = $row['fullname'];
        }
        $stmt->close();

        // GET VALUE >> FLOW RATE, DATE + TIME
        $sql_last = "
            SELECT flow_avg, logdate, logtime
            FROM tbltransaction
            WHERE logby = ?
            ORDER BY tid DESC
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql_last);
        $stmt->bind_param("s", $acc_code);
        $stmt->execute();
        $result = $stmt->get_result();

        $flow_rate = 0;
        $last_update = '';
        if ($row = $result->fetch_assoc()) {
            $flow_rate = $row['flow_avg'];
            $last_update = $row['logdate'] . " " . $row['logtime'];
        }
        $stmt->close();

        // GET VALUE >> SUMMARY FLOW RATE PER CURRENT DATE.
        $sql_sum = "
            SELECT SUM(flow_avg) AS total_volume
            FROM tbltransaction
            WHERE logby = ?
            AND DATE(logdate) = CURDATE()
        ";

        $stmt = $conn->prepare($sql_sum);
        $stmt->bind_param("s", $acc_code);
        $stmt->execute();
        $result = $stmt->get_result();

        $urine_volume = 0;
        if ($row = $result->fetch_assoc()) {
            $urine_volume = $row['total_volume'] ?? 0;
        }
        $stmt->close();

        // GET VALUE >> STATUS VALUE IS MEANING ACTION AND STATUS ,SHOW STATUS BY COLOR.
        $sql_status = "
            SELECT meaning, action, status
            FROM tblstatus
            WHERE ? BETWEEN svalue AND evalue
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql_status);
        $stmt->bind_param("d", $flow_rate);
        $stmt->execute();
        $result = $stmt->get_result();

        $status_text = "Unknown";
        $status_color = "secondary";

        if ($row = $result->fetch_assoc()) {
            $status_text = $row['meaning'] . " : " . $row['action'] . " (" . $row['status'] . ")";

            // กำหนดสีตามสถานะ
            if (strtolower($row['status']) == 'critical') {
                $status_color = "danger";
            } elseif (strtolower($row['status']) == 'warning') {
                $status_color = "warning";
            } else {
                $status_color = "success";
            }
        }
        $stmt->close();

        // GET VALUE >> FLOW RATE, DATE + TIME and STATUS / ACTION / TREAT.
        $sql = "
            SELECT 
                t.flow_avg,
                t.logdate,
                t.logtime,
                s.status,
                s.action
            FROM tbltransaction t
            LEFT JOIN tblstatus s 
                ON t.flow_avg BETWEEN s.svalue AND s.evalue
            WHERE t.logby = ?
            ORDER BY t.tid DESC
            LIMIT 5;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $acc_code);
        $stmt->execute();
        $result = $stmt->get_result();

        $stmt->close();

    }
?>

<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Urinary Catheter | Dashboard</title>
    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->
    <!--begin::Primary Meta Tags-->
    <meta name="title" content="Urinary Catheter" />
    <meta name="author" content="ColorlibHQ" />
    <meta name="description" content="Urinary Catheter" />
    <meta name="keywords" content="Urinary Catheter" />
    <!--end::Primary Meta Tags-->
    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="./css/adminlte.css" as="style" />
    <!--end::Accessibility Features-->
    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
      media="print"
      onload="this.media='all'"
    />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="./css/adminlte.css" />
    <!--end::Required Plugin(AdminLTE)-->
    <!-- apexcharts -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
      integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0="
      crossorigin="anonymous"
    />
    <!-- jsvectormap -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
      integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4="
      crossorigin="anonymous"
    />
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
              </a>
            </li>
          </ul>
          <!--end::Start Navbar Links-->
          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto">
            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img
                  src="./assets/img/user2-160x160.jpg"
                  class="user-image rounded-circle shadow"
                  alt="User Image"
                />
                <span class="d-none d-md-inline">DR.PATTHAWEE P.</span>
              </a>
            </li>
            <!--end::User Menu Dropdown-->
          </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->
      <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
          <!--begin::Brand Link-->
          <a href="./index.php" class="brand-link">
            <!--begin::Brand Text-->
            <span class="brand-text fw-light">Urinary Catheter</span>
            <!--end::Brand Text-->
          </a>
          <!--end::Brand Link-->
        </div>
        <!--end::Sidebar Brand-->
        <!--begin::Sidebar Wrapper-->
        <div class="sidebar-wrapper">
          <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul
              class="nav sidebar-menu flex-column"
              data-lte-toggle="treeview"
              role="navigation"
              aria-label="Main navigation"
              data-accordion="false"
              id="navigation"
            >
              <li class="nav-item menu-open">
                <a href="#" class="nav-link active">
                  <i class="nav-icon bi bi-speedometer"></i>
                  <p>Dashboard</p>
                </a>
              </li>
            </ul>
            <!--end::Sidebar Menu-->
          </nav>
        </div>
        <!--end::Sidebar Wrapper-->
      </aside>
      <!--end::Sidebar-->
      <!--begin::App Main-->
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Blockage Monitoring System</h3></div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="./index.php">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Analytic</li>
                </ol>
              </div>
            </div>
            <!--end::Row-->
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content Header-->

        <!--begin::App Content-->
        <div class="app-content">
            <!--begin::Container-->
            <div class="container-fluid">
                
                <div class="col-12">
                    <div class="info-box text-bg-warning">
                        <div class="info-box-content m-2">
                            <span class="info-box-text h4">
                                <i class="bi bi-person h4"></i>
                                &nbsp;&nbsp;
                                <strong><?= htmlspecialchars($fullname); ?></strong>
                            </span>
                            <span class="info-box-text h5">
                                HN : <?= htmlspecialchars($hnno); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="info-box bg-warning-subtle text-dark">
                        <div class="info-box-content m-2">

                            <!-- Title -->
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-record-circle h4 me-2 text-success"></i>
                                <span class="h4 mb-0"><strong>Monitoring</strong></span>
                            </div>

                            <!-- Data -->
                            <div class="row">
                                <div class="col-lg-3 col-12">
                                    <p class="info-box-text h5 text-muted text-wrap">
                                        Urine Flow rate:
                                        <span class="text-success">
                                            <strong><?= number_format($flow_rate, 2) ?> mL/h</strong>
                                        </span>
                                    </p>
                                </div>

                                <div class="col-lg-3 col-12">
                                    <p class="info-box-text h5 text-muted text-wrap">
                                        Urine Volume:
                                        <span class="text-success">
                                            <strong><?= number_format($urine_volume, 2) ?> mL</strong>
                                        </span>
                                    </p>
                                </div>

                                <div class="col-lg-6 col-12">
                                    <p class="info-box-text h5 text-muted text-wrap">
                                        Status:
                                        <span class="text-<?= $status_color ?>">
                                            <strong><?= htmlspecialchars($status_text) ?></strong>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Last update -->
                            <small class="text-muted">Last updated: <?= $last_update ?></small>

                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="row">

                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="info-box bg-warning text-dark d-flex 
                                        justify-content-center align-items-center"
                                style="height: 110px;">
                                <span class="info-box-icon text-warning-emphasis fs-2">
                                    <i class="bi bi-tools"></i>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6 col-12">
                            <a href="./graph.php?acc_code=<?= urlencode($acc_code) ?>" 
                            class="text-decoration-none">
                                <div class="info-box bg-warning text-dark d-flex
                                            justify-content-center align-items-center"
                                    style="height: 110px; cursor: pointer;">
                                    <span class="info-box-icon text-warning-emphasis fs-2">
                                        <i class="bi bi-graph-up"></i>
                                    </span>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="info-box bg-warning text-dark d-flex 
                                        justify-content-center align-items-center"
                                style="height: 110px;">
                                <span class="info-box-icon text-warning-emphasis fs-2">
                                    <i class="bi bi-telephone-fill"></i>
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-12">
                    <div class="info-box text-dark">
                        <div class="info-box-content m-2">

                                <!-- Title -->
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-chat-right-quote h4 me-2"></i>
                                    <span class="h4 mb-0"><strong>Log</strong></span>
                                </div>

                                <!-- Data -->
                                <div class="row">
                                    <div class="col-12">

                                        <?php if ($result && $result->num_rows > 0): ?>

                                            <?php while ($row = $result->fetch_assoc()): ?>

                                                <div class="info-box-text h6 text-wrap mb-1">
                                                    <strong>
                                                        <?= date("d/m/Y", strtotime($row['logtime'])) ?>
                                                        <?= date("H:i:s", strtotime($row['logtime'])) ?>
                                                        - [<?= htmlspecialchars($row['status']) ?>]
                                                    </strong>
                                                    Flow Avg: <?= number_format($row['flow_avg'], 2) ?> mL/hr
                                                </div>

                                                <div class="small text-muted text-wrap mb-2">
                                                    Action: <?= htmlspecialchars($row['action']) ?>
                                                </div>

                                                <div class="progress mb-3">
                                                    <div class="progress-bar w-100"></div>
                                                </div>

                                            <?php endwhile; ?>

                                        <?php else: ?>

                                            <div class="text-center text-muted py-4 h5">
                                                Not found Log
                                            </div>

                                        <?php endif; ?>

                                    </div>
                                </div>


                            </div>
                        </div>
                </div>


            </div>
            <!--end::App container-->
        </div>
        <!--end::App Content-->

      </main>
      <!--end::App Main-->
      <!--begin::Footer-->
      <footer class="app-footer">
        <!--begin::To the end-->
        <div class="float-end d-none d-sm-inline"></div>
        <!--end::To the end-->
        <!--begin::Copyright-->
        <strong>Copyright &copy; 2025&nbsp;Urinary Catheter.</strong> All rights reserved.
        <!--end::Copyright-->
      </footer>
      <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <!--begin::Script-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script
      src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
    <script src="./js/adminlte.js"></script>
    <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
              theme: Default.scrollbarTheme,
              autoHide: Default.scrollbarAutoHide,
              clickScroll: Default.scrollbarClickScroll,
            },
          });
        }
      });
    </script>
    <!--end::OverlayScrollbars Configure-->
    <!-- OPTIONAL SCRIPTS -->
    <!-- sortablejs -->
    <script
      src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"
      crossorigin="anonymous"
    ></script>
    <!-- sortablejs -->
    <script>
      new Sortable(document.querySelector('.connectedSortable'), {
        group: 'shared',
        handle: '.card-header',
      });

      const cardHeaders = document.querySelectorAll('.connectedSortable .card-header');
      cardHeaders.forEach((cardHeader) => {
        cardHeader.style.cursor = 'move';
      });
    </script>
    <!-- apexcharts -->
    <script
      src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
      integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8="
      crossorigin="anonymous"
    ></script>
    <!-- ChartJS -->
    <script>
      // NOTICE!! DO NOT USE ANY OF THIS JAVASCRIPT
      // IT'S ALL JUST JUNK FOR DEMO
      // ++++++++++++++++++++++++++++++++++++++++++

      const sales_chart_options = {
        series: [
          {
            name: 'Digital Goods',
            data: [28, 48, 40, 19, 86, 27, 90],
          },
          {
            name: 'Electronics',
            data: [65, 59, 80, 81, 56, 55, 40],
          },
        ],
        chart: {
          height: 300,
          type: 'area',
          toolbar: {
            show: false,
          },
        },
        legend: {
          show: false,
        },
        colors: ['#0d6efd', '#20c997'],
        dataLabels: {
          enabled: false,
        },
        stroke: {
          curve: 'smooth',
        },
        xaxis: {
          type: 'datetime',
          categories: [
            '2023-01-01',
            '2023-02-01',
            '2023-03-01',
            '2023-04-01',
            '2023-05-01',
            '2023-06-01',
            '2023-07-01',
          ],
        },
        tooltip: {
          x: {
            format: 'MMMM yyyy',
          },
        },
      };

      const sales_chart = new ApexCharts(
        document.querySelector('#revenue-chart'),
        sales_chart_options,
      );
      sales_chart.render();
    </script>
    <!-- jsvectormap -->
    <script
      src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/js/jsvectormap.min.js"
      integrity="sha256-/t1nN2956BT869E6H4V1dnt0X5pAQHPytli+1nTZm2Y="
      crossorigin="anonymous"
    ></script>
    <script
      src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/maps/world.js"
      integrity="sha256-XPpPaZlU8S/HWf7FZLAncLg2SAkP8ScUTII89x9D3lY="
      crossorigin="anonymous"
    ></script>
    <!-- jsvectormap -->
    <script>
      // World map by jsVectorMap
      new jsVectorMap({
        selector: '#world-map',
        map: 'world',
      });

      // Sparkline charts
      const option_sparkline1 = {
        series: [
          {
            data: [1000, 1200, 920, 927, 931, 1027, 819, 930, 1021],
          },
        ],
        chart: {
          type: 'area',
          height: 50,
          sparkline: {
            enabled: true,
          },
        },
        stroke: {
          curve: 'straight',
        },
        fill: {
          opacity: 0.3,
        },
        yaxis: {
          min: 0,
        },
        colors: ['#DCE6EC'],
      };

      const sparkline1 = new ApexCharts(document.querySelector('#sparkline-1'), option_sparkline1);
      sparkline1.render();

      const option_sparkline2 = {
        series: [
          {
            data: [515, 519, 520, 522, 652, 810, 370, 627, 319, 630, 921],
          },
        ],
        chart: {
          type: 'area',
          height: 50,
          sparkline: {
            enabled: true,
          },
        },
        stroke: {
          curve: 'straight',
        },
        fill: {
          opacity: 0.3,
        },
        yaxis: {
          min: 0,
        },
        colors: ['#DCE6EC'],
      };

      const sparkline2 = new ApexCharts(document.querySelector('#sparkline-2'), option_sparkline2);
      sparkline2.render();

      const option_sparkline3 = {
        series: [
          {
            data: [15, 19, 20, 22, 33, 27, 31, 27, 19, 30, 21],
          },
        ],
        chart: {
          type: 'area',
          height: 50,
          sparkline: {
            enabled: true,
          },
        },
        stroke: {
          curve: 'straight',
        },
        fill: {
          opacity: 0.3,
        },
        yaxis: {
          min: 0,
        },
        colors: ['#DCE6EC'],
      };

      const sparkline3 = new ApexCharts(document.querySelector('#sparkline-3'), option_sparkline3);
      sparkline3.render();
    </script>
    <!--end::Script-->
  </body>
  <!--end::Body-->
</html>
