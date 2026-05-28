<?php
// ==============================================
// submit.php – Handles form data & file uploads
// ==============================================

// --- DATABASE CONFIGURATION (REPLACE WITH YOUR OWN) ---
$host   = 'localhost';        // InfinityFree typically uses something like sql123.epizy.com
$dbname = 'your_database';    // your actual database name
$user   = 'your_username';    // your database username
$pass   = 'your_password';    // your database password

// --- CONNECT TO DATABASE WITH PDO ---
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed. Please try again later.");
}

// --- CHECK IF FORM WAS SUBMITTED ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

// --- FILE UPLOAD HANDLING ---
// Create uploads folder if it doesn't exist
if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
}

// Allowed image types and max size (2 MB)
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
$max_size      = 2 * 1024 * 1024; // 2MB

// Helper function to safely upload a file
function uploadFile($file_input_name) {
    global $allowed_types, $max_size;

    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error for $file_input_name.");
    }

    $file = $_FILES[$file_input_name];

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_types)) {
        throw new Exception("$file_input_name must be a JPG or PNG image.");
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        throw new Exception("$file_input_name is too large. Maximum 2MB allowed.");
    }

    // Generate a safe, unique filename
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_name = uniqid('img_', true) . '.' . strtolower($ext);
    $destination = 'uploads/' . $new_name;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Could not save $file_input_name. Check folder permissions.");
    }

    return $new_name;
}

// Upload both files – catch errors and stop if any fail
try {
    $passport_file = uploadFile('passport');
    $idcard_file   = uploadFile('idCard');
} catch (Exception $e) {
    die("Upload error: " . $e->getMessage());
}

// --- SANITIZE & COLLECT TEXT INPUTS ---
$fname          = trim(strip_tags($_POST['fname'] ?? ''));
$lname          = trim(strip_tags($_POST['lname'] ?? ''));
$dob            = trim(strip_tags($_POST['dob'] ?? ''));
$contact_address = trim(strip_tags($_POST['contactAddress'] ?? ''));
$phone          = trim(strip_tags($_POST['number'] ?? ''));
$village        = trim(strip_tags($_POST['village'] ?? ''));
$school         = trim(strip_tags($_POST['school'] ?? ''));
$father_name    = trim(strip_tags($_POST['father'] ?? ''));
$father_address = trim(strip_tags($_POST['fathersAddress'] ?? ''));
$mother_name    = trim(strip_tags($_POST['mother'] ?? ''));
$mother_address = trim(strip_tags($_POST['mothersAddress'] ?? ''));
$guarantor_name    = trim(strip_tags($_POST['guarantor'] ?? ''));
$guarantor_address = trim(strip_tags($_POST['guarantorAddress'] ?? ''));
$guarantor_phone   = trim(strip_tags($_POST['guarantorNumber'] ?? ''));
$witness_name      = trim(strip_tags($_POST['witness'] ?? ''));
$witness_phone     = trim(strip_tags($_POST['witnessNumber'] ?? ''));

// Basic validation (all required fields must be filled)
if (empty($fname) || empty($lname) || empty($dob) || empty($contact_address) || empty($phone)) {
    die("Please fill in all required fields.");
}

// --- INSERT INTO DATABASE ---
$sql = "INSERT INTO loans 
        (fname, lname, dob, contact_address, phone, village, school, 
         father_name, father_address, mother_name, mother_address, 
         guarantor_name, guarantor_address, guarantor_phone, 
         witness_name, witness_phone, passport_file, idcard_file, status)
        VALUES 
        (:fname, :lname, :dob, :contact, :phone, :village, :school,
         :father, :father_addr, :mother, :mother_addr,
         :guarantor, :guarantor_addr, :guarantor_phone,
         :witness, :witness_phone, :passport, :idcard, 'Pending')";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':fname'          => $fname,
        ':lname'          => $lname,
        ':dob'            => $dob,
        ':contact'        => $contact_address,
        ':phone'          => $phone,
        ':village'        => $village,
        ':school'         => $school,
        ':father'         => $father_name,
        ':father_addr'    => $father_address,
        ':mother'         => $mother_name,
        ':mother_addr'    => $mother_address,
        ':guarantor'      => $guarantor_name,
        ':guarantor_addr' => $guarantor_address,
        ':guarantor_phone'=> $guarantor_phone,
        ':witness'        => $witness_name,
        ':witness_phone'  => $witness_phone,
        ':passport'       => $passport_file,
        ':idcard'         => $idcard_file
    ]);

    // Success message
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Success</title><style>body{font-family:sans-serif; padding:20px; text-align:center;} a{color:#000080;}</style></head><body>";
    echo "<h2>✅ Application submitted successfully!</h2>";
    echo "<p><a href='index.html'>← Back to form</a></p>";
    echo "</body></html>";
} catch (PDOException $e) {
    // Remove uploaded files if DB insert fails
    unlink('uploads/' . $passport_file);
    unlink('uploads/' . $idcard_file);
    die("Database error: Could not save your application. Please try again.");
}