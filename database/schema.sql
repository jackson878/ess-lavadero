-- =====================================================================
--  ESS-Lavadero - Esquema de base de datos (MySQL / MariaDB)
--  Proyecto: Sistema de Información para lavadero de carros y motos
--
--  Cómo usarlo:
--    1) phpMyAdmin > Importar > este archivo, o
--    2) Consola:  mysql -u root < database/schema.sql
--  Después cargue los datos de prueba con database/seed.sql
--
--  ATENCIÓN: este script BORRA y vuelve a crear la base ess_lavadero.
-- =====================================================================

DROP DATABASE IF EXISTS ess_lavadero;
CREATE DATABASE ess_lavadero
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ess_lavadero;

-- ---------------------------------------------------------------------
-- 1. USUARIOS: personas que entran al sistema y su rol
--    dueno   = acceso total
--    cajero  = clientes, vehículos, órdenes, caja
--    lavador = solo su cola de vehículos
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    usuario         VARCHAR(50)  NOT NULL,              -- nombre de inicio de sesión
    password_hash   VARCHAR(255) NOT NULL,              -- bcrypt (password_hash de PHP)
    rol             ENUM('dueno','cajero','lavador') NOT NULL,
    telefono        VARCHAR(20)  NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,    -- 0 = no puede iniciar sesión
    ultimo_acceso   DATETIME     NULL,
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_usuario (usuario),
    KEY idx_usuarios_rol (rol)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. CLIENTES
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    telefono        VARCHAR(20)  NOT NULL,              -- celular (10 dígitos) para WhatsApp
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_clientes_nombre (nombre),
    KEY idx_clientes_telefono (telefono)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. VEHÍCULOS: cada vehículo pertenece a un cliente; la placa es única
-- ---------------------------------------------------------------------
CREATE TABLE vehiculos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id      INT UNSIGNED NOT NULL,
    placa           VARCHAR(10)  NOT NULL,              -- se guarda en MAYÚSCULAS sin espacios
    tipo            ENUM('carro','moto') NOT NULL,
    marca           VARCHAR(50)  NULL,
    color           VARCHAR(30)  NULL,
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_vehiculos_placa (placa),
    KEY idx_vehiculos_tipo (tipo),
    CONSTRAINT fk_vehiculos_cliente FOREIGN KEY (cliente_id)
        REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. CATÁLOGO DE SERVICIOS: lo que vende el lavadero
