<?php
// app/models/User.php
require_once 'app/helpers/Database.php';

class User {
    private $db;
    public function __construct() {
        $this->db = (new Database())->conn;
    }
    public function login($username, $password) {
        $sql = "SELECT * FROM Users WHERE correo = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['contrasena'])) {
                return $row;
            }
        }
        return false;
    }
}
?>