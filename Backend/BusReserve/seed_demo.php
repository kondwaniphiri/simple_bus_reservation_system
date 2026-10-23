<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
// Additive demo fixtures. Existing accounts, bookings and administrator passwords are retained.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/DbOperations.php';
require_once __DIR__.'/demo_dates.php';
date_default_timezone_set('Africa/Lusaka');
$db=new DbOperations();
function demoUser($db,$email,$name,$role,$site,$city,$password='DemoTravel2026!') {
    $existing=$db->one('SELECT id FROM login WHERE email=?',[$email]);
    if($existing) return (int)$existing['id'];
    $db->query('INSERT INTO login(email,username,password,location,position,site_id) VALUES(?,?,?,?,?,?)',[$email,$name,password_hash($password,PASSWORD_DEFAULT),$city,$role,$site]);
    return $db->conn->insert_id;
}
$db->conn->begin_transaction();
try {
    $cities=['Lusaka','Kitwe','Ndola','Chingola'];
    $addresses=['Counter A, Intercity Bus Terminus','Counter B, Kitwe Central Bus Station','Counter C, Ndola Main Bus Station','Counter D, Chingola Bus Station'];
    $sites=[]; $vehicles=[]; $drivers=[]; $customers=[]; $managers=[];
    foreach($cities as $i=>$city) {
        $name=$city.' Travel Centre';
        $site=$db->one('SELECT site_id FROM sites WHERE name=?',[$name]);
        if(!$site) { $db->query('INSERT INTO sites(name,city,address,phone,status) VALUES(?,?,?,?,?)',[$name,$city,$addresses[$i],'Contact branch counter','active']); $site=['site_id'=>$db->conn->insert_id]; }
        $siteId=(int)$site['site_id']; $sites[]=$siteId;
        $managers[]=demoUser($db,strtolower($city).'.manager@hardwork.local',$city.' Manager','manager',$siteId,$city);
        for($j=0;$j<3;$j++) {
            $plate='HW-'.strtoupper(substr($city,0,3)).'-'.($j+1);
            $bus=$db->one('SELECT bus_id FROM buses WHERE number_plate=?',[$plate]);
            $state=$j===2?($i%2?'retired':'maintenance'):'active';
            if(!$bus) {
                $db->query('INSERT INTO buses(bus,number_plate,seats,home,site_id,status,odometer,service_due) VALUES(?,?,?,?,?,?,?,?)',[$city.($j===0?' Express Coach':($j===1?' Comfort Coach':' Reserve Coach')),$plate,$j===1?32:48,$city,$siteId,$state,42000+$i*11000+$j*8000,demoDate($j===2?-2:60+$i)]);
                $bus=['bus_id'=>$db->conn->insert_id];
            }
            $vehicles[$i][$j]=(int)$bus['bus_id'];
            $licence='HW-LIC-'.($i+1).'-'.($j+1);
            $driver=$db->one('SELECT driver_id FROM drivers WHERE licence=?',[$licence]);
            if(!$driver) {
                $names=[['Daniel Phiri','Ruth Banda','Joseph Tembo'],['Grace Mwansa','Peter Chileshe','Alice Mulenga'],['James Zulu','Esther Chanda','Samuel Musonda'],['Mary Bwalya','David Mumba','Lucy Musonda']];
                $db->query('INSERT INTO drivers(site_id,name,phone,licence,licence_expiry,status) VALUES(?,?,?,?,?,?)',[$siteId,$names[$i][$j],'Contact branch office',$licence,demoDate($j===2?-10:365),'active']);
                $driver=['driver_id'=>$db->conn->insert_id];
            }
            $drivers[$i][$j]=(int)$driver['driver_id'];
            if(!$db->one('SELECT maintenance_id FROM maintenance WHERE bus_id=?',[(int)$bus['bus_id']])) {
                $db->query('INSERT INTO maintenance(bus_id,service_date,odometer,cost,notes) VALUES(?,?,?,?,?)',[(int)$bus['bus_id'],demoDate(-14-$j),40000+$i*11000+$j*8000,(string)(1800+$j*950),['Routine oil and filter service; tyres and lights inspected.','Brake inspection, wheel alignment and safety check.','Scheduled workshop inspection; verify fleet status before assignment.'][$j]]);
            }
        }
    }
    $names=['Chipo Banda','Mwewa Phiri','Thandiwe Zulu','Brian Mwansa','Natasha Tembo','Kelvin Chanda','Luyando Mumba','Mutinta Bwalya'];
    foreach($names as $i=>$name) $customers[]=demoUser($db,'passenger'.($i+1).'@hardwork.local',$name,'customer',null,$cities[$i%4]);
    $admin=demoUser($db,'operations@hardwork.local','Operations Administrator','admin',$sites[0],'Lusaka');
    $customerId=demoUser($db,'customer@hardwork.local','Customer Account','customer',null,'Lusaka','Customer2025!');
    $tripCount=0; $bookingCount=0;
    for($day=1;$day<=14;$day++) foreach($cities as $i=>$city) for($service=0;$service<2;$service++) {
        $date=demoDate($day);$busId=$vehicles[$i][$service];$departure=$service?'14:00:00':'06:30:00';
        if($db->one('SELECT trip_id FROM schedule WHERE bus_id=? AND date=? AND departure_time=?',[$busId,$date,$departure])) continue;
        $destination=$cities[($i+($service?2:1))%4];$fare=(string)(110+$i*30+($service?45:0));$capacity=$service?32:48;
        $taken=$day<=7?[2+$i,8+$service]:[]; $seats=array_values(array_diff(range(1,$capacity),$taken));
        $db->query('INSERT INTO schedule(bus_id,home,destination,departure_time,arrival_time,date,seats,site_id,driver_id,fare,status,arrival_date) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',[$busId,$city,$destination,$departure,$service?'21:30:00':'12:30:00',$date,json_encode($seats),$sites[$i],$drivers[$i][$service],$fare,'scheduled',$date]);
        $tripId=$db->conn->insert_id;$tripCount++;
        foreach($taken as $k=>$seat) {
            $db->query('INSERT INTO reservations(user_id,trip_id,seat,amount,created_at) VALUES(?,?,?,?,?)',[$customers[($i*2+$k)%8],$tripId,$seat,$fare,demoDate(0).' 09:00:00']);$bookingCount++;
        }
    }
    // Historical examples demonstrate manifests and operational trip states.
    foreach(['completed','departed','cancelled'] as $i=>$status) {
        $date=demoDate(-3-$i);$busId=$vehicles[$i][0];
        if($db->one('SELECT trip_id FROM schedule WHERE bus_id=? AND date=?',[$busId,$date])) continue;
        $taken=$status==='cancelled'?[]:[4,11];
        $db->query('INSERT INTO schedule(bus_id,home,destination,departure_time,arrival_time,date,seats,site_id,driver_id,fare,status,arrival_date) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',[$busId,$cities[$i],$cities[($i+1)%4],'06:00:00','12:00:00',$date,json_encode(array_values(array_diff(range(1,48),$taken))),$sites[$i],$drivers[$i][0],'180.00',$status,$date]);
        $tripId=$db->conn->insert_id;$tripCount++;
        foreach($taken as $k=>$seat){$db->query('INSERT INTO reservations(user_id,trip_id,seat,amount,created_at) VALUES(?,?,?,?,?)',[$customers[$k],$tripId,$seat,'180.00',demoDate(-7).' 09:00:00']);$bookingCount++;}
    }
    $history=$db->one("SELECT s.* FROM schedule s JOIN buses b ON b.bus_id=s.bus_id WHERE b.number_plate LIKE 'HW-%' AND s.status='completed' ORDER BY s.trip_id LIMIT 1 FOR UPDATE");
    if($history && !$db->one('SELECT reservation_id FROM reservations WHERE user_id=? AND trip_id=?',[$customerId,(int)$history['trip_id']])) {
        $available=json_decode($history['seats'],true);
        if($available) {
            $seat=(int)array_shift($available);
            $db->query('INSERT INTO reservations(user_id,trip_id,seat,amount,created_at) VALUES(?,?,?,?,?)',[$customerId,(int)$history['trip_id'],$seat,$history['fare'],demoDate(-7).' 09:00:00']);
            $db->query('UPDATE schedule SET seats=? WHERE trip_id=?',[json_encode(array_values($available)),(int)$history['trip_id']]);$bookingCount++;
        }
    }
    for($i=1;$i<=4;$i++) $db->query('INSERT IGNORE INTO newsletter_subscriptions(email,created_at) VALUES(?,?)',['passenger'.$i.'@hardwork.local',demoDate(0).' 09:00:00']);
    if(!$db->one("SELECT audit_id FROM audit_log WHERE action='initialRecords'")) {
        foreach($managers as $i=>$manager) $db->audit(['id'=>$manager],'initialRecords','Branch setup and fleet safety review: '.$cities[$i]);
        $db->audit(['id'=>$admin],'initialRecords','Bookings, maintenance history and dispatch states loaded.');
        $db->query("UPDATE audit_log SET created_at=? WHERE action='initialRecords'",[demoDate(0).' 09:00:00']);
    }
    $db->conn->commit();
    echo "Records ready: 4 branches, 12 vehicles, 12 drivers, 14 accounts; $tripCount new trips and $bookingCount new bookings.\n";
    echo "Demo accounts use password DemoTravel2026! Existing account passwords were preserved.\n";
} catch(Throwable $e) { $db->conn->rollback();throw $e; }
