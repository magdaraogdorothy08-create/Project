<?php
session_start();
include('../includes/auth_check.php');
include('../includes/db_connect.php');

if ($_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard – CliniCare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<header class="navbar navbar-dark sticky-top">
  <div class="container d-flex justify-content-between align-items-center h-100">
    
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#" style="height: 100%; display: flex; align-items: center;">
        <div style="height: 40px; width: 40px; display: flex; align-items: center; justify-content: center;">
            <img src="../assets/pictures/logo.png" alt="Logo" style="height: 150%; width: auto; max-height: 48px;">
        </div>
        <span>CliniCare – Admin Panel</span>
    </a>
    
    <div class="d-flex align-items-center gap-3">
        <span id="realtimeClock" class="text-white small fw-bold d-none d-md-block"></span>
        <a href="../logout.php" class="btn btn-outline-light">Logout</a>
    </div>
  </div>
</header>

<div class="container mt-4 flex-grow-1">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="text-white" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">System Users</h4>
      <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
          + Add Doctor/Staff
      </button>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT id, name, email, phone, role, is_active FROM users ORDER BY role ASC, name ASC";
                $result = $conn->query($sql);
                
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                    
                    $badgeColor = 'bg-primary'; 
                    if ($row['role'] == 'admin') $badgeColor = 'bg-dark';
                    if ($row['role'] == 'patient') $badgeColor = 'bg-info text-dark'; 
                    if ($row['role'] == 'secretary') $badgeColor = 'bg-secondary'; 

                    echo "<td><span class='badge $badgeColor'>" . ucfirst($row['role']) . "</span></td>";
                    
                    if ($row['is_active'] == 1) {
                        echo "<td><span class='badge bg-success'>Active</span></td>";
                        if ($row['id'] == $_SESSION['user_id']) {
                             echo "<td><button class='btn btn-sm btn-secondary' disabled>Current User</button></td>";
                        } else {
                             echo "<td><button class='btn btn-sm btn-danger' onclick='toggleStatus(" . $row['id'] . ", 0)'>Deactivate</button></td>";
                        }
                    } else {
                        echo "<td><span class='badge bg-secondary'>Inactive</span></td>";
                        echo "<td><button class='btn btn-sm btn-success' onclick='toggleStatus(" . $row['id'] . ", 1)'>Activate</button></td>";
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
  </div>
</div>

<footer class="mt-auto">&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>

<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Staff</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="addUserForm">
          <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="09xxxxxxxxx" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);" required>
          </div>
          <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-select">
                <option value="doctor">Doctor</option>
                <option value="admin">Admin</option>
                <option value="secretary">Secretary</option> 
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="saveUserBtn">Create Account</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateClock() {
    var now = new Date();
    var options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    document.getElementById('realtimeClock').innerText = now.toLocaleString('en-US', options);
}
setInterval(updateClock, 1000);
updateClock();

document.getElementById('saveUserBtn').addEventListener('click', function() {
    var form = document.getElementById('addUserForm');
    var phoneInput = form.querySelector('input[name="phone"]').value;
    if (!phoneInput) { alert("Please enter a phone number."); return; }
    if (!/^09\d{9}$/.test(phoneInput)) { alert("Phone number must start with '09' and be exactly 11 digits long."); return; }

    var formData = new FormData(form);
    fetch('add_user.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) { alert('User created successfully!'); location.reload(); } 
        else { alert('Error: ' + data.message); }
    })
    .catch(error => alert('Error: ' + error));
});

function toggleStatus(userId, newStatus) {
    var action = newStatus == 1 ? "activate" : "deactivate";
    if (confirm("Are you sure you want to " + action + " this user?")) {
        var formData = new FormData();
        formData.append('user_id', userId);
        formData.append('status', newStatus);
        fetch('update_status.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) { location.reload(); } 
            else { alert('Error: ' + data.message); }
        })
        .catch(error => alert('Error: ' + error));
    }
}
</script>
</body>
</html>