-- Migración: crear tablas para cotizaciones
CREATE TABLE IF NOT EXISTS cotizaciones (
  id_cotizacion INT AUTO_INCREMENT PRIMARY KEY,
  id_paciente INT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  vigencia_dias INT DEFAULT 15,
  subtotal DECIMAL(10,2) DEFAULT 0,
  descuento_tipo ENUM('fijo','porcentaje') DEFAULT 'fijo',
  descuento_valor DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2) DEFAULT 0,
  estado VARCHAR(20) DEFAULT 'borrador',
  moneda VARCHAR(10) DEFAULT 'S/.',
  usuario_id INT NULL,
  observaciones TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cotizaciones_detalle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_cotizacion INT NOT NULL,
  tipo VARCHAR(20) NULL,
  id_referencia INT NULL,
  descripcion TEXT,
  cantidad INT DEFAULT 1,
  precio DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2) DEFAULT 0,
  FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones(id_cotizacion) ON DELETE CASCADE
);

-- Opcional: añadir referencia desde pagos a cotizaciones
ALTER TABLE pagos ADD COLUMN IF NOT EXISTS id_cotizacion INT NULL;
