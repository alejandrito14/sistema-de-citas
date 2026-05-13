-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.3 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para citas_medicas_db
CREATE DATABASE IF NOT EXISTS `citas_medicas_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `citas_medicas_db`;

-- Volcando estructura para tabla citas_medicas_db.archivos_paciente
CREATE TABLE IF NOT EXISTS `archivos_paciente` (
  `id_archivo` int NOT NULL AUTO_INCREMENT,
  `id_paciente` int NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_archivo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_subida` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_archivo`),
  KEY `id_paciente` (`id_paciente`),
  CONSTRAINT `archivos_paciente_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.archivos_paciente: ~0 rows (aproximadamente)
DELETE FROM `archivos_paciente`;
INSERT INTO `archivos_paciente` (`id_archivo`, `id_paciente`, `nombre_archivo`, `ruta_archivo`, `tipo_archivo`, `fecha_subida`) VALUES
	(1, 4, 'Curso de PostgreSQL.pdf', 'DOC_692c322d2092a3.05734163.pdf', 'pdf', '2025-11-30 07:01:49');

-- Volcando estructura para tabla citas_medicas_db.auditoria
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `accion` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tabla_afectada` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_registro_afectado` int DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `ip_usuario` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.auditoria: ~0 rows (aproximadamente)
DELETE FROM `auditoria`;

-- Volcando estructura para tabla citas_medicas_db.citas
CREATE TABLE IF NOT EXISTS `citas` (
  `id_cita` int NOT NULL AUTO_INCREMENT,
  `id_paciente` int NOT NULL,
  `id_medico` int NOT NULL,
  `id_servicio` int DEFAULT NULL,
  `fecha_cita` datetime NOT NULL,
  `motivo` text COLLATE utf8mb4_general_ci,
  `peso` decimal(5,2) DEFAULT NULL,
  `talla` decimal(5,2) DEFAULT NULL,
  `temperatura` decimal(4,1) DEFAULT NULL,
  `presion_arterial` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `diagnostico` text COLLATE utf8mb4_general_ci,
  `prescripcion` text COLLATE utf8mb4_general_ci,
  `dias_reposo` int DEFAULT '0',
  `fecha_fin_reposo` date DEFAULT NULL,
  `estado` enum('Pendiente','Confirmada','Cancelada','Finalizada') COLLATE utf8mb4_general_ci DEFAULT 'Pendiente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cita`),
  KEY `id_paciente` (`id_paciente`),
  KEY `id_medico` (`id_medico`),
  KEY `fk_cita_servicio` (`id_servicio`),
  CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`id_medico`) REFERENCES `medicos` (`id_medico`),
  CONSTRAINT `fk_cita_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id_servicio`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.citas: ~2 rows (aproximadamente)
DELETE FROM `citas`;
INSERT INTO `citas` (`id_cita`, `id_paciente`, `id_medico`, `id_servicio`, `fecha_cita`, `motivo`, `peso`, `talla`, `temperatura`, `presion_arterial`, `diagnostico`, `prescripcion`, `dias_reposo`, `fecha_fin_reposo`, `estado`, `created_at`) VALUES
	(1, 4, 1, NULL, '2025-11-30 13:00:00', 'chequeo médico', NULL, NULL, NULL, NULL, 'diagnostico 1', 'receta 1', 0, NULL, 'Finalizada', '2025-11-29 15:22:39'),
	(2, 4, 2, 4, '2025-12-02 10:00:00', 'cita de consulta', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'Pendiente', '2025-11-30 12:41:05');

-- Volcando estructura para tabla citas_medicas_db.configuracion
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int NOT NULL,
  `nombre_clinica` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `moneda` varchar(5) COLLATE utf8mb4_general_ci DEFAULT 'S/.',
  `logo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.configuracion: ~1 rows (aproximadamente)
DELETE FROM `configuracion`;
INSERT INTO `configuracion` (`id`, `nombre_clinica`, `direccion`, `telefono`, `email`, `moneda`, `logo`) VALUES
	(1, 'Centro Médico Salud', 'Av. Principal 123, Lima', '(01) 555-0000', 'contacto@saludtotal.com', 'S/.', 'logo_clinica.png');

-- Volcando estructura para tabla citas_medicas_db.especialidades
CREATE TABLE IF NOT EXISTS `especialidades` (
  `id_especialidad` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_especialidad`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.especialidades: ~5 rows (aproximadamente)
DELETE FROM `especialidades`;
INSERT INTO `especialidades` (`id_especialidad`, `nombre`) VALUES
	(1, 'Medicina General'),
	(2, 'Cardiología'),
	(3, 'Pediatría'),
	(4, 'Dermatología'),
	(5, 'Ginecología');

