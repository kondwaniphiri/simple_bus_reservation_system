<?php

class Connection{

    private $conn;

    function __construct(){
        
    }

    function connect(){
        include_once dirname(__FILE__).'/Constants.php';
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        if(mysqli_connect_errno()){
            echo "Database failed to connect".mysqli_connect_err();
        }
        return $this->conn;
    }
}

?>