<?php
session_start();
include('../includes/db_connect.php');

// Security Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'secretary') {
    header("Location: ../login.php");
    exit();
}

// --- DATA FETCHING FOR LISTS ---
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// 1. Today's Appointments (Updated to fetch Doctor Name)
$sql_today = "SELECT a.start_date, p.name as p_name, d.name as d_name, a.status 
              FROM appointments a 
              JOIN users p ON a.patient_id = p.id 
              JOIN users d ON a.doctor_id = d.id
              WHERE DATE(a.start_date) = '$today' ORDER BY a.start_date ASC";
$res_today = $conn->query($sql_today);

// 2. Tomorrow's Appointments (Updated to fetch Doctor Name)
$sql_tmrw = "SELECT a.start_date, p.name as p_name, d.name as d_name 
             FROM appointments a 
             JOIN users p ON a.patient_id = p.id 
             JOIN users d ON a.doctor_id = d.id
             WHERE DATE(a.start_date) = '$tomorrow' ORDER BY a.start_date ASC";
$res_tmrw = $conn->query($sql_tmrw);

// 3. Requested (Pending) Appointments (Updated to fetch Doctor Name)
$sql_pending = "SELECT a.proposed_date, p.name as p_name, d.name as d_name 
                FROM appointments a 
                JOIN users p ON a.patient_id = p.id 
                JOIN users d ON a.doctor_id = d.id
                WHERE a.reschedule_status = 'Pending' ORDER BY a.proposed_date ASC";
$res_pending = $conn->query($sql_pending);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secretary Dashboard – CliniCare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="../assets/css/style.css" rel="stylesheet">
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<style>
    /* Compact Lists for Panels */
    .list-group-item { padding: 0.5rem; font-size: 0.9rem; }
    .card-header { font-weight: bold; background-color: var(--lnu-blue); color: white; }
    .status-dot { height: 10px; width: 10px; background-color: #bbb; border-radius: 50%; display: inline-block; }
    .dot-green { background-color: #198754; }
    .dot-blue { background-color: #0d6efd; }
    
    /* Ensure Calendar container fills height but auto-adjusts */
    #calendar { } 
    
    .reschedule-alert {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 15px;
    }
</style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<header class="navbar navbar-dark sticky-top" style="background-color: var(--lnu-blue);">
<div class="container d-flex justify-content-between align-items-center h-100">
    
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#" style="height: 100%; display: flex; align-items: center;">
        <div style="height: 40px; width: 40px; display: flex; align-items: center; justify-content: center;">
            <img src="../assets/img/logo.png" alt="Logo" style="height: 150%; width: auto; max-height: 48px;">
        </div>
        <span>CliniCare – Secretary Panel</span>
    </a>
    
    <div class="d-flex align-items-center gap-3">
        <!-- Added Clock Here -->
        <span id="realtimeClock" class="text-white small fw-bold d-none d-md-block"></span>

        <!-- Notification Dropdown -->
        <div class="dropdown">
            <!-- Added data-bs-auto-close="outside" so clicking checkboxes doesn't close it -->
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

<div class="container-fluid mt-4 flex-grow-1">
    <div class="row h-100">
        
        <!-- LEFT: MASTER CALENDAR (2/3 width) -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-body p-0"> 
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <!-- RIGHT: STATUS PANELS (1/3 width) -->
        <div class="col-md-4 d-flex flex-column gap-3">
            
            <!-- Panel 1: Today's List -->
            <div class="card shadow-sm flex-fill">
                <div class="card-header bg-primary"><i class="bi bi-calendar-event"></i> Today's List</div>
                <ul class="list-group list-group-flush overflow-auto" style="max-height: 200px;">
                    <?php if($res_today->num_rows > 0): ?>
                        <?php while($row = $res_today->fetch_assoc()): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="fw-bold">
                                        <?php 
                                            $dotClass = ($row['status'] == 'Completed') ? 'dot-green' : 'dot-blue';
                                            echo "<span class='status-dot $dotClass me-1'></span>";
                                            echo date('g:i A', strtotime($row['start_date'])); 
                                        ?>
                                    </span>
                                </div>
                                <div><?php echo htmlspecialchars($row['p_name']); ?></div>
                                <small class="text-muted fst-italic">Dr. <?php echo htmlspecialchars($row['d_name']); ?></small>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted text-center">No appointments today.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Panel 2: Tomorrow's List -->
            <div class="card shadow-sm flex-fill">
                <div class="card-header bg-info text-dark"><i class="bi bi-calendar-plus"></i> Tomorrow's List</div>
                <ul class="list-group list-group-flush overflow-auto" style="max-height: 200px;">
                    <?php if($res_tmrw->num_rows > 0): ?>
                        <?php while($row = $res_tmrw->fetch_assoc()): ?>
                            <li class="list-group-item">
                                <span class="fw-bold"><?php echo date('g:i A', strtotime($row['start_date'])); ?></span><br>
                                <?php echo htmlspecialchars($row['p_name']); ?><br>
                                <small class="text-muted fst-italic">Dr. <?php echo htmlspecialchars($row['d_name']); ?></small>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted text-center">No appointments for tomorrow.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Panel 3: Requested/Pending List -->
            <div class="card shadow-sm flex-fill">
                <div class="card-header bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Requested / Pending</div>
                <ul class="list-group list-group-flush overflow-auto" style="max-height: 200px;">
                    <?php if($res_pending->num_rows > 0): ?>
                        <?php while($row = $res_pending->fetch_assoc()): ?>
                            <li class="list-group-item">
                                <div class="fw-bold text-danger">Reschedule Request</div>
                                <small>Prop: <?php echo date('M j, g:i A', strtotime($row['proposed_date'])); ?></small><br>
                                <?php echo htmlspecialchars($row['p_name']); ?><br>
                                <small class="text-muted fst-italic">Dr. <?php echo htmlspecialchars($row['d_name']); ?></small>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted text-center">No pending requests.</li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>
    </div>
</div>

<footer class="mt-auto py-3 text-center text-white" style="background-color: var(--lnu-blue);">
    &copy; <?php echo date("Y"); ?> Leyte Normal University – CliniCare
</footer>

<!-- Read-Only Event Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Appointment Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Patient:</strong> <span id="modalPatient"></span></p>
        <p><strong>Date/Time:</strong> <span id="modalTime"></span></p>
        <p><strong>Assigned Doctor:</strong> <span id="modalDoctor"></span></p>
        <p><strong>Status:</strong> <span id="modalStatus" class="badge"></span></p>
        
        <div id="pendingAlert" class="alert alert-warning mt-2" style="display:none;">
            <strong>Reschedule Requested:</strong><br>
            Proposed: <span id="modalProposed"></span><br>
            Reason: <span id="modalReason"></span>
        </div>
      </div>
      <div class="modal-footer">
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
// Clock Script
function updateClock() {
    var now = new Date();
    var options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    document.getElementById('realtimeClock').innerText = now.toLocaleString('en-US', options);
}
setInterval(updateClock, 1000);
updateClock();

var notifModal;

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    notifModal = new bootstrap.Modal(document.getElementById('notificationModal'));

    // Start Polling Notifications
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

    // --- CALENDAR SETUP ---
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        height: 'auto',
        fixedWeekCount: false,
        
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('../includes/fetch_appointments.php?v=' + new Date().getTime()) 
            .then(r => r.json())
            .then(data => {
                if(data.success) successCallback(data.events);
                else failureCallback(new Error(data.error));
            });
        },
        eventClick: function(info) {
            var props = info.event.extendedProps;
            document.getElementById('modalPatient').innerText = props.patient_name || info.event.title;
            document.getElementById('modalDoctor').innerText = "Dr. " + (props.doctor_name || "Unknown");
            document.getElementById('modalTime').innerText = info.event.start.toLocaleString();
            
            var badge = document.getElementById('modalStatus');
            badge.innerText = props.status || 'Scheduled';
            
            if(props.resched_status === 'Pending') {
                badge.className = 'badge bg-warning text-dark';
                badge.innerText = 'Pending Approval';
                document.getElementById('pendingAlert').style.display = 'block';
                document.getElementById('modalProposed').innerText = new Date(props.proposed_date).toLocaleString();
                document.getElementById('modalReason').innerText = props.reschedule_reason;
            } else if (props.status === 'Completed') {
                badge.className = 'badge bg-success';
                document.getElementById('pendingAlert').style.display = 'none';
            } else {
                badge.className = 'badge bg-primary';
                document.getElementById('pendingAlert').style.display = 'none';
            }
            
            viewModal.show();
        }
    });
    calendar.render();
});

