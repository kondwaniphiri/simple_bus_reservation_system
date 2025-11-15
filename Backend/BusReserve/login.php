<?php
// Example: Dummy users (later replace with database)
$users = [
    ['email' => 'admin@example.com', 'password' => 'admin123', 'role' => 'admin'],
    ['email' => 'user@example.com', 'password' => 'user123', 'role' => 'user']
];

// Get POST data
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$response = ['success' => false, 'message' => 'Invalid email or password.'];

foreach ($users as $user) {
    if ($user['email'] === $email && $user['password'] === $password) {
        // Authentication successful
        $response = [
            'success' => true,
            'role' => $user['role'] // passing role back
        ];
        break;
    }
}

// Set JSON header
header('Content-Type: application/json');
echo json_encode($response);
?>
