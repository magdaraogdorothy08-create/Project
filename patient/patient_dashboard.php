<?php
session_start();
include('../includes/auth_check.php');
include('../includes/db_connect.php'); // Include DB to fetch current phone

// Get Student ID from session
$student_id_display = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : '';

// Fetch current user details for the Profile Modal
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email, phone, student_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Dashboard – CliniCare</title>
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
        <span>CliniCare – Patient Dashboard</span>
    </a>
    
    <div class="d-flex align-items-center gap-3">
        <span id="realtimeClock" class="text-white small fw-bold d-none d-md-block"></span>

        <div class="dropdown">
            <button class="btn btn-outline-light position-relative border-0" type="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                <i class="bi bi-bell-fill fs-5"></i>
                <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notifDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                <li class="dropdown-header border-bottom d-flex justify-content-between align-items-center bg-light sticky-top">
                    <div class="d-flex align-items-center gap-2" onclick="event.stopPropagation()">
                        <input class="form-check-input m-0" type="checkbox" id="selectAllNotifs" title="Select All">
                        <label class="form-check-label small" for="selectAllNotifs">All</label>
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-danger py-0 border-0" id="btnBulkDelete" title="Delete Selected" style="display:none;">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                </li>
                <div id="notifList">
                    <li><a class="dropdown-item text-muted text-center" href="#">Loading...</a></li>
                </div>
            </ul>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-light btn-sm fw-bold text-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Menu
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="bi bi-person-gear"></i> Edit Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>
    </div>
  </div>
</header>

<div class="container mt-4 flex-grow-1">
  <div class="mb-3 text-white">
      <h4 class="mb-0" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Welcome, <?php echo $_SESSION['user_name']; ?></h4>
      
      <?php if(!empty($student_id_display)): ?>
          <small class="d-block" style="font-size: 0.9rem; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">Student ID: <?php echo $student_id_display; ?></small>
      <?php else: ?>
          <small class="d-block" style="font-size: 0.9rem; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5); font-weight: bold; letter-spacing: 1px;">FACULTY / STAFF</small>
      <?php endif; ?>
      
  </div>

  <div class="card shadow-sm">
    <div class="card-header">Your Follow-Up Schedule</div>
    <div class="card-body">
      <div id="calendar"></div>
    </div>
  </div>
</div>

