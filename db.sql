-- ...otras tablas...

-- Tabla Users
DROP TABLE IF EXISTS Users;
CREATE TABLE Users (
    num_doc BIGINT PRIMARY KEY,
    tipo_documento INT,
    apellidos VARCHAR(100),
    nombres VARCHAR(100),
    telefono BIGINT,
    correo VARCHAR(100),
    cargo VARCHAR(50),
    rol VARCHAR(20),
    contrasena VARCHAR(255)
);

-- Usuarios de ejemplo para pruebas de roles y autorizaciones
INSERT INTO Users (num_doc, tipo_documento, apellidos, nombres, telefono, correo, cargo, rol, contrasena) VALUES
    (1001, 1, 'Admin', 'Inventixor', 3001112233, 'admin@inventixor.com', 'Administrador General', 'admin', '$2y$10$adminhash'),
    (1002, 1, 'Coordinador', 'Inventixor', 3002223344, 'coordinador@inventixor.com', 'Coordinador de Inventario', 'coordinador', '$2y$10$coordhash');
-- Base de datos: inventixor
CREATE DATABASE IF NOT EXISTS inventixor;
USE inventixor;

-- Tabla Categoria
DROP TABLE IF EXISTS Categoria;
CREATE TABLE Categoria (
    id_categ INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255)
);

-- Tabla Subcategoria
DROP TABLE IF EXISTS Subcategoria;
CREATE TABLE Subcategoria (
    id_subcg INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    id_categ INT,
    FOREIGN KEY (id_categ) REFERENCES Categoria(id_categ)
);

-- Tabla Proveedores
DROP TABLE IF EXISTS Proveedores;
CREATE TABLE Proveedores (
    id_nit INT AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(100) NOT NULL,
    contacto VARCHAR(100),
    direccion VARCHAR(255),
    correo VARCHAR(100),
    telefono VARCHAR(20),
    estado VARCHAR(20),
    detalles VARCHAR(255)
);

-- Tabla Users
DROP TABLE IF EXISTS Users;
CREATE TABLE Users (
    num_doc BIGINT PRIMARY KEY,
    tipo_documento INT,
    apellidos VARCHAR(100),
    nombres VARCHAR(100),
    telefono BIGINT,
    correo VARCHAR(100),
    cargo VARCHAR(50),
    rol VARCHAR(20),
    contrasena VARCHAR(255)
);

-- Tabla Productos
DROP TABLE IF EXISTS Productos;
CREATE TABLE Productos (
    id_prod INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    modelo VARCHAR(100),
    talla VARCHAR(50),
    color VARCHAR(50),
    stock VARCHAR(20),
    fecha_ing DATE,
    material VARCHAR(100),
    id_subcg INT,
    id_nit INT,
    num_doc BIGINT,
    FOREIGN KEY (id_subcg) REFERENCES Subcategoria(id_subcg),
    FOREIGN KEY (id_nit) REFERENCES Proveedores(id_nit),
    FOREIGN KEY (num_doc) REFERENCES Users(num_doc)
);

-- Tabla Alertas
DROP TABLE IF EXISTS Alertas;
CREATE TABLE Alertas (
    id_alerta INT AUTO_INCREMENT PRIMARY KEY,
    tipo_alerta VARCHAR(100),
    observacion VARCHAR(255),
    nivel_alerta VARCHAR(50),
    fecha_generacion DATE,
    estado VARCHAR(20),
    id_prod INT,
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod)
);

-- Tabla Salidas
DROP TABLE IF EXISTS Salidas;
CREATE TABLE Salidas (
    id_salida INT AUTO_INCREMENT PRIMARY KEY,
    tipo_salida VARCHAR(100),
    fecha_hora DATETIME,
    cantidad VARCHAR(20),
    observacion VARCHAR(255),
    id_prod INT,
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod)
);

-- Tabla Reportes
DROP TABLE IF EXISTS Reportes;
CREATE TABLE Reportes (
    id_repor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    descripcion VARCHAR(255),
    fecha_hora DATETIME,
    num_doc BIGINT,
    id_nit INT,
    id_prod INT,
    id_alerta INT,
    FOREIGN KEY (num_doc) REFERENCES Users(num_doc),
    FOREIGN KEY (id_nit) REFERENCES Proveedores(id_nit),
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod),
    FOREIGN KEY (id_alerta) REFERENCES Alertas(id_alerta)
);

-- Tabla Autorizaciones
DROP TABLE IF EXISTS Autorizaciones;
CREATE TABLE IF NOT EXISTS Autorizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modulo VARCHAR(50) NOT NULL,
    id_registro INT NOT NULL,
    usuario_solicita BIGINT NOT NULL,
    usuario_autoriza BIGINT,
    estado VARCHAR(20) DEFAULT 'pendiente',
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta DATETIME,
    comentario VARCHAR(255),
    FOREIGN KEY (usuario_solicita) REFERENCES Users(num_doc),
    FOREIGN KEY (usuario_autoriza) REFERENCES Users(num_doc)
);