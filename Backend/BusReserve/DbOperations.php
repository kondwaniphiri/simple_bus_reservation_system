<?php

class DbOperations {

    private $conn;
    public $export;

    function __construct() {
        require_once dirname(__FILE__) . '/Connection.php';
        $db = new Connection();
        $this->conn = $db->connect();
    }

    // Check if user exists
    function checkUser($username) {
        $statement = $this->conn->prepare("SELECT * FROM `login` WHERE `username` = ?");
        $statement->bind_param("s", $username);
        $statement->execute();
        $statement->store_result();
        return $statement->num_rows > 0;
    }
        // Get all users
    function getUsers() {
        $statement = $this->conn->prepare("SELECT `id`, `username`, `email`, `location` FROM `login`");
        if ($statement->execute()) {
            $result = $statement->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return false;
        }
    }

    // Create new user with hashed password
    function createUser($email, $username, $pass, $location) {
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
        $statement = $this->conn->prepare("INSERT INTO `login`(`email`, `username`, `password`, `location`) VALUES (?, ?, ?, ?)");
        $statement->bind_param("ssss", $email, $username, $hashed_password, $location);
        return $statement->execute();
    }

    // Login user and verify password
    function login($email, $pass) {
        $statement = $this->conn->prepare("SELECT * FROM `login` WHERE `email` = ?");
        $statement->bind_param("s", $email);
        $statement->execute();
        $result = $statement->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($pass, $user['password'])) {
                return $user; // Successful login
            } else {
                return false; // Password incorrect
            }
        } else {
            return false; // Email not found
        }
    }




    // Get all buses
    function getBus() {
        $statement = $this->conn->prepare("SELECT * FROM `buses`");
        if ($statement->execute()) {
            $result = $statement->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return false;
        }
    }

    // Add new bus
    function addBus($bus, $numberPlate, $seats, $home) {
        $statement = $this->conn->prepare("INSERT INTO `buses`(`bus`, `number_plate`, `seats`, `home`) VALUES (?, ?, ?, ?)");
        $statement->bind_param("ssis", $bus, $numberPlate, $seats, $home);
        return $statement->execute();
    }

    // Update existing bus
    function updateBus($busId, $busName, $numberPlate, $seats, $home) {
        $statement = $this->conn->prepare("UPDATE `buses` SET `bus` = ?, `number_plate` = ?, `seats` = ?, `home` = ? WHERE `bus_id` = ?");
        $statement->bind_param("ssisi", $busName, $numberPlate, $seats, $home, $busId);
        if($statement->execute()){
            return true;
        }else{
            return false;
        }
    }

    // Delete bus
    function deleteBus($busId) {
        $statement = $this->conn->prepare("DELETE FROM `buses` WHERE `bus_id` = ?");
        $statement->bind_param("i", $busId);

        if($statement->execute()){
            return true;
        }else{
            return false;
        }
    }







    // Get all schedules
    function getSchedule() {
        $statement = $this->conn->prepare("SELECT * FROM `schedule`");
        if ($statement->execute()) {
            $result = $statement->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return false;
        }
    }

    // Add new schedule
    function addSchedule($busId, $home, $destination, $departure, $arrival, $date, $seatArray) {
        $seatsJson = $seatArray; // Convert the array to JSON
        $statement = $this->conn->prepare("INSERT INTO `schedule`(`bus_id`, `home`, `destination`, `departure_time`, `arrival_time`, `date`, `seats`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $statement->bind_param("issssss", $busId, $home, $destination, $departure, $arrival, $date, $seatsJson);
    
        return $statement->execute();
    }

    function editSchedule($tripId, $home, $destination, $departure, $arrival, $date, $seatArray) {
        $seatsJson = $seatArray; // Convert seats array to JSON
    
        $statement = $this->conn->prepare("UPDATE `schedule` 
            SET  `home` = ?, `destination` = ?, `departure_time` = ?, `arrival_time` = ?, `date` = ?, `seats` = ? 
            WHERE `trip_id` = ?");
        $statement->bind_param("ssssssi", $home, $destination, $departure, $arrival, $date, $seatsJson, $tripId);
    
        return $statement->execute();
    }

    
    function updateScheduleSeats($tripId, $seatArray) {
        $seatsJson = $seatArray; // Convert seats array to JSON
    
        $statement = $this->conn->prepare("UPDATE `schedule` SET  `seats` = ? WHERE `trip_id` = ?");
        $statement->bind_param("si", $seatsJson, $tripId);
        
        if($statement->execute()){
            return true;
        }else{
            return false;
        }
    }


    function deleteSchedule($tripId) {
        $statement = $this->conn->prepare("DELETE FROM `schedule` where `trip_id` = ?");
        $statement->bind_param("i", $tripId);

        if($statement->execute()){
            return true;
        }else{
            return false;
        }
    }
    







    // Get all reservations
    function getReservations() {
        $statement = $this->conn->prepare("SELECT * FROM `reservations`");
        if ($statement->execute()) {
            $result = $statement->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return false;
        }
    }

    // update reservation
    function updateReservation($reservation_id, $trip_Id, $seat) {
        $statement = $this->conn->prepare("UPDATE `reservations` set `trip_id` = ?, `seat` = ? where `reservation_id` = ? ");
        $statement->bind_param("iii", $trip_Id, $seat, $reservation_id);
        if($statement->execute()){
            return true;
        }else{
            return false;
        }
    }

    // add reservation

    function addReservation($trip_id, $user_id, $seat) {
        $statement = $this->conn->prepare("INSERT INTO `reservations` ( `user_id`,`trip_id`, `seat`) VALUES (?, ?, ?)");
        $statement->bind_param("iii",  $user_id, $trip_id, $seat);
        if($statement->execute()){
            return true;
        }else{
            return false;
        }
    }

    // Delete reservation
    function deleteReservation($reservation_id) {
        $statement = $this->conn->prepare("DELETE FROM `reservations` WHERE `reservation_id` = ?");
        $statement->bind_param("i", $reservation_id);
        if($statement->execute()){
            return true;
        }else{
            return false;
        }
    }


}

?>
