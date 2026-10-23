<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
// Adds a fixed, current operating window without moving the original 2025 records.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/DbOperations.php';
$db=new DbOperations();$start='2026-10-24';
$cities=['Lusaka','Kitwe','Ndola','Chingola'];
$phones=['+260 211 567 890','+260 211 234 567','+260 212 345 678','+260 212 456 789'];
$addresses=['Inter-City Bus Terminal, Lumumba Road','Kitwe Central Bus Station, Freedom Avenue','Ndola Bus Terminal, Broadway','Chingola Bus Station, Independence Way'];
$names=['Gift Chisenga','Linda Sichone','Patrick Chomba','Martha Lungu','Francis Sakala','Rose Nkhoma','Andrew Kalaba','Mercy Chola'];
$customers=$db->rows("SELECT id FROM login WHERE position='customer' AND email LIKE '%@hardwork.local' ORDER BY id");
if(!$customers)exit("Run seed_demo.php first to create customer accounts.\n");
$db->conn->begin_transaction();$created=0;$bookings=0;
try{
 foreach($cities as $i=>$city){
  $site=$db->one('SELECT site_id FROM sites WHERE name=?',[$city.' Travel Centre']);if(!$site)throw new RuntimeException('Missing branch: '.$city);$siteId=(int)$site['site_id'];
  $db->query('UPDATE sites SET address=?,phone=? WHERE site_id=?',[$addresses[$i],$phones[$i],$siteId]);
  for($service=0;$service<2;$service++){
   $plate='OP-'.strtoupper(substr($city,0,3)).'-'.($service+1);$capacity=$service?32:48;
   $bus=$db->one('SELECT bus_id FROM buses WHERE number_plate=?',[$plate]);
   if(!$bus){$db->query("INSERT INTO buses(bus,number_plate,seats,home,site_id,status,odometer,service_due) VALUES(?,?,?,?,?,'active',?,'2027-04-29')",[$city.($service?' Afternoon Coach':' Morning Coach'),$plate,$capacity,$city,$siteId,75000+$i*4000+$service*1200]);$bus=['bus_id'=>$db->conn->insert_id];}
   $busId=(int)$bus['bus_id'];$licence='OP-LIC-'.($i+1).'-'.($service+1);
   $driver=$db->one('SELECT driver_id FROM drivers WHERE licence=?',[$licence]);
   if(!$driver){$db->query("INSERT INTO drivers(site_id,name,phone,licence,licence_expiry,status) VALUES(?,?,?,?,'2028-04-29','active')",[$siteId,$names[$i*2+$service],$phones[$i],$licence]);$driver=['driver_id'=>$db->conn->insert_id];}
   if(!$db->one('SELECT maintenance_id FROM maintenance WHERE bus_id=?',[$busId]))$db->query("INSERT INTO maintenance(bus_id,service_date,odometer,cost,notes) VALUES(?,'2026-10-20',?,?,?)",[$busId,74000+$i*4000+$service*1200,'2250.00','Oil, brake and tyre inspection completed before scheduled service.']);
   for($day=0;$day<14;$day++){
    $date=(new DateTimeImmutable($start))->modify('+'.$day.' days')->format('Y-m-d');$departure=$service?'14:00:00':'06:30:00';
    if($db->one('SELECT trip_id FROM schedule WHERE bus_id=? AND date=? AND departure_time=?',[$busId,$date,$departure]))continue;
    $taken=$day<7?[3,10]:[];$fare=(string)(160+$i*25+$service*30);
    $db->query("INSERT INTO schedule(bus_id,home,destination,departure_time,arrival_time,date,seats,site_id,driver_id,fare,status,arrival_date) VALUES(?,?,?,?,?,?,?,?,?,?,'scheduled',?)",[$busId,$city,$cities[($i+$service+1)%4],$departure,$service?'21:00:00':'12:30:00',$date,json_encode(array_values(array_diff(range(1,$capacity),$taken))),$siteId,(int)$driver['driver_id'],$fare,$date]);
    $tripId=$db->conn->insert_id;$created++;
    foreach($taken as $j=>$seat){$customer=(int)$customers[($i*2+$service+$j+$day)%count($customers)]['id'];$db->query('INSERT INTO reservations(user_id,trip_id,seat,amount) VALUES(?,?,?,?)',[$customer,$tripId,$seat,$fare]);$bookings++;}
   }
  }
 }
 if(!$db->one("SELECT enquiry_id FROM contact_enquiries WHERE sender_key='initial-records'")){
  foreach([['Route information','Please provide information about the Lusaka to Kitwe departure and boarding point.','new'],['Luggage enquiry','Please advise whether a suitcase and a small carry-on bag can be taken on the scheduled coach.','in_progress'],['Reservation assistance','I need assistance finding the reservation number for my upcoming journey.','closed']] as $i=>$record){
   $site=$db->one('SELECT site_id FROM sites WHERE name=?',[$cities[$i].' Travel Centre']);
   $db->query('INSERT INTO contact_enquiries(site_id,name,email,phone,subject,message,status,sender_key) VALUES(?,?,?,?,?,?,?,?)',[(int)$site['site_id'],'Customer Account','customer@hardwork.local','',$record[0],$record[1],$record[2],'initial-records']);
  }
 }
 if($created){$admin=$db->one("SELECT id FROM login WHERE position='admin' ORDER BY id LIMIT 1");$db->audit($admin,'operatingSchedule','Departures added for 24 October to 6 November 2026; pre-service checks recorded.');}
 $db->conn->commit();echo "Added $created trips and $bookings bookings; fleet, driver, maintenance, contact and branch records ready.\n";
}catch(Throwable $e){$db->conn->rollback();throw $e;}
