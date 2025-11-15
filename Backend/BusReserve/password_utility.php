<?php
/**
 * Password Utility Script
 * This script helps you work with hashed passwords in the database
 * 
 * SECURITY WARNING: Delete this file after use in production!
 */

include_once 'Connection.php';

// Initialize database connection
$db = new Connection();
$conn = $db->connect();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Password Utility - Bus Reservation System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #711313;
            border-bottom: 3px solid #711313;
            padding-bottom: 10px;
        }
        h2 {
            color: #711313;
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #711313;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .warning {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success {
            background-color: #d4edda;
            border: 2px solid #28a745;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .danger {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .form-group {
            margin: 15px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type='email'], input[type='password'], input[type='text'] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background-color: #711313;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #560d0d;
        }
        .code-block {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Password Utility Tool</h1>
        
        <div class='danger'>
            <strong>⚠️ SECURITY WARNING:</strong> This file should be deleted after use in production!
        </div>";

// Function to reset a user's password
if (isset($_POST['reset_password'])) {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    
    if (!empty($email) && !empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE login SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        
        if ($stmt->execute()) {
            echo "<div class='success'>✅ Password updated successfully for: $email<br>New password: $new_password</div>";
        } else {
            echo "<div class='danger'>❌ Error updating password: " . $conn->error . "</div>";
        }
        $stmt->close();
    }
}

// Function to reset all test accounts to default password
if (isset($_POST['reset_all'])) {
    $default_password = 'password123';
    $hashed = password_hash($default_password, PASSWORD_DEFAULT);
    
    $emails = ['peter@gmail.com', 'jacob@gmail.com', 'john@gmail.com', 
               'paul@gmail.com', 'jane@gmail.com', 'nancy@gmail.com', 'mary@gmail.com'];
    
    $updated = 0;
    foreach ($emails as $email) {
        $stmt = $conn->prepare("UPDATE login SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        if ($stmt->execute()) {
            $updated++;
        }
        $stmt->close();
    }
    
    echo "<div class='success'>✅ Reset $updated customer accounts to default password: <strong>password123</strong></div>";
}

// Display all users
echo "<h2>📋 Current User Accounts</h2>";
$result = $conn->query("SELECT id, email, username, location, position FROM login ORDER BY position DESC, id ASC");

if ($result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Username</th>
                <th>Location</th>
                <th>Role</th>
            </tr>";
    
    while ($row = $result->fetch_assoc()) {
        $role = $row['position'] == 'admin' ? '<strong>Admin</strong>' : 'Customer';
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['email']}</td>
                <td>{$row['username']}</td>
                <td>{$row['location']}</td>
                <td>{$role}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found in database.</p>";
}

// Display current known passwords
echo "<h2>🔑 Known Test Account Credentials</h2>";
echo "<div class='code-block'>";
echo "<strong>Admin Account:</strong><br>";
echo "Email: admin@admin<br>";
echo "Password: admin123<br><br>";

echo "<strong>Customer Test Accounts (if reset using button below):</strong><br>";
echo "Email: peter@gmail.com  | Password: password123<br>";
echo "Email: jacob@gmail.com  | Password: password123<br>";
echo "Email: john@gmail.com   | Password: password123<br>";
echo "Email: paul@gmail.com   | Password: password123<br>";
echo "Email: jane@gmail.com   | Password: password123<br>";
echo "Email: nancy@gmail.com  | Password: password123<br>";
echo "Email: mary@gmail.com   | Password: password123<br>";
echo "</div>";

// Reset all test accounts form
echo "<h2>🔄 Quick Reset All Test Accounts</h2>";
echo "<form method='POST'>
        <p>Reset all customer test accounts to password: <strong>password123</strong></p>
        <button type='submit' name='reset_all' onclick=\"return confirm('Are you sure you want to reset all test account passwords?')\">
            Reset All Customer Passwords
        </button>
      </form>";

// Individual password reset form
echo "<h2>🔧 Reset Individual Password</h2>";
echo "<form method='POST'>
        <div class='form-group'>
            <label>Email Address:</label>
            <input type='email' name='email' required placeholder='user@example.com'>
        </div>
        <div class='form-group'>
            <label>New Password:</label>
            <input type='text' name='new_password' required placeholder='Enter new password'>
        </div>
        <button type='submit' name='reset_password'>Update Password</button>
      </form>";

// Instructions
echo "<h2>📖 How to Use This Tool</h2>";
echo "<div class='warning'>
        <strong>Option 1: Reset All Test Accounts</strong><br>
        Click the 'Reset All Customer Passwords' button above to set all customer accounts to password: <code>password123</code>
        <br><br>
        <strong>Option 2: Reset Individual Password</strong><br>
        Use the form above to set a custom password for any specific user account.
        <br><br>
        <strong>Option 3: Create New Hash</strong><br>
        Use the password verification tool below to generate new password hashes.
      </div>";

// Password hash generator
echo "<h2>🔐 Generate Password Hash</h2>";
if (isset($_POST['generate_hash'])) {
    $password_to_hash = $_POST['password_to_hash'];
    $new_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);
    echo "<div class='success'>
            <strong>Password:</strong> $password_to_hash<br>
            <strong>Hash:</strong> <code>$new_hash</code><br>
            <small>Copy this hash and paste it into the database password field</small>
          </div>";
}

echo "<form method='POST'>
        <div class='form-group'>
            <label>Password to Hash:</label>
            <input type='text' name='password_to_hash' required>
        </div>
        <button type='submit' name='generate_hash'>Generate Hash</button>
      </form>";

// Password verification
echo "<h2>✅ Verify Password</h2>";
if (isset($_POST['verify_password'])) {
    $test_email = $_POST['test_email'];
    $test_password = $_POST['test_password'];
    
    $stmt = $conn->prepare("SELECT password FROM login WHERE email = ?");
    $stmt->bind_param("s", $test_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($test_password, $row['password'])) {
            echo "<div class='success'>✅ Password is CORRECT for $test_email</div>";
        } else {
            echo "<div class='danger'>❌ Password is INCORRECT for $test_email</div>";
        }
    } else {
        echo "<div class='danger'>❌ Email not found in database</div>";
    }
    $stmt->close();
}

echo "<form method='POST'>
        <div class='form-group'>
            <label>Email:</label>
            <input type='email' name='test_email' required>
        </div>
        <div class='form-group'>
            <label>Password to Test:</label>
            <input type='password' name='test_password' required>
        </div>
        <button type='submit' name='verify_password'>Verify Password</button>
      </form>";

echo "
        <div class='warning' style='margin-top: 40px;'>
            <strong>⚠️ IMPORTANT SECURITY NOTES:</strong>
            <ul>
                <li>Delete this file (<code>password_utility.php</code>) after you're done using it</li>
                <li>Never use simple passwords like 'password123' in production</li>
                <li>Bcrypt hashes are one-way and cannot be reversed</li>
                <li>Each time you hash a password, the hash will be different (this is normal)</li>
            </ul>
        </div>
    </div>
</body>
</html>";

$conn->close();
?>
