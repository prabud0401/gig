<?php
// Include the database connection file and PHPMailer
include 'db_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php'; // Autoload PHPMailer dependencies

// Function to get category name from category_id
function getCategoryName($category_id, $conn) {
    $category_name = '';
    $stmt = $conn->prepare("SELECT category_name FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $stmt->bind_result($category_name);
    $stmt->fetch();
    $stmt->close();
    return $category_name;
}

// Function to insert job data into the 'jobs' table
function insertJob($title, $description, $image, $category_id, $location, $price, $phone, $due_date, $posted_date, $conn) {
    // Prepare the SQL insert statement
    $stmt = $conn->prepare("INSERT INTO jobs (title, description, image, category_id, location, price, phone, due_date, posted_date) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Bind the parameters to the SQL query
    $stmt->bind_param("sssisddss", $title, $description, $image, $category_id, $location, $price, $phone, $due_date, $posted_date);

    // Execute the query and check for errors
    if ($stmt->execute()) {
        return ['status' => 'success', 'message' => 'Job posted successfully!'];
    } else {
        return ['status' => 'error', 'message' => 'Error posting job: ' . $conn->error];
    }

    // Close the statement
    $stmt->close();
}

// Function to send the job post email to all registered users
function sendJobPostEmail($jobTitle, $jobDescription, $image, $category_id, $location, $price, $phone, $due_date, $posted_date, $conn) {
    // Fetch the category name using the category ID
    $category_name = getCategoryName($category_id, $conn);

    // Fetch all registered emails from the 'users' table
    $sql = "SELECT email FROM users";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Loop through each user email and send the job post email
        while ($row = $result->fetch_assoc()) {
            $email = $row['email'];

            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);

            try {
                // SMTP configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';            // Use Gmail SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'prabud0401@gmail.com';  // Your Gmail address
                $mail->Password = 'rype qpim qzdw vfnd';   // Gmail App password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Sender and recipient details
                $mail->setFrom('prabud0401@gmail.com', 'GigConnect Job Notifications'); // Sender email and name
                $mail->addAddress($email); // Recipient's email

                // Email content with card-style layout
                $mail->isHTML(true);                                  // Set email format to HTML
                $mail->Subject = 'New Job Post: ' . $jobTitle;
                $mail->Body    = '
                <div style="font-family: Arial, sans-serif; color: #333; padding: 20px; background-color: #f4f4f4;">
                    <div style="max-width: 600px; margin: 0 auto; background-color: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">
                        <div style="padding: 20px;">
                            <h2 style="text-align: center; color: #4CAF50;">New Job Posted on GigConnect</h2>
                            <hr style="border: none; border-top: 2px solid #4CAF50; margin-bottom: 20px;">
                            
                            <img src="' . $image . '" alt="Job Image" style="width: 100%; max-height: 250px; object-fit: cover; border-radius: 10px; margin-bottom: 20px;">
                            
                            <p style="font-size: 16px; line-height: 1.6; color: #555;">A new job that might interest you has just been posted. Check out the details below:</p>
                            
                            <div style="background-color: #fafafa; padding: 20px; border-radius: 10px; border: 1px solid #ddd; margin-bottom: 20px;">
                                <h3 style="color: #333;">' . $jobTitle . '</h3>
                                <p><strong>Description:</strong> ' . $jobDescription . '</p>
                                <p><strong>Category:</strong> ' . $category_name . '</p>
                                <p><strong>Location:</strong> ' . $location . '</p>
                                <p><strong>Price:</strong> $' . $price . '</p>
                                <p><strong>Phone:</strong> ' . $phone . '</p>
                                <p><strong>Due Date:</strong> ' . $due_date . '</p>
                                <p><strong>Posted Date:</strong> ' . $posted_date . '</p>
                            </div>
                            
                            <p style="font-size: 16px; line-height: 1.6;">For more information or to apply for this job, visit <a href="https://gigconnect.com" style="color: #4CAF50; text-decoration: none;">GigConnect</a>.</p>
                            <hr style="border: none; border-top: 1px solid #ddd; margin-top: 20px;">
                            <footer style="text-align: center; font-size: 12px; color: #777;">
                                © 2024 GigConnect, All rights reserved.<br>
                                <a href="https://gigconnect.com" style="color: #4CAF50; text-decoration: none;">Visit our website</a>
                            </footer>
                        </div>
                    </div>
                </div>';

                // Send the email
                $mail->send();
            } catch (Exception $e) {
                // Log the error or take appropriate action
                error_log("Failed to send email to " . $email . ": " . $mail->ErrorInfo);
            }
        }
    }
}

// Handle AJAX request for job posting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $location = $_POST['location'];
    $price = $_POST['price'];
    $phone = $_POST['phone'];
    $due_date = $_POST['due_date'];
    $posted_date = date('Y-m-d H:i:s');
    $image = '';

    // Validate form input
    if (empty($title) || empty($description) || empty($category_id) || empty($location) || empty($price) || empty($phone) || empty($due_date)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }
    
    // Check for image upload or image link
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Image file upload
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($_FILES['image']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if file is an actual image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $target_file;  // Use uploaded file path as image
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error uploading the image.']);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'File is not an image.']);
            exit();
        }
    } elseif (!empty($_POST['image_link'])) {
        // Image URL provided
        $image = $_POST['image_link'];  // Use URL as image
    } else {
        echo json_encode(['status' => 'error', 'message' => 'You must provide an image or an image link.']);
        exit();
    }

    // Insert job data into the database
    $result = insertJob($title, $description, $image, $category_id, $location, $price, $phone, $due_date, $posted_date, $conn);

    // Send the job post email to all registered users if job was posted successfully
    if ($result['status'] === 'success') {
        sendJobPostEmail($title, $description, $image, $category_id, $location, $price, $phone, $due_date, $posted_date, $conn);
    }

    // Return the result as JSON
    echo json_encode($result);
    exit();
}
?>
