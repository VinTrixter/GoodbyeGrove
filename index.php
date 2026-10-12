<?php
session_start();

date_default_timezone_set('Asia/Manila');

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "funeral_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Automatic database schema updates
$conn->query("SHOW COLUMNS FROM client LIKE 'status'")->num_rows == 0 && $conn->query("ALTER TABLE client ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Active'");
$conn->query("SHOW COLUMNS FROM employee LIKE 'status'")->num_rows == 0 && $conn->query("ALTER TABLE employee ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Active'");
$conn->query("SHOW COLUMNS FROM booking LIKE 'downpayment'")->num_rows == 0 && $conn->query("ALTER TABLE booking ADD COLUMN downpayment DECIMAL(10,2) NOT NULL DEFAULT 0.00");

$today = date('Y-m-d');

// Fetch confirmed active bookings for instant JS date conflict validation
$activeBookingsQuery = $conn->query("SELECT venueID, startDate, endDate FROM booking WHERE bookingStatus = 'Confirmed'");
$activeBookings = [];
if ($activeBookingsQuery && $activeBookingsQuery->num_rows > 0) {
    while ($row = $activeBookingsQuery->fetch_assoc()) {
        $activeBookings[] = $row;
    }
}

//Handle Image Uploads with strict file type validation
function uploadVenueImage($fileInputName, &$uploadError = null) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES[$fileInputName]['tmp_name'];
        $file_name = $_FILES[$fileInputName]['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = array("jpg", "jpeg", "png", "webp", "gif");

        if (!in_array($file_ext, $allowed)) {
            $uploadError = "Upload Error: Only JPG, PNG, WEBP, and GIF files are allowed.";
            return false;
        }

        if (@getimagesize($file_tmp) === false) {
            $uploadError = "Upload Error: The selected file is not a valid image.";
            return false;
        }

        $upload_dir = "uploads/venues/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $newFileName = "venue_" . uniqid() . "." . $file_ext;
        $targetPath  = $upload_dir . $newFileName;

        if (move_uploaded_file($file_tmp, $targetPath)) {
            return $targetPath;
        }
    }
    return null;
}

