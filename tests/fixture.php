<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
// Destructive fixture setup is restricted to a database ending in _test.
require __DIR__.'/../Backend/BusReserve/Connection.php';
require __DIR__.'/../Backend/BusReserve/Constants.php';
if (PHP_SAPI!=='cli' || !str_ends_with(DB_NAME,'_test')) exit("Use a dedicated BUS_DB_NAME ending in _test.\n");
$db=(new Connection())->connect();
if(($argv[1]??'')==='ageTrip') {
 $id=(int)($argv[2]??0);
 $s=$db->prepare('UPDATE schedule SET date=DATE_SUB(CURDATE(),INTERVAL 1 DAY),arrival_date=DATE_SUB(CURDATE(),INTERVAL 1 DAY) WHERE trip_id=?');
 $s->bind_param('i',$id);$s->execute();exit;
}
foreach(['contact_enquiries','audit_log','reservations','schedule','maintenance','drivers','buses','login','sites','login_attempts','newsletter_subscriptions'] as $table) {
 if($db->query("SHOW TABLES LIKE '$table'")->num_rows) $db->query("DELETE FROM `$table`");
}
$db->query("INSERT INTO sites(site_id,name,city) VALUES (1,'Lusaka HQ','Lusaka'),(2,'Kitwe Depot','Kitwe')");
$password=password_hash('TestingPass123!',PASSWORD_DEFAULT);
$users=[[1,'admin@test.example','Admin','admin',1],[2,'customer@test.example','Customer',null,null],[3,'manager@test.example','Manager','manager',1],[4,'other@test.example','Other',null,null],[5,'manager2@test.example','Manager Two','manager',2]];
foreach($users as $u){$s=$db->prepare("INSERT INTO login(id,email,username,password,location,position,site_id) VALUES(?,?,?,?,'Lusaka',?,?)");$s->bind_param('issssi',$u[0],$u[1],$u[2],$password,$u[3],$u[4]);$s->execute();}
echo "Test accounts ready.\n";
