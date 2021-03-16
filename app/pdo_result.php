<?php

namespace App;

use PDO;

class pdo_result {
    //Class to mimick MySQLi fetch functions
    function __construct($stmt)
    {   
        if (!$stmt) return null;
        $this->stmt = $stmt;
        $this->num_rows = $stmt->rowCount();
    }

    function fetch_assoc(){
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    function fetch_array(){
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    function fetch_row(){
        return $this->stmt->fetch(PDO::FETCH_NUM);
    }

    function fetch_object(){
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    function fetch_all(){
        return $this->stmt->fetch(PDO::FETCH_LAZY);
    }

    function fetch(){
        return $this->stmt->fetch(PDO::FETCH_LAZY);
    }

    function fetchAllObject(){
        return $this->stmt->fetchAll(PDO::FETCH_CLASS);
    }

    function free_result(){
        return;
    }    
}
