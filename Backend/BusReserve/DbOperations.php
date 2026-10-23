<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
class DbOperations {
    public $conn;
    function __construct() { require_once __DIR__.'/Connection.php'; $this->conn=(new Connection())->connect(); }
    function query($sql, $values=[]) {
        $s=$this->conn->prepare($sql);
        if ($values) { $types=''; foreach($values as $v) $types.=is_int($v)?'i':'s'; $s->bind_param($types,...$values); }
        $s->execute(); return $s;
    }
    function rows($sql,$values=[]) { return $this->query($sql,$values)->get_result()->fetch_all(MYSQLI_ASSOC); }
    function one($sql,$values=[]) { return $this->rows($sql,$values)[0] ?? null; }
    function audit($user,$action,$details) { $this->query('INSERT INTO audit_log(user_id,action,details) VALUES(?,?,?)',[(int)$user['id'],$action,$details]); }
    function scope($user,$site) {
        if ($user['position']!=='admin' && ($user['position']!=='manager' || !$user['site_id'] || (int)$site!==(int)$user['site_id'])) throw new RuntimeException('This branch is outside your access.',403);
    }
    function staff($user) { if (!in_array($user['position'],['admin','manager'],true)) throw new RuntimeException('Staff access required.',403); }
    function future($trip) {
        if (!$trip || $trip['status']!=='scheduled' || strtotime($trip['date'].' '.$trip['departure_time'])<=time()) throw new RuntimeException('This trip is no longer open for booking.',409);
    }
    // All seat changes lock schedules first, in ascending order, then reservations.
    function reserve($user,$tripId,$seat,$reservationId=0,$customer=0) {
        $this->conn->begin_transaction();
        try {
            $old=$reservationId ? $this->one('SELECT * FROM reservations WHERE reservation_id=?',[$reservationId]) : null;
            if ($reservationId && !$old) throw new RuntimeException('Reservation not found.',404);
            $ids=[$tripId]; if($old) $ids[]=(int)$old['trip_id']; $ids=array_unique($ids); sort($ids);
            $locked=[]; foreach($ids as $id) $locked[$id]=$this->one('SELECT * FROM schedule WHERE trip_id=? FOR UPDATE',[$id]);
            $trip=$locked[$tripId]; $this->future($trip);
            if (!$this->one("SELECT site_id FROM sites WHERE site_id=? AND status='active'",[(int)$trip['site_id']])) throw new RuntimeException('This branch is not accepting bookings.',409);
            if($user['position']==='admin' || $user['position']==='manager') $this->scope($user,$trip['site_id']);
            if($old) {
                $current=$this->one('SELECT * FROM reservations WHERE reservation_id=? FOR UPDATE',[$reservationId]);
                if (!$current || $current['trip_id']!=$old['trip_id'] || $current['seat']!=$old['seat']) throw new RuntimeException('Reservation changed. Refresh and retry.',409);
                $this->staff($user); $this->scope($user,$locked[$old['trip_id']]['site_id']); $this->future($locked[$old['trip_id']]);
            }
            $bus=$this->one('SELECT * FROM buses WHERE bus_id=? FOR UPDATE',[(int)$trip['bus_id']]);
            if (!$bus || $bus['status']!=='active' || ($bus['service_due'] && $bus['service_due']<$trip['date'])) throw new RuntimeException('This vehicle is unavailable or needs servicing.',409);
            if($seat<1 || $seat>(int)$bus['seats']) throw new RuntimeException('Invalid seat number.',422);
            $seats=array_map('intval',json_decode($trip['seats'],true));
            $same=$old && $old['trip_id']==$tripId && $old['seat']==$seat;
            if(!$same && !in_array($seat,$seats,true)) throw new RuntimeException('This seat has already been booked. Choose another.',409);
            if(!$same) {
                if($old) { $oldSeats=array_map('intval',json_decode($locked[$old['trip_id']]['seats'],true)); $oldSeats[]=(int)$old['seat']; $oldSeats=array_values(array_unique($oldSeats)); sort($oldSeats); $this->query('UPDATE schedule SET seats=? WHERE trip_id=?',[json_encode($oldSeats),(int)$old['trip_id']]); if($old['trip_id']==$tripId) $seats=$oldSeats; }
                $seats=array_values(array_diff($seats,[$seat]));
                $this->query('UPDATE schedule SET seats=? WHERE trip_id=?',[json_encode($seats),$tripId]);
            }
            if($old) $this->query('UPDATE reservations SET trip_id=?,seat=?,amount=? WHERE reservation_id=?',[$tripId,$seat,$trip['fare'],$reservationId]);
            else {
                $customer=$user['position']==='admin' || $user['position']==='manager' ? $customer : (int)$user['id'];
                if(!$this->one('SELECT id FROM login WHERE id=?',[$customer])) throw new RuntimeException('Customer not found.',422);
                $this->query('INSERT INTO reservations(user_id,trip_id,seat,amount) VALUES(?,?,?,?)',[$customer,$tripId,$seat,$trip['fare']]);
                $reservationId=$this->conn->insert_id;
            }
            $this->audit($user,$old?'editReservation':'addReservation',(string)$reservationId);
            $this->conn->commit(); return $reservationId;
        } catch(Throwable $e) { $this->conn->rollback(); throw $e; }
    }
    function cancel($user,$id) {
        $this->conn->begin_transaction();
        try {
            $r=$this->one('SELECT * FROM reservations WHERE reservation_id=?',[$id]);
            if(!$r) throw new RuntimeException('Reservation not found.',404);
            $trip=$this->one('SELECT * FROM schedule WHERE trip_id=? FOR UPDATE',[(int)$r['trip_id']]);
            $current=$this->one('SELECT * FROM reservations WHERE reservation_id=? FOR UPDATE',[$id]);
            if(!$current || $current['trip_id']!=$r['trip_id'] || $current['seat']!=$r['seat']) throw new RuntimeException('Reservation changed. Refresh and retry.',409);
            if(in_array($user['position'],['admin','manager'],true)) $this->scope($user,$trip['site_id']);
            elseif($r['user_id']!=$user['id']) throw new RuntimeException('Access denied.',403);
            $this->future($trip);
            $seats=json_decode($trip['seats'],true); $seats[]=(int)$r['seat']; $seats=array_values(array_unique(array_map('intval',$seats))); sort($seats);
            $this->query('UPDATE schedule SET seats=? WHERE trip_id=?',[json_encode($seats),(int)$r['trip_id']]);
            $this->query('DELETE FROM reservations WHERE reservation_id=?',[$id]);
            $this->audit($user,'cancelReservation',json_encode($r)); $this->conn->commit();
        } catch(Throwable $e) { $this->conn->rollback(); throw $e; }
    }
}
