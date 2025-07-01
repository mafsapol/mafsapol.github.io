<?php
// IMPORTANT: Replace this with YOUR actual email address where you want to receive bookings
$your_email = 'mafsapol2@gmail.com'; // E.g., hiraya.pr@gmail.com
$subject = 'New Booking Request from Hiraya Private Resort Website';
$from_email = 'noreply@yourdomain.com'; // Change to an email address on your website's domain, if you have one.
                                        // Otherwise, use a valid email or your host's recommended 'From' address.

// --- 1. Collect Form Data ---
$full_name = $_POST['fullName'] ?? 'N/A';
$email_address = $_POST['email'] ?? 'N/A';
$phone_number = $_POST['phone'] ?? 'N/A';
$check_in_date = $_POST['checkInDate'] ?? 'N/A';
$check_out_date = $_POST['checkOutDate'] ?? 'N/A';
$guests = $_POST['guests'] ?? 'N/A';
$message = $_POST['message'] ?? 'No additional notes.';
$booking_type = $_POST['bookingType'] ?? 'General'; // From hidden input

// --- 2. Handle File Upload (Proof of Payment) ---
$upload_successful = false;
$attachment_details = '';
$uploaded_file_name_on_server = ''; // To store the actual file name on the server

if (isset($_FILES['downpaymentProof']) && $_FILES['downpaymentProof']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/proofs/'; // Folder to save uploads. MAKE SURE THIS FOLDER EXISTS AND IS WRITABLE!
                                     // Create this folder manually via FTP/cPanel or your host's file manager.
                                     // Set permissions: 755 (or 777 if 755 doesn't work, but 755 is more secure)

    // Check if the directory exists, if not, try to create it
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true); // Creates the directory recursively
    }

    $file_tmp_name = $_FILES['downpaymentProof']['tmp_name'];
    $original_file_name = basename($_FILES['downpaymentProof']['name']);
    $file_extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
    $unique_file_name = uniqid('proof_') . '.' . $file_extension; // Generate a unique name to prevent overwrites
    $destination_path = $upload_dir . $unique_file_name;

    if (move_uploaded_file($file_tmp_name, $destination_path)) {
        $upload_successful = true;
        $uploaded_file_name_on_server = $unique_file_name;
        // Provide a link to the uploaded file in the email (if you access files via HTTP)
        $attachment_details = "\nProof of Payment Link: http://" . $_SERVER['HTTP_HOST'] . "/" . $upload_dir . $unique_file_name . "\n";
    } else {
        error_log("Failed to move uploaded file for booking. Temp: " . $file_tmp_name . ", Dest: " . $destination_path);
        $attachment_details = "\nNOTE: Proof of payment upload failed on the server.\n";
    }
} else {
    $attachment_details = "\nNo proof of payment file was uploaded, or an upload error occurred.\n";
    if (isset($_FILES['downpaymentProof'])) {
        error_log("Upload error code for downpaymentProof: " . $_FILES['downpaymentProof']['error']);
    }
}


// --- 3. Construct the Email Body ---
$email_body = "You have a new booking request for Hiraya Private Resort!\n\n";
$email_body .= "--------------------------------------\n";
$email_body .= "Booking Type: " . $booking_type . "\n";
$email_body .= "Full Name: " . $full_name . "\n";
$email_body .= "Email: " . $email_address . "\n";
$email_body .= "Phone: " . $phone_number . "\n";
$email_body .= "Check-in Date: " . $check_in_date . "\n";
$email_body .= "Check-out Date: " . $check_out_date . "\n";
$email_body .= "Number of Guests: " . $guests . "\n";
$email_body .= "Additional Notes: \n" . $message . "\n";
$email_body .= "--------------------------------------\n\n";
$email_body .= $attachment_details; // Include the details about the uploaded file
$email_body .= "\n\nPlease verify the payment and contact the customer to confirm the booking.";


// --- 4. Prepare Email Headers (for simple text email) ---
// For sending attachments reliably, using PHPMailer is highly recommended.
// This example sends just a link to the uploaded file.
$headers = "From: " . $from_email . "\r\n";
$headers .= "Reply-To: " . $email_address . "\r\n"; // So you can reply directly to the customer
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/plain; charset=iso-8859-1\r\n"; // Plain text email

// --- 5. Send the Email ---
if (mail($your_email, $subject, $email_body, $headers)) {
    // Success: Send JSON response to frontend
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Booking request submitted successfully!']);
} else {
    // Failure: Send JSON error response to frontend
    header('Content-Type: application/json');
    http_response_code(500); // Set HTTP status code to 500 (Internal Server Error)
    echo json_encode(['success' => false, 'message' => 'Failed to send booking request email. Please try again.']);
    // Log the error for debugging (check your web host's error logs)
    error_log("Email sending failed for booking from " . $full_name . " to " . $your_email);
}

// Optional: If you want to automatically delete the uploaded file after sending the email, uncomment this:
// if ($upload_successful && file_exists($destination_path)) {
//     unlink($destination_path);
// }

?>