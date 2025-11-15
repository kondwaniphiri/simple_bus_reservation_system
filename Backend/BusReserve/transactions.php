<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit();
}

include_once 'DbOperations.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $db = new DbOperations();
    $db->__construct();

    // Register user
    if ($_POST['action'] == "register") {
        if ($db->createUser($_POST['email'], $_POST['username'], $_POST['password'], $_POST['location'])) {
            $response["register"] = true;
            $response["message"] = "User Successfully added!";
        } else {
            $response["error"] = true;
            $response["message"] = "Something went wrong, try again";
        }
    }

    // Login
    if ($_POST['action'] == "login") {
        $loginResult = $db->login($_POST['email'], $_POST['password']);
        if ($loginResult) {
            $response = $loginResult;
            $response["login"] = true;
            $response["error"] = false;
            $response["message"] = "Welcome " . $response['username'];
        } else {
            $response["login"] = false;
            $response["error"] = true;
            $response["message"] = "Wrong email or password, try again";
        }
    }

        // Get users
        if ($_POST['action'] == "getUsers") {
            $users = $db->getUsers();
            if ($users) {
                $response["error"] = false;
                $response['data'] = $users;
            } else {
                $response["error"] = true;
                $response["message"] = "Something went wrong, try again";
            }
        }

        



    // Get buses
    if ($_POST['action'] == "getBus") {
        $buses = $db->getBus();
        if ($buses) {
            $response["error"] = false;
            $response['data'] = $buses;
        } else {
            $response["error"] = true;
            $response["message"] = "Something went wrong, try again";
        }
    }

        // Add a new bus
        if ($_POST['action'] == "addBus") {
            if ($db->addBus($_POST['bus'], $_POST['numberPlate'], $_POST['seats'], $_POST['home'])) {
                $response["error"] = false;
                $response["message"] = "Bus successfully added!";
            } else {
                $response["error"] = true;
                $response["message"] = "Something went wrong, try again";
            }
        }
    
        // edit buses
        if ($_POST['action'] == "editBus") {
            $buses = $db->updateBus( $_POST['bus_id'], $_POST['bus'], $_POST['number_plate'],$_POST['seats'],$_POST['home']);
            if ($buses) {
                $response["error"] = false;
                $response["message"] = "Bus succesfully updated";
                $response['data'] = $buses;
            } else {
                $response["error"] = true;
                $response["message"] = "Something went wrong, try again";
            }
        }
        
        // Delete bus

        if ($_POST['action'] == "deleteBus") {
        if ($db->deleteBus($_POST['bus_id'])) {
            $response["error"] = false;
            $response["message"] = "Bus successfully deleted!";
        } else {
            $response["error"] = true;
            $response["message"] = "Failed to delete bus, try again";
        }
    }





        // Get schedule
        if ($_POST['action'] == "getSchedule") {
            $schedule = $db->getSchedule();
            if ($schedule) {
                $response["error"] = false;
                $response['data'] = $schedule;
            } else {
                $response["error"] = true;
                $response["message"] = "Something went wrong, try again";
            }
        }

    // Edit schedule
    if ($_POST['action'] == "editSchedule") {
        if ($db->editSchedule($_POST['trip_id'], $_POST['home'], $_POST['destination'], $_POST['departure_time'], $_POST['arrival_time'], $_POST['date'], $_POST['seats'])) {
            $response["error"] = false;
            $response["message"] = "Schedule successfully updated!";
        } else {
            $response["error"] = true;
            $response["message"] = "Failed to update schedule, try again";
        }
    }

        // Add a new schedule
        if ($_POST['action'] == "addSchedule") {
            if ($db->addSchedule($_POST['bus_id'], $_POST['home'], $_POST['destination'], $_POST['departure_time'], $_POST['arrival_time'], $_POST['date'], $_POST['seats'])) {
                $response["error"] = false;
                $response["message"] = "Schedule successfully added!";
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to add schedule, try again";
            }
        }

        // Delete schedule
        if ($_POST['action'] == "deleteSchedule") {
            if ($db->deleteSchedule($_POST['trip_id'])) {
                $response["error"] = false;
                $response["message"] = "Schedule successfully deleted!";
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to delete schedule, try again";
            }
        }






    // Get reservations
    if ($_POST['action'] == "getReservations") {
        $reservations = $db->getReservations();
        if ($reservations) {
            $response["error"] = false;
            $response['data'] = $reservations;
        } else {
            $response["error"] = true;
            $response["message"] = "Something went wrong, try again";
        }
    }

    // Edit reservation
    if ($_POST['action'] == "editReservation") {
        if ($db->updateReservation($_POST['reservation_id'], $_POST['trip_id'], $_POST['seat'])) {
            $response["error"] = false;
            $response["message"] = "Reservation successfully updated!";
        } else {
            $response["error"] = true;
            $response["message"] = "Failed to update reservation, try again";
        }
    }

    // Delete reservation
    if ($_POST['action'] == "deleteReservation") {
        if ($db->deleteReservation($_POST['reservation_id'])) {
            $response["error"] = false;
            $response["message"] = "Reservation successfully deleted!";
        } else {
            $response["error"] = true;
            $response["message"] = "Failed to delete reservation, try again";
        }
    }


    if($_POST['action'] == "addReservation"){
        if ($db->addReservation($_POST['trip_id'],$_POST['user_id'],$_POST['seat'])){
            $response["error"] = false;
            $response["message"] = "Reservation successfully added!";
        }else{

        }
    }


    if($_POST['action'] == "updateScheduleSeats"){

        if ($db->updateScheduleSeats($_POST['trip_id'], $_POST['seats'])) {
            $response["error"] = false;
            $response["message"] = "Schedule successfully updated!";
        } else {
            $response["error"] = true;
            $response["message"] = "Failed to update schedule, try again";
        }
    }


}

echo json_encode($response);

?>
