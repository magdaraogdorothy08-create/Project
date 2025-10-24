<?php
include('../includes/auth_check.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Dashboard – CliniCare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/fullcalendar/main.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<header class="navbar navbar-dark">
  <div class="container d-flex justify-content-between">
    <span class="navbar-brand fw-bold">CliniCare – Patient Dashboard</span>
    <a href="../logout.php" class="btn btn-outline-light">Logout</a>
  </div>
</header>

<div class="container mt-4">
  <h4 class="text-primary mb-3">Welcome, <?php echo $_SESSION['user_name']; ?></h4>
  <div class="card shadow-sm">
    <div class="card-header">Your Follow-Up Schedule</div>
    <div class="card-body">
      <div id="calendar"></div>
    </div>
  </div>
</div>

<footer>&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/fullcalendar/main.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek'
    },
    events: [
      { title: 'Follow-Up with Dr. Brown', start: '2025-10-10', backgroundColor: '#002366' },
      { title: 'Rescheduled Check-Up', start: '2025-10-15', backgroundColor: '#FFD700' }
    ],
    eventClick: function(info) {
      alert('Follow-Up Details:\n' + info.event.title + '\nDate: ' + info.event.startStr);
    }
  });
  calendar.render();
});
</script>
</body>
</html>

