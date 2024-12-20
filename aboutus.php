<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);  
error_reporting(E_ALL);         

// Database connection
$conn = new mysqli("localhost:4306", "root", "", "tulsi_makeover");

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle review form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    // Get the form data and sanitize it
    $name = htmlspecialchars(trim($_POST['name']));
    $review = htmlspecialchars(trim($_POST['review']));

    // Check if name and review are not empty
    if (empty($name) || empty($review)) {
        $error_message = "Name and Review are required!";
    } else {
        // Prepare the SQL query
        $sql = "INSERT INTO reviews (name, review) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $error_message = "Error in preparing the statement: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $name, $review);

            // Execute and check if the review was inserted successfully
            if ($stmt->execute()) {
                $success_message = "Review submitted successfully!";
            } else {
                $error_message = "Error: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        }
    }
}

// Fetch reviews from the database
$sql = "SELECT name, review, date FROM reviews ORDER BY date DESC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us | Tulsi Makeover</title>
  <style>
    /* Basic Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      color: #333;
      background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      background-attachment: fixed;
    }

    /* Header Styling */
    .header {
      padding: 40px 20px;
      text-align: center;
      background: rgba(51, 51, 51, 0.8);
      color: #fff;
      width: 100%;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .header h1 {
      font-size: 36px;
      margin-bottom: 10px;
    }

    .header p {
      font-size: 18px;
      color: #ddd;
    }

    /* Container */
    .container {
      width: 90%;
      max-width: 900px;
      margin: 40px auto;
    }

    /* Section Styling */
    .section {
      margin-bottom: 40px;
      padding: 20px;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 10px;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }

    .section h2 {
      font-size: 28px;
      margin-bottom: 20px;
      color: #d2691e;
      text-align: center;
    }

    .section p {
      font-size: 16px;
      color: #555;
      line-height: 1.8;
    }

    /* Testimonials */
    .testimonials {
      padding: 20px;
      background-color: #f1f1f1;
      border-left: 4px solid #d2691e;
      border-radius: 5px;
    }

    .testimonial {
      margin-bottom: 20px;
    }

    .testimonial p {
      font-style: italic;
      color: #555;
    }

    .testimonial span {
      display: block;
      margin-top: 10px;
      color: #888;
      font-size: 14px;
    }

    /* Form Styling */
    form {
      background: rgba(255, 255, 255, 0.9);
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 500px;
      margin: auto;
    }

    form label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }

    form input, form textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }

    form button {
      background-color: #d2691e;
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    form button:hover {
      background-color: #a75414;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <h1>About Tulsi Makeover</h1>
  </div>

  <!-- Introduction Section -->
  <div class="container">
    <div class="section">
      <h2>About Us</h2>
      <p>Welcome to Tulsi Makeover, where beauty meets expertise. Our salon offers a wide range of beauty and grooming services designed to enhance your natural glow. With a team of skilled professionals and a relaxing ambiance, we aim to make every visit unforgettable.</p>
    </div>

    <!-- Our Location Section -->
    <div class="section">
      <h2>Our Location</h2>
      <p><strong>Address:</strong> 33 Sai Punjan 2, Ahir Faliya, Jay Ambey Bungalows, Palsana</p>
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3538.6050147755814!2d72.97897247503295!3d21.080394380581907!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be05b003ea542b1%3A0x84270d47901df8d1!2sTulsi%20Makeover!5e1!3m2!1sen!2sin!4v1733972650629!5m2!1sen!2sin" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>      <p><strong>Contact Us:</strong> +91 9875112236</p>
    </div>

    <!-- Testimonials Section -->
    <div class="section testimonials">
      <h2>What Our Clients Say</h2>
      <?php
      // Display reviews if available
      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              echo "<div class='testimonial'>";
              echo "<p>\"" . htmlspecialchars($row['review']) . "\"</p>";
              echo "<span>– " . htmlspecialchars($row['name']) . " (" . date("d M Y", strtotime($row['date'])) . ")</span>";
              echo "</div>";
          }
      } else {
          echo "<p>No reviews yet. Be the first to leave one!</p>";
      }
      ?>
    </div>

    <!-- Review Form Section -->
    <div class="section">
      <h2>Leave a Review</h2>
      <?php
      // Display success or error messages
      if (isset($success_message)) {
          echo "<p style='color: green;'>$success_message</p>";
      } elseif (isset($error_message)) {
          echo "<p style='color: red;'>$error_message</p>";
      }
      ?>
      <form action="aboutus.php" method="post">
        <label for="name">Your Name:</label><br>
        <input type="text" id="name" name="name" required><br><br>
        <label for="review">Your Review:</label><br>
        <textarea id="review" name="review" rows="4" required></textarea><br><br>
        <button type="submit" name="submit_review">Submit Review</button>
      </form>
    </div>
  </div>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
