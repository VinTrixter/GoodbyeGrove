<?php
session_start();

// 1. DATABASE CONNECTION
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "funeral_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$today = date('Y-m-d');

// Fetch confirmed active bookings for instant JS date conflict validation
$activeBookingsQuery = $conn->query("SELECT venueID, startDate, endDate FROM booking WHERE bookingStatus = 'Confirmed'");
$activeBookings = [];
if ($activeBookingsQuery && $activeBookingsQuery->num_rows > 0) {
    while ($row = $activeBookingsQuery->fetch_assoc()) {
        $activeBookings[] = $row;
    }
}

// HELPER FUNCTION: Handle Image Uploads
function uploadVenueImage($fileInputName) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES[$fileInputName]['tmp_name'];
        $file_name = $_FILES[$fileInputName]['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = array("jpg", "jpeg", "png", "webp", "gif");

        if (in_array($file_ext, $allowed)) {
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
        
        $uploadedPath = uploadVenueImage('venue_image');
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
        
        $uploadedPath = uploadVenueImage('venue_image');

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

    if ($action === 'delete_venue') {
        $venueID = intval($_POST['venueID']);
        $stmt = $conn->prepare("DELETE FROM venue WHERE venueID=?");
        $stmt->bind_param("i", $venueID);
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

        $stmt = $conn->prepare("INSERT INTO client (firstName, lastName, contactNum, emailAddr) VALUES (?, ?, ?, ?)");
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

    if ($action === 'delete_client') {
        $clientID = intval($_POST['clientID']);
        $stmt = $conn->prepare("DELETE FROM client WHERE clientID=?");
        $stmt->bind_param("i", $clientID);
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

        $stmt = $conn->prepare("INSERT INTO employee (firstName, lastName, role, emailAddr) VALUES (?, ?, ?, ?)");
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

    if ($action === 'delete_staff') {
        $empID = intval($_POST['empID']);
        $stmt = $conn->prepare("DELETE FROM employee WHERE empID=?");
        $stmt->bind_param("i", $empID);
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

        // Backend Date Validation Guard
        if ($startDate < $today || $endDate < $startDate) {
            header("Location: index.php#reservation");
            exit();
        }

        $daysUsed = max(1, round((strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24)));

        $vStmt = $conn->prepare("SELECT baseprice FROM venue WHERE venueID = ?");
        $vStmt->bind_param("i", $venueID);
        $vStmt->execute();
        $vRes = $vStmt->get_result()->fetch_assoc();
        $baseprice = $vRes ? $vRes['baseprice'] : 0;
        $vStmt->close();

        $totalPrice = $daysUsed * $baseprice;

        $stmt = $conn->prepare("INSERT INTO booking (clientID, venueID, empID, deceasedName, startDate, endDate, bookingStatus, paymentStatus, daysUsed, totalPrice) VALUES (?, ?, ?, ?, ?, ?, 'Confirmed', ?, ?, ?)");
        $stmt->bind_param("iiissssdd", $clientID, $venueID, $empID, $deceasedName, $startDate, $endDate, $paymentStatus, $daysUsed, $totalPrice);

        if ($stmt->execute()) {
            $uStmt = $conn->prepare("UPDATE venue SET availability = 'Booked' WHERE venueID = ?");
            $uStmt->bind_param("i", $venueID);
            $uStmt->execute();
            $uStmt->close();
        }
        $stmt->close();
        header("Location: index.php#bookings");
        exit();
    }

    if ($action === 'update_booking_status') {
        $bookingID     = intval($_POST['bookingID']);
        $paymentStatus = $_POST['paymentStatus'];
        $bookingStatus = $_POST['bookingStatus'];

        $stmt = $conn->prepare("UPDATE booking SET paymentStatus=?, bookingStatus=? WHERE bookingID=?");
        $stmt->bind_param("ssi", $paymentStatus, $bookingStatus, $bookingID);
        $stmt->execute();
        $stmt->close();

        if ($bookingStatus === 'Cancelled') {
            $bStmt = $conn->prepare("SELECT venueID FROM booking WHERE bookingID=?");
            $bStmt->bind_param("i", $bookingID);
            $bStmt->execute();
            $venueID = $bStmt->get_result()->fetch_assoc()['venueID'] ?? 0;
            $bStmt->close();

            if ($venueID > 0) {
                $uStmt = $conn->prepare("UPDATE venue SET availability='Available' WHERE venueID=?");
                $uStmt->bind_param("i", $venueID);
                $uStmt->execute();
                $uStmt->close();
            }
        }
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
  /* Hide input number spin buttons */
  input[type=number]::-webkit-inner-spin-button, 
  input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
  }
  input[type=number] {
    -moz-appearance: textfield;
  }
  .booking-filter {
    display: inline-flex;
    gap: 4px;
    padding: 4px;
    background: #f1f5f1;
    border: 1px solid #d8e2d8;
    border-radius: 8px;
  }
  .booking-filter-btn {
    border: 0;
    border-radius: 5px;
    padding: 7px 13px;
    background: transparent;
    color: #365939;
    font: inherit;
    cursor: pointer;
  }
  .booking-filter-btn.selected {
    background: #365939;
    color: #fff;
  }
  .venue-ranking {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 18px;
  }
  .venue-ranking-row {
    display: grid;
    grid-template-columns: minmax(130px, 240px) 64px minmax(0, 1fr);
    align-items: center;
    gap: 18px;
  }
  .venue-ranking-name {
    min-width: 0;
    font-weight: 600;
    overflow-wrap: anywhere;
  }
  .venue-ranking-count {
    text-align: right;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
  }
  .venue-bar-track {
    position: relative;
    height: 30px;
    min-width: 0;
    overflow: hidden;
    border-radius: 6px;
    background: #edf1ed;
  }
  .venue-bar-fill {
    height: 100%;
    min-width: 0;
    border-radius: 6px;
    background: #527d55;
    transition: width 0.25s ease;
  }
  .venue-bar-count {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    padding: 0 10px;
    color: #1f3321;
    font-weight: 700;
    font-size: 0.9rem;
    text-shadow: 0 1px 0 rgba(255,255,255,0.55);
    pointer-events: none;
  }
  @media (max-width: 600px) {
    .venue-ranking-row { grid-template-columns: minmax(80px, 1fr) 44px minmax(0, 1.5fr); gap: 8px; }
    .venue-ranking-name { font-size: 0.9rem; }
    .venue-ranking-count { font-size: 0.9rem; }
  }
</style>
</head>
<body>
<header>
  <h1>Goodbye Grove</h1>
  <div>
    <label for="roleSelector" style="font-size: 0.9rem; margin-right: 6px;">Logged in View:</label>
    <select id="roleSelector" onchange="switchRole(this.value)" style="padding: 4px; border-radius: 4px;">
      <option value="Admin">Administrator View</option>
      <option value="Staff">Staff View</option>
    </select>
  </div>
</header>

<div class="layout">
  <nav aria-label="Main menu">
    <p>Menu</p>
    <a href="#dashboard">Dashboard</a>
    <a href="#book">Book a Resource</a>
    <a href="#venues" class="admin-nav">Manage Venues</a>
    <a href="#clients">Clients</a>
    <a href="#staff">Staff Records</a>
    <a href="#bookings">Booking History</a>
    <a href="#reports">Reports</a>
  </nav>

  <main>
    <!-- Dashboard View -->
    <section class="view" id="dashboard">
      <h2>Overview</h2>
      <div class="stats">
        <?php
          $bookingsCount = $conn->query("SELECT COUNT(*) AS total FROM booking")->fetch_assoc()['total'] ?? 0;
          $availVenues   = $conn->query("SELECT COUNT(*) AS avail FROM venue WHERE availability = 'Available'")->fetch_assoc()['avail'] ?? 0;
          $totalVenues   = $conn->query("SELECT COUNT(*) AS total FROM venue")->fetch_assoc()['total'] ?? 0;
          $pendingPay    = $conn->query("SELECT COUNT(*) AS pending FROM booking WHERE paymentStatus = 'Pending'")->fetch_assoc()['pending'] ?? 0;
        ?>
        <div class="stat">Total Bookings <b><?php echo $bookingsCount; ?></b></div>
        <div class="stat">Available Venues <b><?php echo $availVenues . ' / ' . $totalVenues; ?></b></div>
        <div class="stat">Pending Payments <b><?php echo $pendingPay; ?></b></div>
      </div>
      <div class="panel">
        <h3>Resource Availability Overview</h3>
        <table>
          <thead><tr><th>Resource Name</th><th>Category/Type</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php
            $res = $conn->query("SELECT * FROM venue");
            if ($res && $res->num_rows > 0):
              while ($row = $res->fetch_assoc()):
            ?>
              <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['type']); ?></td>
                <td><?php echo htmlspecialchars($row['capacity']); ?></td>
                <td class="<?php echo ($row['availability'] === 'Available') ? 'ok' : 'bad'; ?>"><?php echo htmlspecialchars($row['availability']); ?></td>
                <td><a href="#book" class="btn ghost" style="padding: 2px 8px; font-size: 0.8rem;">Book</a></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="5">No venues recorded in database.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Book a Resource View -->
    <section class="view" id="book">
      <h2>Search & Book Resources</h2>
      
      <?php
        $searchType = trim($_GET['search_type'] ?? '');
        $searchDate = trim($_GET['search_date'] ?? '');
      ?>

      <form action="index.php" method="GET" class="bar">
        <select name="search_type" aria-label="Venue type">
          <option value="">All Resource Types</option>
          <option value="Chapel" <?php if ($searchType === 'Chapel') echo 'selected'; ?>>Chapel</option>
          <option value="Viewing room" <?php if ($searchType === 'Viewing room') echo 'selected'; ?>>Viewing room</option>
        </select>
        <input type="date" name="search_date" aria-label="Date" min="<?php echo $today; ?>" value="<?php echo htmlspecialchars($searchDate); ?>">
        <button class="btn" type="submit">Search Available</button>
      </form>

      <div class="cards">
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
                WHERE 1=1";

        if (!empty($searchType)) {
            $sql .= " AND v.type = '" . $conn->real_escape_string($searchType) . "'";
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
          <article class="card">
            <img class="photo" src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($v['name']); ?>">
            <h3><?php echo htmlspecialchars($v['name']); ?></h3>
            <p>Capacity <?php echo $v['capacity']; ?>, <?php echo htmlspecialchars($v['type']); ?></p>
            <p><strong>Php <?php echo number_format($v['baseprice'], 2); ?>/day</strong></p>
            <p class="<?php echo $isAvailable ? 'ok' : 'bad'; ?>">
              <?php echo $statusText; ?>
              <?php if (!empty($searchDate)): ?>
                <span style="font-size:0.8rem; font-weight:normal; display:block; color:#666;">(on <?php echo htmlspecialchars($searchDate); ?>)</span>
              <?php endif; ?>
            </p>

            <p style="font-size: 0.85rem; color: #555; margin-top: 4px;">
              <?php if (!$isAvailable && !empty($v['current_start'])): ?>
                <strong>Booking Period:</strong> <?php echo $v['current_start']; ?> to <?php echo $v['current_end']; ?>
              <?php elseif (!empty($v['next_start'])): ?>
                <strong>Next Booking:</strong> <?php echo $v['next_start']; ?> to <?php echo $v['next_end']; ?>
              <?php else: ?>
                <span style="color: #28a745;">No upcoming bookings</span>
              <?php endif; ?>
            </p>

            <?php if ($isAvailable): ?>
              <a class="btn" href="#reservation" onclick="openBookingForm(<?php echo $v['venueID']; ?>, '<?php echo addslashes(htmlspecialchars($v['name'])); ?>')">Book Resource</a>
            <?php else: ?>
              <a class="btn" aria-disabled="true" style="opacity:0.6; cursor:not-allowed;">Currently Booked</a>
            <?php endif; ?>
          </article>
        <?php endwhile; else: ?>
          <p>No resources found matching criteria.</p>
        <?php endif; ?>
      </div>
    </section>

    <!-- Manage Venues View (Admin Only) -->
    <section class="view" id="venues">
      <h2>Manage Resource Inventory (Admin)</h2>
      
      <div class="panel">
        <h3>+ Add New Resource / Venue</h3>
        <form action="index.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add_venue">
          <p><label>Name: <input type="text" name="name" required></label></p>
          <p>
            <label>Category / Type: 
              <select name="type">
                <option value="Chapel">Chapel</option>
                <option value="Viewing room">Viewing room</option>
              </select>
            </label>
          </p>
          <p><label>Capacity: <input type="number" name="capacity" min="1" required></label></p>
          <p><label>Base Price per Day (PHP): <input type="number" step="0.01" name="baseprice" required></label></p>
          <p>
            <label>Availability: 
              <select name="availability">
                <option value="Available">Available</option>
                <option value="Booked">Booked</option>
                <option value="Maintenance">Maintenance</option>
              </select>
            </label>
          </p>
          <p><label>Photo Upload: <input type="file" name="venue_image" accept="image/*" required></label></p>
          <button class="btn" type="submit">Save Resource</button>
        </form>
      </div>

      <div class="panel">
        <h3>Current Resource Inventory</h3>
        <table>
          <thead><tr><th>ID</th><th>Photo</th><th>Name</th><th>Type</th><th>Capacity</th><th>Price/Day</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php
            $vList = $conn->query("SELECT * FROM venue");
            if ($vList && $vList->num_rows > 0):
              while ($v = $vList->fetch_assoc()):
                $photoPath = !empty($v['imagePath']) ? $v['imagePath'] : "uploads/venues/default.jpg";
            ?>
              <tr>
                <td>R-<?php echo $v['venueID']; ?></td>
                <td><img src="<?php echo htmlspecialchars($photoPath); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" alt="Venue photo"></td>
                <td><?php echo htmlspecialchars($v['name']); ?></td>
                <td><?php echo htmlspecialchars($v['type']); ?></td>
                <td><?php echo $v['capacity']; ?></td>
                <td>Php <?php echo number_format($v['baseprice'], 2); ?></td>
                <td class="<?php echo ($v['availability'] === 'Available') ? 'ok' : 'bad'; ?>"><?php echo htmlspecialchars($v['availability']); ?></td>
                <td>
                  <details style="display:inline-block;">
                    <summary class="btn ghost" style="padding:2px 6px; font-size:0.8rem;">Edit</summary>
                    <form action="index.php" method="POST" enctype="multipart/form-data" style="margin-top:8px; padding:10px; border:1px solid #ccc; background:#fff;">
                      <input type="hidden" name="action" value="edit_venue">
                      <input type="hidden" name="venueID" value="<?php echo $v['venueID']; ?>">
                      <p><label>Name: <input type="text" name="name" value="<?php echo htmlspecialchars($v['name']); ?>" required></label></p>
                      <p>
                        <label>Category: 
                          <select name="type">
                            <option value="Chapel" <?php if($v['type']=='Chapel') echo 'selected'; ?>>Chapel</option>
                            <option value="Viewing room" <?php if($v['type']=='Viewing room') echo 'selected'; ?>>Viewing room</option>
                          </select>
                        </label>
                      </p>
                      <p><label>Capacity: <input type="number" name="capacity" value="<?php echo $v['capacity']; ?>" required></label></p>
                      <p><label>Price: <input type="number" step="0.01" name="baseprice" value="<?php echo $v['baseprice']; ?>" required></label></p>
                      <p>
                        <label>Status: 
                          <select name="availability">
                            <option value="Available" <?php if($v['availability']=='Available') echo 'selected'; ?>>Available</option>
                            <option value="Booked" <?php if($v['availability']=='Booked') echo 'selected'; ?>>Booked</option>
                            <option value="Maintenance" <?php if($v['availability']=='Maintenance') echo 'selected'; ?>>Maintenance</option>
                          </select>
                        </label>
                      </p>
                      <p><label>Replace Photo (Optional): <input type="file" name="venue_image" accept="image/*"></label></p>
                      <button class="btn" type="submit">Update</button>
                    </form>
                  </details>
                  <form action="index.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this venue?');">
                    <input type="hidden" name="action" value="delete_venue">
                    <input type="hidden" name="venueID" value="<?php echo $v['venueID']; ?>">
                    <button class="btn ghost" style="color:var(--bad); border-color:var(--bad); padding:2px 6px; font-size:0.8rem;" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="8">No resources recorded.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Clients View -->
    <section class="view" id="clients">
      <h2>Client Records Management</h2>

      <div class="panel">
        <h3>+ Add New Client Record</h3>
        <form action="index.php" method="POST">
          <input type="hidden" name="action" value="add_client">
          <p><label>First Name: <input type="text" name="firstName" required></label></p>
          <p><label>Last Name: <input type="text" name="lastName" required></label></p>
          <p><label>Contact Number: <input type="text" name="contactNum" required></label></p>
          <p><label>Email Address: <input type="email" name="emailAddr" required></label></p>
          <button class="btn" type="submit">Save Client</button>
        </form>
      </div>

      <div class="panel">
        <h3>Registered Clients</h3>
        <table>
          <thead><tr><th>Client ID</th><th>Full Name</th><th>Contact Number</th><th>Email Address</th><th>Actions</th></tr></thead>
          <tbody>
            <?php
            $cList = $conn->query("SELECT * FROM client");
            if ($cList && $cList->num_rows > 0):
              while ($c = $cList->fetch_assoc()):
            ?>
              <tr>
                <td>C-<?php echo $c['clientID']; ?></td>
                <td><?php echo htmlspecialchars($c['firstName'] . ' ' . $c['lastName']); ?></td>
                <td><?php echo htmlspecialchars($c['contactNum']); ?></td>
                <td><?php echo htmlspecialchars($c['emailAddr']); ?></td>
                <td>
                  <details style="display:inline-block;">
                    <summary class="btn ghost" style="padding:2px 6px; font-size:0.8rem;">Edit</summary>
                    <form action="index.php" method="POST" style="margin-top:8px; padding:10px; border:1px solid #ccc; background:#fff;">
                      <input type="hidden" name="action" value="edit_client">
                      <input type="hidden" name="clientID" value="<?php echo $c['clientID']; ?>">
                      <input type="text" name="firstName" value="<?php echo htmlspecialchars($c['firstName']); ?>" required><br><br>
                      <input type="text" name="lastName" value="<?php echo htmlspecialchars($c['lastName']); ?>" required><br><br>
                      <input type="text" name="contactNum" value="<?php echo htmlspecialchars($c['contactNum']); ?>" required><br><br>
                      <input type="email" name="emailAddr" value="<?php echo htmlspecialchars($c['emailAddr']); ?>" required><br><br>
                      <button class="btn" type="submit">Update</button>
                    </form>
                  </details>
                  <form action="index.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete client record?');">
                    <input type="hidden" name="action" value="delete_client">
                    <input type="hidden" name="clientID" value="<?php echo $c['clientID']; ?>">
                    <button class="btn ghost" style="color:var(--bad); border-color:var(--bad); padding:2px 6px; font-size:0.8rem;" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="5">No client records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Staff View -->
    <section class="view" id="staff">
      <h2>Staff Records & Personal Accounts</h2>

      <div class="panel">
        <h3>+ Add / Update Staff Account</h3>
        <form action="index.php" method="POST">
          <input type="hidden" name="action" value="add_staff">
          <p><label>First Name: <input type="text" name="firstName" required></label></p>
          <p><label>Last Name: <input type="text" name="lastName" required></label></p>
          <p>
            <label>System Role: 
              <select name="role">
                <option value="Staff">Staff</option>
                <option value="Administrator">Administrator</option>
              </select>
            </label>
          </p>
          <p><label>Email Address: <input type="email" name="emailAddr" required></label></p>
          <button class="btn" type="submit">Save Account</button>
        </form>
      </div>

      <div class="panel">
        <h3>Employee Directory</h3>
        <table>
          <thead><tr><th>Staff ID</th><th>Employee Name</th><th>Role</th><th>Email Address</th><th>Actions</th></tr></thead>
          <tbody>
            <?php
            $sList = $conn->query("SELECT * FROM employee");
            if ($sList && $sList->num_rows > 0):
              while ($s = $sList->fetch_assoc()):
            ?>
              <tr>
                <td>S-<?php echo $s['empID']; ?></td>
                <td><?php echo htmlspecialchars($s['firstName'] . ' ' . $s['lastName']); ?></td>
                <td><?php echo htmlspecialchars($s['role']); ?></td>
                <td><?php echo htmlspecialchars($s['emailAddr']); ?></td>
                <td>
                  <details style="display:inline-block;">
                    <summary class="btn ghost" style="padding:2px 6px; font-size:0.8rem;">Edit Account</summary>
                    <form action="index.php" method="POST" style="margin-top:8px; padding:10px; border:1px solid #ccc; background:#fff;">
                      <input type="hidden" name="action" value="edit_staff">
                      <input type="hidden" name="empID" value="<?php echo $s['empID']; ?>">
                      <input type="text" name="firstName" value="<?php echo htmlspecialchars($s['firstName']); ?>" required><br><br>
                      <input type="text" name="lastName" value="<?php echo htmlspecialchars($s['lastName']); ?>" required><br><br>
                      <select name="role">
                        <option value="Staff" <?php if($s['role']=='Staff') echo 'selected'; ?>>Staff</option>
                        <option value="Administrator" <?php if($s['role']=='Administrator') echo 'selected'; ?>>Administrator</option>
                      </select><br><br>
                      <input type="email" name="emailAddr" value="<?php echo htmlspecialchars($s['emailAddr']); ?>" required><br><br>
                      <button class="btn" type="submit">Update</button>
                    </form>
                  </details>
                  <form action="index.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete staff record?');">
                    <input type="hidden" name="action" value="delete_staff">
                    <input type="hidden" name="empID" value="<?php echo $s['empID']; ?>">
                    <button class="btn ghost" style="color:var(--bad); border-color:var(--bad); padding:2px 6px; font-size:0.8rem;" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="5">No staff records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Booking History View -->
    <section class="view" id="bookings">
      <h2>Booking History & Management</h2>

      <?php
        $searchBooking = trim($_GET['search_booking'] ?? '');
        $bookingFilter = $_GET['booking_filter'] ?? 'all';
        if (!in_array($bookingFilter, ['all', 'active', 'inactive'], true)) {
            $bookingFilter = 'all';
        }
      ?>

      <div class="panel">
        <form action="index.php" method="GET" style="margin-bottom: 15px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
          <input type="text" name="search_booking" id="bookingSearchInput" placeholder="Search client or deceased name..." value="<?php echo htmlspecialchars($searchBooking); ?>" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; flex: 1; max-width: 360px;">
          <div class="booking-filter" role="group" aria-label="Filter bookings by status">
            <?php foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $filterValue => $filterLabel): ?>
              <button type="submit" name="booking_filter" value="<?php echo $filterValue; ?>" class="booking-filter-btn <?php echo $bookingFilter === $filterValue ? 'selected' : ''; ?>" aria-pressed="<?php echo $bookingFilter === $filterValue ? 'true' : 'false'; ?>"><?php echo $filterLabel; ?></button>
            <?php endforeach; ?>
          </div>
          <button class="btn" type="submit" name="booking_filter" value="<?php echo htmlspecialchars($bookingFilter); ?>">Search</button>
          <?php if (!empty($searchBooking)): ?>
            <a href="index.php?booking_filter=<?php echo urlencode($bookingFilter); ?>#bookings" class="btn ghost" style="padding: 6px 12px; text-decoration: none;">Clear Search</a>
          <?php endif; ?>
        </form>

        <table>
          <thead><tr><th>ID</th><th>Client</th><th>Deceased Name</th><th>Resource</th><th>Recorded By</th><th>Dates</th><th>Total Price</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
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
                $bookingConditions[] = "b.bookingStatus = 'Confirmed'";
            } elseif ($bookingFilter === 'inactive') {
                $bookingConditions[] = "b.bookingStatus <> 'Confirmed'";
            }
            if (!empty($bookingConditions)) {
                $bSql .= ' WHERE ' . implode(' AND ', $bookingConditions);
            }

            $bSql .= " ORDER BY b.bookingID DESC";
            $bList = $conn->query($bSql);

            if ($bList && $bList->num_rows > 0):
              while ($b = $bList->fetch_assoc()):
                $empName = !empty($b['eFirst']) ? htmlspecialchars($b['eFirst'] . ' ' . $b['eLast']) : 'N/A';
                $deceased = !empty($b['deceasedName']) ? htmlspecialchars($b['deceasedName']) : 'N/A';
            ?>
              <tr>
                <td>B-<?php echo $b['bookingID']; ?></td>
                <td><?php echo htmlspecialchars($b['cFirst'] . ' ' . $b['cLast']); ?></td>
                <td><strong><?php echo $deceased; ?></strong></td>
                <td><?php echo htmlspecialchars($b['venueName']); ?></td>
                <td><?php echo $empName; ?></td>
                <td><?php echo $b['startDate'] . ' to ' . $b['endDate']; ?></td>
                <td>Php <?php echo number_format($b['totalPrice'], 2); ?></td>
                <td>
                  <form action="index.php" method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="update_booking_status">
                    <input type="hidden" name="bookingID" value="<?php echo $b['bookingID']; ?>">
                    <input type="hidden" name="bookingStatus" value="<?php echo $b['bookingStatus']; ?>">
                    <select name="paymentStatus" onchange="this.form.submit()" style="padding:2px;">
                      <option value="Paid" <?php if($b['paymentStatus']=='Paid') echo 'selected'; ?>>Paid</option>
                      <option value="Partial" <?php if($b['paymentStatus']=='Partial') echo 'selected'; ?>>Partial</option>
                      <option value="Pending" <?php if($b['paymentStatus']=='Pending') echo 'selected'; ?>>Pending</option>
                    </select>
                  </form>
                </td>
                <td class="<?php echo ($b['bookingStatus'] === 'Confirmed') ? 'ok' : 'bad'; ?>"><?php echo htmlspecialchars($b['bookingStatus']); ?></td>
                <td>
                  <?php if ($b['bookingStatus'] !== 'Cancelled'): ?>
                    <form action="index.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Cancel this booking?');">
                      <input type="hidden" name="action" value="update_booking_status">
                      <input type="hidden" name="bookingID" value="<?php echo $b['bookingID']; ?>">
                      <input type="hidden" name="paymentStatus" value="<?php echo $b['paymentStatus']; ?>">
                      <input type="hidden" name="bookingStatus" value="Cancelled">
                      <button class="btn ghost" style="color:var(--bad); padding:2px 6px; font-size:0.8rem;" type="submit">Cancel</button>
                    </form>
                  <?php endif; ?>
                  <form action="index.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete booking record permanently?');">
                    <input type="hidden" name="action" value="delete_booking">
                    <input type="hidden" name="bookingID" value="<?php echo $b['bookingID']; ?>">
                    <button class="btn ghost" style="padding:2px 6px; font-size:0.8rem;" type="submit">&times;</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="10">No booking records found matching search criteria.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Reports View -->
    <section class="view" id="reports">
      <?php
        $reportStartFrom = trim($_GET['report_start_from'] ?? '');
        $reportStartTo   = trim($_GET['report_start_to'] ?? '');
        $reportDateError = (!empty($reportStartFrom) && !empty($reportStartTo) && $reportStartFrom > $reportStartTo);
      ?>

      <!-- Print/PDF Specific Header Banner -->
      <div class="print-only-header">
        <h1>Goodbye Grove Resource Management</h1>
        <h2>Periodic Resource Booking Summary Report</h2>
        <p>
          <strong>Date Filter Period:</strong> 
          <?php echo (!empty($reportStartFrom) || !empty($reportStartTo)) ? htmlspecialchars(($reportStartFrom ?: 'Beginning') . ' to ' . ($reportStartTo ?: 'Present')) : 'All Time History'; ?>
          &nbsp;|&nbsp; 
          <strong>Generated On:</strong> <?php echo date('F j, Y, g:i a'); ?>
        </p>
      </div>

      <h2>Periodic Booking Reports</h2>

      <div class="panel" style="margin-bottom: 24px;">
        <form action="index.php" method="GET" class="report-filter-bar">
          <label>
            Start Date:
            <input type="date" id="reportStartDate" name="report_start_from" value="<?php echo htmlspecialchars($reportStartFrom); ?>" max="<?php echo htmlspecialchars($reportStartTo); ?>">
          </label>
          <label>
            End Date:
            <input type="date" id="reportEndDate" name="report_start_to" value="<?php echo htmlspecialchars($reportStartTo); ?>" min="<?php echo htmlspecialchars($reportStartFrom); ?>">
          </label>
          <?php if ($reportDateError): ?>
            <p style="color: #b42318; width: 100%; margin: 0;" role="alert">Start date cannot be after the end date.</p>
          <?php endif; ?>
          <button class="btn" type="submit">Filter Reports</button>
          <?php if (!empty($reportStartFrom) || !empty($reportStartTo)): ?>
            <a href="index.php#reports" class="btn ghost" style="text-decoration: none;">Reset Filter</a>
          <?php endif; ?>
          <button type="button" class="btn" onclick="window.print()" style="margin-left: auto;">Print / Export PDF</button>
        </form>
      </div>
      
      <div class="panel">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px;">
          <h3 style="margin: 0;">1. Booking Breakdown per Resource</h3>
          <span class="report-sub-hint" style="font-size: 0.85rem; color: #666;">Click any resource row to expand/collapse details</span>
        </div>
        <table>
          <thead>
            <tr>
              <th>Resource Name</th>
              <th>Total Bookings</th>
              <th>Total Days Booked</th>
              <th>Total Revenue</th>
            </tr>
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
              <!-- Primary Resource Row -->
              <tr class="resource-row" onclick="toggleResourceBreakdown(<?php echo $venueID; ?>)" id="main_row_<?php echo $venueID; ?>" title="Click to view/hide detailed bookings">
                <td>
                  <span class="report-expand-icon" id="icon_<?php echo $venueID; ?>">▶</span>
                  <strong style="color: var(--deep);"><?php echo htmlspecialchars($r['name']); ?></strong>
                </td>
                <td><?php echo $r['totalBookings']; ?> Bookings</td>
                <td><?php echo $r['totalDays']; ?> Days</td>
                <td><strong>Php <?php echo number_format($r['totalRev'], 2); ?></strong></td>
              </tr>

              <!-- Collapsible Sub-Table Row -->
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
                      <table class="sub-report-table">
                        <thead>
                          <tr>
                            <th>Booking ID</th>
                            <th>Client Name</th>
                            <th>Deceased Name</th>
                            <th>Booking Period</th>
                            <th>Payment Status</th>
                            <th>Status</th>
                            <th>Total Price</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($vb = $vBookings->fetch_assoc()): ?>
                            <tr>
                              <td>B-<?php echo $vb['bookingID']; ?></td>
                              <td><?php echo htmlspecialchars($vb['cFirst'] . ' ' . $vb['cLast']); ?></td>
                              <td><strong><?php echo !empty($vb['deceasedName']) ? htmlspecialchars($vb['deceasedName']) : 'N/A'; ?></strong></td>
                              <td><?php echo $vb['startDate'] . ' to ' . $vb['endDate']; ?></td>
                              <td><?php echo htmlspecialchars($vb['paymentStatus']); ?></td>
                              <td><span class="<?php echo ($vb['bookingStatus'] === 'Confirmed') ? 'ok' : 'bad'; ?>"><?php echo htmlspecialchars($vb['bookingStatus']); ?></span></td>
                              <td>Php <?php echo number_format($vb['totalPrice'], 2); ?></td>
                            </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    <?php else: ?>
                      <div class="empty-subtext">No individual booking records found for this resource in the selected date period.</div>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>

            <?php endwhile; endif; ?>
          </tbody>
          <tfoot>
            <tr style="background-color: #f1f5f1;">
              <td><strong>TOTALS</strong></td>
              <td><strong><?php echo $totB; ?> Bookings</strong></td>
              <td><strong><?php echo $totD; ?> Days</strong></td>
              <td><strong>Php <?php echo number_format($totR, 2); ?></strong></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="panel">
        <h3>2. Booking Breakdown per Client</h3>
        <table>
          <thead><tr><th>Client Name</th><th>Assigned Resource</th><th>Booking Dates</th><th>Payment Status</th><th>Total Amount</th></tr></thead>
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
                <td class="<?php echo ($cr['paymentStatus'] === 'Paid') ? 'ok' : 'warn'; ?>"><?php echo htmlspecialchars($cr['paymentStatus']); ?></td>
                <td>Php <?php echo number_format($cr['totalPrice'], 2); ?></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="5">No client booking history recorded for selected date range.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Third Report: Booking Count per Venue -->
      <div class="panel">
        <h3>3. Booking Count per Venue</h3>
        <p style="color: #666; margin-top: -4px;">
          Number of booking records for each venue within the selected reporting period.
        </p>
        <div class="venue-ranking">
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
          $maxVenueBookings = 0;
          if ($venueCountResult) {
              while ($venueCountRow = $venueCountResult->fetch_assoc()) {
                  $venueCounts[] = $venueCountRow;
                  $maxVenueBookings = max($maxVenueBookings, (int) $venueCountRow['bookingCount']);
              }
          }

          if (!empty($venueCounts)):
              foreach ($venueCounts as $venueCountRow):
                  $bookingCount = (int) $venueCountRow['bookingCount'];
                  $barWidth = $maxVenueBookings > 0 ? ($bookingCount / $maxVenueBookings) * 100 : 0;
          ?>
            <div class="venue-ranking-row">
              <div class="venue-ranking-name" title="<?php echo htmlspecialchars($venueCountRow['name']); ?>"><?php echo htmlspecialchars($venueCountRow['name']); ?></div>
              <div class="venue-ranking-count"><?php echo $bookingCount; ?></div>
              <div class="venue-bar-track" role="img" aria-label="<?php echo htmlspecialchars($venueCountRow['name']); ?>: <?php echo $bookingCount; ?> bookings">
                <div class="venue-bar-fill" style="width: <?php echo $barWidth; ?>%;"></div>
              </div>
            </div>
          <?php
              endforeach;
          else:
          ?>
            <p>No venues found in the database.</p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Create Reservation View -->
    <section class="view" id="reservation">
      <div class="form-head">
        <h2>Create Resource Reservation</h2>
        <a href="#book" aria-label="Close">&times;</a>
      </div>
      <form class="panel" action="index.php" method="POST" onsubmit="return handleBookingSubmit(event)">
        <input type="hidden" name="action" value="add_reservation">
        <input type="hidden" name="venueID" id="resVenueID" required>
        
        <fieldset>
          <legend>1. Selected Resource Asset</legend>
          <div style="font-size: 1.15rem; font-weight: bold; color: var(--emerald); padding: 4px 0;">
             <span id="resVenueName">No venue selected</span>
          </div>
        </fieldset>

        <fieldset>
          <legend>2. Name of Deceased</legend>
          <p><label>Name of Deceased: <input type="text" name="deceasedName" placeholder="e.g. Juan Dela Cruz" required></label></p>
        </fieldset>

        <fieldset>
          <legend>3. Select Client Record</legend>
          <select name="clientID" required>
            <option value="" disabled selected>-- Select Client --</option>
            <?php
            $clients = $conn->query("SELECT clientID, firstName, lastName FROM client");
            if ($clients && $clients->num_rows > 0):
              while ($c = $clients->fetch_assoc()):
            ?>
              <option value="<?php echo $c['clientID']; ?>"><?php echo htmlspecialchars($c['firstName'] . ' ' . $c['lastName']); ?> (ID #C-<?php echo $c['clientID']; ?>)</option>
            <?php endwhile; endif; ?>
          </select>
        </fieldset>

        <fieldset>
          <legend>4. Recording Staff / Employee</legend>
          <label>Recorded By:
            <select name="empID" required>
              <option value="" disabled selected>-- Select Staff / Employee --</option>
              <?php
              $employees = $conn->query("SELECT empID, firstName, lastName, role FROM employee");
              if ($employees && $employees->num_rows > 0):
                while ($e = $employees->fetch_assoc()):
              ?>
                <option value="<?php echo $e['empID']; ?>"><?php echo htmlspecialchars($e['firstName'] . ' ' . $e['lastName'] . ' (' . $e['role'] . ')'); ?></option>
              <?php endwhile; else: ?>
                <option value="" disabled>No staff members found. Please add a staff record first.</option>
              <?php endif; ?>
            </select>
          </label>
        </fieldset>

        <fieldset>
          <legend>5. Schedule Dates</legend>
          <label>Start Date <input type="date" name="startDate" id="resStartDate" min="<?php echo $today; ?>" oninput="validateBookingDates()" onchange="validateBookingDates()" required></label>
          <label>End Date <input type="date" name="endDate" id="resEndDate" min="<?php echo $today; ?>" oninput="validateBookingDates()" onchange="validateBookingDates()" required></label>
          
          <div id="dateOverlapError" style="display:none; color: #b30000; font-weight: bold; margin-top: 10px; padding: 10px; border: 1px solid #b30000; background-color: #fde8e8; border-radius: 4px; font-size: 0.9rem;"></div>
        </fieldset>

        <fieldset>
          <legend>6. Payment Information</legend>
          <label>Payment Status:
            <select name="paymentStatus">
              <option value="Paid">Paid</option>
              <option value="Partial">Partial / Downpayment</option>
              <option value="Pending">Pending</option>
            </select>
          </label>
        </fieldset>

        <div class="actions">
          <a class="btn ghost" href="#book">Cancel</a>
          <button class="btn" id="submitBookingBtn" type="submit">Save Reservation</button>
        </div>
      </form>
    </section>
  </main>
</div>

<script>
const todayStr = "<?php echo $today; ?>";
const activeBookings = <?php echo json_encode($activeBookings); ?>;

function openBookingForm(venueID, venueName) {
  document.getElementById('resVenueID').value = venueID;
  document.getElementById('resVenueName').textContent = venueName;
  
  const startInput = document.getElementById('resStartDate');
  const endInput = document.getElementById('resEndDate');
  
  startInput.min = todayStr;
  endInput.min = todayStr;
  
  validateBookingDates();
}

function toggleResourceBreakdown(venueID) {
  const detailRow = document.getElementById('breakdown_' + venueID);
  const mainRow = document.getElementById('main_row_' + venueID);
  const icon = document.getElementById('icon_' + venueID);
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

function filterBookingTable() {
  const input = document.getElementById('bookingSearchInput');
  if (!input) return;
  const filter = input.value.toLowerCase();
  const table = document.querySelector('#bookings table tbody');
  if (!table) return;
  const rows = table.getElementsByTagName('tr');

  for (let i = 0; i < rows.length; i++) {
    const clientCell = rows[i].getElementsByTagName('td')[1];   // Client column
    const deceasedCell = rows[i].getElementsByTagName('td')[2]; // Deceased Name column
    if (clientCell && deceasedCell) {
      const clientText = clientCell.textContent || clientCell.innerText;
      const deceasedText = deceasedCell.textContent || deceasedCell.innerText;
      if (clientText.toLowerCase().indexOf(filter) > -1 || deceasedText.toLowerCase().indexOf(filter) > -1) {
        rows[i].style.display = "";
      } else {
        rows[i].style.display = "none";
      }
    }
  }
}

function validateBookingDates() {
  const venueID = document.getElementById('resVenueID').value;
  const startInput = document.getElementById('resStartDate');
  const endInput = document.getElementById('resEndDate');
  const startDateVal = startInput.value;
  const endDateVal = endInput.value;
  const errorDiv = document.getElementById('dateOverlapError');
  const submitBtn = document.getElementById('submitBookingBtn');

  // Enforce minimum date constraints
  startInput.min = todayStr;
  if (startDateVal && startDateVal >= todayStr) {
    endInput.min = startDateVal;
  } else {
    endInput.min = todayStr;
  }

  let errorMsg = '';

  if (startDateVal && startDateVal < todayStr) {
    errorMsg = '⚠️ Start Date cannot be prior to current date (' + todayStr + ').';
  } else if (endDateVal && endDateVal < todayStr) {
    errorMsg = '⚠️ End Date cannot be prior to current date (' + todayStr + ').';
  } else if (startDateVal && endDateVal && endDateVal < startDateVal) {
    errorMsg = '⚠️ End Date cannot be prior to the Start Date (' + startDateVal + ').';
  } else if (venueID && startDateVal && endDateVal) {
    // Check overlap: (StartA <= EndB) and (EndA >= StartB)
    const hasOverlap = activeBookings.some(b => {
      if (parseInt(b.venueID) === parseInt(venueID)) {
        return (startDateVal <= b.endDate && endDateVal >= b.startDate);
      }
      return false;
    });
    if (hasOverlap) {
      errorMsg = '⚠️ This resource is already booked for the selected date range. Please choose different dates.';
    }
  }

  if (errorMsg !== '') {
    errorDiv.innerHTML = errorMsg;
    errorDiv.style.display = 'block';

    if (startDateVal && (startDateVal < todayStr || (endDateVal && endDateVal < startDateVal))) {
      startInput.style.border = '2px solid #b30000';
    } else {
      startInput.style.border = '';
    }

    if (endDateVal && (endDateVal < todayStr || endDateVal < startDateVal)) {
      endInput.style.border = '2px solid #b30000';
    } else {
      endInput.style.border = '';
    }

    if (submitBtn) submitBtn.disabled = true;
  } else {
    errorDiv.style.display = 'none';
    startInput.style.border = '';
    endInput.style.border = '';
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

function enforceAllMinDates() {
  const dateInputs = document.querySelectorAll('input[type="date"]');
  dateInputs.forEach(input => {
    // Report filters must allow historical dates; only booking/search dates use today's minimum.
    if (input.name === 'report_start_from' || input.name === 'report_start_to') return;

    input.setAttribute('min', todayStr);
    input.addEventListener('change', function() {
      if (this.value && this.value < todayStr && this.id !== 'resEndDate') {
        this.value = todayStr;
      }
    });
  });
}

function setupReportDateFilters() {
  const startInput = document.getElementById('reportStartDate');
  const endInput = document.getElementById('reportEndDate');
  if (!startInput || !endInput) return;

  function syncDateLimits() {
    endInput.min = startInput.value || '';
    startInput.max = endInput.value || '';

    // Keep invalid ranges from being submitted, including dates typed manually.
    if (startInput.value && endInput.value && startInput.value > endInput.value) {
      startInput.setCustomValidity('Start date cannot be after the end date.');
      endInput.setCustomValidity('End date cannot be before the start date.');
    } else {
      startInput.setCustomValidity('');
      endInput.setCustomValidity('');
    }
  }

  startInput.addEventListener('change', syncDateLimits);
  endInput.addEventListener('change', syncDateLimits);
  startInput.form.addEventListener('submit', function(event) {
    syncDateLimits();
    if (!startInput.checkValidity() || !endInput.checkValidity()) {
      event.preventDefault();
      startInput.reportValidity();
      endInput.reportValidity();
    }
  });
  syncDateLimits();
}

function switchRole(role) {
  localStorage.setItem('selectedRole', role);
  const isStaff = (role === 'Staff');
  
  document.querySelectorAll('.admin-nav').forEach(el => {
    el.classList.toggle('hidden-role', isStaff);
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

  document.querySelectorAll('nav a').forEach(a => {
    a.classList.toggle('active', a.hash === hash);
  });
}

window.addEventListener('DOMContentLoaded', () => {
  const savedRole = localStorage.getItem('selectedRole') || 'Admin';
  const selector = document.getElementById('roleSelector');
  if (selector) selector.value = savedRole;

  enforceAllMinDates();
  setupReportDateFilters();

  const urlParams = new URLSearchParams(window.location.search);
  if ((urlParams.has('search_type') || urlParams.has('search_date')) && !location.hash) {
    location.hash = '#book';
  }
  if (urlParams.has('search_booking') && !location.hash) {
    location.hash = '#bookings';
  }
  if ((urlParams.has('report_start_from') || urlParams.has('report_start_to')) && !location.hash) {
    location.hash = '#reports';
  }

  switchRole(savedRole);
});

window.addEventListener('hashchange', () => {
  showView();
});
</script>
</body>
</html>