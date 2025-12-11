<?php
// Enable error reporting to see if file paths are wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Include the helper file where your settings (IP, User, Pass) are saved
if (file_exists('includes/notification_helper.php')) {
    include('includes/notification_helper.php');
} else {
    die("Error: Could not find 'includes/notification_helper.php'. Check your file path.");
}

echo "<h3>SMS Connection Test</h3>";

// 2. Define the recipient and message
// REPLACE THIS with your own phone number for testing
$test_number = "09685489179"; 
$test_message = "This is a test message from CliniCare via Android Gateway.";

echo "Target Number: $test_number<br>";
echo "Message: $test_message<br><br>";

echo "Attempting to send...<br>";

// 3. Call the function
// This function uses the IP/Password from notification_helper.php automatically
if (function_exists('sendSMSViaAndroid')) {
    sendSMSViaAndroid($test_number, $test_message);
    echo "<strong style='color:green'>Command sent!</strong><br>";
    echo "Check your phone to see if the SMS was sent.<br>";
    echo "If it failed, check the file <code>includes/sms_debug.log</code>.";
} else {
    echo "<strong style='color:red'>Error: Function sendSMSViaAndroid not found.</strong>";
}
?>