<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
// The former browser-accessible password reset utility is intentionally retired.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/Connection.php';
if ($argc !== 2) exit("Usage: php password_utility.php email (password is read from stdin)\n");
fwrite(STDOUT, "New password (minimum 10 characters): ");
$password = rtrim(fgets(STDIN), "\r\n");
if (strlen($password)<10 || strlen($password)>72) exit("Password must contain 10-72 characters.\n");
$db=(new Connection())->connect();
$stmt=$db->prepare('UPDATE login SET password=? WHERE email=?');
$hash=password_hash($password,PASSWORD_DEFAULT);
$stmt->bind_param('ss',$hash,$argv[1]); $stmt->execute();
echo $stmt->affected_rows ? "Password updated.\n" : "Account not found.\n";
