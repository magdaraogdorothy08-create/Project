<?php
session_start();
include('../includes/auth_check.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Dashboard – CliniCare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="navbar navbar-dark">
  <div class="container d-flex justify-content-between">
    <span class="navbar-brand fw-bold">CliniCare – Patient Dashboard</span>
    <a href="../logout.php" class="btn btn-outline-light">Logout</a>
  </div>
</header>

<div class="container mt-4 flex-grow-1">
  <h4 class="text-white mb-3" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Welcome, <?php echo $_SESSION['user_name']; ?></h4>
  <div class="card shadow-sm">
    <div class="card-header">Your Follow-Up Schedule</div>
    <div class="card-body">
      <div id="calendar"></div>
    </div>
  </div>
</div>

<footer class="mt-auto">&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>

<div class="modal fade" id="viewAppointmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Appointment Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Appointment:</strong> <span id="viewAppointmentTitle"></span></p>
        <p><strong>Date & Time:</strong> <span id="viewAppointmentTime"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  var viewModal = new bootstrap.Modal(document.getElementById('viewAppointmentModal'));

  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'timeGridWeek',
    height: 'auto',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek'
    },
    eventTimeFormat: { 
      hour: 'numeric',
      minute: '2-digit',
      meridiem: 'short'
    },
    slotMinTime: '08:00:00',
    slotMaxTime: '17:01:00', 
    
    // --- THIS IS THE FIX ---
    slotDuration: '00:30:00',
    slotLabelInterval: '00:30:00',
    defaultTimedEventDuration: '00:30:00',
    // --- END OF FIX ---

    events: function(fetchInfo, successCallback, failureCallback) {
      fetch('../includes/fetch_appointments.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            successCallback(data.events);
          } else {
            failureCallback(new Error('Server error: ' + data.error));
          }
        })
        .catch(error => {
          failureCallback(new Error('Network error: ' + error.message));
        });
    },
    
    eventClick: function(info) {
      document.getElementById('viewAppointmentTitle').innerText = info.event.title;
      document.getElementById('viewAppointmentTime').innerText = info.event.start.toLocaleString();
      viewModal.show();
    }
  });
  calendar.render();
});
</script>
</body>
</html>