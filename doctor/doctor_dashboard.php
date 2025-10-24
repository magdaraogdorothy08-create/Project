<?php
include('../includes/auth_check.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Dashboard – CliniCare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/fullcalendar/main.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<header class="navbar navbar-dark">
  <div class="container d-flex justify-content-between">
    <span class="navbar-brand fw-bold">CliniCare – Doctor Dashboard</span>
    <a href="../logout.php" class="btn btn-outline-light">Logout</a>
  </div>
</header>

<div class="container mt-4">
  <h4 class="text-primary mb-3">Welcome, Dr. <?php echo $_SESSION['user_name']; ?></h4>
  <div class="card shadow-sm">
    <div class="card-header">Follow-Up Appointment Calendar</div>
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
      { title: 'John Doe – Follow-up', start: '2025-10-10', backgroundColor: '#002366' },
      { title: 'Mary Cruz – Check-up', start: '2025-10-12', backgroundColor: '#FFD700' }
    ],
    dateClick: function(info) {
      alert('Add follow-up on ' + info.dateStr);
    },
    eventClick: function(info) {
      alert('Edit/Update appointment: ' + info.event.title);
    }
  });
  calendar.render();
});
</script>
</body>
</html>

