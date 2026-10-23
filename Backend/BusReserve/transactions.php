<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
ini_set('display_errors','0');
ini_set('session.gc_maxlifetime','1800');
date_default_timezone_set('Africa/Lusaka');
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Strict','path'=>'/']);
session_name('BUSRESERVESESSID');
session_start();
if (!empty($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > 1800) { $_SESSION=[]; session_regenerate_id(true); }
$_SESSION['last_seen']=time();
function reply($data,$status=200) { http_response_code($status); echo json_encode($data); exit; }
function fail($message,$status=422) { throw new RuntimeException($message,$status); }
function val($key,$max=255) { $v=trim((string)($_POST[$key]??'')); if($v==='' || strlen($v)>$max) fail("Invalid or missing $key."); return $v; }
function num($key,$min=1,$max=2147483647) { $v=filter_var($_POST[$key]??null,FILTER_VALIDATE_INT); if($v===false || $v===null || $v<$min || $v>$max) fail("Invalid $key."); return $v; }
function day($key) { $v=val($key,10); $d=DateTime::createFromFormat('!Y-m-d',$v); if(!$d || $d->format('Y-m-d')!==$v) fail("Invalid $key."); return $v; }
function choice($key,$choices) { $v=val($key); if(!in_array($v,$choices,true)) fail("Invalid $key."); return $v; }
function money($key) { $v=val($key,16); if(!preg_match('/^\d{1,8}(\.\d{1,2})?$/',$v)) fail("Invalid $key."); return $v; }
function clockValue($key) { $v=val($key,8); if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',$v)) fail("Invalid $key."); return strlen($v)===5?$v.':00':$v; }
function passwordInput($key) { $v=(string)($_POST[$key]??''); if(strlen($v)<10 || strlen($v)>72) fail('Use a password with 10-72 bytes.'); return $v; }
try {
    if($_SERVER['REQUEST_METHOD']!=='POST') { header('Allow: POST'); reply(['error'=>true,'message'=>'Use POST.'],405); }
    require_once __DIR__.'/DbOperations.php'; $db=new DbOperations();
    $action=val('action',50);
    if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    if($action==='session') {
        $user=empty($_SESSION['uid'])?null:$db->one('SELECT id,email,username,position,site_id FROM login WHERE id=?',[(int)$_SESSION['uid']]);
        reply(['error'=>false,'user'=>$user,'csrf'=>$_SESSION['csrf']]);
    }
    if($action==='getPublicInfo') {
        reply(['error'=>false,
            'sites'=>$db->rows("SELECT site_id,name,city,address,phone FROM sites WHERE status='active' ORDER BY city,name"),
            'routes'=>$db->rows("SELECT s.home,s.destination,MIN(s.fare) AS starting_fare,MIN(TIMESTAMP(s.date,s.departure_time)) AS next_departure FROM schedule s JOIN sites branch ON branch.site_id=s.site_id JOIN buses b ON b.bus_id=s.bus_id WHERE branch.status='active' AND b.status='active' AND s.status='scheduled' AND TIMESTAMP(s.date,s.departure_time)>NOW() AND (b.service_due IS NULL OR b.service_due>=s.date) GROUP BY s.home,s.destination ORDER BY next_departure,s.home,s.destination LIMIT 6")
        ]);
    }
    if(empty($_SESSION['uid']) && !in_array($action,['login','register','contact'],true)) fail('Please sign in.',401);
    if(!hash_equals($_SESSION['csrf'],(string)($_SERVER['HTTP_X_CSRF_TOKEN']??''))) fail('Session expired. Refresh the page.',403);
    if($action==='contact') {
        if(!empty($_POST['website'])) reply(['error'=>false,'message'=>'Your enquiry has been received.']);
        $name=val('name',100);$email=val('email',100);$subject=val('subject',150);$message=val('message',CONTACT_MAX_LENGTH);
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) fail('Enter a valid email address.');
        if(strlen($message)<CONTACT_MIN_LENGTH) fail('Please provide at least '.CONTACT_MIN_LENGTH.' characters about your enquiry.');
        $phone=trim((string)($_POST['phone']??''));if(strlen($phone)>40) fail('Phone number is too long.');
        $site=empty($_POST['site_id'])?null:num('site_id');
        if($site && !$db->one("SELECT site_id FROM sites WHERE site_id=? AND status='active'",[$site])) fail('Choose an active branch.');
        $sender=hash('sha256',$_SERVER['REMOTE_ADDR']??'local');
        $lock=$db->one('SELECT GET_LOCK(?,5) AS acquired',['contact:'.substr($sender,0,48)]);
        if(!$lock || !$lock['acquired']) fail('Please try again shortly.',429);
        try {
            $count=$db->one('SELECT COUNT(*) AS n FROM contact_enquiries WHERE sender_key=? AND created_at>DATE_SUB(NOW(),INTERVAL 1 HOUR)',[$sender]);
            if($count['n']>=CONTACT_RATE_LIMIT) fail('Too many enquiries. Please try again later or call the branch.',429);
            $db->query('INSERT INTO contact_enquiries(site_id,name,email,phone,subject,message,sender_key) VALUES(?,?,?,?,?,?,?)',[$site,$name,$email,$phone,$subject,$message,$sender]);
            $id=$db->conn->insert_id;
        }finally{$db->query('SELECT RELEASE_LOCK(?)',['contact:'.substr($sender,0,48)]);}
        reply(['error'=>false,'enquiry_id'=>$id,'message'=>'Your enquiry has been received. Reference #'.$id.'.']);
    }
    if($action==='register') {
        $email=val('email',50); if(!filter_var($email,FILTER_VALIDATE_EMAIL)) fail('Enter a valid email.');
        $password=passwordInput('password');
        $db->query("INSERT INTO login(email,username,password,location,position) VALUES(?,?,?,?,'customer')",[$email,val('username',100),password_hash($password,PASSWORD_DEFAULT),val('location',100)]);
        reply(['error'=>false,'register'=>true,'message'=>'Account created. You can now sign in.']);
    }
    if($action==='login') {
        $email=val('email',50); $pass=(string)($_POST['password']??'');
        $key=hash('sha256',($_SERVER['REMOTE_ADDR']??'').'|'.strtolower($email));
        $db->query('INSERT INTO login_attempts(attempt_key,attempts,last_attempt) VALUES(?,1,?) ON DUPLICATE KEY UPDATE attempts=IF(last_attempt < ?,1,attempts+1), last_attempt=VALUES(last_attempt)',[$key,time(),time()-900]);
        $attempt=$db->one('SELECT attempts FROM login_attempts WHERE attempt_key=?',[$key]);
        if($attempt['attempts']>10) fail('Too many attempts. Wait 15 minutes.',429);
        $user=$db->one('SELECT * FROM login WHERE email=?',[$email]);
        if(!$user || !password_verify($pass,$user['password'])) fail('Invalid email or password.',401);
        $db->query('DELETE FROM login_attempts WHERE attempt_key=?',[$key]);
        session_regenerate_id(true); $_SESSION['uid']=(int)$user['id']; $_SESSION['csrf']=bin2hex(random_bytes(32));
        unset($user['password']); reply(array_merge($user,['error'=>false,'login'=>true,'csrf'=>$_SESSION['csrf'],'message'=>'Welcome '.$user['username']]));
    }
    $user=empty($_SESSION['uid'])?null:$db->one('SELECT id,email,username,position,site_id FROM login WHERE id=?',[(int)$_SESSION['uid']]);
    if(!$user) fail('Please sign in.',401);
    if($action==='logout') { $_SESSION=[]; session_destroy(); reply(['error'=>false]); }
    if($action==='subscribeNewsletter') {
        $email=val('email',50);
        if(strcasecmp($email,$user['email'])!==0) fail('Use the email address registered to your account.');
        $db->query('INSERT IGNORE INTO newsletter_subscriptions(email) VALUES(?)',[$user['email']]);
        reply(['error'=>false,'message'=>'Your newsletter preference is saved.']);
    }
    if($action==='unsubscribeNewsletter') {
        $db->query('DELETE FROM newsletter_subscriptions WHERE email=?',[$user['email']]);
        reply(['error'=>false,'message'=>'You have unsubscribed.']);
    }
    if($action==='changePassword') {
        $row=$db->one('SELECT password FROM login WHERE id=?',[(int)$user['id']]);
        if(!password_verify((string)($_POST['current_password']??''),$row['password'])) fail('Current password is incorrect.');
        $password=passwordInput('new_password');
        $db->query('UPDATE login SET password=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),(int)$user['id']]); session_regenerate_id(true);
        reply(['error'=>false,'message'=>'Password changed.']);
    }
    $staff=in_array($user['position'],['admin','manager'],true);
    $scope=$user['position']==='manager'?' AND s.site_id='.(int)$user['site_id']:'';
    if($action==='getSites') { $sql='SELECT * FROM sites'; if($user['position']==='manager') $sql.=' WHERE site_id='.(int)$user['site_id']; elseif(!$staff) $sql.=" WHERE status='active'"; reply(['error'=>false,'data'=>$db->rows($sql)]); }
    if($action==='getBus') { $db->staff($user); $sql='SELECT * FROM buses'; if($user['position']==='manager') $sql.=' WHERE site_id='.(int)$user['site_id']; reply(['error'=>false,'data'=>$db->rows($sql)]); }
    if($action==='getSchedule') {
        $where=$staff?'1=1':"s.status='scheduled' AND TIMESTAMP(s.date,s.departure_time)>NOW() AND b.status='active' AND (b.service_due IS NULL OR b.service_due>=s.date) AND EXISTS(SELECT 1 FROM sites WHERE site_id=s.site_id AND status='active')";
        reply(['error'=>false,'data'=>$db->rows("SELECT s.*, b.bus,b.number_plate,b.seats AS capacity,branch.name AS branch_name,branch.address AS boarding_address,branch.phone AS branch_phone FROM schedule s JOIN buses b ON b.bus_id=s.bus_id LEFT JOIN sites branch ON branch.site_id=s.site_id WHERE $where $scope ORDER BY s.date,s.departure_time")]);
    }
    if($action==='getReservations') {
        $where=$staff?'1=1':'r.user_id='.(int)$user['id'];
        reply(['error'=>false,'data'=>$db->rows("SELECT r.*,s.home,s.destination,s.date,s.departure_time,s.arrival_date,s.arrival_time,s.status,s.site_id,u.username,u.email,b.bus,b.number_plate,branch.name AS branch_name,branch.address AS boarding_address FROM reservations r JOIN schedule s ON s.trip_id=r.trip_id JOIN login u ON u.id=r.user_id JOIN buses b ON b.bus_id=s.bus_id LEFT JOIN sites branch ON branch.site_id=s.site_id WHERE $where $scope ORDER BY r.reservation_id DESC")]);
    }
    if($action==='addReservation' || $action==='editReservation') {
        $id=$db->reserve($user,num('trip_id'),num('seat'),$action==='editReservation'?num('reservation_id'):0,$staff?num('user_id'):0);
        reply(['error'=>false,'reservation_id'=>$id,'message'=>'Reservation saved.']);
    }
    if($action==='deleteReservation') { $db->cancel($user,num('reservation_id')); reply(['error'=>false,'message'=>'Reservation cancelled; seat released.']); }
    $db->staff($user);
    if($action==='getEnquiries') {
        reply(['error'=>false,'data'=>$db->rows('SELECT enquiry_id,site_id,name,email,phone,subject,message,status,created_at FROM contact_enquiries'.($user['position']==='manager'?' WHERE site_id='.(int)$user['site_id']:'').' ORDER BY enquiry_id DESC LIMIT 500')]);
    }
    if($action==='getUsers') { reply(['error'=>false,'data'=>$db->rows("SELECT id,username,email,location,position,site_id FROM login".($user['position']==='manager'?" WHERE position IS NULL OR position='customer' OR site_id=".(int)$user['site_id']:''))]); }
    if($action==='getDrivers') { reply(['error'=>false,'data'=>$db->rows('SELECT * FROM drivers'.($user['position']==='manager'?' WHERE site_id='.(int)$user['site_id']:''))]); }
    if($action==='getMaintenance') { reply(['error'=>false,'data'=>$db->rows('SELECT m.*,b.bus,b.number_plate,b.site_id FROM maintenance m JOIN buses b ON b.bus_id=m.bus_id'.($user['position']==='manager'?' WHERE b.site_id='.(int)$user['site_id']:'').' ORDER BY service_date DESC')]); }
    if($action==='getAudit') { if($user['position']!=='admin') fail('Administrator access required.',403); reply(['error'=>false,'data'=>$db->rows('SELECT a.*,u.username FROM audit_log a LEFT JOIN login u ON u.id=a.user_id ORDER BY audit_id DESC LIMIT 500')]); }
    $db->conn->begin_transaction();
    if($action==='saveEnquiry') {
        $id=num('enquiry_id');$row=$db->one('SELECT site_id FROM contact_enquiries WHERE enquiry_id=? FOR UPDATE',[$id]);
        if(!$row) fail('Enquiry not found.',404);$db->scope($user,$row['site_id']);
        $db->query('UPDATE contact_enquiries SET status=? WHERE enquiry_id=?',[choice('status',['new','in_progress','closed']),$id]);
    } elseif($action==='saveSite') {
        if($user['position']!=='admin') fail('Administrator access required.',403);
        $id=num('site_id',0); $values=[val('name',100),val('city',100),trim((string)($_POST['address']??'')),trim((string)($_POST['phone']??'')),choice('status',['active','inactive'])];
        if(strlen($values[2])>255 || strlen($values[3])>40) fail('Address or phone is too long.');
        if($id && !$db->one('SELECT site_id FROM sites WHERE site_id=? FOR UPDATE',[$id])) fail('Branch not found.',404);
        if($id) { $values[]=$id; $db->query('UPDATE sites SET name=?,city=?,address=?,phone=?,status=? WHERE site_id=?',$values); }
        else $db->query('INSERT INTO sites(name,city,address,phone,status) VALUES(?,?,?,?,?)',$values);
    } elseif($action==='saveStaff') {
        if($user['position']!=='admin') fail('Administrator access required.',403);
        $id=num('id'); if($id===$user['id']) fail('You cannot change your own role.');
        if(!$db->one('SELECT id FROM login WHERE id=? FOR UPDATE',[$id])) fail('Account not found.',404);
        $role=choice('position',['customer','manager','admin']); $site=num('site_id');
        if(!$db->one('SELECT site_id FROM sites WHERE site_id=?',[$site])) fail('Branch not found.');
        $db->query('UPDATE login SET position=?,site_id=? WHERE id=?',[$role,$site,$id]);
    } elseif($action==='saveDriver') {
        $id=num('driver_id',0); $site=num('site_id'); $db->scope($user,$site);
        if($id) { $old=$db->one('SELECT * FROM drivers WHERE driver_id=? FOR UPDATE',[$id]); if(!$old) fail('Driver not found.',404); $db->scope($user,$old['site_id']); }
        if(!$db->one("SELECT site_id FROM sites WHERE site_id=? AND status='active'",[$site])) fail('Choose an active branch.');
        $values=[$site,val('name',100),val('phone',40),val('licence',80),day('licence_expiry'),choice('status',['active','inactive'])];
        if($id && $db->one("SELECT trip_id FROM schedule WHERE driver_id=? AND (status='departed' OR (status='scheduled' AND TIMESTAMP(COALESCE(arrival_date,date),arrival_time)>NOW()))",[$id])) fail('Reassign upcoming trips before editing this driver.',409);
        if($id) { $values[]=$id; $db->query('UPDATE drivers SET site_id=?,name=?,phone=?,licence=?,licence_expiry=?,status=? WHERE driver_id=?',$values); }
        else $db->query('INSERT INTO drivers(site_id,name,phone,licence,licence_expiry,status) VALUES(?,?,?,?,?,?)',$values);
    } elseif($action==='saveBus') {
        $id=num('bus_id',0); $site=num('site_id'); $db->scope($user,$site);
        $branch=$db->one("SELECT * FROM sites WHERE site_id=? AND status='active'",[$site]); if(!$branch) fail('Choose an active branch.');
        $capacity=num('seats',1,100); $odo=num('odometer',0); $due=empty($_POST['service_due'])?null:day('service_due');
        if($id) {
            $old=$db->one('SELECT * FROM buses WHERE bus_id=? FOR UPDATE',[$id]); if(!$old) fail('Bus not found.',404); $db->scope($user,$old['site_id']);
            if($odo<$old['odometer']) fail('Odometer cannot decrease.');
            if($db->one("SELECT trip_id FROM schedule WHERE bus_id=? AND (status='departed' OR (status='scheduled' AND TIMESTAMP(COALESCE(arrival_date,date),arrival_time)>NOW()))",[$id])) fail('Reassign upcoming trips before editing this vehicle.',409);
        }
        $values=[val('bus',100),val('number_plate',100),$capacity,$branch['city'],$site,choice('status',['active','maintenance','retired']),$odo,$due];
        if($id) { $values[]=$id; $db->query('UPDATE buses SET bus=?,number_plate=?,seats=?,home=?,site_id=?,status=?,odometer=?,service_due=? WHERE bus_id=?',$values); }
        else $db->query('INSERT INTO buses(bus,number_plate,seats,home,site_id,status,odometer,service_due) VALUES(?,?,?,?,?,?,?,?)',$values);
    } elseif($action==='saveSchedule') {
        $id=num('trip_id',0); $old=null;
        if($id) { $old=$db->one('SELECT * FROM schedule WHERE trip_id=? FOR UPDATE',[$id]); if(!$old) fail('Trip not found.',404); $db->scope($user,$old['site_id']); $db->future($old); }
        $busId=num('bus_id'); $bus=$db->one('SELECT * FROM buses WHERE bus_id=? FOR UPDATE',[$busId]);
        if(!$bus || $bus['status']!=='active') fail('Choose an active vehicle.'); $db->scope($user,$bus['site_id']);
        if(!$db->one("SELECT site_id FROM sites WHERE site_id=? AND status='active'",[(int)$bus['site_id']])) fail('Branch is inactive.');
        $driverId=num('driver_id'); $driver=$db->one('SELECT * FROM drivers WHERE driver_id=? FOR UPDATE',[$driverId]);
        $date=day('date'); $arrivalDate=day('arrival_date'); $departure=clockValue('departure_time'); $arrival=clockValue('arrival_time');
        $start=$date.' '.$departure; $end=$arrivalDate.' '.$arrival;
        if(strtotime($start)<=time() || strtotime($end)<=strtotime($start)) fail('Departure must be in the future and arrival after departure.');
        if(!$driver || $driver['status']!=='active' || $driver['site_id']!=$bus['site_id'] || $driver['licence_expiry']<$arrivalDate) fail('Choose an active driver from the vehicle branch with a valid licence.');
        if($bus['service_due'] && $bus['service_due']<$arrivalDate) fail('Vehicle needs servicing before this trip.');
        if($db->one("SELECT trip_id FROM schedule WHERE trip_id<>? AND status IN ('scheduled','departed') AND (bus_id=? OR driver_id=?) AND TIMESTAMP(date,departure_time)<? AND TIMESTAMP(COALESCE(arrival_date,date),arrival_time)>?",[$id,$busId,$driverId,$end,$start])) fail('Vehicle or driver has an overlapping trip.',409);
        $home=val('home',100); $dest=val('destination',100); if(strcasecmp($home,$dest)===0) fail('Origin and destination must differ.');
        $booked=$id?$db->rows('SELECT seat FROM reservations WHERE trip_id=?',[$id]):[]; $taken=array_map('intval',array_column($booked,'seat'));
        if($taken && max($taken)>(int)$bus['seats']) fail('Vehicle is too small for existing bookings.');
        if($booked && ($old['bus_id']!=$busId || $old['date']!==$date || $old['departure_time']!==$departure || $old['home']!==$home || $old['destination']!==$dest)) fail('Cancel bookings before changing the vehicle, route or departure.',409);
        $seats=json_encode(array_values(array_diff(range(1,(int)$bus['seats']),$taken)));
        $values=[$busId,$home,$dest,$departure,$arrival,$date,$seats,(int)$bus['site_id'],$driverId,money('fare'),$arrivalDate];
        if($id) { $values[]=$id; $db->query('UPDATE schedule SET bus_id=?,home=?,destination=?,departure_time=?,arrival_time=?,date=?,seats=?,site_id=?,driver_id=?,fare=?,arrival_date=? WHERE trip_id=?',$values); }
        else $db->query('INSERT INTO schedule(bus_id,home,destination,departure_time,arrival_time,date,seats,site_id,driver_id,fare,arrival_date) VALUES(?,?,?,?,?,?,?,?,?,?,?)',$values);
    } elseif($action==='setTripStatus') {
        $id=num('trip_id'); $trip=$db->one('SELECT * FROM schedule WHERE trip_id=? FOR UPDATE',[$id]);
        if(!$trip) fail('Trip not found.',404); $db->scope($user,$trip['site_id']);
        $status=choice('status',['departed','completed']);
        if($status==='departed') {
            if($trip['status']!=='scheduled' || strtotime($trip['date'].' '.$trip['departure_time'])>time()) fail('A scheduled trip can depart at or after its departure time.',409);
            $vehicle=$db->one('SELECT * FROM buses WHERE bus_id=? FOR UPDATE',[(int)$trip['bus_id']]);
            $driver=$db->one('SELECT * FROM drivers WHERE driver_id=? FOR UPDATE',[(int)$trip['driver_id']]);
            if(!$vehicle || $vehicle['status']!=='active' || ($vehicle['service_due'] && $vehicle['service_due']<date('Y-m-d'))) fail('This vehicle is not cleared for departure.',409);
            if(!$driver || $driver['status']!=='active' || $driver['licence_expiry']<date('Y-m-d')) fail('Assign a driver with a valid licence before departure.',409);
        } elseif($trip['status']!=='departed' || strtotime(($trip['arrival_date']?:$trip['date']).' '.$trip['arrival_time'])>time()) fail('A departed trip can complete at or after its arrival time.',409);
        $db->query('UPDATE schedule SET status=? WHERE trip_id=?',[$status,$id]);
    } elseif($action==='cancelTrip') {
        $id=num('trip_id'); $trip=$db->one('SELECT * FROM schedule WHERE trip_id=? FOR UPDATE',[$id]); $db->future($trip); $db->scope($user,$trip['site_id']);
        if($db->one('SELECT reservation_id FROM reservations WHERE trip_id=?',[$id])) fail('Cancel the reservations before cancelling this trip.',409);
        $db->query("UPDATE schedule SET status='cancelled' WHERE trip_id=?",[$id]);
    } elseif($action==='saveMaintenance') {
        $id=num('bus_id'); $bus=$db->one('SELECT * FROM buses WHERE bus_id=? FOR UPDATE',[$id]); if(!$bus) fail('Vehicle not found.'); $db->scope($user,$bus['site_id']);
        $odo=num('odometer',0); if($odo<$bus['odometer']) fail('Odometer cannot decrease.');
        $date=day('service_date'); if($date>date('Y-m-d')) fail('Service date cannot be in the future.');
        $due=day('service_due'); if($due<=$date) fail('Next service must be after this service.');
        $db->query('INSERT INTO maintenance(bus_id,service_date,odometer,cost,notes) VALUES(?,?,?,?,?)',[$id,$date,$odo,money('cost'),val('notes',1000)]);
        $db->query('UPDATE buses SET odometer=?,service_due=? WHERE bus_id=?',[$odo,$due,$id]);
    } else fail('Unknown or retired action.',400);
    $db->audit($user,$action,json_encode(['id'=>$_POST['enquiry_id']??$_POST['trip_id']??$_POST['bus_id']??$_POST['site_id']??$_POST['driver_id']??$_POST['id']??0]));
    $db->conn->commit(); reply(['error'=>false,'message'=>'Changes saved.']);
} catch(Throwable $e) {
    if(isset($db)) { try { $db->conn->rollback(); } catch(Throwable $ignored) {} }
    if($e instanceof mysqli_sql_exception) { error_log($e->getMessage()); reply(['error'=>true,'message'=>$e->getCode()===1062?'This record already exists or the seat was just booked.':'Database unavailable. Check configuration and run the migration.'], $e->getCode()===1062?409:503); }
    $status=in_array($e->getCode(),[400,401,403,404,409,422,429],true)?$e->getCode():500;
    if($status===500) error_log((string)$e);
    reply(['error'=>true,'message'=>$status===500?'An unexpected error occurred.':$e->getMessage()],$status);
}
