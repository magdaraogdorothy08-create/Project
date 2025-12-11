<?php
session_start(); 
include('../includes/auth_check.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Dashboard – CliniCare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="../assets/css/style.css" rel="stylesheet">
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<style>
    .reschedule-alert {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 15px;
    }
</style>
</head>
<body class="d-flex flex-column min-vh-100">

<header class="navbar navbar-dark sticky-top">
<div class="container d-flex justify-content-between align-items-center h-100">
    
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#" style="height: 100%; display: flex; align-items: center;">
        <div style="height: 40px; width: 40px; display: flex; align-items: center; justify-content: center;">
            <img src="../assets/pictures/logo.png" alt="Logo" style="height: 150%; width: auto; max-height: 48px;">
        </div>
        <span>CliniCare – Doctor Panel</span>
    </a>

    <div class="d-flex align-items-center gap-3">
        <!-- Clock -->
        <span id="realtimeClock" class="text-white small fw-bold d-none d-md-block"></span>

        <!-- Notification Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-light position-relative border-0" type="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                <i class="bi bi-bell-fill fs-5"></i>
                <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notifDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                
                <!-- HEADER: Select All | Delete -->
                <li class="dropdown-header border-bottom d-flex justify-content-between align-items-center bg-light sticky-top">
                    <div class="d-flex align-items-center gap-2" onclick="event.stopPropagation()">
                        <input class="form-check-input m-0" type="checkbox" id="selectAllNotifs" title="Select All">
                        <label class="form-check-label small" for="selectAllNotifs">All</label>
                    </div>
                    
                    <div class="d-flex gap-1">
                        <!-- Bulk Delete -->
                        <button class="btn btn-sm btn-outline-danger py-0 border-0" id="btnBulkDelete" title="Delete Selected" style="display:none;">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                </li>
                <div id="notifList"><li><a class="dropdown-item text-muted text-center" href="#">Loading...</a></li></div>
            </ul>
        </div>
        <a href="../logout.php" class="btn btn-light btn-sm fw-bold text-primary">Logout</a>
    </div>
  </div>
</header>

<div class="container mt-4 flex-grow-1">
  <h4 class="text-white mb-3" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Welcome, Dr. <?php echo $_SESSION['user_name']; ?></h4>
  <div class="card shadow-sm">
    <div class="card-header">Follow-Up Appointment Calendar</div>
    <div class="card-body">
      <div id="calendar"></div>
    </div>
  </div>
</div>

<footer class="mt-auto">&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>

<!-- Add Appointment Modal -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add Follow-Up Appointment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="appointmentForm">
          <div class="mb-3"><label>Date</label><input type="date" class="form-control" id="appointmentDateInput" name="start_date" required></div>
          <div class="mb-3"><label>Time</label><input type="time" class="form-control" id="appointmentTimeInput" name="start_time" step="900" required></div>
          <div class="mb-3"><label>Patient</label><select class="form-select" id="patientSelect" name="patient_id" required><option value="">Loading...</option></select></div>
          <div class="mb-3"><label>Reason / Title</label><input type="text" class="form-control" id="appointmentTitle" name="title" required></div>
        </form>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-primary" id="saveAppointment">Save Appointment</button></div>
    </div>
  </div>
</div>

<!-- View/Manage Appointment Modal -->
<div class="modal fade" id="viewAppointmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Appointment Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="viewAppointmentId">
        <p><strong>Patient:</strong> <span id="viewPatientName"></span></p>
        <p><strong>Current Schedule:</strong> <span id="viewAppointmentTime"></span></p>
        <p><strong>Status:</strong> <span id="viewAppointmentStatus" class="badge"></span></p>

        <!-- Reschedule Request Section (Visible if Pending) -->
        <div id="rescheduleRequestSection" style="display:none;" class="reschedule-alert">
            <h6 class="text-warning fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Reschedule Requested</h6>
            <p class="mb-1">Patient wants to move to:</p>
            <p class="fs-5 fw-bold" id="viewProposedDate"></p>
            <p class="small text-muted">Reason: <span id="viewRescheduleReason"></span></p>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-success flex-fill" onclick="handleRescheduleResponse('approve')">Approve</button>
                <button class="btn btn-danger flex-fill" onclick="handleRescheduleResponse('deny')">Deny</button>
            </div>
        </div>

        <!-- Doctor Reschedule Actions -->
        <div class="mt-4 pt-3 border-top">
            <h6 class="fw-bold">Doctor Actions</h6>
            <button class="btn btn-outline-primary w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#docReschedCollapse">Change Date / Reschedule</button>
            <div class="collapse" id="docReschedCollapse">
                <div class="card card-body bg-light">
                    <label class="small">New Date:</label><input type="date" id="docNewDate" class="form-control mb-2">
                    <label class="small">New Time:</label><input type="time" id="docNewTime" class="form-control mb-2">
                    <button class="btn btn-primary w-100 btn-sm" onclick="handleRescheduleResponse('doctor_reschedule')">Update & Notify Patient</button>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="markCompletedBtn">Mark Completed</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteAppointment">Delete</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Notification Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><span id="notifDetailStatus" class="badge"></span><span id="notifDetailDate" class="text-muted small ms-2"></span></div>
        <p id="notifDetailMessage" class="fs-6"></p><input type="hidden" id="notifDetailId">
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-danger" id="btnDeleteNotif">Delete</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
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

// --- WEEKEND BLOCKING FUNCTION ---
// Returns TRUE if blocked, FALSE if allowed
function validateWeekday(input) {
    if(!input.value) return;
    const date = new Date(input.value);
    const day = date.getUTCDay(); // 0 is Sunday, 6 is Saturday
    if (day === 0 || day === 6) {
        alert("Weekend appointments are not allowed. Please select a weekday (Mon-Fri).");
        input.value = ""; // Clear input
    }
}

// Attach validator to inputs
document.getElementById('appointmentDateInput').addEventListener('input', function() { validateWeekday(this); });
document.getElementById('docNewDate').addEventListener('input', function() { validateWeekday(this); });

var notifModal, viewModal, calendar;

document.addEventListener('DOMContentLoaded', function() {
    notifModal = new bootstrap.Modal(document.getElementById('notificationModal'));
    viewModal = new bootstrap.Modal(document.getElementById('viewAppointmentModal'));
    var addModal = new bootstrap.Modal(document.getElementById('addAppointmentModal'));
    var addForm = document.getElementById('appointmentForm');
    var patientSelect = document.getElementById('patientSelect');
    var calendarEl = document.getElementById('calendar');

    loadNotifications(); 
    setInterval(loadNotifications, 5000); 

    // --- BULK DELETE HANDLER ---
    document.getElementById('btnBulkDelete').addEventListener('click', function(e) {
        e.stopPropagation();
        var checkboxes = document.querySelectorAll('.notif-checkbox:checked');
        var ids = [];
        checkboxes.forEach(function(cb) { ids.push(cb.value); });
        if (ids.length === 0) return;
        if (confirm("Delete " + ids.length + " notifications?")) {
            var formData = new FormData();
            formData.append('ids', JSON.stringify(ids));
            fetch('../includes/delete_multiple_notifications.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                    document.getElementById('selectAllNotifs').checked = false;
                } else {
                    alert("Error: " + data.message);
                }
            });
        }
    });

    document.getElementById('selectAllNotifs').addEventListener('change', function(e) {
        e.stopPropagation();
        var isChecked = e.target.checked;
        document.querySelectorAll('.notif-checkbox').forEach(function(checkbox) { checkbox.checked = isChecked; });
        updateBulkDeleteVisibility();
    });

    document.getElementById('notifList').addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('notif-checkbox')) {
            updateBulkDeleteVisibility();
            var all = document.querySelectorAll('.notif-checkbox');
            var checked = document.querySelectorAll('.notif-checkbox:checked');
            document.getElementById('selectAllNotifs').checked = (all.length > 0 && all.length === checked.length);
        }
    });

    document.getElementById('notifList').addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('notif-checkbox') || e.target.closest('.checkbox-wrapper'))) {
            e.stopPropagation();
        }
    });

    document.getElementById('btnDeleteNotif').addEventListener('click', function() {
        if(confirm("Delete this notification?")) {
            var id = document.getElementById('notifDetailId').value;
            var formData = new FormData();
            formData.append('id', id);
            fetch('../includes/delete_notification.php', { method: 'POST', body: formData })
            .then(() => { notifModal.hide(); loadNotifications(); });
        }
    });

    // --- CALENDAR & APPOINTMENT LOGIC ---
    fetch('fetch_patients.php').then(r => r.json()).then(data => {
        if(data.success) {
            patientSelect.innerHTML = '<option value="">-- Select a Patient --</option>';
            data.patients.forEach(p => patientSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`);
        }
    });

    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        // Grey out weekends visually (Sat/Sun are 0 and 6)
        businessHours: { daysOfWeek: [ 1, 2, 3, 4, 5 ] }, 
        
        events: function(fetchInfo, successCallback, failureCallback) {
        fetch('../includes/fetch_appointments.php?v=' + new Date().getTime())
            .then(r => r.json()).then(data => {
                if (data.success) successCallback(data.events);
                else failureCallback(new Error(data.error));
            });
        },
        dateClick: function(info) {
            // Block Weekend Clicks
            var day = info.date.getDay();
            if (day === 0 || day === 6) {
                alert("You cannot schedule appointments on weekends.");
                return;
            }

            addForm.reset();
            document.getElementById('appointmentDateInput').value = info.dateStr;
            document.getElementById('appointmentTimeInput').value = "08:00";
            addModal.show();
        },
        eventClick: function(info) {
            openAppointmentModal(info.event);
        }
    });

    document.getElementById('saveAppointment').addEventListener('click', function() {
        var formData = new FormData(addForm);
        fetch('create_appointment.php', { method: 'POST', body: formData }).then(r => r.json()).then(data => {
            if(data.success) { addModal.hide(); calendar.refetchEvents(); alert('Created!'); }
            else { alert(data.message); }
        });
    });

    document.getElementById('confirmDeleteAppointment').addEventListener('click', function() {
        if(confirm("Are you sure?")) {
            var formData = new FormData();
            formData.append('appointment_id', document.getElementById('viewAppointmentId').value);
            fetch('delete_appointment.php', { method: 'POST', body: formData }).then(r => r.json()).then(data => {
                if(data.success) { viewModal.hide(); calendar.refetchEvents(); alert('Deleted!'); }
            });
        }
    });
    
    document.getElementById('markCompletedBtn').addEventListener('click', function() {
        var formData = new FormData();
        formData.append('appointment_id', document.getElementById('viewAppointmentId').value);
        fetch('mark_completed.php', { method: 'POST', body: formData }).then(r => r.json()).then(data => {
            if(data.success) { viewModal.hide(); calendar.refetchEvents(); alert('Completed!'); }
        });
    });

    calendar.render();
});

function updateBulkDeleteVisibility() {
    var count = document.querySelectorAll('.notif-checkbox:checked').length;
    var btn = document.getElementById('btnBulkDelete');
    btn.style.display = (count > 0) ? 'block' : 'none';
}

function openAppointmentModal(event) {
    var props = event.extendedProps;
    document.getElementById('viewAppointmentId').value = event.id;
    document.getElementById('viewPatientName').innerText = props.patient_name || 'Patient';
    document.getElementById('viewAppointmentTime').innerText = event.start.toLocaleString();
    
    var statusBadge = document.getElementById('viewAppointmentStatus');
    var completeBtn = document.getElementById('markCompletedBtn');
    var requestSection = document.getElementById('rescheduleRequestSection');

    if (props.resched_status === 'Pending') {
        statusBadge.innerText = "Reschedule Requested";
        statusBadge.className = 'badge bg-warning text-dark';
        requestSection.style.display = 'block';
        document.getElementById('viewProposedDate').innerText = new Date(props.proposed_date).toLocaleString();
        document.getElementById('viewRescheduleReason').innerText = props.reschedule_reason;
        completeBtn.style.display = 'none';
    } else {
        requestSection.style.display = 'none';
        statusBadge.innerText = props.status || 'Pending';
        if(props.status === 'Completed') {
            statusBadge.className = 'badge bg-success';
            completeBtn.style.display = 'none';
        } else {
            statusBadge.className = 'badge bg-primary';
            completeBtn.style.display = 'inline-block';
        }
    }
    viewModal.show();
}

function handleRescheduleResponse(action) {
    var id = document.getElementById('viewAppointmentId').value;
    var formData = new FormData();
    formData.append('action', action);
    formData.append('appointment_id', id);

    if (action === 'doctor_reschedule') {
        var newDate = document.getElementById('docNewDate').value;
        var newTime = document.getElementById('docNewTime').value;
        if (!newDate || !newTime) { alert("Please select date and time"); return; }
        
        // Weekend check again here just in case
        var d = new Date(newDate);
        if(d.getUTCDay() === 0 || d.getUTCDay() === 6) {
             alert("Weekends are not allowed.");
             return;
        }

        formData.append('new_date', newDate);
        formData.append('new_time', newTime);
    }

    if (!confirm("Confirm this action?")) return;

    fetch('handle_reschedule_response.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Action Successful!');
            viewModal.hide();
            calendar.refetchEvents();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function handleNotificationClick(notifId, message, date, isRead, appointmentId) {
    markRead(notifId);
    if (appointmentId) {
        var event = calendar.getEventById(appointmentId);
        if (event) {
            openAppointmentModal(event);
        } else {
            alert("This appointment is in a different month. Please check the calendar for: " + date);
        }
    } else {
        document.getElementById('notifDetailId').value = notifId;
        document.getElementById('notifDetailMessage').innerText = message;
        document.getElementById('notifDetailDate').innerText = date;
        var s = document.getElementById('notifDetailStatus');
        s.className = 'badge bg-secondary'; s.innerText = 'READ';
        notifModal.show();
    }
}

function loadNotifications() {
    fetch('../includes/fetch_notifications.php?v=' + new Date().getTime())
    .then(r => r.json())
    .then(data => {
        const list = document.getElementById('notifList');
        const badge = document.getElementById('notifBadge');
        const selectAll = document.getElementById('selectAllNotifs');
        const currentlyChecked = Array.from(document.querySelectorAll('.notif-checkbox:checked')).map(cb => cb.value);

        list.innerHTML = ''; 
        
        let unread = data.filter(n => n.is_read == 0).length;
        if (unread > 0) { badge.innerText = unread; badge.style.display = 'inline-block'; } 
        else { badge.style.display = 'none'; }
        
        selectAll.disabled = (data.length === 0);
        if (data.length === 0) selectAll.checked = false;

        if(data.length > 0) {
             data.forEach(n => {
                 let bg = n.is_read == 0 ? 'bg-light fw-bold' : '';
                 let tag = n.is_read == 0 ? '<span class="badge bg-success float-end" style="font-size:0.6rem">NEW</span>' : '';
                 let isChecked = currentlyChecked.includes(String(n.id)) ? 'checked' : '';

                 const item = document.createElement('li');
                 item.innerHTML = `
                    <div class="dropdown-item border-bottom py-2 d-flex align-items-start gap-2 ${bg}">
                        <div class="pt-1 checkbox-wrapper" onclick="event.stopPropagation()">
                            <input type="checkbox" class="notif-checkbox form-check-input" value="${n.id}" ${isChecked}>
                        </div>
                        <div class="flex-grow-1" style="cursor:pointer;" onclick="handleNotificationClick(${n.id}, '${escapeHtml(n.message)}', '${n.created_at}', ${n.is_read}, ${n.appointment_id || 'null'})">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">${n.created_at}</small>
                                ${tag}
                            </div>
                            <div class="text-wrap small">${n.message}</div>
                        </div>
                    </div>`;
                 list.appendChild(item);
             });
             var visibleCheckboxes = document.querySelectorAll('.notif-checkbox');
             var checkedCheckboxes = document.querySelectorAll('.notif-checkbox:checked');
             if(visibleCheckboxes.length > 0 && visibleCheckboxes.length === checkedCheckboxes.length) {
                 selectAll.checked = true;
             }
        } else { 
            list.innerHTML = '<li><span class="dropdown-item text-muted text-center small">No notifications</span></li>'; 
        }
        updateBulkDeleteVisibility();
    });
}

function markRead(id) { var fd=new FormData(); fd.append('id', id); fetch('../includes/mark_notification_read.php', {method:'POST', body:fd}).then(loadNotifications); }
function escapeHtml(text) { var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }; return text.replace(/[&<>"']/g, function(m) { return map[m]; }); }
</script>
</body>
</html>