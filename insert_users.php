<?php
// script para insertar los usuarios iniciales en la tabla Users
// Ejecutar desde la raíz del proyecto: php insert_users.php

require_once 'app/helpers/Database.php';

function insertarUsuarios($conn) {
    $usuarios = [
        [
            'num_doc' => 1001,
            'tipo_documento' => 1,
            'apellidos' => 'Administrador',
            'nombres' => 'Admin',
            'telefono' => 3001111111,
            'correo' => 'admin',
            'cargo' => 'Administrador',
            'rol' => 'admin',
            'contrasena' => password_hash('admin123', PASSWORD_DEFAULT)
        ],
        [
            'num_doc' => 1002,
            'tipo_documento' => 1,
            'apellidos' => 'Coordinador',
            'nombres' => 'Coordi',
            'telefono' => 3002222222,
            'correo' => 'coordinador',
            'cargo' => 'Coordinador',
            'rol' => 'coordinador',
            'contrasena' => password_hash('coordi123', PASSWORD_DEFAULT)
        ],
        [
            'num_doc' => 1003,
            'tipo_documento' => 1,
            'apellidos' => 'Auxiliar',
            'nombres' => 'Auxi',
            'telefono' => 3003333333,
            'correo' => 'auxiliar',
            'cargo' => 'Auxiliar',
            'rol' => 'auxiliar',
            'contrasena' => password_hash('auxi123', PASSWORD_DEFAULT)
        ]
    ];

    foreach ($usuarios as $u) {
        $sql = "INSERT INTO Users (num_doc, tipo_documento, apellidos, nombres, telefono, correo, cargo, rol, contrasena) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iississss', $u['num_doc'], $u['tipo_documento'], $u['apellidos'], $u['nombres'], $u['telefono'], $u['correo'], $u['cargo'], $u['rol'], $u['contrasena']);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo "Usuario {$u['nombres']} insertado correctamente.\n";
        } else {
            echo "Error al insertar usuario {$u['nombres']}.\n";
        }
        $stmt->close();
    }
}

$db = new Database();
insertarUsuarios($db->conn);
?>