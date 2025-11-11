<?php
session_start(); 
include('../includes/auth_check.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Dashboard – CliniCare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
</head>
<body class="d-flex flex-column min-vh-100">
<header class="navbar navbar-dark">
  <div class="container d-flex justify-content-between">
    <span class="navbar-brand fw-bold">CliniCare – Doctor Dashboard</span>
    <a href="../logout.php" class="btn btn-outline-light">Logout</a>
  </div>
</header>

<div class="container mt-4 flex-grow-1">
  <h4 class="text-white mb-3" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Welcome, Dr. <?php echo $_SESSION['user_name']; ?></h4>
  <div class="card shadow-sm">
    <div class="card-header">Follow-Up Appointment Calendar (Click a date to add / Click event to view)</div>
    <div class="card-body">
      <div id="calendar"></div>
    </div>
  </div>
</div>

<footer class="mt-auto">&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>

<div class="modal fade" id="addAppointmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Follow-Up Appointment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="appointmentForm">
          <div class="mb-3">
            <label for="appointmentDateInput" class="form-label">Date</label>
            <input type="date" class="form-control" id="appointmentDateInput" name="start_date" required>
          </div>
          <div class="mb-3">
            <label for="appointmentTimeInput" class="form-label">Time</label>
            <input type="time" class="form-control" id="appointmentTimeInput" name="start_time" step="900" required>
          </div>
          <div class="mb-3">
            <label for="patientSelect" class="form-label">Patient</label>
            <select class="form-select" id="patientSelect" name="patient_id" required>
              <option value="">Loading patients...</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="appointmentTitle" class="form-label">Reason / Title</label>
            <input type="text" class="form-control" id="appointmentTitle" name="title" value="Follow-up Check-up" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveAppointment">Save Appointment</button>
      </div>
    </div>
  </div>
</div>

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
        <input type="hidden" id="viewAppointmentId">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteAppointment">Delete Appointment</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  var addModal = new bootstrap.Modal(document.getElementById('addAppointmentModal'));
  var addForm = document.getElementById('appointmentForm');
  var patientSelect = document.getElementById('patientSelect');
  var viewModal = new bootstrap.Modal(document.getElementById('viewAppointmentModal'));

  // Populate Patient Dropdown
  fetch('fetch_patients.php')
    .then(response => {
      if (!response.ok) {
         return response.text().then(text => {
            throw new Error('Network error: ' + response.statusText + ' - ' + text);
         });
      }
      return response.json(); 
    })
    .then(data => {
      if (data.success) {
        patientSelect.innerHTML = '<option value="">-- Select a Patient --</option>'; 
        if (data.patients.length === 0) {
          patientSelect.innerHTML += '<option value="" disabled>No patients found</option>';
        } else {
          data.patients.forEach(patient => {
            patientSelect.innerHTML += `<option value="${patient.id}">${patient.name}</option>`;
          });
        }
      } else {
        throw new Error('Server error: ' + data.error);
      }
    })
    .catch(error => {
      console.error('Error fetching patients:', error);
      patientSelect.innerHTML = '<option value="">-- Error Loading Patients --</option>';
      alert('Could not load patient list. \nError: ' + error.message);
    });

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
    // This tells events to stack instead of overlapping side-by-side
    eventOverlap: false,
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
    
    dateClick: function(info) {
      var clickDate = info.date;
      var dateStr = clickDate.getFullYear() + '-' + String(clickDate.getMonth() + 1).padStart(2, '0') + '-' + String(clickDate.getDate()).padStart(2, '0');
      var timeStr = String(clickDate.getHours()).padStart(2, '0') + ':' + String(clickDate.getMinutes()).padStart(2, '0');
      
      addForm.reset();
      document.getElementById('appointmentDateInput').value = dateStr;
      document.getElementById('appointmentTimeInput').value = timeStr;
      addModal.show();
    },
    
    eventClick: function(info) {
      document.getElementById('viewAppointmentTitle').innerText = info.event.title;
      document.getElementById('viewAppointmentTime').innerText = info.event.start.toLocaleString();
      document.getElementById('viewAppointmentId').value = info.event.id;
      viewModal.show();
    }
  });

  // Handle Save (Add) Button Click
  document.getElementById('saveAppointment').addEventListener('click', function() {
    var formData = new FormData(addForm);
    var startTime = formData.get('start_time');

    if (startTime < '08:00' || startTime > '17:00') {
        alert('Error: Appointments can only be set between 8:00 AM and 5:00 PM.');
        return; 
    }
    
    if (!formData.get('patient_id')) {
        alert('Please select a patient.');
        return;
    }

    fetch('create_appointment.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
        if (!response.ok) {
           return response.text().then(text => {
              throw new Error('Network error: ' + response.statusText + ' - ' + text);
           });
        }
        return response.json();
    })
    .then(data => {
      if (data.success) {
        addModal.hide();
        calendar.refetchEvents();
        alert('Appointment successfully created!'); 
      } else {
        throw new Error('Server error: ' + data.message);
      }
    })
    .catch(error => {
        console.error('Error saving appointment:', error);
        alert('Could not save appointment. \nError: ' + error.message);
    });
  });
  
  // Handle Delete Button Click
  document.getElementById('confirmDeleteAppointment').addEventListener('click', function() {
    var appointmentId = document.getElementById('viewAppointmentId').value;
    var formData = new FormData();
    formData.append('appointment_id', appointmentId);

    fetch('delete_appointment.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
        if (!response.ok) {
           return response.text().then(text => {
              throw new Error('Network error: ' + response.statusText + ' - ' + text);
           });
        }
        return response.json();
    })
    .then(data => {
      if (data.success) {
        viewModal.hide(); 
        calendar.refetchEvents(); 
        alert('Appointment deleted successfully.');
      } else {
        throw new Error('Server error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error deleting appointment:', error);
      alert('Could not delete appointment. \nError: ' + error.message);
    });
  });

  calendar.render();
});
</script>
</body>
</html>