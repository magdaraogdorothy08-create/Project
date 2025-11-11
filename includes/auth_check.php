<?php
// We remove session_start() from this file.
// The file that *includes* this is now responsible for starting the session.

if (!isset($_SESSION['user_id'])) {
    // This file is included by both pages and API files.
    // We can't just redirect, because it will break API calls.
    // Instead, we'll check if headers have been sent. If not, it's a page, so redirect.
    // If headers *have* been sent (by an API file setting Content-Type: json), we just stop.
    if (!headers_sent()) {
        // This is probably a page. Redirect.
        // NOTE: This assumes it's being included from a file one level deep (like /doctor/dashboard.php)
        header("Location: ../login.php"); 
    }
    // If headers were sent, just exit. The API file will fail.
    exit();
}
?>