// Helper for Bulk Delete Button
function updateBulkDeleteVisibility() {
    var count = document.querySelectorAll('.notif-checkbox:checked').length;
    var btn = document.getElementById('btnBulkDelete');
    if (count > 0) btn.style.display = 'block';
    else btn.style.display = 'none';
}

// --- NOTIFICATION LOGIC ---
function loadNotifications() {
    fetch('../includes/fetch_notifications.php?v=' + new Date().getTime())
    .then(r => r.json())
    .then(data => {
        const list = document.getElementById('notifList');
        const badge = document.getElementById('notifBadge');
        const selectAll = document.getElementById('selectAllNotifs');
        
        // Preserve Checked State
        const currentlyChecked = Array.from(document.querySelectorAll('.notif-checkbox:checked')).map(cb => cb.value);

        list.innerHTML = ''; 
        
        let unread = data.filter(n => n.is_read == 0).length;
        if (unread > 0) { badge.innerText = unread; badge.style.display = 'inline-block'; } 
        else { badge.style.display = 'none'; }
        
        // Manage Select All State
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
             
             // Restore "Select All"
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
    // Mark read
    var fd = new FormData(); fd.append('id', id); fetch('../includes/mark_notification_read.php', { method: 'POST', body: fd }).then(loadNotifications);

    // If linked to appointment, open calendar details
    if (appointmentId) {
        var event = calendar.getEventById(appointmentId);
        if (event) {
            // Wait for modal to hide if open
            viewModal.hide();
            // Trigger click logic manually
            var props = event.extendedProps;
            document.getElementById('modalPatient').innerText = props.patient_name || event.title;
            document.getElementById('modalDoctor').innerText = "Dr. " + (props.doctor_name || "Unknown");
            document.getElementById('modalTime').innerText = event.start.toLocaleString();
            
            var badge = document.getElementById('modalStatus');
            var alertBox = document.getElementById('pendingAlert');
            
            if(props.resched_status === 'Pending') {
                badge.className = 'badge bg-warning text-dark';
                badge.innerText = 'Pending Approval';
                alertBox.style.display = 'block';
                document.getElementById('modalProposed').innerText = new Date(props.proposed_date).toLocaleString();
                document.getElementById('modalReason').innerText = props.reschedule_reason;
            } else {
                alertBox.style.display = 'none';
                badge.innerText = props.status || 'Scheduled';
                if (props.status === 'Completed') badge.className = 'badge bg-success';
                else badge.className = 'badge bg-primary';
            }
            viewModal.show();
            return;
        }
    } 
    
    // Normal Notification
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