--    Precios en pesos colombianos (sin decimales en la práctica)
-- ---------------------------------------------------------------------
CREATE TABLE servicios_catalogo (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    tipo_vehiculo   ENUM('carro','moto') NOT NULL,
    precio          DECIMAL(12,2) NOT NULL,
    duracion_min    SMALLINT UNSIGNED NOT NULL,         -- duración estimada en minutos
    descripcion     VARCHAR(255) NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_servicio_nombre_tipo (nombre, tipo_vehiculo),
    KEY idx_servicios_tipo (tipo_vehiculo, activo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. ÓRDENES: un ingreso de un vehículo al lavadero
--    Flujo de estados: en_espera -> en_lavado -> terminado -> entregado
--    (cancelada se deja por si el cliente se va antes de lavar)
-- ---------------------------------------------------------------------
CREATE TABLE ordenes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id     INT UNSIGNED NOT NULL,
    lavador_id      INT UNSIGNED NOT NULL,              -- usuario con rol lavador
    creado_por      INT UNSIGNED NOT NULL,              -- cajero o dueño que registró
    estado          ENUM('en_espera','en_lavado','terminado','entregado','cancelada')
                    NOT NULL DEFAULT 'en_espera',
    total           DECIMAL(12,2) NOT NULL DEFAULT 0,   -- suma de orden_detalle
    observaciones   VARCHAR(255) NULL,
    fecha_ingreso   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ordenes_fecha (fecha_ingreso),
    KEY idx_ordenes_estado (estado),
    KEY idx_ordenes_lavador_estado (lavador_id, estado),
    CONSTRAINT fk_ordenes_vehiculo FOREIGN KEY (vehiculo_id)
        REFERENCES vehiculos (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ordenes_lavador FOREIGN KEY (lavador_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ordenes_creado_por FOREIGN KEY (creado_por)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. DETALLE DE LA ORDEN: los servicios incluidos en cada orden
--    Se copia el precio del momento para que cambios futuros del
--    catálogo no alteren ventas pasadas.
-- ---------------------------------------------------------------------
CREATE TABLE orden_detalle (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id        INT UNSIGNED NOT NULL,
    servicio_id     INT UNSIGNED NOT NULL,
    precio          DECIMAL(12,2) NOT NULL,             -- precio cobrado (copia del catálogo)
    UNIQUE KEY uq_detalle_orden_servicio (orden_id, servicio_id),
    KEY idx_detalle_servicio (servicio_id),
    CONSTRAINT fk_detalle_orden FOREIGN KEY (orden_id)
        REFERENCES ordenes (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detalle_servicio FOREIGN KEY (servicio_id)
        REFERENCES servicios_catalogo (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. HISTORIAL DE ESTADOS: fecha y hora de cada cambio de estado
--    Sirve para el KPI "tiempo promedio de atención".
-- ---------------------------------------------------------------------
CREATE TABLE orden_estados (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id        INT UNSIGNED NOT NULL,
    estado          ENUM('en_espera','en_lavado','terminado','entregado','cancelada') NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL,              -- quién hizo el cambio
    fecha_hora      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_estados_orden (orden_id, estado),
    KEY idx_estados_fecha (fecha_hora),
    CONSTRAINT fk_estados_orden FOREIGN KEY (orden_id)
        REFERENCES ordenes (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_estados_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. CAJA: una apertura/cierre por día
--    efectivo_esperado = base_inicial + pagos en efectivo del día
--    diferencia        = efectivo_contado - efectivo_esperado
-- ---------------------------------------------------------------------
CREATE TABLE caja_aperturas_cierres (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fecha                DATE          NOT NULL,
    usuario_apertura_id  INT UNSIGNED  NOT NULL,
    fecha_apertura       DATETIME      NOT NULL,
    base_inicial         DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado               ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
    usuario_cierre_id    INT UNSIGNED  NULL,
    fecha_cierre         DATETIME      NULL,
    total_efectivo       DECIMAL(12,2) NULL,            -- ventas en efectivo
    total_transferencia  DECIMAL(12,2) NULL,            -- Nequi / transferencia
    total_tarjeta        DECIMAL(12,2) NULL,
    total_ventas         DECIMAL(12,2) NULL,            -- suma de los tres medios
    efectivo_esperado    DECIMAL(12,2) NULL,
    efectivo_contado     DECIMAL(12,2) NULL,
    diferencia           DECIMAL(12,2) NULL,            -- negativo = faltante
    observaciones        VARCHAR(255)  NULL,
    UNIQUE KEY uq_caja_fecha (fecha),
    KEY idx_caja_estado (estado),
    CONSTRAINT fk_caja_usuario_apertura FOREIGN KEY (usuario_apertura_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_caja_usuario_cierre FOREIGN KEY (usuario_cierre_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. PAGOS: cobro de una orden terminada (un pago por orden)
--    Todo pago queda amarrado a la caja abierta de ese día.
-- ---------------------------------------------------------------------
CREATE TABLE pagos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id        INT UNSIGNED  NOT NULL,
    caja_id         INT UNSIGNED  NOT NULL,
    metodo          ENUM('efectivo','transferencia','tarjeta') NOT NULL,
    valor           DECIMAL(12,2) NOT NULL,
    referencia      VARCHAR(50)   NULL,                 -- nro. de comprobante Nequi/tarjeta
    usuario_id      INT UNSIGNED  NOT NULL,             -- cajero que cobró
    fecha_hora      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pagos_orden (orden_id),
    KEY idx_pagos_fecha (fecha_hora),
    KEY idx_pagos_caja_metodo (caja_id, metodo),
    CONSTRAINT fk_pagos_orden FOREIGN KEY (orden_id)
        REFERENCES ordenes (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pagos_caja FOREIGN KEY (caja_id)
        REFERENCES caja_aperturas_cierres (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pagos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. INSUMOS: shampoo, cera, silicona, toallas...
--     stock_actual se actualiza con cada movimiento.
-- ---------------------------------------------------------------------
CREATE TABLE insumos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)  NOT NULL,
    unidad          VARCHAR(20)   NOT NULL,             -- litro, galón, unidad, kg...
    stock_actual    DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_minimo    DECIMAL(10,2) NOT NULL DEFAULT 0,   -- por debajo de esto = alerta
    costo_unitario  DECIMAL(12,2) NOT NULL DEFAULT 0,
    activo          TINYINT(1)    NOT NULL DEFAULT 1,
    creado_en       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME      NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_insumos_nombre (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. MOVIMIENTOS DE INSUMO: entradas (compras) y salidas (consumo)
-- ---------------------------------------------------------------------
CREATE TABLE movimientos_insumo (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    insumo_id       INT UNSIGNED  NOT NULL,
    tipo            ENUM('entrada','salida') NOT NULL,
    cantidad        DECIMAL(10,2) NOT NULL,             -- siempre positiva
    motivo          VARCHAR(150)  NULL,
    usuario_id      INT UNSIGNED  NOT NULL,
    fecha_hora      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mov_insumo_fecha (insumo_id, fecha_hora),
    CONSTRAINT fk_mov_insumo FOREIGN KEY (insumo_id)
        REFERENCES insumos (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. EQUIPOS: hidrolavadoras, aspiradoras, compresor...
-- ---------------------------------------------------------------------
CREATE TABLE equipos (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre              VARCHAR(100) NOT NULL,
    tipo                ENUM('hidrolavadora','aspiradora','compresor','pulidora','otro') NOT NULL,
    marca               VARCHAR(50)  NULL,
    serial              VARCHAR(50)  NULL,
    estado              ENUM('operativo','en_mantenimiento','fuera_de_servicio')
                        NOT NULL DEFAULT 'operativo',
    fecha_compra        DATE NULL,
    fecha_ultimo_mant   DATE NULL,                      -- se actualiza al registrar mantenimiento
    fecha_proximo_mant  DATE NULL,
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_equipos_serial (serial),
    KEY idx_equipos_proximo (fecha_proximo_mant)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 13. MANTENIMIENTOS: historial de mantenimientos de cada equipo
-- ---------------------------------------------------------------------
CREATE TABLE mantenimientos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipo_id       INT UNSIGNED  NOT NULL,
    fecha           DATE          NOT NULL,
    tipo            ENUM('preventivo','correctivo') NOT NULL,
    descripcion     VARCHAR(255)  NOT NULL,
    costo           DECIMAL(12,2) NOT NULL DEFAULT 0,
    proxima_fecha   DATE          NULL,
    usuario_id      INT UNSIGNED  NOT NULL,
    creado_en       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mant_equipo_fecha (equipo_id, fecha),
    CONSTRAINT fk_mant_equipo FOREIGN KEY (equipo_id)
        REFERENCES equipos (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_mant_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
