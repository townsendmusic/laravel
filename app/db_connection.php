<?php

namespace App;

use DB;

class db_connection
{
    public function connect()
    {
        $this->conn = DB::connection()->getPdo();
    }

    public function write_connect()
    {
        $this->conn = DB::connection()->getPdo();
    }

    public function getDefaultTransactionSettings()
    {
        $settings = [
            'allowed_to_fail' => false,
            'allowed_max_affected_rows' => 1,
            'allowed_to_affect_zero' => true
        ];

        return $settings;
    }

    //Turn on the autocommit false and enable prepared statements for any queries
    public function safetyNet()
    {
        if (!isset($this->conn_write)) {
            $this->write_connect();
        }

        $this->conn_write->beginTransaction();
        $this->safety = true;
    }

    public function beginTransaction()
    {
        if (!isset($this->conn_write)) {
            $this->write_connect();
        }

        $this->conn_write->beginTransaction();
        $this->transaction = true;
    }

    public function query($query, $values = '', $settings_override = [])
    {
        $settings = $this->getDefaultTransactionSettings();
        if (is_array($settings_override) && count($settings_override) > 0) {
            foreach ($settings_override as $key => $value) {
                $settings[$key] = $value;
            }
        }

        //if (!stristr($query,"") && !stristr($query,"")) error_log("QUERY WITHOUT DB: $query");
        global $updated_tables;

        $query_type = substr(trim($query), 0, 6);

        if (isset($_COOKIE['debugcheck']) && $_COOKIE['debugcheck'] != ''  && $_COOKIE['debugcheck'] != 'off') {
            $this->debug = true;
        }

        if ($values && !empty($values)) {
            if (!is_array($values)) {
                $values = [$values];
            } //Convert string to array

            //Run 2 stripslashes on passed values in case they've' been escaped elsewhere
            $stripped_values = [];
            foreach ($values as $value) {
                $stripped_values[] = stripslashes(stripslashes($value));
            }
            $values = $stripped_values;
        }

        if (strtoupper($query_type) != "SELECT") {
            if (!isset($this->conn_write)) {
                $this->write_connect();
            }

            if (isset($this->safety)) {//Run safety checks if net is set

                try {
                    if (!$values) {
                        //$result = $this->conn_write->query($query);//Use non-prepared query if no values to bind
                        $this->stmt = $this->prepareLegacyQuery($query, $this->conn_write);
                    } else {
                        $this->stmt = $this->conn_write->prepare($query);

                        if (!$this->stmt && $settings['allowed_to_fail'] == false) {
                            $error = $this->conn_write->errorInfo();
                            throw new Exception($error[2]);
                        }

                        $this->stmt->execute($values);
                    }

                    if ($this->stmt->rowCount() < 1 && $settings['allowed_to_affect_zero'] == false) {
                        throw new Exception("Query affected no rows ($query).");
                    }

                    if ($this->stmt->rowCount() > $settings['allowed_max_affected_rows']) {
                        throw new Exception("Query affected to many rows. Allowed value:{$settings['allowed_max_affected_rows']}; Affected value:".$this->stmt->rowCount()." ($query).");
                    }

                    $result = new pdo_result($this->stmt);

                    $this->affected_rows = $this->stmt->rowCount();
                } catch (Exception $e) {//If any safety checks fail then rollback queries and pass on the failed exception
                    $this->conn_write->rollback();
                    throw $e;
                }
            } else {
                if (!$values) {
                    $result = new pdo_result($this->prepareLegacyQuery($query, $this->conn_write));
                } else {
                    $stmt = $this->conn_write->prepare($query);
                    if (!$stmt) {
                        $error = $this->conn_write->errorInfo();
                        throw new Exception("$error[2]");
                        die;
                    }
                    $stmt->execute($values);
                    $result = new pdo_result($stmt);

                    $this->affected_rows = $stmt->rowCount();
                }
            }

            if (isset($updated_tables) && is_array($updated_tables)) {
                $updated_tables = array_replace($updated_tables, $this->getTables($query, "write"));
            } else {
                $updated_tables = $this->getTables($query, "write");
            }

            $this->insert_id = $this->conn_write->lastInsertId();
            $this->error = $this->conn_write->errorInfo();

            return $result;
        }

        if (!isset($this->conn)) {
            $this->connect();
        }

        if (isset($_GET['dbtest'])) {
            $db_time = microtime(true);
        }

        //Check if something has been written in the last 100 millseconds and use write instead of read if true to avoid replication lag
        $use_conn = ($this->useWriteConnection($query) && isset($this->conn_write)) ? $this->conn_write : $this->conn;

        if (!$values) {
            $result = new pdo_result($this->prepareLegacyQuery($query, $use_conn));
        } else {
            $stmt = $use_conn->prepare($query);
            if (!$stmt) {
                $error = $use_conn->errorInfo();
                throw new Exception("$error[2] $query");
                die;
            }
            $stmt->execute($values);
            $result = new pdo_result($stmt);
        }

        if (isset($_GET['dbtest'])) {
            $this->run_queries[] = str_replace("\n", "", str_replace("\r", "", $query));
            $this->query_times[] = microtime(true) - $db_time . ' second(s)';
        }

        $this->error = $this->conn->errorInfo();

        return $result;
    }

