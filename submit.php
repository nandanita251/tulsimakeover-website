<?php
// Include PHPMailer for sending emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php'; // Ensure you have PHPMailer installed via Composer

// Database credentials
$host = 'localhost:4306'; // Use default port 3306 if not required to specify
$dbname = 'salon';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handling the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $service = $_POST['service'];
    $status = 'pending';  // Set the initial status of the appointment

    // Insert the appointment into the database
    $sql = "INSERT INTO booking (name, email, phone, date, time, service, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $name, $email, $phone, $date, $time, $service, $status);

    if ($stmt->execute()) {
        // After insertion, send confirmation email to the user
        sendAppointmentEmail($name, $email, $service, $date, $time, 'confirm');
        echo "Your appointment has been successfully booked!";  // Success message
    } else {
        echo "Error booking the appointment.";
    }
}

// Admin action for accepting, canceling, or rescheduling appointments
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    // Validate action
    if (!in_array($action, ['accept', 'cancel', 'reschedule'])) {
        echo "Invalid action!";
        exit;
    }

    // Prepare the update SQL query based on the action
    if ($action == 'accept') {
        $status = 'confirmed';
        $update_sql = "UPDATE booking SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            $booking = getBookingDetails($id);
            sendAppointmentEmail($booking['name'], $booking['email'], $booking['service'], $booking['date'], $booking['time'], 'confirm');
            echo "Appointment confirmed! Email sent to the customer.";
        } else {
            echo "Error updating appointment status.";
        }
    } elseif ($action == 'cancel') {
        $status = 'canceled';
        $update_sql = "UPDATE booking SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            $booking = getBookingDetails($id);
            sendAppointmentEmail($booking['name'], $booking['email'], $booking['service'], $booking['date'], $booking['time'], 'cancel');
            echo "Appointment canceled! Email sent to the customer.";
        } else {
            echo "Error updating appointment status.";
        }
    } elseif ($action == 'reschedule') {
        // Display reschedule form if not submitted yet
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_date']) && isset($_POST['new_time'])) {
            $new_date = $_POST['new_date'];
            $new_time = $_POST['new_time'];

            // Update date and time for the reschedule action
            $update_sql = "UPDATE booking SET date = ?, time = ?, status = 'rescheduled' WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $new_date, $new_time, $id);

            if ($stmt->execute()) {
                $booking = getBookingDetails($id);
                sendAppointmentEmail($booking['name'], $booking['email'], $booking['service'], $new_date, $new_time, 'reschedule');
                echo "Appointment rescheduled! Email sent to the customer.";
            } else {
                echo "Error updating rescheduled appointment.";
            }
        } else {
            // Show the reschedule form
            echo '
                <form method="POST">
                    <label for="new_date">New Date:</label>
                    <input type="date" name="new_date" id="new_date" required><br>
                    <label for="new_time">New Time:</label>
                    <input type="time" name="new_time" id="new_time" required><br>
                    <button type="submit">Reschedule Appointment</button>
                </form>
            ';
        }
    }
}

$conn->close();

/**
 * Function to fetch booking details from the database
 */
function getBookingDetails($id) {
    global $conn;
    $sql = "SELECT * FROM booking WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Function to send email based on admin action (accept, cancel, reschedule)
 */
function sendAppointmentEmail($name, $email, $service, $date, $time, $action) {
    // Load PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nandanita25@gmail.com'; // Your email address
        $mail->Password = 'mzxe hvmh wgqh cicm'; // App password (replace with your app password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('nandanita25@gmail.com', 'Tulsi Makeover');
        $mail->addAddress($email, $name); // Customer's email

        // Email subject and body
        if ($action == 'confirm') {
            $mail->Subject = 'Appointment Confirmation';
            $mail->Body = "
                <p>Dear {$name},</p>
                <p>Your appointment for <strong>{$service}</strong> has been confirmed. Here are the details:</p>
                <ul>
                    <li><strong>Date:</strong> {$date}</li>
                    <li><strong>Time:</strong> {$time}</li>
                </ul>
                <p>We look forward to seeing you!</p>
                <p>Best regards,<br>Tulsi Makeover</p>";
        } elseif ($action == 'cancel') {
            $mail->Subject = 'Appointment Canceled';
            $mail->Body = "
                <p>Dear {$name},</p>
                <p>Your appointment for <strong>{$service}</strong> has been canceled. We're sorry for the inconvenience.</p>
                <p>If you'd like to reschedule, please visit our website.</p>
                <p>Best regards,<br>Tulsi Makeover</p>";
        } elseif ($action == 'reschedule') {
            $mail->Subject = 'Appointment Rescheduled';
            $mail->Body = "
                <p>Dear {$name},</p>
                <p>Your appointment for <strong>{$service}</strong> has been rescheduled. Here are the new details:</p>
                <ul>
                    <li><strong>New Date:</strong> {$date}</li>
                    <li><strong>New Time:</strong> {$time}</li>
                </ul>
                <p>We look forward to seeing you!</p>
                <p>Best regards,<br>Tulsi Makeover</p>";
        }

        // Send the email
        $mail->isHTML(true);
        $mail->send();
    } catch (Exception $e) {
        // Log error if email cannot be sent
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
?>
