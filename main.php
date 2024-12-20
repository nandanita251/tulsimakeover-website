<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars($_POST['message']);
    
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }
    
    // Admin email
    $admin_email = "nandanita25@gmail.com"; // Replace with actual admin email
    
    // Email subject and body
    $subject = "New Contact Form Submission";
    $body = "Name: $name\nEmail: $email\nMessage: $message";
    
    // Send email to admin
    if (mail($admin_email, $subject, $body)) {
        echo json_encode(['success' => true, 'message' => 'Thank you for contacting us!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error sending email. Please try again.']);
    }
}
?>