    public function rollback()
    {
        $this->conn_write->rollback();
    }

    //Commit queires run through the safety net if all have run successfully and turn off safety net switch
    public function commit()
    {
        $this->stmt = null;
        if (isset($this->safety)) {
            unset($this->safety);
        }
        if (isset($this->transaction)) {
            unset($this->transaction);
        }
        return $this->conn_write->commit();
    }

    public function prepareLegacyQuery($query, $conn)
    {
        //Regex to pull values within single quotes but not ignores escaped quotes within them
        $regex = "/'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'/s";

        $new_query = preg_replace($regex, "?", $query);//Return new query with ? values
        preg_match_all($regex, $query, $values, PREG_PATTERN_ORDER);//Return an array of the retrieved values

        //Format the new values into an array suitable for execute
        $new_values = [];
        foreach ($values[0] as $value) {
            $new_values[] = stripslashes(trim($value, "'"));
        }

        $stmt = $conn->prepare($new_query);

        if (!$stmt) {
            $error = $conn->errorInfo();
            throw new Exception("$error[2] $query");
        }
        $stmt->execute($new_values);

        return $stmt;
    }

    public function getTables($query, $type)
    {
        $tables = [];

        $x = 0;
        $query2 = $query;

        do {
            $one = stristr($query, 'UPDATE ');
            $length = 7;
            if (!$one){
                $one = stristr($query, 'INSERT INTO ');
                $length = 12;
            }
            if (!$one){
                $one = stristr($query, 'FROM ');
                $length = 5;
            }
            if (isset($one) && $one != '') {

                $one = substr($one,$length);

                if (strstr($one, ' ')) {
                    $table = substr($one, 0, strpos($one, " "));
                } else {
                    $table = $one;
                }

                $tables[$table] = microtime(true);
                //error_log("$type - ".substr($one,0,strpos($one," "))." - ".microtime(true));
            }

            if (strstr($one, ' ')) {
                $query = substr($one, strpos($one, " "));
            } else {
                $query = '';
            }

            $x++;
            if ($x > 20) {
                if ($x > 30) {
                    die;
                }
            }
        } while ($one != false);

        do {
            $one = stristr($query2, 'JOIN ');
            if (isset($one) && $one != '') {

                $one = substr($one,5);

                if (strstr($one, ' ')) {
                    $table = substr($one, 0, strpos($one, " "));
                } else {
                    $table = $one;
                }

                $tables[$table] = microtime(true);
            }

            if (strstr($one, ' ')) {
                $query2 = substr($one, strpos($one, " "));
            } else {
                $query2 = '';
            }

            $x++;
            if ($x > 20) {
                if ($x > 30) {
                    die;
                }
            }
        } while ($one != false);

        return $tables;
    }

    public function useWriteConnection($query)
    {
        global $updated_tables;

        //Ensure a write connection is used when using transactions
        if (isset($this->transaction)) {
            return true;
        }

        $check_time = 100;//Time in milliseconds.  Use write connection if difference between queries is below this

        if (isset($updated_tables) && is_array($updated_tables)) {
            $write_tables = $updated_tables;
            $tables = $this->getTables($query, "read");

            foreach ($tables as $table => $time) {
                if (isset($write_tables[$table])) {
                    $difference = ($time*1000)-($write_tables[$table]*1000);
                    //error_log("$table $difference $check_time");
                    if ($difference < $check_time) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function real_escape_string($string)
    {
        if (is_array($string)) {
            return;
        }
        
        return addslashes($string);
    }
}
