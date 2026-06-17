-- ================================================
-- BASE DE DATOS: miCaja - Sistema POS (Local de Pollo)
-- ================================================

CREATE DATABASE IF NOT EXISTS micaja
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE micaja;

-- ================================================
-- TABLA: categorias
-- Agrupa los productos (ej: Combos, Piezas, Bebidas)
-- ================================================
CREATE TABLE categorias (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(80)  NOT NULL,
  activo      TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ================================================
-- TABLA: productos
-- Catálogo de items que vende el local
-- ================================================
CREATE TABLE productos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id  INT UNSIGNED NOT NULL,
  nombre        VARCHAR(150) NOT NULL,
  descripcion   TEXT,
  precio        DECIMAL(10,2) NOT NULL,
  imagen_url    VARCHAR(500),          -- ruta o URL de la imagen
  stock         INT          NOT NULL DEFAULT 0,
  activo        TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_producto_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ================================================
-- TABLA: ventas
-- Cabecera de cada compra realizada
-- ================================================
CREATE TABLE ventas (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  total           DECIMAL(10,2) NOT NULL,
  monto_pagado    DECIMAL(10,2) NOT NULL,         -- cuánto entregó el cliente
  vuelto          DECIMAL(10,2) GENERATED ALWAYS  -- calculado automáticamente
                    AS (monto_pagado - total) STORED,
  metodo_pago     ENUM('efectivo','tarjeta','transferencia') NOT NULL DEFAULT 'efectivo',
  estado          ENUM('pendiente','completada','anulada')   NOT NULL DEFAULT 'completada',
  cajero          VARCHAR(100),                  -- nombre o ID del cajero
  cliente         VARCHAR(120),                  -- nombre del cliente (opcional)
  nota            TEXT,
  creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ================================================
-- TABLA: venta_detalle
-- Líneas de cada venta (qué productos y cuántos)
-- ================================================
CREATE TABLE venta_detalle (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  venta_id      INT UNSIGNED  NOT NULL,
  producto_id   INT UNSIGNED  NOT NULL,
  nombre_producto VARCHAR(150) NOT NULL,  -- snapshot al momento de vender
  precio_unitario DECIMAL(10,2) NOT NULL, -- snapshot del precio
  cantidad      INT UNSIGNED  NOT NULL,
  subtotal      DECIMAL(10,2) GENERATED ALWAYS
                  AS (precio_unitario * cantidad) STORED,

  CONSTRAINT fk_detalle_venta
    FOREIGN KEY (venta_id) REFERENCES ventas(id)
    ON UPDATE CASCADE ON DELETE CASCADE,

  CONSTRAINT fk_detalle_producto
    FOREIGN KEY (producto_id) REFERENCES productos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ================================================
-- ÍNDICES
-- ================================================
CREATE INDEX idx_productos_categoria ON productos(categoria_id);
CREATE INDEX idx_productos_activo    ON productos(activo);
CREATE INDEX idx_ventas_fecha        ON ventas(creado_en);
CREATE INDEX idx_ventas_estado       ON ventas(estado);
CREATE INDEX idx_detalle_venta       ON venta_detalle(venta_id);
CREATE INDEX idx_detalle_producto    ON venta_detalle(producto_id);

-- ================================================
-- DATOS INICIALES: categorías de ejemplo
-- ================================================
INSERT INTO categorias (nombre) VALUES
  ('Combos'),
  ('Piezas de Pollo'),
  ('Acompañamientos'),
  ('Bebidas'),
  ('Postres');

-- ================================================
-- TABLA: usuarios (para login básico)
-- ================================================
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(150),
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Usuario por defecto: admin / admin (cambia la contraseña en producción)
INSERT INTO usuarios (username, password_hash, nombre) VALUES
  ('admin', '$2y$10$7fp34qb7pNqXnqW4qnKp9eSaQPyzV3suoFAqvWecgkOgu4GTgwrQG', 'Administrador'),
  ('usuario', '$2y$10$GrGPXC6PNiICLONADf9BbOQMAfKPTWtZV8XimUf5ZrUyygpsZ2U5S', 'Cajero');

-- Si prefieres crear más usuarios manualmente ejecuta:
-- INSERT INTO usuarios (username, password_hash, nombre) VALUES ('usuario2', '<hash>', 'Nombre');

-- ================================================
-- TABLA: login_intentos
-- ================================================
CREATE TABLE IF NOT EXISTS login_intentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  exitoso TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_intentos_username_ip (username, ip),
  INDEX idx_login_intentos_creado_en (creado_en)
) ENGINE=InnoDB;

