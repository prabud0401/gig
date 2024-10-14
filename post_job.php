<?php
// Include the database connection file
include 'db.php';

session_start();
if (!isset($_SESSION['email'])) {
    echo "User is not logged in.";
    exit();
}

$email = $_SESSION['email'];

// Fetch the profile picture for the logged-in user
$query = "SELECT profile_picture FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $profilePic = $row['profile_picture'];
} else {
    // Default profile picture if none is set
    $profilePic = 'img/default_profile.png';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job</title>
<link rel="stylesheet" href="css/postjob.css">
</head>
<body>
<header>
    <div class="logo">
        <img src="img/logo/logo.jpg" alt="GigConnect Logo">
    </div>
    <nav>
        <a href="home.php">Home</a>
        <a href="view_jobs.php">Jobs</a>
        <a href="post_job.php" class="button">Post Job</a>
        <a href="profile.php" class="profile-icon" title="View Profile">
       
    <img src="<?php echo $profilePic; ?>" alt="P" style="width: 40px; height: 40px; border-radius: 50%;">
</a>

<a href="logout.php" class="button">Logout</a>
    </nav>

    <!-- header start here -->
</header>
    <h2>Post a New Job</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <label for="title">Job Title:</label><br>
        <input type="text" id="title" name="title" required><br><br>

        <label for="description">Job Description:</label><br>
        <textarea id="description" name="description" required></textarea><br><br>

        <label for="image">Job Image:</label><br>
        <input type="file" id="image" name="image"><br><br>
        <div id="image-preview"></div>

        <label for="category_id">Category:</label><br>
        <select id="category_id" name="category_id" required>
            <option value="">Select Category</option>
            <?php
            // Fetch categories from the database to populate the dropdown
            $result = $conn->query("SELECT id, category_name FROM categories");

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['id'] . "'>" . $row['category_name'] . "</option>";
                }
            } else {
                echo "<option value=''>No categories available</option>";
            }
            ?>
        </select><br><br>

        <label for="location">Location:</label><br>
        <input type="text" id="location" name="location" required><br><br>

        <label for="phone">Phone Number:</label><br>
        <input type="tel" id="phone" name="phone" required><br><br>

        <label for="due_date">Due Date:</label><br>
        <input type="date" id="due_date" name="due_date" required><br><br>

        <input type="submit" name="post_job" value="Post Job">
    </form>

    <?php
// Include the database connection file


// Fetch the logged-in user's id (assuming 'users' table has 'id', 'email' fields)
$email = $_SESSION['email'];
$user_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
$user_query->bind_param("s", $email);
$user_query->execute();
$user_query->bind_result($user_id);
$user_query->fetch();
$user_query->close();

// Check if user id is valid
if (!$user_id) {
    echo "User profile not found.";
    exit();
}
?>

<!-- HTML Form to Post Jobs -->
<!DOCTYPE html>
<html lang="en">
<head>
    
</head>
<body>

    <?php
    // PHP Logic to Post the Job
    if (isset($_POST['post_job'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $location = $_POST['location'];
        $phone = $_POST['phone'];
        $due_date = $_POST['due_date'];
        $image = '';
        $posted_date = date('Y-m-d H:i:s'); // Get the current date and time for the posted date

        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES['image']['name']);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if file is an actual image
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check !== false) {
                // Move the uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image = $target_file;
                } else {
                    echo "Error uploading the image.";
                }
            } else {
                echo "File is not an image.";
            }
        }

        // Ensure all required fields are filled
        if (!empty($title) && !empty($description) && !empty($category_id) && !empty($location) && !empty($phone) && !empty($due_date)) {
            // Prepare the SQL insert statement, now including user_id
$stmt = $conn->prepare("INSERT INTO jobs (user_id, title, description, image, category_id, location, phone, due_date, posted_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

// Updated bind_param statement: using 's' for due_date and posted_date
$stmt->bind_param("isssissss", $user_id, $title, $description, $image, $category_id, $location, $phone, $due_date, $posted_date);


            // Execute the query and check for errors
            if ($stmt->execute()) {
                echo "Job posted successfully!";
                
            } else {
                echo "Error posting job: " . $conn->error;
            }

            // Close the statement
            $stmt->close();
        } else {
            echo "All fields are required!";
        }
    }

    $conn->close();
    ?>
</body>
</html>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // File Preview
        const imageInput = document.getElementById('image');
        const previewContainer = document.getElementById('image-preview');

        imageInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" alt="Image Preview" style="max-width: 100%; margin-top: 10px;">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Form Validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function (event) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;

            requiredFields.forEach(function (field) {
                if (!field.value) {
                    valid = false;
                    alert(`${field.name} is required.`);
                }
            });

            if (!valid) {
                event.preventDefault(); // Prevent form submission if validation fails
            }
        });
    });
</script>