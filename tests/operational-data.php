<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../Backend/BusReserve/DbOperations.php';$db=new DbOperations();$checks=0;
function operationalCheck($yes,$text){global $checks;if(!$yes)throw new RuntimeException($text);$checks++;}
$trips=$db->rows("SELECT s.*,b.seats AS capacity,b.service_due,b.status AS vehicle_status,d.licence_expiry,d.site_id AS driver_site FROM schedule s JOIN buses b ON b.bus_id=s.bus_id JOIN drivers d ON d.driver_id=s.driver_id WHERE b.number_plate LIKE 'OP-%'");
operationalCheck(count($trips)===112,'Expected 112 operational trips');
foreach($trips as $trip){
 operationalCheck($trip['date']>='2026-10-24'&&$trip['date']<='2026-11-06','Operating window changed');
 operationalCheck($trip['vehicle_status']==='active'&&$trip['service_due']>=$trip['arrival_date'],'Vehicle is not cleared');
 operationalCheck($trip['driver_site']==$trip['site_id']&&$trip['licence_expiry']>=$trip['arrival_date'],'Driver assignment invalid');
 $available=array_map('intval',json_decode($trip['seats'],true));$taken=array_map('intval',array_column($db->rows('SELECT seat FROM reservations WHERE trip_id=?',[(int)$trip['trip_id']]),'seat'));
 operationalCheck(!array_intersect($available,$taken),'Booked seat is available');$all=array_merge($available,$taken);sort($all);operationalCheck($all===range(1,(int)$trip['capacity']),'Seat inventory mismatch');
}
operationalCheck(!$db->one("SELECT a.trip_id FROM schedule a JOIN schedule b ON a.trip_id<b.trip_id AND (a.bus_id=b.bus_id OR a.driver_id=b.driver_id) JOIN buses v ON v.bus_id=a.bus_id WHERE v.number_plate LIKE 'OP-%' AND a.status='scheduled' AND b.status='scheduled' AND TIMESTAMP(a.date,a.departure_time)<TIMESTAMP(b.arrival_date,b.arrival_time) AND TIMESTAMP(b.date,b.departure_time)<TIMESTAMP(a.arrival_date,a.arrival_time)"),'Overlapping operational assignments');
operationalCheck((int)$db->one("SELECT COUNT(*) AS n FROM maintenance m JOIN buses b ON b.bus_id=m.bus_id WHERE b.number_plate LIKE 'OP-%'")['n']===8,'Service records missing');
echo "PASS: $checks operational data checks.\n";
