<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
// Read-only consistency checks for the demo fixture set.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../Backend/BusReserve/DbOperations.php';
require __DIR__.'/../Backend/BusReserve/demo_dates.php';
$db=new DbOperations();$checks=0;
function expectDemo($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
expectDemo((int)$db->one("SELECT COUNT(*) AS n FROM sites WHERE name IN ('Lusaka Travel Centre','Kitwe Travel Centre','Ndola Travel Centre','Chingola Travel Centre')")['n']===4,'Four demo branches required');
expectDemo((int)$db->one("SELECT COUNT(*) AS n FROM buses WHERE number_plate LIKE 'HW-%'")['n']===12,'Twelve demo vehicles required');
expectDemo((int)$db->one("SELECT COUNT(*) AS n FROM drivers WHERE licence LIKE 'HW-%'")['n']===12,'Twelve demo drivers required');
expectDemo((int)$db->one("SELECT COUNT(*) AS n FROM login WHERE position='manager' AND email LIKE '%@hardwork.local'")['n']>=4,'Branch manager accounts missing');
expectDemo((int)$db->one("SELECT COUNT(*) AS n FROM maintenance WHERE bus_id IN (SELECT bus_id FROM buses WHERE number_plate LIKE 'HW-%')")['n']===12,'Maintenance records missing');
$trips=$db->rows("SELECT s.*,b.seats AS capacity FROM schedule s JOIN buses b ON b.bus_id=s.bus_id WHERE b.number_plate LIKE 'HW-%'");
expectDemo(count($trips)>=115,'Demo schedules missing');
expectDemo(demoDate(0)==='2025-04-29','The demo creation date must stay fixed');
foreach($trips as $trip){
 expectDemo($trip['date']>=demoDate(-5) && $trip['date']<=demoDate(14),'Demo trip is outside the creation-date window');
 $available=array_map('intval',json_decode($trip['seats'],true));
 $taken=array_map('intval',array_column($db->rows('SELECT seat FROM reservations WHERE trip_id=?',[(int)$trip['trip_id']]),'seat'));
 expectDemo(count(array_intersect($available,$taken))===0,'Booked seat is still available');
 $all=array_merge($available,$taken);sort($all);
 expectDemo($all===range(1,(int)$trip['capacity']),'Seat inventory does not match capacity');
 expectDemo(strtotime($trip['arrival_date'].' '.$trip['arrival_time'])>strtotime($trip['date'].' '.$trip['departure_time']),'Invalid journey times');
 $driver=$db->one('SELECT site_id,licence_expiry FROM drivers WHERE driver_id=?',[(int)$trip['driver_id']]);
 expectDemo($driver && $driver['site_id']==$trip['site_id'],'Driver is assigned to the wrong branch');
}
expectDemo(!$db->one("SELECT a.trip_id FROM schedule a JOIN schedule b ON a.trip_id<b.trip_id AND (a.bus_id=b.bus_id OR a.driver_id=b.driver_id) JOIN buses v ON v.bus_id=a.bus_id WHERE v.number_plate LIKE 'HW-%' AND a.status='scheduled' AND b.status='scheduled' AND TIMESTAMP(a.date,a.departure_time)<TIMESTAMP(b.arrival_date,b.arrival_time) AND TIMESTAMP(b.date,b.departure_time)<TIMESTAMP(a.arrival_date,a.arrival_time)"),'Overlapping demo vehicle or driver assignments');
echo "PASS: $checks demo data consistency checks.\n";
