<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
// Export the configured working database as one consistent, importable snapshot.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/Connection.php';
require_once __DIR__.'/demo_dates.php';
date_default_timezone_set('Africa/Lusaka');
$db=(new Connection())->connect();
$tables=['sites','login','buses','drivers','schedule','reservations','maintenance','audit_log','newsletter_subscriptions','login_attempts','contact_enquiries'];
$path=dirname(__DIR__).'/bus_ticket.sql';$counts=[];$temp=null;
try{
 $db->begin_transaction(MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT|MYSQLI_TRANS_START_READ_ONLY);
 $out="-- Hard Work Logistics: current database snapshot\n-- Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025\n-- Generated: ".date('Y-m-d H:i:s').' CAT'."\n-- Import into an EMPTY MySQL 8 database. Existing tables are not overwritten.\n-- Dates are preserved exactly, including history anchored to ".SITE_CREATED_DATE.".\n-- Passwords remain hashed; private .env credentials are not included.\nSET NAMES utf8mb4;\nSET time_zone='+02:00';\nSET @OLD_FK=@@FOREIGN_KEY_CHECKS;\nSET FOREIGN_KEY_CHECKS=0;\nSTART TRANSACTION;\n\n";
 foreach($tables as $table){
  $out.=$db->query("SHOW CREATE TABLE `$table`")->fetch_row()[1].";\n\n";
  if($table==='login_attempts'){$counts[$table]=0;continue;}
  $columns=$db->query("SHOW COLUMNS FROM `$table`")->fetch_all(MYSQLI_ASSOC);
  $primary=array_values(array_filter($columns,function($c){return $c['Key']==='PRI';}));
  $order=$primary?' ORDER BY '.implode(',',array_map(function($c){return '`'.$c['Field'].'`';},$primary)):'';
  $rows=$db->query("SELECT * FROM `$table`".$order)->fetch_all(MYSQLI_ASSOC);$counts[$table]=count($rows);
  $fields=implode(',',array_map(function($c){return '`'.$c['Field'].'`';},$columns));
  foreach($rows as $row){
   $values=[];foreach($columns as $column){$value=$row[$column['Field']];$values[]=$value===null?'NULL':"'".$db->real_escape_string((string)$value)."'";}
   $out.="INSERT INTO `$table` ($fields) VALUES (".implode(',',$values).");\n";
  }
  $out.="\n";
 }
 $out.="COMMIT;\nSET FOREIGN_KEY_CHECKS=@OLD_FK;\n";
 $db->commit();
 $temp=tempnam(sys_get_temp_dir(),'hwl-snapshot-');
 if(file_put_contents($temp,$out)===false || !rename($temp,$path))throw new RuntimeException('Unable to write the SQL snapshot.');
 echo "Exported current records to Backend/bus_ticket.sql\n";
 foreach($counts as $table=>$count)echo "$table: $count\n";
 echo "Temporary login-throttle state is intentionally empty in the export.\n";
} catch(Throwable $e){try{$db->rollback();}catch(Throwable $ignored){}throw $e;}
finally{if($temp&&is_file($temp))unlink($temp);}
