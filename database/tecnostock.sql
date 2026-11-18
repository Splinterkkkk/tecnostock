CREATE TABLE usuario (
    id_usuario  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(100) NOT NULL,
    correo      VARCHAR(150) NOT NULL,
    clave       VARCHAR(255) NOT NULL,
    estado      TINYINT(1)   NOT NULL DEFAULT 1,   -- 1 = activo, 0 = inactivo
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuario_correo (correo)
) ENGINE=InnoDB;


CREATE TABLE categoria (
    id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(80)  NOT NULL,
    descripcion  VARCHAR(255) NULL,
    estado       TINYINT(1)   NOT NULL DEFAULT 1,  -- 1 = activa, 0 = inactiva
    PRIMARY KEY (id_categoria),
    UNIQUE KEY uq_categoria_nombre (nombre)
) ENGINE=InnoDB;


CREATE TABLE producto (
    id_producto  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    codigo       VARCHAR(30)   NOT NULL,
    nombre       VARCHAR(120)  NOT NULL,
    descripcion  VARCHAR(255)  NULL,
    precio       DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_actual INT           NOT NULL DEFAULT 0,
    stock_minimo INT           NOT NULL DEFAULT 0,
    estado       TINYINT(1)    NOT NULL DEFAULT 1,  -- 1 = activo, 0 = desactivado
    id_categoria INT UNSIGNED  NOT NULL,
    PRIMARY KEY (id_producto),
    UNIQUE KEY uq_producto_codigo (codigo),
    KEY idx_producto_nombre (nombre),
    KEY idx_producto_categoria (id_categoria),
    CONSTRAINT fk_producto_categoria
        FOREIGN KEY (id_categoria) REFERENCES categoria (id_categoria)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_producto_precio       CHECK (precio >= 0),
    CONSTRAINT ck_producto_stock_actual CHECK (stock_actual >= 0),
    CONSTRAINT ck_producto_stock_minimo CHECK (stock_minimo >= 0)
) ENGINE=InnoDB;


CREATE TABLE movimiento (
    id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha_hora    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo          ENUM('ENTRADA','SALIDA') NOT NULL,
    cantidad      INT          NOT NULL,
    observacion   VARCHAR(255) NULL,
    id_producto   INT UNSIGNED NOT NULL,
    id_usuario    INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_movimiento),
    KEY idx_movimiento_producto (id_producto),
    KEY idx_movimiento_usuario  (id_usuario),
    KEY idx_movimiento_fecha    (fecha_hora),
    CONSTRAINT fk_movimiento_producto
        FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimiento_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_movimiento_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB;

-- DATOS DE PRUEBA

INSERT INTO usuario (nombre, correo, clave, estado) VALUES
('Encargado de Inventario', 'admin@tecnostock.cl',
 '$2y$10$avJQBxr296LGsDcEPhzPtu14guq5/1SopzUFC4Y.y5t7TU8V298hW', 1);

INSERT INTO categoria (nombre, descripcion) VALUES
('Audio',        'Audífonos, parlantes y micrófonos'),
('Cables',       'Cables y adaptadores'),
('Periféricos',  'Mouse, teclados y webcams'),
('Almacenamiento','Pendrives, discos y tarjetas de memoria');


INSERT INTO producto
    (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria)
VALUES
('AUD-001', 'Audífonos Bluetooth',  'Inalámbricos con cancelación de ruido', 24990, 25, 5,  1, 1),
('AUD-002', 'Parlante Portátil',    'Parlante bluetooth resistente al agua',  19990, 12, 4,  1, 1),
('CAB-001', 'Cable HDMI 2m',        'Cable HDMI 4K de 2 metros',               4990,  3, 10, 1, 2),
('CAB-002', 'Cable USB-C 1m',       'Carga rápida y datos',                    3990, 40, 15, 1, 2),
('PER-001', 'Mouse Inalámbrico',    'Mouse óptico 1600 DPI',                   8990, 30, 8,  1, 3),
('PER-002', 'Teclado Mecánico',     'Teclado retroiluminado en español',      34990,  9, 3,  1, 3),
('ALM-001', 'Pendrive 64GB',        'USB 3.0',                                 7990,  4, 10, 1, 4),
('PER-003', 'Webcam HD (descontinuada)', 'Producto que ya no se comercializa', 14990, 0, 0,  0, 3);

INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, id_producto, id_usuario) VALUES
('2026-11-02 09:00:00', 'ENTRADA', 30, 'Compra inicial a proveedor',   1, 1),
('2026-11-05 15:30:00', 'SALIDA',   5, 'Venta a cliente',              1, 1),
('2026-11-03 10:00:00', 'ENTRADA', 15, 'Compra inicial a proveedor',   3, 1),
('2026-11-10 12:15:00', 'SALIDA',  12, 'Venta mayorista',              3, 1),
('2026-11-04 11:00:00', 'ENTRADA', 10, 'Reposición',                   7, 1),
('2026-11-12 17:45:00', 'SALIDA',   6, 'Venta a cliente',              7, 1);