// 2. FORM PROCESSING HANDLERS
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // ==================== VENUE CRUD ====================
    if ($action === 'add_venue') {
        $name         = trim($_POST['name']);
        $type         = trim($_POST['type']);
        $capacity     = intval($_POST['capacity']);
        $availability = trim($_POST['availability']);
        $baseprice    = floatval($_POST['baseprice']);
        
        $uploadError  = null;
        $uploadedPath = uploadVenueImage('venue_image', $uploadError);
        $imagePath    = $uploadedPath ? $uploadedPath : "uploads/venues/default.jpg";

        $stmt = $conn->prepare("INSERT INTO venue (name, type, capacity, availability, baseprice, imagePath) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisds", $name, $type, $capacity, $availability, $baseprice, $imagePath);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#venues");
        exit();
    }

    if ($action === 'edit_venue') {
        $venueID      = intval($_POST['venueID']);
        $name         = trim($_POST['name']);
        $type         = trim($_POST['type']);
        $capacity     = intval($_POST['capacity']);
        $availability = trim($_POST['availability']);
        $baseprice    = floatval($_POST['baseprice']);
        
        $uploadError  = null;
        $uploadedPath = uploadVenueImage('venue_image', $uploadError);

        if ($uploadedPath) {
            $stmt = $conn->prepare("UPDATE venue SET name=?, type=?, capacity=?, availability=?, baseprice=?, imagePath=? WHERE venueID=?");
            $stmt->bind_param("ssisdsi", $name, $type, $capacity, $availability, $baseprice, $uploadedPath, $venueID);
        } else {
            $stmt = $conn->prepare("UPDATE venue SET name=?, type=?, capacity=?, availability=?, baseprice=? WHERE venueID=?");
            $stmt->bind_param("ssisdi", $name, $type, $capacity, $availability, $baseprice, $venueID);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#venues");
        exit();
    }

    if ($action === 'archive_venue' || $action === 'delete_venue') {
        $venueID      = intval($_POST['venueID']);
        $targetStatus = $_POST['target_status'] ?? 'Archived';
        $stmt = $conn->prepare("UPDATE venue SET availability=? WHERE venueID=?");
        $stmt->bind_param("si", $targetStatus, $venueID);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#venues");
        exit();
    }

    // ==================== CLIENT CRUD ====================
    if ($action === 'add_client') {
        $firstName  = trim($_POST['firstName']);
        $lastName   = trim($_POST['lastName']);
        $contactNum = trim($_POST['contactNum']);
        $emailAddr  = trim($_POST['emailAddr']);

        $stmt = $conn->prepare("INSERT INTO client (firstName, lastName, contactNum, emailAddr, status) VALUES (?, ?, ?, ?, 'Active')");
        $stmt->bind_param("ssss", $firstName, $lastName, $contactNum, $emailAddr);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#clients");
        exit();
    }

    if ($action === 'edit_client') {
        $clientID   = intval($_POST['clientID']);
        $firstName  = trim($_POST['firstName']);
        $lastName   = trim($_POST['lastName']);
        $contactNum = trim($_POST['contactNum']);
        $emailAddr  = trim($_POST['emailAddr']);

        $stmt = $conn->prepare("UPDATE client SET firstName=?, lastName=?, contactNum=?, emailAddr=? WHERE clientID=?");
        $stmt->bind_param("ssssi", $firstName, $lastName, $contactNum, $emailAddr, $clientID);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#clients");
        exit();
    }

    if ($action === 'archive_client' || $action === 'delete_client') {
        $clientID     = intval($_POST['clientID']);
        $targetStatus = $_POST['target_status'] ?? 'Archived';
        $stmt = $conn->prepare("UPDATE client SET status=? WHERE clientID=?");
        $stmt->bind_param("si", $targetStatus, $clientID);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#clients");
        exit();
    }

    // ==================== STAFF CRUD ====================
    if ($action === 'add_staff') {
        $firstName = trim($_POST['firstName']);
        $lastName  = trim($_POST['lastName']);
        $role      = trim($_POST['role']);
        $emailAddr = trim($_POST['emailAddr']);

        $stmt = $conn->prepare("INSERT INTO employee (firstName, lastName, role, emailAddr, status) VALUES (?, ?, ?, ?, 'Active')");
        $stmt->bind_param("ssss", $firstName, $lastName, $role, $emailAddr);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#staff");
        exit();
    }

    if ($action === 'edit_staff') {
        $empID     = intval($_POST['empID']);
        $firstName = trim($_POST['firstName']);
        $lastName  = trim($_POST['lastName']);
        $role      = trim($_POST['role']);
        $emailAddr = trim($_POST['emailAddr']);

        $stmt = $conn->prepare("UPDATE employee SET firstName=?, lastName=?, role=?, emailAddr=? WHERE empID=?");
        $stmt->bind_param("ssssi", $firstName, $lastName, $role, $emailAddr, $empID);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#staff");
        exit();
    }

    if ($action === 'archive_staff' || $action === 'delete_staff') {
        $empID        = intval($_POST['empID']);
        $targetStatus = $_POST['target_status'] ?? 'Archived';
        $stmt = $conn->prepare("UPDATE employee SET status=? WHERE empID=?");
        $stmt->bind_param("si", $targetStatus, $empID);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#staff");
        exit();
    }

    // ==================== BOOKING CRUD ====================
    if ($action === 'add_reservation') {
        $clientID      = intval($_POST['clientID']);
        $venueID       = intval($_POST['venueID']);
        $empID         = intval($_POST['empID']);
        $deceasedName  = trim($_POST['deceasedName'] ?? '');
        $startDate     = $_POST['startDate'];
        $endDate       = $_POST['endDate'];
        $paymentStatus = $_POST['paymentStatus'];
        $downpayment   = ($paymentStatus === 'Partial') ? floatval($_POST['downpayment'] ?? 0) : 0.00;

        if ($startDate < $today || $endDate < $startDate) {
            header("Location: index.php#book");
            exit();
        }

        $daysUsed = max(1, round((strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24)));

        $vStmt = $conn->prepare("SELECT baseprice FROM venue WHERE venueID = ? AND COALESCE(NULLIF(TRIM(availability), ''), 'Archived') <> 'Archived'");
        $vStmt->bind_param("i", $venueID);
        $vStmt->execute();
        $vRes = $vStmt->get_result()->fetch_assoc();
        $vStmt->close();
        if (!$vRes) {
            header("Location: index.php#book");
            exit();
        }
        $baseprice  = $vRes['baseprice'];
        $totalPrice = $daysUsed * $baseprice;

        if ($paymentStatus === 'Paid') {
            $downpayment = $totalPrice;
        }

        $stmt = $conn->prepare("INSERT INTO booking (clientID, venueID, empID, deceasedName, startDate, endDate, bookingStatus, paymentStatus, daysUsed, totalPrice, downpayment) VALUES (?, ?, ?, ?, ?, ?, 'Confirmed', ?, ?, ?, ?)");
        $stmt->bind_param("iiissssddd", $clientID, $venueID, $empID, $deceasedName, $startDate, $endDate, $paymentStatus, $daysUsed, $totalPrice, $downpayment);
        $stmt->execute();
        $stmt->close();

        header("Location: index.php#bookings");
        exit();
    }

    if ($action === 'update_booking_status') {
        $bookingID     = intval($_POST['bookingID']);
        $paymentStatus = $_POST['paymentStatus'];
        $bookingStatus = $_POST['bookingStatus'];
        $addPayment    = floatval($_POST['addPayment'] ?? 0);

        $bStmt = $conn->prepare("SELECT totalPrice, downpayment FROM booking WHERE bookingID=?");
        $bStmt->bind_param("i", $bookingID);
        $bStmt->execute();
        $bData = $bStmt->get_result()->fetch_assoc();
        $bStmt->close();

        $currentDown = $bData['downpayment'] ?? 0;
        $totalPrice  = $bData['totalPrice'] ?? 0;
        $newDown     = $currentDown + $addPayment;

        if ($paymentStatus === 'Paid') {
            $newDown = $totalPrice;
        }

        $stmt = $conn->prepare("UPDATE booking SET paymentStatus=?, bookingStatus=?, downpayment=? WHERE bookingID=?");
        $stmt->bind_param("ssdi", $paymentStatus, $bookingStatus, $newDown, $bookingID);
        $stmt->execute();
        $stmt->close();

        header("Location: index.php#bookings");
        exit();
    }

    if ($action === 'delete_booking') {
        $bookingID = intval($_POST['bookingID']);
        $stmt = $conn->prepare("DELETE FROM booking WHERE bookingID=?");
        $stmt->bind_param("i", $bookingID);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#bookings");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Goodbye Grove - Resource Management</title>
<link rel="icon" type="image/x-icon" href="iconLogo.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">

<!-- Bootstrap 5 CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Header Banner -->
<header class="navbar navbar-dark bg-deep px-4 py-3 shadow-sm">
  <div class="d-flex align-items-center gap-3">
    <img src="logo.png" alt="Goodbye Grove logo" style="width: 48px; height: 48px; object-fit: contain;">
    <h1 class="h3 text-white mb-0">Goodbye Grove</h1>
  </div>
  <div class="d-flex align-items-center gap-2">
    <label for="roleSelector" class="text-white small mb-0">Logged in View:</label>
    <select id="roleSelector" onchange="switchRole(this.value)" class="form-select form-select-sm" style="width: auto;">
      <option value="Admin">Administrator View</option>
      <option value="Staff">Staff View</option>
    </select>
  </div>
</header>

<div class="container-fluid">
  <div class="row min-vh-100">
    <!-- Sidebar Navigation -->
    <nav class="col-md-3 col-lg-2 bg-deep p-3 sidebar-nav" aria-label="Main menu">
      <a href="#dashboard" class="nav-link">Dashboard</a>
      <a href="#book" class="nav-link">Book a Resource</a>
      <a href="#venues" class="nav-link admin-nav">Manage Venues</a>
      <a href="#clients" class="nav-link">Clients</a>
      <a href="#staff" class="nav-link">Staff Records</a>
      <a href="#bookings" class="nav-link">Booking History</a>
      <a href="#reports" class="nav-link">Reports</a>
    </nav>

    <!-- Main Workspace -->
    <main class="col-md-9 col-lg-10 p-4">

      <!-- Dashboard View -->
      <section class="view" id="dashboard">
        <h2 class="mb-4">Overview</h2>
        
        <?php
          //Total Bookings for Current Month
          $currentMonthName = date('F');
          $currentYearMonth = date('Y-m');
          $bookingsCount = $conn->query("SELECT COUNT(*) AS total FROM booking WHERE DATE_FORMAT(startDate, '%Y-%m') = '$currentYearMonth'")->fetch_assoc()['total'] ?? 0;
          
          $availVenues   = $conn->query("SELECT COUNT(*) AS avail FROM venue v WHERE COALESCE(NULLIF(TRIM(v.availability), ''), 'Archived') <> 'Archived' AND NOT EXISTS (SELECT 1 FROM booking b WHERE b.venueID = v.venueID AND b.bookingStatus = 'Confirmed' AND b.startDate <= '$today' AND b.endDate >= '$today')")->fetch_assoc()['avail'] ?? 0;
          $totalVenues   = $conn->query("SELECT COUNT(*) AS total FROM venue")->fetch_assoc()['total'] ?? 0;
          $pendingPay    = $conn->query("SELECT COUNT(*) AS pending FROM booking WHERE paymentStatus = 'Pending' OR paymentStatus = 'Partial'")->fetch_assoc()['pending'] ?? 0;
        ?>

        <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
          <div class="col">
            <div class="card border-0 border-start border-4 border-success shadow-sm p-3 bg-white">
              <span class="text-muted small">Total Bookings (<?php echo $currentMonthName; ?>)</span>
              <span class="fs-2 fw-bold text-success"><?php echo $bookingsCount; ?></span>
            </div>
          </div>
          <div class="col">
            <div class="card border-0 border-start border-4 border-success shadow-sm p-3 bg-white">
              <span class="text-muted small">Available Venues</span>
              <span class="fs-2 fw-bold text-success"><?php echo $availVenues . ' / ' . $totalVenues; ?></span>
            </div>
          </div>
          <div class="col">
            <div class="card border-0 border-start border-4 border-success shadow-sm p-3 bg-white">
              <span class="text-muted small">Pending / Partial Payments</span>
              <span class="fs-2 fw-bold text-success"><?php echo $pendingPay; ?></span>
            </div>
          </div>
        </div>

        <div class="card shadow-sm p-4 bg-white mb-4">
          <h3 class="h5 text-success mb-3">Resource Availability Overview</h3>
          <?php
            $overviewFilter = $_GET['overview_filter'] ?? 'All';
            if (!in_array($overviewFilter, ['All', 'Available', 'Booked', 'Archived'], true)) {
                $overviewFilter = 'All';
            }
            $overviewSql = "SELECT v.*,
                CASE
                  WHEN v.availability = 'Archived' OR v.availability IS NULL OR TRIM(v.availability) = '' THEN 'Archived'
                  WHEN v.availability = 'Maintenance' THEN 'Maintenance'
                  WHEN EXISTS (SELECT 1 FROM booking b WHERE b.venueID = v.venueID AND b.bookingStatus = 'Confirmed' AND b.startDate <= '$today' AND b.endDate >= '$today') THEN 'Booked'
                  ELSE 'Available'
                END AS todayStatus
              FROM venue v";
            if ($overviewFilter !== 'All') {
                $overviewSql .= " HAVING todayStatus = '" . $conn->real_escape_string($overviewFilter) . "'";
            }
            $overviewSql .= " ORDER BY v.name ASC";
            $res = $conn->query($overviewSql);
          ?>
          <form action="index.php#dashboard" method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-auto">
              <label class="form-label mb-1 fw-bold small text-success">Display</label>
              <select name="overview_filter" class="form-select form-select-sm">
                <?php foreach (['All', 'Available', 'Booked', 'Archived'] as $statusOption): ?>
                  <option value="<?php echo $statusOption; ?>" <?php if ($overviewFilter === $statusOption) echo 'selected'; ?>><?php echo $statusOption; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-auto">
              <button class="btn btn-emerald btn-sm" type="submit">Search</button>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
              <thead class="table-light">
                <tr><th>Resource Name</th><th>Category/Type</th><th>Capacity</th><th>Status</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php if ($res && $res->num_rows > 0): while ($row = $res->fetch_assoc()):
                  $todayStatus = $row['todayStatus'] ?: 'Archived';
                ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                    <td><?php echo htmlspecialchars($row['capacity']); ?></td>
                    <td>
                      <?php if ($todayStatus === 'Available'): ?>
                        <span class="badge bg-success">Available</span>
                      <?php elseif ($todayStatus === 'Booked'): ?>
                        <span class="badge bg-warning text-dark">Booked</span>
                      <?php else: ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($todayStatus); ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($todayStatus === 'Archived'): ?>
                        <span class="text-muted small">Archived</span>
                      <?php elseif ($todayStatus === 'Booked'): ?>
                        <span class="text-warning fw-bold small">Booked today</span>
                      <?php else: ?>
                        <button type="button" class="btn btn-outline-emerald btn-sm" onclick="openBookingForm(<?php echo $row['venueID']; ?>, '<?php echo addslashes(htmlspecialchars($row['name'])); ?>', <?php echo $row['baseprice']; ?>)">Book</button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td colspan="5" class="text-muted text-center">No venues match this filter.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Book a Resource View -->
      <section class="view" id="book">
        <h2 class="mb-4">Search & Book Resources</h2>
        
        <?php
          $searchType = trim($_GET['search_type'] ?? '');
          $searchDate = trim($_GET['search_date'] ?? '');
          $isFilterApplied = (!empty($searchType) || !empty($searchDate));
        ?>

        <form action="index.php#book" method="GET" class="row g-2 align-items-center mb-4">
          <div class="col-auto">
            <select name="search_type" class="form-select form-select-sm" aria-label="Venue type">
              <option value="">All Resource Types</option>
              <option value="Chapel" <?php if ($searchType === 'Chapel') echo 'selected'; ?>>Chapel</option>
              <option value="Viewing room" <?php if ($searchType === 'Viewing room') echo 'selected'; ?>>Viewing room</option>
            </select>
          </div>
          <div class="col-auto">
            <input type="date" class="form-control form-control-sm" name="search_date" aria-label="Date" min="<?php echo $today; ?>" value="<?php echo htmlspecialchars($searchDate); ?>">
          </div>
          <div class="col-auto">
            <button class="btn btn-emerald btn-sm" type="submit">Search Available</button>
          </div>
          <?php if ($isFilterApplied): ?>
            <div class="col-auto">
              <a href="index.php#book" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
          <?php endif; ?>
        </form>

        <div class="row row-cols-1 row-cols-md-3 g-4">
          <?php
          $checkDate = (!empty($searchDate) && $searchDate >= $today) ? $conn->real_escape_string($searchDate) : $today;

          $sql = "SELECT v.*, 
                    (SELECT COUNT(*) 
                     FROM booking b 
                     WHERE b.venueID = v.venueID 
                       AND b.bookingStatus = 'Confirmed'
                       AND b.startDate <= '$checkDate' 
                       AND b.endDate >= '$checkDate'
                    ) AS isBookedOnDate,
                    (SELECT startDate FROM booking b 
                     WHERE b.venueID = v.venueID AND b.bookingStatus = 'Confirmed' 
                       AND b.startDate <= '$checkDate' AND b.endDate >= '$checkDate' 
                     LIMIT 1
                    ) AS current_start,
                    (SELECT endDate FROM booking b 
                     WHERE b.venueID = v.venueID AND b.bookingStatus = 'Confirmed' 
                       AND b.startDate <= '$checkDate' AND b.endDate >= '$checkDate' 
                     LIMIT 1
                    ) AS current_end,
                    (SELECT startDate FROM booking b 
                     WHERE b.venueID = v.venueID AND b.bookingStatus = 'Confirmed' 
                       AND b.startDate > '$checkDate' 
                     ORDER BY b.startDate ASC LIMIT 1
                    ) AS next_start,
                    (SELECT endDate FROM booking b 
                     WHERE b.venueID = v.venueID AND b.bookingStatus = 'Confirmed' 
                       AND b.startDate > '$checkDate' 
                     ORDER BY b.startDate ASC LIMIT 1
                    ) AS next_end
                  FROM venue v
                  WHERE COALESCE(NULLIF(TRIM(v.availability), ''), 'Archived') <> 'Archived'";

          if (!empty($searchType)) {
              $sql .= " AND v.type = '" . $conn->real_escape_string($searchType) . "'";
          }

          // Filter out booked resources so only UNBOOKED resources are shown when search is applied
          if ($isFilterApplied) {
              $sql .= " AND NOT EXISTS (
                  SELECT 1 FROM booking b 
                  WHERE b.venueID = v.venueID 
                    AND b.bookingStatus = 'Confirmed' 
                    AND b.startDate <= '$checkDate' 
                    AND b.endDate >= '$checkDate'
              )";
          }

          $venueCards = $conn->query($sql);

          if ($venueCards && $venueCards->num_rows > 0):
            while ($v = $venueCards->fetch_assoc()):
              $imgSrc = !empty($v['imagePath']) ? $v['imagePath'] : "uploads/venues/default.jpg";
              
              if ($v['availability'] === 'Maintenance') {
                  $statusText = 'Maintenance';
                  $isAvailable = false;
              } else {
                  $isAvailable = ($v['isBookedOnDate'] == 0);
                  $statusText = $isAvailable ? 'Available' : 'Booked';
              }
          ?>
            <div class="col">
              <div class="card h-100 shadow-sm border">
                <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($v['name']); ?>" style="height: 160px; object-fit: cover;">
                <div class="card-body">
                  <h5 class="card-title text-success"><?php echo htmlspecialchars($v['name']); ?></h5>
                  <p class="card-text text-muted mb-1">Capacity <?php echo $v['capacity']; ?>, <?php echo htmlspecialchars($v['type']); ?></p>
                  <p class="card-text fw-bold text-dark mb-2">Php <?php echo number_format($v['baseprice'], 2); ?> / day</p>
                  
                  <?php if ($isAvailable): ?>
                    <span class="badge bg-success mb-2">Available</span>
                  <?php else: ?>
                    <span class="badge bg-danger mb-2">Booked</span>
                  <?php endif; ?>

                  <p class="small text-muted mt-2 mb-0">
                    <?php if (!$isAvailable && !empty($v['current_start'])): ?>
                      <strong>Booking Period:</strong> <?php echo $v['current_start']; ?> to <?php echo $v['current_end']; ?>
                    <?php elseif (!empty($v['next_start'])): ?>
                      <strong>Next Booking:</strong> <?php echo $v['next_start']; ?> to <?php echo $v['next_end']; ?>
                    <?php else: ?>
                      <span class="text-success">No upcoming bookings</span>
                    <?php endif; ?>
                  </p>
                </div>
                <div class="card-footer bg-transparent border-top-0 pb-3">
                  <?php if ($isAvailable): ?>
                    <button type="button" class="btn btn-emerald w-100" onclick="openBookingForm(<?php echo $v['venueID']; ?>, '<?php echo addslashes(htmlspecialchars($v['name'])); ?>', <?php echo $v['baseprice']; ?>)">Book Resource</button>
                  <?php else: ?>
                    <button class="btn btn-secondary w-100" disabled>Currently Booked</button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; else: ?>
            <p class="text-muted px-3">No available resources found matching your search criteria.</p>
          <?php endif; ?>
        </div>
      </section>

      <!-- Manage Venues View (Admin Only) -->
      <section class="view" id="venues">
        <h2 class="mb-4">Manage Resource Inventory (Admin)</h2>
        
        <div class="card shadow-sm p-4 bg-white mb-4">
          <h3 class="h5 text-success mb-3">+ Add New Resource / Venue</h3>
          <form action="index.php#venues" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_venue">
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Category / Type</label>
              <select name="type" class="form-select">
                <option value="Chapel">Chapel</option>
                <option value="Viewing room">Viewing room</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Capacity</label>
              <input type="number" class="form-control" name="capacity" min="1" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Base Price per Day (PHP)</label>
              <input type="number" step="0.01" class="form-control" name="baseprice" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Availability</label>
              <select name="availability" class="form-select">
                <option value="Available">Available</option>
                <option value="Booked">Booked</option>
                <option value="Maintenance">Maintenance</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Photo Upload</label>
              <input type="file" class="form-control" name="venue_image" accept="image/*" required>
            </div>
            <button class="btn btn-emerald" type="submit">Save Resource</button>
          </form>
        </div>

        <div class="card shadow-sm p-4 bg-white">
          <h3 class="h5 text-success mb-3">Current Resource Inventory</h3>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
              <thead class="table-light">
                <tr><th>ID</th><th>Photo</th><th>Name</th><th>Type</th><th>Capacity</th><th>Price/Day</th><th>Status</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php
                // Dynamically evaluate status based on TODAY's date
                $vList = $conn->query("
                  SELECT v.*,
                    CASE
                      WHEN v.availability = 'Archived' OR v.availability IS NULL OR TRIM(v.availability) = '' THEN 'Archived'
                      WHEN v.availability = 'Maintenance' THEN 'Maintenance'
                      WHEN EXISTS (SELECT 1 FROM booking b WHERE b.venueID = v.venueID AND b.bookingStatus = 'Confirmed' AND b.startDate <= '$today' AND b.endDate >= '$today') THEN 'Booked'
                      ELSE 'Available'
                    END AS todayStatus
                  FROM venue v ORDER BY v.venueID ASC
                ");

                if ($vList && $vList->num_rows > 0):
                  while ($v = $vList->fetch_assoc()):
                    $photoPath  = !empty($v['imagePath']) ? $v['imagePath'] : "uploads/venues/default.jpg";
                    $statusToday = $v['todayStatus'];
                    $isArchived  = ($statusToday === 'Archived');
                ?>
                  <tr>
                    <td>R-<?php echo $v['venueID']; ?></td>
                    <td><img src="<?php echo htmlspecialchars($photoPath); ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;" alt="Venue photo"></td>
                    <td><?php echo htmlspecialchars($v['name']); ?></td>
                    <td><?php echo htmlspecialchars($v['type']); ?></td>
                    <td><?php echo $v['capacity']; ?></td>
                    <td>Php <?php echo number_format($v['baseprice'], 2); ?></td>
                    <td>
                      <?php if ($statusToday === 'Available'): ?>
                        <span class="badge bg-success">Available</span>
                      <?php elseif ($statusToday === 'Booked'): ?>
                        <span class="badge bg-warning text-dark">Booked</span>
                      <?php else: ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($statusToday); ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex gap-2 align-items-center">
                        <?php if (!$isArchived): ?>
                          <button type="button" class="btn btn-outline-secondary btn-sm edit-venue-btn"
                            data-id="<?php echo $v['venueID']; ?>"
                            data-name="<?php echo htmlspecialchars($v['name'], ENT_QUOTES); ?>"
                            data-type="<?php echo htmlspecialchars($v['type'], ENT_QUOTES); ?>"
                            data-capacity="<?php echo $v['capacity']; ?>"
                            data-price="<?php echo $v['baseprice']; ?>"
                            data-status="<?php echo htmlspecialchars($v['availability'], ENT_QUOTES); ?>">
                            Edit
                          </button>

                          <form action="index.php#venues" method="POST" class="d-inline" onsubmit="return confirm('Archive this venue?');">
                            <input type="hidden" name="action" value="archive_venue">
                            <input type="hidden" name="venueID" value="<?php echo $v['venueID']; ?>">
                            <input type="hidden" name="target_status" value="Archived">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Archive</button>
                          </form>
                        <?php else: ?>
                          <form action="index.php#venues" method="POST" class="d-inline">
                            <input type="hidden" name="action" value="archive_venue">
                            <input type="hidden" name="venueID" value="<?php echo $v['venueID']; ?>">
                            <input type="hidden" name="target_status" value="Available">
                            <button class="btn btn-outline-success btn-sm" type="submit">Restore</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td colspan="8" class="text-center text-muted">No resources recorded.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Clients View -->
      <section class="view" id="clients">
        <h2 class="mb-4">Client Records Management</h2>

        <div class="card shadow-sm p-4 bg-white mb-4">
          <h3 class="h5 text-success mb-3">+ Add New Client Record</h3>
          <form action="index.php#clients" method="POST">
            <input type="hidden" name="action" value="add_client">
            <div class="mb-3">
              <label class="form-label">First Name</label>
              <input type="text" class="form-control" name="firstName" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Last Name</label>
              <input type="text" class="form-control" name="lastName" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Contact Number</label>
              <input type="text" class="form-control" name="contactNum" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" name="emailAddr" required>
            </div>
            <button class="btn btn-emerald" type="submit">Save Client</button>
          </form>
        </div>

        <div class="card shadow-sm p-4 bg-white">
          <h3 class="h5 text-success mb-3">Registered Clients</h3>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
              <thead class="table-light">
                <tr><th>Client ID</th><th>Full Name</th><th>Contact Number</th><th>Email Address</th><th>Status</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php
                $cList = $conn->query("SELECT * FROM client ORDER BY clientID DESC");
                if ($cList && $cList->num_rows > 0):
                  while ($c = $cList->fetch_assoc()):
                    $cStatus = $c['status'] ?? 'Active';
                ?>
                  <tr>
                    <td>C-<?php echo $c['clientID']; ?></td>
                    <td><?php echo htmlspecialchars($c['firstName'] . ' ' . $c['lastName']); ?></td>
                    <td><?php echo htmlspecialchars($c['contactNum']); ?></td>
                    <td><?php echo htmlspecialchars($c['emailAddr']); ?></td>
                    <td>
                      <span class="badge <?php echo ($cStatus === 'Active') ? 'bg-success' : 'bg-secondary'; ?>"><?php echo htmlspecialchars($cStatus); ?></span>
                    </td>
                    <td>
                      <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm edit-client-btn"
                          data-id="<?php echo $c['clientID']; ?>"
                          data-first="<?php echo htmlspecialchars($c['firstName'], ENT_QUOTES); ?>"
                          data-last="<?php echo htmlspecialchars($c['lastName'], ENT_QUOTES); ?>"
                          data-contact="<?php echo htmlspecialchars($c['contactNum'], ENT_QUOTES); ?>"
                          data-email="<?php echo htmlspecialchars($c['emailAddr'], ENT_QUOTES); ?>">
                          Edit
                        </button>

                        <form action="index.php#clients" method="POST" class="d-inline" onsubmit="return confirm('Change status for this client?');">
                          <input type="hidden" name="action" value="archive_client">
                          <input type="hidden" name="clientID" value="<?php echo $c['clientID']; ?>">
                          <?php if ($cStatus === 'Active'): ?>
                            <input type="hidden" name="target_status" value="Archived">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Archive</button>
                          <?php else: ?>
                            <input type="hidden" name="target_status" value="Active">
                            <button class="btn btn-outline-success btn-sm" type="submit">Restore</button>
                          <?php endif; ?>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td colspan="6" class="text-center text-muted">No client records found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Staff View -->
      <section class="view" id="staff">
        <h2 class="mb-4">Staff Records & Personal Accounts</h2>

        <div class="card shadow-sm p-4 bg-white mb-4 admin-only-staff-action">
          <h3 class="h5 text-success mb-3">+ Add / Update Staff Account</h3>
          <form action="index.php#staff" method="POST">
            <input type="hidden" name="action" value="add_staff">
            <div class="mb-3">
              <label class="form-label">First Name</label>
              <input type="text" class="form-control" name="firstName" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Last Name</label>
              <input type="text" class="form-control" name="lastName" required>
            </div>
            <div class="mb-3">
              <label class="form-label">System Role</label>
              <select name="role" class="form-select">
                <option value="Staff">Staff</option>
                <option value="Administrator">Administrator</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" name="emailAddr" required>
            </div>
            <button class="btn btn-emerald" type="submit">Save Account</button>
          </form>
        </div>

        <div class="card shadow-sm p-4 bg-white">
          <h3 class="h5 text-success mb-3">Employee Directory</h3>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
              <thead class="table-light">
                <tr><th>Staff ID</th><th>Employee Name</th><th>Role</th><th>Email Address</th><th>Status</th><th class="admin-only-staff-action">Actions</th></tr>
              </thead>
              <tbody>
                <?php
                $sList = $conn->query("SELECT * FROM employee ORDER BY empID DESC");
                if ($sList && $sList->num_rows > 0):
                  while ($s = $sList->fetch_assoc()):
                    $sStatus = $s['status'] ?? 'Active';
                ?>
                  <tr>
                    <td>S-<?php echo $s['empID']; ?></td>
                    <td><?php echo htmlspecialchars($s['firstName'] . ' ' . $s['lastName']); ?></td>
                    <td><?php echo htmlspecialchars($s['role']); ?></td>
                    <td><?php echo htmlspecialchars($s['emailAddr']); ?></td>
                    <td>
                      <span class="badge <?php echo ($sStatus === 'Active') ? 'bg-success' : 'bg-secondary'; ?>"><?php echo htmlspecialchars($sStatus); ?></span>
                    </td>
                    <td class="admin-only-staff-action">
                      <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm edit-staff-btn"
                          data-id="<?php echo $s['empID']; ?>"
                          data-first="<?php echo htmlspecialchars($s['firstName'], ENT_QUOTES); ?>"
                          data-last="<?php echo htmlspecialchars($s['lastName'], ENT_QUOTES); ?>"
                          data-role="<?php echo htmlspecialchars($s['role'], ENT_QUOTES); ?>"
                          data-email="<?php echo htmlspecialchars($s['emailAddr'], ENT_QUOTES); ?>">
                          Edit
                        </button>

                        <form action="index.php#staff" method="POST" class="d-inline" onsubmit="return confirm('Change status for this staff account?');">
                          <input type="hidden" name="action" value="archive_staff">
                          <input type="hidden" name="empID" value="<?php echo $s['empID']; ?>">
                          <?php if ($sStatus === 'Active'): ?>
                            <input type="hidden" name="target_status" value="Archived">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Archive</button>
                          <?php else: ?>
                            <input type="hidden" name="target_status" value="Active">
                            <button class="btn btn-outline-success btn-sm" type="submit">Restore</button>
                          <?php endif; ?>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td colspan="6" class="text-center text-muted">No staff records found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Booking History View -->
      <section class="view" id="bookings">
        <h2 class="mb-4">Booking History & Management</h2>

        <?php
          $searchBooking = trim($_GET['search_booking'] ?? '');
          $bookingFilter = $_GET['booking_filter'] ?? 'all';
          if (!in_array($bookingFilter, ['all', 'active', 'inactive'], true)) {
              $bookingFilter = 'all';
          }
          $paymentFilter = $_GET['payment_filter'] ?? 'All';
          if (!in_array($paymentFilter, ['All', 'Paid', 'Partial', 'Pending'], true)) {
              $paymentFilter = 'All';
          }
          $bookingStartDate = trim($_GET['booking_start_date'] ?? '');
          $bookingEndDate   = trim($_GET['booking_end_date'] ?? '');
          $bookingDateError = $bookingStartDate !== '' && $bookingEndDate !== '' && $bookingStartDate > $bookingEndDate;
        ?>

        <div class="card shadow-sm p-4 bg-white mb-4">
          <form action="index.php#bookings" method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-auto">
              <label class="form-label small mb-1 fw-bold text-success">Booking Filter</label>
              <select name="booking_filter" class="form-select form-select-sm">
                <option value="all" <?php if ($bookingFilter === 'all') echo 'selected'; ?>>All Bookings</option>
                <option value="active" <?php if ($bookingFilter === 'active') echo 'selected'; ?>>Active (Current / Upcoming)</option>
                <option value="inactive" <?php if ($bookingFilter === 'inactive') echo 'selected'; ?>>Inactive (Past / Cancelled)</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="form-label small mb-1 fw-bold text-success">Payment Status</label>
              <select name="payment_filter" class="form-select form-select-sm">
                <?php foreach (['All', 'Paid', 'Partial', 'Pending'] as $paymentOption): ?>
                  <option value="<?php echo $paymentOption; ?>" <?php if ($paymentFilter === $paymentOption) echo 'selected'; ?>><?php echo $paymentOption; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-auto">
              <label class="form-label small mb-1 fw-bold text-success">Start Date</label>
              <input type="date" name="booking_start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($bookingStartDate); ?>">
            </div>
            <div class="col-auto">
              <label class="form-label small mb-1 fw-bold text-success">End Date</label>
              <input type="date" name="booking_end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($bookingEndDate); ?>">
            </div>
            <div class="col-auto">
              <label class="form-label small mb-1 fw-bold text-success">Client / Deceased Name</label>
              <input type="text" name="search_booking" id="bookingSearchInput" class="form-control form-control-sm" placeholder="Enter a name" value="<?php echo htmlspecialchars($searchBooking); ?>">
            </div>
            <div class="col-auto">
              <button class="btn btn-emerald btn-sm" type="submit">Search</button>
            </div>
            <?php if ($bookingFilter !== 'all' || $paymentFilter !== 'All' || $bookingStartDate !== '' || $bookingEndDate !== '' || $searchBooking !== ''): ?>
              <div class="col-auto">
                <a href="index.php#bookings" class="btn btn-outline-secondary btn-sm">Reset</a>
              </div>
            <?php endif; ?>
          </form>

          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
              <thead class="table-light">
                <tr><th>ID</th><th>Client</th><th>Deceased Name</th><th>Resource</th><th>Recorded By</th><th>Dates</th><th>Total Price</th><th>Paid</th><th>Balance</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php
                $bSql = "
                  SELECT b.*, v.name AS venueName, c.firstName AS cFirst, c.lastName AS cLast, e.firstName AS eFirst, e.lastName AS eLast 
                  FROM booking b
                  JOIN venue v ON b.venueID = v.venueID
                  JOIN client c ON b.clientID = c.clientID
                  LEFT JOIN employee e ON b.empID = e.empID
                ";

                $bookingConditions = [];
                if (!empty($searchBooking)) {
                    $escapedSearch = $conn->real_escape_string($searchBooking);
                    $bookingConditions[] = "(c.firstName LIKE '%$escapedSearch%'
                                    OR c.lastName LIKE '%$escapedSearch%'
                                    OR CONCAT(c.firstName, ' ', c.lastName) LIKE '%$escapedSearch%'
                                    OR b.deceasedName LIKE '%$escapedSearch%')";
                }

                if ($bookingFilter === 'active') {
                    $bookingConditions[] = "b.bookingStatus = 'Confirmed' AND b.endDate >= '$today'";
                } elseif ($bookingFilter === 'inactive') {
                    $bookingConditions[] = "(b.bookingStatus <> 'Confirmed' OR b.endDate < '$today')";
                }

                if ($paymentFilter !== 'All') {
                    $bookingConditions[] = "b.paymentStatus = '" . $conn->real_escape_string($paymentFilter) . "'";
                }
                if ($bookingStartDate !== '' && !$bookingDateError) {
                    $bookingConditions[] = "b.startDate >= '" . $conn->real_escape_string($bookingStartDate) . "'";
                }
                if ($bookingEndDate !== '' && !$bookingDateError) {
                    $bookingConditions[] = "b.startDate <= '" . $conn->real_escape_string($bookingEndDate) . "'";
                }
                if (!empty($bookingConditions)) {
                    $bSql .= ' WHERE ' . implode(' AND ', $bookingConditions);
                }

                $bSql .= " ORDER BY b.bookingID DESC";
                $bList = $conn->query($bSql);

                if ($bList && $bList->num_rows > 0):
                  while ($b = $bList->fetch_assoc()):
                    $empName    = !empty($b['eFirst']) ? htmlspecialchars($b['eFirst'] . ' ' . $b['eLast']) : 'N/A';
                    $deceased   = !empty($b['deceasedName']) ? htmlspecialchars($b['deceasedName']) : 'N/A';
                    $paidAmt    = floatval($b['downpayment'] ?? 0);
                    $totalPrice = floatval($b['totalPrice']);
                    $balance    = max(0, $totalPrice - $paidAmt);
                    $isPast     = ($b['endDate'] < $today);
                ?>
                  <tr>
                    <td>B-<?php echo $b['bookingID']; ?></td>
                    <td><?php echo htmlspecialchars($b['cFirst'] . ' ' . $b['cLast']); ?></td>
                    <td><strong><?php echo $deceased; ?></strong></td>
                    <td><?php echo htmlspecialchars($b['venueName']); ?></td>
                    <td><?php echo $empName; ?></td>
                    <td><?php echo $b['startDate'] . ' to ' . $b['endDate']; ?></td>
                    <td>Php <?php echo number_format($totalPrice, 2); ?></td>
                    <td class="text-success fw-bold">Php <?php echo number_format($paidAmt, 2); ?></td>
                    <td class="<?php echo ($balance > 0) ? 'text-danger' : 'text-muted'; ?> fw-bold">Php <?php echo number_format($balance, 2); ?></td>
                    <td>
                      <details class="d-inline-block">
                        <summary class="btn btn-outline-secondary btn-sm py-0 px-2"><?php echo htmlspecialchars($b['paymentStatus']); ?></summary>
                        <form action="index.php#bookings" method="POST" class="mt-2 p-2 border rounded bg-white shadow-sm">
                          <input type="hidden" name="action" value="update_booking_status">
                          <input type="hidden" name="bookingID" value="<?php echo $b['bookingID']; ?>">
                          <input type="hidden" name="bookingStatus" value="<?php echo $b['bookingStatus']; ?>">
                          <div class="mb-2">
                            <label class="form-label small mb-1">Status</label>
                            <select name="paymentStatus" class="form-select form-select-sm">
                              <option value="Paid" <?php if($b['paymentStatus']=='Paid') echo 'selected'; ?>>Paid</option>
                              <option value="Partial" <?php if($b['paymentStatus']=='Partial') echo 'selected'; ?>>Partial</option>
                              <option value="Pending" <?php if($b['paymentStatus']=='Pending') echo 'selected'; ?>>Pending</option>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label small mb-1">Add Payment</label>
                            <input type="number" step="0.01" min="0" name="addPayment" placeholder="0.00" class="form-control form-control-sm">
                          </div>
                          <button class="btn btn-emerald btn-sm w-100" type="submit">Save</button>
                        </form>
                      </details>
                    </td>
                    <td>
                      <?php if ($b['bookingStatus'] === 'Confirmed'): ?>
                        <?php if ($isPast): ?>
                          <span class="badge bg-secondary">Completed</span>
                        <?php else: ?>
                          <span class="badge bg-success">Confirmed</span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="badge bg-danger"><?php echo htmlspecialchars($b['bookingStatus']); ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex gap-2 align-items-center">
                        <?php if ($b['bookingStatus'] !== 'Cancelled' && !$isPast): ?>
                          <form action="index.php#bookings" method="POST" class="d-inline" onsubmit="return confirm('Cancel this booking?');">
                            <input type="hidden" name="action" value="update_booking_status">
                            <input type="hidden" name="bookingID" value="<?php echo $b['bookingID']; ?>">
                            <input type="hidden" name="paymentStatus" value="<?php echo $b['paymentStatus']; ?>">
                            <input type="hidden" name="bookingStatus" value="Cancelled">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Cancel</button>
                          </form>
                        <?php endif; ?>
                        <form action="index.php#bookings" method="POST" class="d-inline" onsubmit="return confirm('Delete booking record permanently?');">
                          <input type="hidden" name="action" value="delete_booking">
                          <input type="hidden" name="bookingID" value="<?php echo $b['bookingID']; ?>">
                          <button class="btn btn-outline-secondary btn-sm" type="submit">&times;</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td colspan="12" class="text-center text-muted">No booking records found matching search criteria.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Reports View -->
      <section class="view" id="reports">
        <?php
          $reportStartFrom = trim($_GET['report_start_from'] ?? '');
          $reportStartTo   = trim($_GET['report_start_to'] ?? '');
          $reportDateError = (!empty($reportStartFrom) && !empty($reportStartTo) && $reportStartFrom > $reportStartTo);
        ?>

        <!-- Executive PDF Letterhead Header -->
        <div class="print-only-header">
          <div class="print-header-top">
            <div class="print-brand">
              <img src="logo.png" alt="Goodbye Grove logo">
              <div>
                <h1 class="print-brand-title">Goodbye Grove</h1>
                <p class="print-brand-sub">Resource Management & Funeral Services</p>
              </div>
            </div>
            <div class="print-report-title">
              <h2>EXECUTIVE RESOURCE BOOKING REPORT</h2>
            </div>
          </div>
          <div class="print-meta-bar">
            <div><strong>Date Filter Range:</strong> <?php echo (!empty($reportStartFrom) || !empty($reportStartTo)) ? htmlspecialchars(($reportStartFrom ?: 'Beginning') . ' to ' . ($reportStartTo ?: 'Present')) : 'All Time History'; ?></div>
            <div><strong>Report Generated:</strong> <?php echo date('F j, Y, g:i A'); ?></div>
            <div><strong>Generated By:</strong> System Administrator</div>
          </div>
        </div>

        <h2 class="mb-4">Periodic Booking Reports</h2>

        <div class="card shadow-sm p-4 bg-white mb-4">
          <form action="index.php#reports" method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="form-label small fw-bold text-success mb-1">Start Date</label>
              <input type="date" id="reportStartDate" name="report_start_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($reportStartFrom); ?>">
            </div>
            <div class="col-auto">
              <label class="form-label small fw-bold text-success mb-1">End Date</label>
              <input type="date" id="reportEndDate" name="report_start_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($reportStartTo); ?>">
            </div>
            <div class="col-auto">
              <button class="btn btn-emerald btn-sm" type="submit">Filter Reports</button>
            </div>
            <?php if (!empty($reportStartFrom) || !empty($reportStartTo)): ?>
              <div class="col-auto">
                <a href="index.php#reports" class="btn btn-outline-secondary btn-sm">Reset Filter</a>
              </div>
            <?php endif; ?>
            <div class="col-auto ms-auto">
              <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">Print / Export PDF</button>
            </div>
          </form>
        </div>

        <div class="card shadow-sm p-4 bg-white mb-4">
          <h3 class="h5 text-success mb-3">1. Booking Breakdown per Resource</h3>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
              <thead class="table-light">
                <tr><th>Resource Name</th><th>Total Bookings</th><th>Total Days Booked</th><th>Total Revenue</th></tr>
              </thead>
              <tbody>
                <?php
                $resJoinCond = "";
                if (!empty($reportStartFrom)) {
                    $escapedFrom = $conn->real_escape_string($reportStartFrom);
                    $resJoinCond .= " AND b.startDate >= '$escapedFrom'";
                }
                if (!empty($reportStartTo)) {
                    $escapedTo = $conn->real_escape_string($reportStartTo);
                    $resJoinCond .= " AND b.startDate <= '$escapedTo'";
                }

                $repSql = "
                  SELECT v.venueID, v.name, COUNT(b.bookingID) AS totalBookings, COALESCE(SUM(b.daysUsed), 0) AS totalDays, COALESCE(SUM(b.totalPrice), 0) AS totalRev
                  FROM venue v
                  LEFT JOIN booking b ON v.venueID = b.venueID $resJoinCond
                  GROUP BY v.venueID
                ";
                $repList = $conn->query($repSql);

                $totB = 0; $totD = 0; $totR = 0;
                if ($repList && $repList->num_rows > 0):
                  while ($r = $repList->fetch_assoc()):
                    $totB += $r['totalBookings'];
                    $totD += $r['totalDays'];
                    $totR += $r['totalRev'];
                    $venueID = $r['venueID'];
                ?>
                  <tr class="resource-row" onclick="toggleResourceBreakdown(<?php echo $venueID; ?>)" id="main_row_<?php echo $venueID; ?>">
                    <td>
                      <span class="report-expand-icon" id="icon_<?php echo $venueID; ?>">▶</span>
                      <strong><?php echo htmlspecialchars($r['name']); ?></strong>
                    </td>
                    <td><?php echo $r['totalBookings']; ?> Bookings</td>
                    <td><?php echo $r['totalDays']; ?> Days</td>
                    <td><strong>Php <?php echo number_format($r['totalRev'], 2); ?></strong></td>
                  </tr>

                  <tr id="breakdown_<?php echo $venueID; ?>" class="resource-detail-row">
                    <td colspan="4" class="detail-cell">
                      <div class="detail-wrapper">
                        <?php
                        $vBookingsSql = "
                          SELECT b.*, c.firstName AS cFirst, c.lastName AS cLast
                          FROM booking b
                          JOIN client c ON b.clientID = c.clientID
                          WHERE b.venueID = $venueID $resJoinCond
                          ORDER BY b.startDate DESC
                        ";
                        $vBookings = $conn->query($vBookingsSql);

                        if ($vBookings && $vBookings->num_rows > 0):
                        ?>
                          <table class="table table-sm table-bordered bg-white mb-0">
                            <thead class="table-light">
                              <tr><th>Booking ID</th><th>Client Name</th><th>Deceased Name</th><th>Booking Period</th><th>Payment Status</th><th>Status</th><th>Total Price</th></tr>
                            </thead>
                            <tbody>
                              <?php while ($vb = $vBookings->fetch_assoc()): ?>
                                <tr>
                                  <td>B-<?php echo $vb['bookingID']; ?></td>
                                  <td><?php echo htmlspecialchars($vb['cFirst'] . ' ' . $vb['cLast']); ?></td>
                                  <td><strong><?php echo !empty($vb['deceasedName']) ? htmlspecialchars($vb['deceasedName']) : 'N/A'; ?></strong></td>
                                  <td><?php echo $vb['startDate'] . ' to ' . $vb['endDate']; ?></td>
                                  <td><?php echo htmlspecialchars($vb['paymentStatus']); ?></td>
                                  <td><span class="badge <?php echo ($vb['bookingStatus'] === 'Confirmed') ? 'bg-success' : 'bg-danger'; ?>"><?php echo htmlspecialchars($vb['bookingStatus']); ?></span></td>
                                  <td>Php <?php echo number_format($vb['totalPrice'], 2); ?></td>
                                </tr>
                              <?php endwhile; ?>
                            </tbody>
                          </table>
                        <?php else: ?>
                          <div class="text-muted small fst-italic">No individual booking records found for this resource in the selected date period.</div>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>

                <?php endwhile; endif; ?>
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <td><strong>TOTALS</strong></td>
                  <td><strong><?php echo $totB; ?> Bookings</strong></td>
                  <td><strong><?php echo $totD; ?> Days</strong></td>
                  <td><strong>Php <?php echo number_format($totR, 2); ?></strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div class="card shadow-sm p-4 bg-white mb-4">
          <h3 class="h5 text-success mb-3">2. Booking Breakdown per Client</h3>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
              <thead class="table-light">
                <tr><th>Client Name</th><th>Assigned Resource</th><th>Booking Dates</th><th>Payment Status</th><th>Total Amount</th></tr>
              </thead>
              <tbody>
                <?php
                $cWhereCond = " WHERE 1=1";
                if (!empty($reportStartFrom)) {
                    $escapedFrom = $conn->real_escape_string($reportStartFrom);
                    $cWhereCond .= " AND b.startDate >= '$escapedFrom'";
                }
                if (!empty($reportStartTo)) {
                    $escapedTo = $conn->real_escape_string($reportStartTo);
                    $cWhereCond .= " AND b.startDate <= '$escapedTo'";
                }

                $cRepSql = "
                  SELECT c.firstName, c.lastName, v.name AS venueName, b.startDate, b.endDate, b.paymentStatus, b.totalPrice
                  FROM booking b
                  JOIN client c ON b.clientID = c.clientID
                  JOIN venue v ON b.venueID = v.venueID
                  $cWhereCond
                  ORDER BY b.startDate DESC
                ";
                $cRep = $conn->query($cRepSql);

                if ($cRep && $cRep->num_rows > 0):
                  while ($cr = $cRep->fetch_assoc()):
                ?>
                  <tr>
                    <td><?php echo htmlspecialchars($cr['firstName'] . ' ' . $cr['lastName']); ?></td>
                    <td><?php echo htmlspecialchars($cr['venueName']); ?></td>
                    <td><?php echo $cr['startDate'] . ' - ' . $cr['endDate']; ?></td>
                    <td><span class="badge <?php echo ($cr['paymentStatus'] === 'Paid') ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo htmlspecialchars($cr['paymentStatus']); ?></span></td>
                    <td>Php <?php echo number_format($cr['totalPrice'], 2); ?></td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td colspan="5" class="text-center text-muted">No client booking history recorded for selected date range.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Booking Count Chart -->
        <div class="card shadow-sm p-4 bg-white">
          <h3 class="h5 text-success mb-2">3. Booking Count per Venue</h3>
          <?php
            $venueCountCond = "";
            if (!empty($reportStartFrom)) {
                $escapedFrom = $conn->real_escape_string($reportStartFrom);
                $venueCountCond .= " AND b.startDate >= '$escapedFrom'";
            }
            if (!empty($reportStartTo)) {
                $escapedTo = $conn->real_escape_string($reportStartTo);
                $venueCountCond .= " AND b.startDate <= '$escapedTo'";
            }
            $venueCountSql = "
                SELECT v.venueID, v.name, COUNT(b.bookingID) AS bookingCount
                FROM venue v
                LEFT JOIN booking b ON v.venueID = b.venueID $venueCountCond
                GROUP BY v.venueID, v.name
                ORDER BY bookingCount DESC, v.name ASC
            ";
            $venueCountResult = $conn->query($venueCountSql);
            $venueCounts = [];
            $totalVenueBookings = 0;
            $pieColors = ['#0b6e4f', '#6F9E76', '#D6A84F', '#527AA3', '#B66D59', '#8B79B8', '#3D9A9A', '#C477A8'];
            if ($venueCountResult) {
                while ($venueCountRow = $venueCountResult->fetch_assoc()) {
                    $venueCountRow['bookingCount'] = (int)$venueCountRow['bookingCount'];
                    $venueCounts[] = $venueCountRow;
                    $totalVenueBookings += $venueCountRow['bookingCount'];
                }
            }
            $pieSegments = [];
            $pieLegend = [];
            $currentAngle = 0;
            foreach ($venueCounts as $i => $venueCountRow) {
                $count = $venueCountRow['bookingCount'];
                $color = $pieColors[$i % count($pieColors)];
                $pieLegend[] = ['name' => $venueCountRow['name'], 'count' => $count, 'color' => $color];
                if ($totalVenueBookings > 0 && $count > 0) {
                    $nextAngle = $currentAngle + ($count / $totalVenueBookings) * 360;
                    $pieSegments[] = $color . ' ' . number_format($currentAngle, 3, '.', '') . 'deg ' . number_format($nextAngle, 3, '.', '') . 'deg';
                    $currentAngle = $nextAngle;
                }
            }
          ?>
          <?php if ($totalVenueBookings > 0): ?>
            <div class="venue-pie-layout">
              <div class="venue-pie-chart" style="background: conic-gradient(<?php echo implode(', ', $pieSegments); ?>);"></div>
              <div class="venue-pie-legend">
                <?php foreach ($pieLegend as $legendItem): ?>
                  <div class="venue-pie-legend-item">
                    <span class="venue-pie-swatch" style="background:<?php echo htmlspecialchars($legendItem['color']); ?>;"></span>
                    <span class="venue-pie-name"><?php echo htmlspecialchars($legendItem['name']); ?></span>
                    <span class="venue-pie-count"><?php echo $legendItem['count']; ?> bookings</span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php else: ?>
            <p class="text-muted">No bookings recorded for the selected reporting period.</p>
          <?php endif; ?>
        </div>
      </section>

    </main>
  </div>
</div>

<!--Bootstrap-->

<!-- Booking -->
<div class="modal fade" id="addReservationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-deep text-white">
        <h5 class="modal-title h6">Create Resource Reservation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="index.php#bookings" method="POST" onsubmit="return handleBookingSubmit(event)">
        <div class="modal-body">
          <input type="hidden" name="action" value="add_reservation">
          <input type="hidden" name="venueID" id="resVenueID" required>
          
          <div class="mb-3">
            <label class="form-label fw-bold text-success mb-1">Selected Resource Asset</label>
            <div class="fs-5 text-dark fw-semibold" id="resVenueName">No venue selected</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold text-success mb-1">Name of Deceased</label>
            <input type="text" class="form-control" name="deceasedName" placeholder="e.g. Juan Dela Cruz" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold text-success mb-1">Select Client Record</label>
            <select name="clientID" class="form-select" required>
              <option value="" disabled selected>-- Select Client --</option>
              <?php
              $clients = $conn->query("SELECT clientID, firstName, lastName FROM client WHERE COALESCE(status, 'Active') = 'Active' ORDER BY firstName ASC");
              if ($clients && $clients->num_rows > 0):
                while ($c = $clients->fetch_assoc()):
              ?>
                <option value="<?php echo $c['clientID']; ?>"><?php echo htmlspecialchars($c['firstName'] . ' ' . $c['lastName']); ?> (ID #C-<?php echo $c['clientID']; ?>)</option>
              <?php endwhile; endif; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold text-success mb-1">Recording Staff / Employee</label>
            <select name="empID" class="form-select" required>
              <option value="" disabled selected>-- Select Staff / Employee --</option>
              <?php
              $employees = $conn->query("SELECT empID, firstName, lastName, role FROM employee WHERE COALESCE(status, 'Active') = 'Active' ORDER BY firstName ASC");
              if ($employees && $employees->num_rows > 0):
                while ($e = $employees->fetch_assoc()):
              ?>
                <option value="<?php echo $e['empID']; ?>"><?php echo htmlspecialchars($e['firstName'] . ' ' . $e['lastName'] . ' (' . $e['role'] . ')'); ?></option>
              <?php endwhile; endif; ?>
            </select>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold text-success mb-1">Start Date</label>
              <input type="date" class="form-control" name="startDate" id="resStartDate" min="<?php echo $today; ?>" oninput="validateBookingDates()" onchange="validateBookingDates()" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold text-success mb-1">End Date</label>
              <input type="date" class="form-control" name="endDate" id="resEndDate" min="<?php echo $today; ?>" oninput="validateBookingDates()" onchange="validateBookingDates()" required>
            </div>
          </div>

          <!-- duration and cost -->
          <div id="bookingSummaryBox" class="alert alert-success d-flex justify-content-between align-items-center mb-3" style="display: none;">
            <div>
              <span class="text-muted small d-block">Booking Duration</span>
              <span id="summaryDays" class="fw-bold fs-6">0 days</span>
            </div>
            <div class="text-end">
              <span class="text-muted small d-block">Estimated Total Cost</span>
              <span id="summaryCost" class="fs-5 fw-bold text-success">Php 0.00</span>
            </div>
          </div>

          <div id="dateOverlapError" class="alert alert-danger" style="display:none;"></div>

          <div class="mb-3">
            <label class="form-label fw-bold text-success mb-1">Payment Status</label>
            <select name="paymentStatus" id="resPaymentStatus" class="form-select" onchange="toggleDownpaymentInput()">
              <option value="Paid">Paid</option>
              <option value="Partial">Partial / Downpayment</option>
              <option value="Pending" selected>Pending</option>
            </select>
          </div>

          <div id="downpaymentField" class="mb-3" style="display:none;">
            <label class="form-label fw-bold text-success mb-1">Downpayment Amount (PHP)</label>
            <input type="number" step="0.01" min="0" class="form-control" name="downpayment" placeholder="0.00">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-emerald" id="submitBookingBtn">Save Reservation</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!--Edit venue -->
<div class="modal fade" id="editVenueModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-deep text-white">
        <h5 class="modal-title h6">Edit Resource Inventory</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="index.php#venues" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="action" value="edit_venue">
          <input type="hidden" name="venueID" id="editVenueID">
          
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" id="editVenueName" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Category / Type</label>
            <select class="form-select" name="type" id="editVenueType">
              <option value="Chapel">Chapel</option>
              <option value="Viewing room">Viewing room</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input type="number" class="form-control" name="capacity" id="editVenueCapacity" min="1" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Base Price per Day (PHP)</label>
            <input type="number" step="0.01" class="form-control" name="baseprice" id="editVenueBaseprice" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Availability</label>
            <select class="form-select" name="availability" id="editVenueAvailability">
              <option value="Available">Available</option>
              <option value="Booked">Booked</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Replace Photo (Optional)</label>
            <input type="file" class="form-control" name="venue_image" accept="image/*">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-emerald">Update Resource</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!--client -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-deep text-white">
        <h5 class="modal-title h6">Edit Client Record</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="index.php#clients" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="edit_client">
          <input type="hidden" name="clientID" id="editClientID">
          
          <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" class="form-control" name="firstName" id="editClientFirstName" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" class="form-control" name="lastName" id="editClientLastName" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" class="form-control" name="contactNum" id="editClientContactNum" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="emailAddr" id="editClientEmailAddr" required>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-emerald">Update Client</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- staff-->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-deep text-white">
        <h5 class="modal-title h6">Edit Staff Account</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="index.php#staff" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="edit_staff">
          <input type="hidden" name="empID" id="editEmpID">
          
          <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" class="form-control" name="firstName" id="editStaffFirstName" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" class="form-control" name="lastName" id="editStaffLastName" required>
          </div>

          <div class="mb-3">
            <label class="form-label">System Role</label>
            <select class="form-select" name="role" id="editStaffRole">
              <option value="Staff">Staff</option>
              <option value="Administrator">Administrator</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="emailAddr" id="editStaffEmailAddr" required>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-emerald">Update Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JavaScript Bundle CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const todayStr = "<?php echo $today; ?>";
const activeBookings = <?php echo json_encode($activeBookings); ?>;
let currentVenuePrice = 0;

function toggleDownpaymentInput() {
  const statusSelect = document.getElementById('resPaymentStatus');
  const dpField = document.getElementById('downpaymentField');
  if (statusSelect && dpField) {
    dpField.style.display = (statusSelect.value === 'Partial') ? 'block' : 'none';
  }
}

// Global Event Listener using Bootstrap JS API
document.addEventListener('click', function(e) {
  const venueBtn = e.target.closest('.edit-venue-btn');
  if (venueBtn) {
    document.getElementById('editVenueID').value = venueBtn.dataset.id;
    document.getElementById('editVenueName').value = venueBtn.dataset.name;
    document.getElementById('editVenueType').value = venueBtn.dataset.type;
    document.getElementById('editVenueCapacity').value = venueBtn.dataset.capacity;
    document.getElementById('editVenueBaseprice').value = venueBtn.dataset.price;
    document.getElementById('editVenueAvailability').value = venueBtn.dataset.status;
    
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editVenueModal')).show();
    return;
  }

  const clientBtn = e.target.closest('.edit-client-btn');
  if (clientBtn) {
    document.getElementById('editClientID').value = clientBtn.dataset.id;
    document.getElementById('editClientFirstName').value = clientBtn.dataset.first;
    document.getElementById('editClientLastName').value = clientBtn.dataset.last;
    document.getElementById('editClientContactNum').value = clientBtn.dataset.contact;
    document.getElementById('editClientEmailAddr').value = clientBtn.dataset.email;
    
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editClientModal')).show();
    return;
  }

  const staffBtn = e.target.closest('.edit-staff-btn');
  if (staffBtn) {
    document.getElementById('editEmpID').value = staffBtn.dataset.id;
    document.getElementById('editStaffFirstName').value = staffBtn.dataset.first;
    document.getElementById('editStaffLastName').value = staffBtn.dataset.last;
    document.getElementById('editStaffRole').value = staffBtn.dataset.role;
    document.getElementById('editStaffEmailAddr').value = staffBtn.dataset.email;
    
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editStaffModal')).show();
    return;
  }
});

function openBookingForm(venueID, venueName, basePrice = 0) {
  document.getElementById('resVenueID').value = venueID;
  document.getElementById('resVenueName').textContent = venueName;
  currentVenuePrice = parseFloat(basePrice) || 0;
  
  const startInput = document.getElementById('resStartDate');
  const endInput   = document.getElementById('resEndDate');
  
  startInput.min = todayStr;
  endInput.min   = todayStr;
  
  validateBookingDates();

  const bookingModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addReservationModal'));
  bookingModal.show();
}

function toggleResourceBreakdown(venueID) {
  const detailRow = document.getElementById('breakdown_' + venueID);
  const mainRow   = document.getElementById('main_row_' + venueID);
  const icon      = document.getElementById('icon_' + venueID);
  if (detailRow) {
    const isCurrentlyOpen = (detailRow.style.display === 'table-row' || detailRow.classList.contains('open'));
    if (isCurrentlyOpen) {
      detailRow.style.display = 'none';
      detailRow.classList.remove('open');
      if (mainRow) mainRow.classList.remove('is-open');
      if (icon) icon.textContent = '▶';
    } else {
      detailRow.style.display = 'table-row';
      detailRow.classList.add('open');
      if (mainRow) mainRow.classList.add('is-open');
      if (icon) icon.textContent = '▼';
    }
  }
}

function validateBookingDates() {
  const venueID      = document.getElementById('resVenueID').value;
  const startInput   = document.getElementById('resStartDate');
  const endInput     = document.getElementById('resEndDate');
  const startDateVal = startInput.value;
  const endDateVal   = endInput.value;
  const errorDiv     = document.getElementById('dateOverlapError');
  const submitBtn    = document.getElementById('submitBookingBtn');
  const summaryBox   = document.getElementById('bookingSummaryBox');
  const summaryDays  = document.getElementById('summaryDays');
  const summaryCost  = document.getElementById('summaryCost');

  startInput.min = todayStr;
  if (startDateVal && startDateVal >= todayStr) {
    endInput.min = startDateVal;
  } else {
    endInput.min = todayStr;
  }

  let errorMsg = '';

  if (startDateVal && startDateVal < todayStr) {
    errorMsg = 'Start Date cannot be prior to current date (' + todayStr + ').';
  } else if (endDateVal && endDateVal < todayStr) {
    errorMsg = 'End Date cannot be prior to current date (' + todayStr + ').';
  } else if (startDateVal && endDateVal && endDateVal < startDateVal) {
    errorMsg = 'End Date cannot be prior to the Start Date (' + startDateVal + ').';
  } else if (venueID && startDateVal && endDateVal) {
    const hasOverlap = activeBookings.some(b => {
      if (parseInt(b.venueID) === parseInt(venueID)) {
        return (startDateVal <= b.endDate && endDateVal >= b.startDate);
      }
      return false;
    });
    if (hasOverlap) {
      errorMsg = 'This resource is already booked for the selected date range. Please choose different dates.';
    }
  }

  if (startDateVal && endDateVal && startDateVal <= endDateVal) {
    const sDate = new Date(startDateVal);
    const eDate = new Date(endDateVal);
    const diffTime = Math.abs(eDate - sDate);
    const days = Math.max(1, Math.round(diffTime / (1000 * 60 * 60 * 24)));
    const totalCost = days * currentVenuePrice;

    if (summaryDays && summaryCost) {
      summaryDays.textContent = days + (days === 1 ? ' day' : ' days');
      summaryCost.textContent = 'Php ' + totalCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      summaryBox.style.display = 'flex';
    }
  } else {
    if (summaryBox) summaryBox.style.display = 'none';
  }

  if (errorMsg !== '') {
    errorDiv.innerHTML = errorMsg;
    errorDiv.style.display = 'block';
    if (submitBtn) submitBtn.disabled = true;
  } else {
    errorDiv.style.display = 'none';
    if (submitBtn) submitBtn.disabled = false;
  }
}

function handleBookingSubmit(e) {
  validateBookingDates();
  const submitBtn = document.getElementById('submitBookingBtn');
  if (submitBtn && submitBtn.disabled) {
    e.preventDefault();
    return false;
  }
  return true;
}

function switchRole(role) {
  localStorage.setItem('selectedRole', role);
  const isStaff = (role === 'Staff');
  
  document.querySelectorAll('.admin-nav').forEach(el => {
    el.classList.toggle('hidden-role', isStaff);
  });

  document.querySelectorAll('.admin-only-staff-action').forEach(el => {
    if (isStaff) {
      el.style.display = 'none';
    } else {
      el.style.display = '';
    }
  });

  const currentHash = location.hash || '#dashboard';
  if (isStaff && currentHash === '#venues') {
    location.hash = '#dashboard';
  } else {
    showView();
  }
}

function showView() {
  const hash = location.hash || '#dashboard';
  
  document.querySelectorAll('.view').forEach(v => {
    if ('#' + v.id === hash) {
      v.classList.add('active-view');
    } else {
      v.classList.remove('active-view');
    }
  });

  document.querySelectorAll('.sidebar-nav .nav-link').forEach(a => {
    a.classList.toggle('active', a.hash === hash);
  });
}

window.addEventListener('DOMContentLoaded', () => {
  const savedRole = localStorage.getItem('selectedRole') || 'Admin';
  const selector  = document.getElementById('roleSelector');
  if (selector) selector.value = savedRole;

  switchRole(savedRole);
});

window.addEventListener('hashchange', () => {
  showView();
});
</script>
</body>
</html>