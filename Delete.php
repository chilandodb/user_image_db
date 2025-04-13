<?php
// Database connection
$db = new mysqli('localhost', 'root', '', 'user_image_db');

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Process deletion
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // First get the image path to delete the file
    $result = $db->query("SELECT image_path FROM users WHERE id = $id");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $imagePath = $user['image_path'];
        
        // Delete the image file if it exists
        if ($imagePath && file_exists("uploads/$imagePath")) {
            unlink("uploads/$imagePath");
        }
    }
    
    // Delete the database record
    if ($db->query("DELETE FROM users WHERE id = $id")) {
        header("Location: index.php?deleted=1");
        exit();
    } else {
        die("Error deleting record: " . $db->error);
    }
}

$db->close();
?>