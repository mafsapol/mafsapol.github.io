<?php
// Set your target email address
$to_email = 'hiraya.pr@gmail.com'; // **CHANGE THIS to your actual email**
$subject = 'New Booking Request for Hiraya Private Resort';

// Initialize variables for email content
$full_name = $_POST['fullName'] ?? 'N/A';
$email_address = $_POST['email'] ?? 'N/A';
$phone_number = $_POST['phone'] ?? 'N/A';
$check_in_date = $_POST['checkInDate'] ?? 'N/A';
$check_out_date = $_POST['checkOutDate'] ?? 'N/A';
$guests = $_POST['guests'] ?? 'N/A';
$message = $_POST['message'] ?? 'No additional notes.';
$booking_type = $_POST['bookingType'] ?? 'General'; // From hidden input

// Prepare email body
$email_body = "Hello Hiraya Private Resort Team,\n\n";
$email_body .= "A new booking request has been submitted!\n\n";
$email_body .= "--- Booking Details ---\n";
$email_body .= "Booking Type: " . $booking_type . "\n";
$email_body .= "Full Name: " . $full_name . "\n";
$email_body .= "Email: " . $email_address . "\n";
$email_body .= "Phone: " . $phone_number . "\n";
$email_body .= "Check-in Date: " . $check_in_date . "\n";
$email_body .= "Check-out Date: " . $check_out_date . "\n";
$email_body .= "Number of Guests: " . $guests . "\n";
$email_body .= "Additional Notes: " . $message . "\n\n";
$email_body .= "-----------------------\n\n";
$email_body .= "Please check the attached proof of payment.\n";
$email_body .= "You can contact the customer at " . $email_address . " or " . $phone_number . ".\n";


// --- Handle File Upload (Proof of Payment) ---
$file_attached = false;
$attachment_path = '';

if (isset($_FILES['downpaymentProof']) && $_FILES['downpaymentProof']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/proofs/'; // **Ensure this directory exists and is writable by the web server**
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true); // Create directory if it doesn't exist
    }

    $file_tmp_name = $_FILES['downpaymentProof']['tmp_name'];
    $file_name = basename($_FILES['downpaymentProof']['name']); // Original file name
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $unique_file_name = uniqid('proof_') . '.' . $file_extension; // Generate unique name
    $attachment_path = $upload_dir . $unique_file_name;

    if (move_uploaded_file($file_tmp_name, $attachment_path)) {
        $file_attached = true;
        $email_body .= "\nProof of Payment: " . $_SERVER['HTTP_HOST'] . "/" . $attachment_path . "\n"; // Link to the uploaded file
    } else {
        error_log("Failed to move uploaded file: " . $file_tmp_name . " to " . $attachment_path);
        $email_body .= "\nWarning: Proof of payment upload failed. Please ask the customer to resend it.\n";
    }
} else {
    $email_body .= "\nNo proof of payment file was uploaded or an error occurred during upload.\n";
    if (isset($_FILES['downpaymentProof'])) {
        error_log("Upload error for downpaymentProof: " . $_FILES['downpaymentProof']['error']);
    }
}

// --- Prepare Headers for Email with Attachment (using a more robust method than simple mail()) ---
// For attachments, it's highly recommended to use a library like PHPMailer or implement MIME headers carefully.
// The basic `mail()` function can be tricky with attachments.
// Below is a simplified attempt for basic functionality, but PHPMailer is better for production.

// Boundary for multipart message
$semi_rand = md5(time());
$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

// Headers
$headers = "From: Hiraya Booking <hiraya.pr@gmail.com>\r\n"; // CHANGE THIS to an email address on your domain
$headers .= "Reply-To: " . $email_address . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed;\r\n";
$headers .= " boundary=\"{$mime_boundary}\"\r\n";

// Multipart body
$message_content = "This is a multi-part message in MIME format.\r\n\r\n";
$message_content .= "--{$mime_boundary}\r\n";
$message_content .= "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n";
$message_content .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message_content .= $email_body . "\r\n\r\n";

// Attach file
if ($file_attached && file_exists($attachment_path)) {
    $message_content .= "--{$mime_boundary}\r\n";
    $message_content .= "Content-Type: " . mime_content_type($attachment_path) . "; name=\"" . $unique_file_name . "\"\r\n";
    $message_content .= "Content-Transfer-Encoding: base64\r\n";
    $message_content .= "Content-Disposition: attachment; filename=\"" . $unique_file_name . "\"\r\n\r\n";
    $message_content .= chunk_split(base64_encode(file_get_contents($attachment_path))) . "\r\n\r\n";
}

$message_content .= "--{$mime_boundary}--\r\n";


// --- Send Email ---
if (mail($to_email, $subject, $message_content, $headers)) {
    // Success response to frontend
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Booking request submitted successfully!']);
} else {
    // Error response to frontend
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Failed to send booking request email.']);
    error_log("Failed to send email to " . $to_email . " for booking from " . $full_name);
}

// Optional: Clean up the uploaded file if you don't need it on the server after emailing
// unlink($attachment_path); 

?>