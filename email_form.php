<?php
session_start();

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Store form data in session for repopulating
$_SESSION['form_data'] = $_POST;

// Validate required fields
$required = ['name', 'email', 'subject', 'message'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header('Location: email_form.php');
        exit;
    }
}

// Sanitize inputs
$name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
$message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format";
    header('Location: email_form.php');
    exit;
}

// Basic spam protection - honeypot could be added here
if (!empty($_POST['website'])) { // Hidden field that should be empty
    die('Spam detected');
}

// Email configuration
$to = 'palaciosdesignit@gmail.com'; // Replace with your email
$headers = "From: $name <$email>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";

// Email content
$email_content = "
    <html>
    <head>
        <title>$subject</title>
    </head>
    <body>
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Subject:</strong> $subject</p>
        <p><strong>Message:</strong></p>
        <p>".nl2br($message)."</p>
    </body>
    </html>
";

// Send email
$mailSent = mail($to, $subject, $email_content, $headers);

// Handle AJAX vs regular form submission
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // AJAX response
    header('Content-Type: application/json');
    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => 'Your message has been sent. Thank you!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
    }
    exit;
} else {
    // Regular form submission
    if ($mailSent) {
        $_SESSION['success'] = 'Your message has been sent. Thank you!';
        unset($_SESSION['form_data']); // Clear form data on success
    } else {
        $_SESSION['error'] = 'Failed to send email. Please try again later.';
    }
    header('Location: email_form.php');
    exit;
}