<?php

namespace Controller\Mysqli;

use \Controller\Http\WebRoot;

class Database extends WebRoot
{

    // mysqli connection
    public $conn;

    // Database class construct
    public function __construct()
    {
        // import and load config file
        $this->getConfigFile();

        // create new mysqli connection and save connection
        $this->conn = new \mysqli(SERVER, USER, PASSWORD, DATABASE);
    }

    // direct execute sql statement
    public function execute_SQL($sql)
    {
        return ($this->conn->query(trim($sql)));
    }

    // escape string
    public function escape_string($string)
    {
        return (trim($this->conn->real_escape_string($string)));
    }

    // encrypt string data using open ssl
    public function encrypt_string($string, $encryption_key = "", $ciphering_value = "AES-128-CTR")
    {
        return (openssl_encrypt(trim($string), $ciphering_value, $encryption_key));
    }

    // decryption string data using openssl
    public function decrypt_string($encrypted_value, $dencryption_key = "", $ciphering_value = "AES-128-CTR")
    {
        return (openssl_decrypt($encrypted_value, $ciphering_value, $dencryption_key));
    }

    // msqli error handling
    public function log_error()
    {
        // return array of list of mysqli error
        return ($this->conn->error_list);
    }

    // database connection disconnect
    public function disconnect()
    {
        if ($this->conn->close()) {

            // if connection was closed
            return (array("success" => true, "mssg" => "Successfully disconnect mysqli database connection"));
        } else {

            // if connect was not closed due to an error
            return (array("success" => false, "mssg" => $this->log_error()));
        }
    }
}
