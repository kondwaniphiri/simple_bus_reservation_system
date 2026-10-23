<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
// Run once from a terminal: php Backend/BusReserve/migrate.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/Connection.php';
$db = (new Connection())->connect();
$db->query("CREATE TABLE IF NOT EXISTS sites (site_id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE, city VARCHAR(100) NOT NULL, address VARCHAR(255) NOT NULL DEFAULT '', phone VARCHAR(40) NOT NULL DEFAULT '', status VARCHAR(20) NOT NULL DEFAULT 'active') ENGINE=InnoDB");
$db->query("CREATE TABLE IF NOT EXISTS drivers (driver_id INT AUTO_INCREMENT PRIMARY KEY, site_id INT NOT NULL, name VARCHAR(100) NOT NULL, phone VARCHAR(40) NOT NULL, licence VARCHAR(80) NOT NULL UNIQUE, licence_expiry DATE NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active') ENGINE=InnoDB");
$db->query("CREATE TABLE IF NOT EXISTS maintenance (maintenance_id INT AUTO_INCREMENT PRIMARY KEY, bus_id INT NOT NULL, service_date DATE NOT NULL, odometer INT NOT NULL, cost DECIMAL(12,2) NOT NULL, notes VARCHAR(1000) NOT NULL) ENGINE=InnoDB");
$db->query("CREATE TABLE IF NOT EXISTS audit_log (audit_id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, action VARCHAR(80) NOT NULL, details VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$db->query("CREATE TABLE IF NOT EXISTS login_attempts (attempt_key VARCHAR(64) PRIMARY KEY, attempts INT NOT NULL, last_attempt BIGINT NOT NULL) ENGINE=InnoDB");
$db->query("CREATE TABLE IF NOT EXISTS newsletter_subscriptions (email VARCHAR(50) PRIMARY KEY, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$db->query("CREATE TABLE IF NOT EXISTS contact_enquiries (enquiry_id INT AUTO_INCREMENT PRIMARY KEY, site_id INT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, phone VARCHAR(40) NOT NULL DEFAULT '', subject VARCHAR(150) NOT NULL, message TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'new', sender_key CHAR(64) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX enquiry_sender_time(sender_key,created_at), INDEX enquiry_branch(site_id)) ENGINE=InnoDB");
$columns = [
 'login'=>['site_id'=>'INT NULL'],
 'buses'=>['site_id'=>'INT NULL','status'=>"VARCHAR(20) NOT NULL DEFAULT 'active'",'odometer'=>'INT NOT NULL DEFAULT 0','service_due'=>'DATE NULL'],
 'schedule'=>['site_id'=>'INT NULL','driver_id'=>'INT NULL','fare'=>'DECIMAL(10,2) NOT NULL DEFAULT 0','status'=>"VARCHAR(20) NOT NULL DEFAULT 'scheduled'",'arrival_date'=>'DATE NULL'],
 'reservations'=>['amount'=>'DECIMAL(10,2) NOT NULL DEFAULT 0','created_at'=>'TIMESTAMP DEFAULT CURRENT_TIMESTAMP']
];
foreach ($columns as $table=>$items) foreach ($items as $column=>$definition) {
 $stmt=$db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
 $stmt->bind_param('ss',$table,$column); $stmt->execute();
 if (!$stmt->get_result()->fetch_row()[0]) $db->query("ALTER TABLE `$table` ADD `$column` $definition");
}
$db->query("INSERT IGNORE INTO sites(name,city) VALUES ('Head Office','Lusaka')");
$db->query('UPDATE buses SET site_id=(SELECT MIN(site_id) FROM sites) WHERE site_id IS NULL');
$db->query('UPDATE schedule s JOIN buses b ON s.bus_id=b.bus_id SET s.site_id=b.site_id WHERE s.site_id IS NULL');
$db->query('UPDATE schedule SET arrival_date=date WHERE arrival_date IS NULL');
foreach (['reservations'=>['uq_trip_seat'=>'trip_id,seat'], 'login'=>['uq_email'=>'email'], 'buses'=>['uq_plate'=>'number_plate(100)']] as $table=>$indexes) foreach($indexes as $name=>$fields) {
 $result=$db->query("SHOW INDEX FROM `$table` WHERE Key_name='$name'");
 if (!$result->num_rows) $db->query("ALTER TABLE `$table` ADD UNIQUE KEY `$name` ($fields)");
}
// Foreign keys keep fleet, branches and bookings connected even outside the UI.
$relations=[
 ['login','fk_login_site','site_id','sites','site_id'],
 ['buses','fk_bus_site','site_id','sites','site_id'],
 ['drivers','fk_driver_site','site_id','sites','site_id'],
 ['schedule','fk_trip_bus','bus_id','buses','bus_id'],
 ['schedule','fk_trip_site','site_id','sites','site_id'],
 ['schedule','fk_trip_driver','driver_id','drivers','driver_id'],
 ['reservations','fk_booking_user','user_id','login','id'],
 ['reservations','fk_booking_trip','trip_id','schedule','trip_id'],
 ['maintenance','fk_service_bus','bus_id','buses','bus_id'],
 ['contact_enquiries','fk_enquiry_site','site_id','sites','site_id']
];
foreach($relations as [$table,$name,$column,$parent,$key]) {
 $stmt=$db->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=?');
 $stmt->bind_param('ss',$table,$name);$stmt->execute();
 if(!$stmt->get_result()->fetch_row()[0]) $db->query("ALTER TABLE `$table` ADD CONSTRAINT `$name` FOREIGN KEY (`$column`) REFERENCES `$parent` (`$key`)");
}
echo "Migration complete. Existing records preserved.\n";
