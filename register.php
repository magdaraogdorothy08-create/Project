<?php
session_start();
include('includes/db_connect.php');

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type']; 
    
    $role = 'patient'; 
    $student_id = null; 

    // VALIDATION START
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='alert alert-danger'>Invalid email format.</div>";
    }
    elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $message = "<div class='alert alert-danger'>Invalid phone number. Format: 09xxxxxxxxx</div>";
    }
    // UPDATED: Check for 8 characters minimum
    elseif (strlen($password) < 8) {
        $message = "<div class='alert alert-danger'>Password must be at least 8 characters long. We recommend using numbers and special characters (e.g., @, #, !) for better security.</div>";
    }
    else {
        // Logic: Check User Type (Student vs Faculty)
        $valid_id = true;
        
        if ($user_type == 'student') {
            $student_id = trim($_POST['student_id']);
            if (empty($student_id) || !preg_match('/^\d{1,7}$/', $student_id)) {
                $message = "<div class='alert alert-danger'>Student ID is required and must be up to 7 digits.</div>";
                $valid_id = false;
            }
        } 
        
        if ($valid_id) {
            // Check for Duplicate Email or Student ID
            $check_sql = "SELECT id FROM users WHERE email = ?";
            if ($student_id) {
                $check_sql .= " OR student_id = '$student_id'";
            }
            
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $message = "<div class='alert alert-danger'>Email or Student ID already exists!</div>";
            } else {
                // Insert New User
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (name, email, phone, student_id, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("ssssss", $name, $email, $phone, $student_id, $hashed_password, $role);

                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>Registration successful! <a href='login.php'>Login here</a>.</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Database Error: " . $stmt->error . "</div>";
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register – CliniCare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<header class="navbar navbar-dark sticky-top">
  <div class="container d-flex justify-content-between align-items-center h-100">
    
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#" style="height: 100%; display: flex; align-items: center;">
        <div style="height: 40px; width: 40px; display: flex; align-items: center; justify-content: center;">
            <img src="assets/pictures/logo.png" alt="Logo" style="height: 150%; width: auto; max-height: 48px;">
        </div>
        <span>CliniCare – Leyte Normal University</span>
    </a>

    <span id="realtimeClock" class="text-white small fw-bold"></span>
  </div>
</header>

<div class="container flex-grow-1 d-flex align-items-center" style="max-width: 500px;">
  <div class="card shadow-sm w-100">
    <div class="card-header text-center">Create Account</div>
    <div class="card-body">
      <?php echo $message; ?>
      <form method="POST" action="">
        
        <div class="mb-3">
          <label>Full Name:</label>
          <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
        </div>
        
        <div class="mb-3">
            <label class="d-block mb-1">I am a:</label>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="user_type" id="typeStudent" value="student" checked onchange="toggleIdField()">
              <label class="form-check-label" for="typeStudent">Student</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="user_type" id="typeFaculty" value="faculty" onchange="toggleIdField()">
              <label class="form-check-label" for="typeFaculty">Faculty / Staff</label>
            </div>
        </div>

        <div class="mb-3" id="studentIdGroup">
          <label>Student ID:</label>
          <input type="text" name="student_id" id="studentIdInput" class="form-control" placeholder="XXXXXXX" maxlength="7" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 7);">
          <div class="form-text text-muted">Required for retrieving hard-copy records.</div>
        </div>

        <div class="mb-3">
          <label>Email:</label>
          <input type="email" name="email" class="form-control" placeholder="user@domain.com" required>
        </div>
        <div class="mb-3">
          <label>Mobile Number:</label>
          <input type="text" name="phone" class="form-control" placeholder="09xxxxxxxxx" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);" required>
        </div>

        <div class="mb-3">
          <label>Password:</label>
          <input type="password" name="password" class="form-control" placeholder="********" required>
          <div class="form-text text-muted">Minimum of 6 letters, use of special characters (e.g., ! @ # $).</div>
        </div>
        
        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
      </form>
      <div class="text-center mt-3">
        <p>Already have an account? <a href="login.php">Login here</a></p>
      </div>
    </div>
  </div>
</div>

<footer class="mt-auto">&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>

<script>
function toggleIdField() {
    var isStudent = document.getElementById('typeStudent').checked;
    var idGroup = document.getElementById('studentIdGroup');
    var idInput = document.getElementById('studentIdInput');

    if (isStudent) {
        idGroup.style.display = 'block'; 
        idInput.required = true;         
    } else {
        idGroup.style.display = 'none';  
        idInput.required = false;        
        idInput.value = '';              
    }
}

toggleIdField();

function updateClock() {
    var now = new Date();
    var options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    document.getElementById('realtimeClock').innerText = now.toLocaleString('en-US', options);
}
setInterval(updateClock, 1000);
updateClock();
</script>
</body>
</html>