<?php
// Include PHPMailer at the top of the file before any other code
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost:4306", "root", "", "salon"); // Database connection

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all appointments from the database
$sql = "SELECT * FROM booking"; // Assuming "booking" is the table name
$result = $conn->query($sql);

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Handle Accept/Cancel/Reschedule actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    // Validate action
    if (!in_array($action, ['accept', 'cancel', 'reschedule'])) {
        echo "Invalid action!";
        exit;
    }

    // Initialize $stmt variable
    $stmt = null;
    $status = '';

    if ($action == 'accept') {
        $status = 'Accepted';
        $update_sql = "UPDATE booking SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $status, $id);
    } elseif ($action == 'cancel') {
        $status = 'Cancelled';
        $update_sql = "UPDATE booking SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $status, $id);
    } elseif ($action == 'reschedule') {
        // Handle reschedule with a new date and time input
        if (isset($_POST['new_date']) && isset($_POST['new_time'])) {
            $new_date = $_POST['new_date'];
            $new_time = $_POST['new_time'];

            $update_sql = "UPDATE booking SET date = ?, time = ? WHERE id = ?";  // Correct column names: 'date' and 'time'
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $new_date, $new_time, $id);
        }
    }

    // Execute the statement if prepared
    if ($stmt && $stmt->execute()) {
        // After the status is updated, fetch the booking details to send an email
        $booking = getBookingDetails($id);

        // Send the email to the customer based on the action (confirm, cancel, or reschedule)
        sendAppointmentEmail($booking['name'], $booking['email'], $booking['service'], $booking['date'], $booking['time'], $action);

        header("Location: admin.php"); // Redirect to refresh the page
        exit;
    } else {
        echo "Error executing statement: " . $conn->error;
    }
}

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
    require 'vendor/autoload.php'; // Ensure you have PHPMailer installed via Composer

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
        if ($action == 'accept') {
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <div class="admin-container">
        <h2>Welcome to Admin Dashboard</h2>

        <a href="admin.php?logout=true" class="logout-btn">Logout</a>

        <!-- Displaying Appointment Data -->
        <table class="appointments-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>" . $row['id'] . "</td>
                            <td>" . $row['name'] . "</td>
                            <td>" . $row['email'] . "</td>
                            <td>" . $row['phone'] . "</td>
                            <td>" . $row['date'] . "</td>
                            <td>" . $row['time'] . "</td>
                            <td>" . $row['service'] . "</td>
                            <td>" . $row['status'] . "</td>
                            <td>
                                <div class='action-btn-container'>
                                    <a href='admin.php?action=accept&id=" . $row['id'] . "' class='action-btn accept'>Accept</a>
                                    <a href='admin.php?action=cancel&id=" . $row['id'] . "' class='action-btn cancel'>Cancel</a>
                                    <a href='admin.php?action=reschedule&id=" . $row['id'] . "' class='action-btn reschedule'>Reschedule</a>
                                </div>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No appointments found</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Reschedule Form (visible only for 'reschedule' action) -->
        <?php
        if (isset($_GET['action']) && $_GET['action'] == 'reschedule') {
            echo "<h3>Reschedule Appointment</h3>
            <form action='admin.php?action=reschedule&id=" . $id . "' method='POST'>
                <label for='new_date'>New Appointment Date:</label>
                <input type='date' id='new_date' name='new_date' required>

                <label for='new_time'>New Appointment Time:</label>
                <input type='time' id='new_time' name='new_time' required>

                <button type='submit' class='btn'>Reschedule</button>
            </form>";
        }
        ?>
    </div>

</body>
</html>

<?php
$conn->close(); // Close the database connection
?>
