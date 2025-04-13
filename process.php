<?php
// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Simple test response
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Debug output
file_put_contents('debug.log', print_r($_POST, true) . print_r($_FILES, true), FILE_APPEND);

// Database configuration
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'user_image_db';

try {
    // Create database connection
    $db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
    
    if ($db->connect_error) {
        throw new Exception("DB Connection failed: " . $db->connect_error);
    }
    
    // Validate inputs
    if (empty($_POST['name']) || empty($_POST['email'])) {
        throw new Exception("Name and email are required");
    }
    
    $name = trim($db->real_escape_string($_POST['name']));
    $email = trim($db->real_escape_string($_POST['email']));
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    
    // Handle file upload
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception("Failed to create upload directory");
        }
    }
    
    if (!is_writable($uploadDir)) {
        throw new Exception("Upload directory is not writable");
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . $_FILES['image']['error']);
    }
    
    $fileTmp = $_FILES['image']['tmp_name'];
    $fileSize = $_FILES['image']['size'];
    $fileType = $_FILES['image']['type'];
    
    // Verify image
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("Only JPG, PNG, and GIF files are allowed");
    }
    
    if ($fileSize > 2097152) { // 2MB
        throw new Exception("File size exceeds 2MB limit");
    }
    
    $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $newFileName = uniqid('', true) . '.' . $fileExt;
    $uploadPath = $uploadDir . $newFileName;
    
    if (!move_uploaded_file($fileTmp, $uploadPath)) {
        throw new Exception("Failed to move uploaded file");
    }
    
    // Insert into database
    $stmt = $db->prepare("INSERT INTO users (name, email, image_path) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    
    $stmt->bind_param("sss", $name, $email, $newFileName);
    if (!$stmt->execute()) {
        unlink($uploadPath); // Clean up file if DB insert fails
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    $db->close();
    
    // Success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'User registered successfully'
    ]);
    
} catch (Exception $e) {
    // Error response
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>