<footer class="mt-auto">&copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare</footer>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="profileForm">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($currentUser['name']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
            </div>
            
            <?php if(!empty($currentUser['student_id'])): ?>
                <div class="mb-3">
                    <label class="form-label">Student ID</label>
                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($currentUser['student_id']); ?>" readonly>
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control bg-light" value="Faculty / Staff" readonly>
                </div>
            <?php endif; ?>
            
            <hr>
            
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($currentUser['phone']); ?>" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);" required>
                <div class="form-text">Ensure this is active to receive SMS notifications.</div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">New Password <small class="text-muted">(Leave blank to keep current)</small></label>
                <input type="password" name="new_password" class="form-control" placeholder="New Password">
            </div>
            
            <div class="mb-3">
                <label class="form-label text-danger">Current Password <small>(Required to save changes)</small></label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveProfileBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- View Appointment Modal -->
<div class="modal fade" id="viewAppointmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Appointment Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Appointment:</strong> <span id="viewAppointmentTitle"></span></p>
        <p><strong>Date & Time:</strong> <span id="viewAppointmentTime"></span></p>
        <p><strong>Status:</strong> <span id="viewAppointmentStatus" class="badge"></span></p>
        
        <div id="patientReschedInfo" class="alert alert-warning mt-3" style="display:none;">
             <i class="bi bi-hourglass-split"></i> <strong>Waiting for Doctor Approval</strong><br>
             You requested to move this to: <br>
             <strong id="viewProposedDate" class="fs-5"></strong>
             <div class="mt-2">
                 <button class="btn btn-sm btn-outline-danger w-100" id="btnCancelRequest">Cancel Reschedule Request</button>
             </div>
        </div>
        <input type="hidden" id="viewAppointmentId">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-warning" id="btnOpenReschedule">Reschedule</button>
      </div>
    </div>
  </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reschedule Appointment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="rescheduleForm">
            <div class="mb-3"><label class="form-label">New Date:</label><input type="date" class="form-control" id="rescheduleDate" required></div>
            <div class="mb-3"><label class="form-label">New Time:</label><input type="time" class="form-control" id="rescheduleTime" required></div>
            <div class="mb-3"><label class="form-label">Reason:</label>
                <select class="form-select" id="rescheduleReason">
                    <option value="Unavailable">I am unavailable at this time</option>
                    <option value="Prior Appointment">I have a prior appointment/class</option>
                    <option value="Unforeseen Emergency">Unforeseen personal emergency</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnConfirmReschedule">Confirm Reschedule</button>
      </div>
    </div>
  </div>
</div>

<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Notification Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
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
// Clock Script
function updateClock() {
    var now = new Date();
    var options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    document.getElementById('realtimeClock').innerText = now.toLocaleString('en-US', options);
}
setInterval(updateClock, 1000);
updateClock();

// --- WEEKEND BLOCKING LOGIC ---
function validateWeekday(input) {
    if(!input.value) return;
    const date = new Date(input.value);
    const day = date.getUTCDay(); // 0 is Sunday, 6 is Saturday
    if (day === 0 || day === 6) {
        alert("Weekend appointments are not allowed. Please select a weekday (Mon-Fri).");
        input.value = ""; // Clear input
    }
}
document.getElementById('rescheduleDate').addEventListener('input', function() { validateWeekday(this); });


var notifModal;

document.addEventListener('DOMContentLoaded', function() {
    notifModal = new bootstrap.Modal(document.getElementById('notificationModal'));
    
    loadNotifications();
    setInterval(loadNotifications, 5000); 

    // --- SAVE PROFILE HANDLER ---
    document.getElementById('saveProfileBtn').addEventListener('click', function() {
        var form = document.getElementById('profileForm');
        var formData = new FormData(form);
        
        fetch('update_profile.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert("Profile updated successfully!");
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => alert("Request failed"));
    });

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

    // Select All Handler
    document.getElementById('selectAllNotifs').addEventListener('change', function(e) {
        e.stopPropagation();
        var isChecked = e.target.checked;
        document.querySelectorAll('.notif-checkbox').forEach(function(checkbox) {
            checkbox.checked = isChecked;
        });
        updateBulkDeleteVisibility();
    });

    // Individual Checkbox Change
    document.getElementById('notifList').addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('notif-checkbox')) {
            updateBulkDeleteVisibility();
            var all = document.querySelectorAll('.notif-checkbox');
            var checked = document.querySelectorAll('.notif-checkbox:checked');
            document.getElementById('selectAllNotifs').checked = (all.length > 0 && all.length === checked.length);
        }
    });

    // Prevent checkbox clicks from closing dropdown
    document.getElementById('notifList').addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('notif-checkbox') || e.target.closest('.checkbox-wrapper'))) {
            e.stopPropagation();
        }
    });

    // Single Notification Delete
    document.getElementById('btnDeleteNotif').addEventListener('click', function() {
        if(confirm("Delete this notification?")) {
            var id = document.getElementById('notifDetailId').value;
            var formData = new FormData();
            formData.append('id', id);
            fetch('../includes/delete_notification.php', { method: 'POST', body: formData })
            .then(() => { notifModal.hide(); loadNotifications(); });
        }
    });

    // Calendar Setup
    var calendarEl = document.getElementById('calendar');
    var viewModal = new bootstrap.Modal(document.getElementById('viewAppointmentModal'));
    var rescheduleModal = new bootstrap.Modal(document.getElementById('rescheduleModal'));

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        // --- ADDED: Grey out weekends visually ---
        businessHours: { daysOfWeek: [ 1, 2, 3, 4, 5 ] }, 
        
        events: function(fetchInfo, successCallback, failureCallback) {
        fetch('../includes/fetch_appointments.php?v=' + new Date().getTime())
            .then(r => r.json()).then(data => {
            if (data.success) successCallback(data.events);
            else failureCallback(new Error(data.error));
            });
        },
        eventClick: function(info) {
            var props = info.event.extendedProps;
            document.getElementById('viewAppointmentTitle').innerText = info.event.title;
            document.getElementById('viewAppointmentTime').innerText = info.event.start.toLocaleString();
            document.getElementById('viewAppointmentId').value = info.event.id;
            
            var statusBadge = document.getElementById('viewAppointmentStatus');
            var reschedInfo = document.getElementById('patientReschedInfo');
            var reschedBtn = document.getElementById('btnOpenReschedule');
            
            if (props.resched_status === 'Pending') {
                statusBadge.innerText = 'Waiting for Approval';
                statusBadge.className = 'badge bg-warning text-dark';
                reschedInfo.style.display = 'block';
                document.getElementById('viewProposedDate').innerText = new Date(props.proposed_date).toLocaleString();
                reschedBtn.disabled = true;
            } else {
                statusBadge.innerText = props.status || 'Pending';
                reschedInfo.style.display = 'none';
                reschedBtn.disabled = false;
                if(props.status === 'Completed') statusBadge.className = 'badge bg-success';
                else statusBadge.className = 'badge bg-primary';
            }
            viewModal.show();
        }
    });

    document.getElementById('btnCancelRequest').addEventListener('click', function() {
        if(confirm("Cancel reschedule request?")) {
            var fd = new FormData(); fd.append('appointment_id', document.getElementById('viewAppointmentId').value);
            fetch('cancel_reschedule.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                if(d.success) { viewModal.hide(); calendar.refetchEvents(); alert('Cancelled.'); }
                else alert(d.message);
            });
        }
    });

    document.getElementById('btnOpenReschedule').addEventListener('click', function() { 
        viewModal.hide(); 
        var today = new Date().toISOString().split('T')[0];
        document.getElementById('rescheduleDate').setAttribute('min', today);
        document.getElementById('rescheduleDate').value = '';
        document.getElementById('rescheduleTime').value = '';
        rescheduleModal.show(); 
    });

    document.getElementById('btnConfirmReschedule').addEventListener('click', function() {
        var id = document.getElementById('viewAppointmentId').value;
        var d = document.getElementById('rescheduleDate').value;
        var t = document.getElementById('rescheduleTime').value;
        var r = document.getElementById('rescheduleReason').value;
        
        if (!d || !t) { alert("Select date and time."); return; }
        
        // Double check weekend via JS before sending (in case they typed it in)
        var checkDate = new Date(d);
        if(checkDate.getUTCDay() === 6 || checkDate.getUTCDay() === 0) {
            alert("Weekends are not allowed.");
            return;
        }

        if (t < "08:00" || t > "17:00") { alert("Hours: 8 AM - 5 PM"); return; }
        var fd = new FormData(); fd.append('appointment_id', id); fd.append('new_start_date', d + ' ' + t); fd.append('reason', r);
        fetch('reschedule_appointment.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
            if (d.success) { alert('Request Sent!'); rescheduleModal.hide(); calendar.refetchEvents(); }
            else { alert('Error: ' + d.message); }
        });
    });

    calendar.render();
});