-- Volcando estructura para tabla citas_medicas_db.horarios_medicos
CREATE TABLE IF NOT EXISTS `horarios_medicos` (
  `id_horario` int NOT NULL AUTO_INCREMENT,
  `id_medico` int NOT NULL,
  `dia_semana` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') COLLATE utf8mb4_general_ci NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  PRIMARY KEY (`id_horario`),
  KEY `id_medico` (`id_medico`),
  CONSTRAINT `horarios_medicos_ibfk_1` FOREIGN KEY (`id_medico`) REFERENCES `medicos` (`id_medico`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.horarios_medicos: ~1 rows (aproximadamente)
DELETE FROM `horarios_medicos`;
INSERT INTO `horarios_medicos` (`id_horario`, `id_medico`, `dia_semana`, `hora_inicio`, `hora_fin`) VALUES
	(1, 1, 'Lunes', '11:00:00', '14:00:00');

-- Volcando estructura para tabla citas_medicas_db.medicamentos
CREATE TABLE IF NOT EXISTS `medicamentos` (
  `id_medicamento` int NOT NULL AUTO_INCREMENT,
  `nombre_comercial` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_generico` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presentacion` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stock` int DEFAULT '0',
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_general_ci DEFAULT 'Activo',
  PRIMARY KEY (`id_medicamento`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.medicamentos: ~3 rows (aproximadamente)
DELETE FROM `medicamentos`;
INSERT INTO `medicamentos` (`id_medicamento`, `nombre_comercial`, `nombre_generico`, `presentacion`, `stock`, `estado`) VALUES
	(1, 'Aspirina Forte', 'Ácido Acetilsalicílico', 'Tableta 500mg', 100, 'Activo'),
	(2, 'Amoxil', 'Amoxicilina', 'Jarabe 250ml', 50, 'Activo'),
	(3, 'Ibuprofeno', 'Ibuprofeno', 'Cápsula 400mg', 200, 'Activo');

-- Volcando estructura para tabla citas_medicas_db.medicos
CREATE TABLE IF NOT EXISTS `medicos` (
  `id_medico` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_especialidad` int NOT NULL,
  `colegiatura` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_medico`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_especialidad` (`id_especialidad`),
  CONSTRAINT `medicos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `medicos_ibfk_2` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidad`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.medicos: ~0 rows (aproximadamente)
DELETE FROM `medicos`;
INSERT INTO `medicos` (`id_medico`, `id_usuario`, `id_especialidad`, `colegiatura`) VALUES
	(1, 5, 1, NULL),
	(2, 6, 3, NULL);

-- Volcando estructura para tabla citas_medicas_db.pagos
CREATE TABLE IF NOT EXISTS `pagos` (
  `id_pago` int NOT NULL AUTO_INCREMENT,
  `id_cita` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `observaciones` text COLLATE utf8mb4_general_ci,
  `fecha_pago` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pago`),
  KEY `id_cita` (`id_cita`),
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.pagos: ~2 rows (aproximadamente)
DELETE FROM `pagos`;
INSERT INTO `pagos` (`id_pago`, `id_cita`, `monto`, `metodo_pago`, `observaciones`, `fecha_pago`) VALUES
	(1, 1, 0.00, 'Efectivo', 'pago de prueba 1', '2025-11-30 08:16:33'),
	(2, 2, 70.00, 'Yape/Plin', 'pago de prueba 2', '2025-11-30 08:16:46');

-- Volcando estructura para tabla citas_medicas_db.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.roles: ~3 rows (aproximadamente)
DELETE FROM `roles`;
INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
	(1, 'Administrador'),
	(2, 'Medico'),
	(3, 'Paciente'),
	(4, 'Recepcionista');

-- Volcando estructura para tabla citas_medicas_db.servicios
CREATE TABLE IF NOT EXISTS `servicios` (
  `id_servicio` int NOT NULL AUTO_INCREMENT,
  `nombre_servicio` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `precio` decimal(10,2) NOT NULL,
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_general_ci DEFAULT 'Activo',
  PRIMARY KEY (`id_servicio`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.servicios: ~4 rows (aproximadamente)
DELETE FROM `servicios`;
INSERT INTO `servicios` (`id_servicio`, `nombre_servicio`, `descripcion`, `precio`, `estado`) VALUES
	(1, 'Consulta Medicina General', NULL, 50.00, 'Activo'),
	(2, 'Consulta Especializada', NULL, 80.00, 'Activo'),
	(3, 'Ecografía Abdominal', NULL, 120.00, 'Activo'),
	(4, 'Limpieza Dental', NULL, 70.00, 'Activo');

-- Volcando estructura para tabla citas_medicas_db.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `documento_identidad` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `grupo_sanguineo` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alergias` text COLLATE utf8mb4_general_ci,
  `enfermedades_cronicas` text COLLATE utf8mb4_general_ci,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_rol` int DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla citas_medicas_db.usuarios: ~4 rows (aproximadamente)
DELETE FROM `usuarios`;
INSERT INTO `usuarios` (`id_usuario`, `nombre`, `documento_identidad`, `email`, `telefono`, `grupo_sanguineo`, `alergias`, `enfermedades_cronicas`, `password`, `avatar`, `id_rol`, `fecha_creacion`) VALUES
	(3, 'Administrador Principal', NULL, 'admin@medico.com', NULL, NULL, NULL, NULL, '$2y$10$AxhduXgXoU9cvbCu.c8PmOdgKGY4zlgGuo5MLcNFyLFBHsQ7fm/n6', NULL, 1, '2025-11-29 14:40:16'),
	(4, 'CARLOS RAMIREZ', NULL, 'carlosramirez@correo.com', '966648329', NULL, NULL, NULL, '$2y$10$uKO5YLaf9KubcA1233NcnOXCTLP2MB6rxhQUqGt57jG7EsIGeQlI6', NULL, 3, '2025-11-29 15:21:20'),
	(5, 'MEDICO 1', NULL, 'medico1@correo.com', NULL, NULL, NULL, NULL, '$2y$10$Jc.FwMdeWR0dhFY34RfGQusMsdF0CEDDu1npH3XlRQ9VkAtFJqlPi', NULL, 2, '2025-11-29 15:21:57'),
	(6, 'MEDICO 2', NULL, 'medico2@medico.com', NULL, NULL, NULL, NULL, '$2y$10$L8KGFapU7oYOgqLxIAQrcebgzhYqPdug1HgzHo.LqRxB0RAxTQKs6', NULL, 2, '2025-11-30 11:51:14'),
	(7, 'Ana Recepción', NULL, 'recepcion@medico.com', NULL, NULL, NULL, NULL, '$2y$10$lo5u63zTLVOHwzK7PYFe5uxa1U31LLmNsnjjxjNPszGP/aSPB8QdW', NULL, 4, '2025-12-01 19:46:23');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
