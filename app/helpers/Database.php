<?php
// app/helpers/Database.php
class Database {
    private $host = 'localhost';
    private $db = 'inventixor';
    private $user = 'root';
    private $pass = '';
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);
        if ($this->conn->connect_error) {
            die('Error de conexión: ' . $this->conn->connect_error);
        }
    }
}
?>