function updateBulkDeleteVisibility() {
    var count = document.querySelectorAll('.notif-checkbox:checked').length;
    var btn = document.getElementById('btnBulkDelete');
    if (count > 0) btn.style.display = 'block';
    else btn.style.display = 'none';
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
                        <div class="flex-grow-1" style="cursor:pointer;" onclick="viewNotification(${n.id}, '${escapeHtml(n.message)}', '${n.created_at}', ${n.is_read}, ${n.appointment_id || 'null'})">
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

function viewNotification(id, message, date, isRead, appointmentId) {
    var fd = new FormData(); fd.append('id', id); fetch('../includes/mark_notification_read.php', { method: 'POST', body: fd }).then(loadNotifications);
    
    if (appointmentId) {
        var event = calendar.getEventById(appointmentId);
        if (event) {
            viewModal.hide();
            var props = event.extendedProps;
            document.getElementById('viewAppointmentTitle').innerText = event.title;
            document.getElementById('viewAppointmentTime').innerText = event.start.toLocaleString();
            document.getElementById('viewAppointmentId').value = event.id;
            
            var statusBadge = document.getElementById('viewAppointmentStatus');
            var reschedInfo = document.getElementById('patientReschedInfo');
            var reschedBtn = document.getElementById('btnOpenReschedule');
            
            if (props.resched_status === 'Pending') {
                statusBadge.innerText = 'Waiting for Approval';
                statusBadge.className = 'badge bg-warning text-dark';
                reschedInfo.style.display = 'block';
                document.getElementById('viewProposedDate').innerText = new Date(props.proposed_date).toLocaleString();
                reschedBtn.disabled = true;
            } else {
                statusBadge.innerText = props.status || 'Pending';
                reschedInfo.style.display = 'none';
                reschedBtn.disabled = false;
                if(props.status === 'Completed') statusBadge.className = 'badge bg-success';
                else statusBadge.className = 'badge bg-primary';
            }
            viewModal.show();
            return;
        }
    }
    
    document.getElementById('notifDetailId').value = id;
    document.getElementById('notifDetailMessage').innerText = message;
    document.getElementById('notifDetailDate').innerText = date;
    const s = document.getElementById('notifDetailStatus');
    s.className = 'badge bg-secondary'; s.innerText = 'READ';
    notifModal.show();
}

function escapeHtml(text) { var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }; return text.replace(/[&<>"']/g, function(m) { return map[m]; }); }
</script>
</body>
</html>