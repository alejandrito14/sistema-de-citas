<?php
class Pago {
    private $conn;
    private $table = 'pagos';

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. REGISTRAR UN PAGO (Ya lo tenías, lo mantenemos)
    public function registrar($datos) {
        // Construir INSERT dinámico según los campos provistos (id_cita e id_cotizacion pueden ser opcionales)
        $hasCot = isset($datos['id_cotizacion']) && $datos['id_cotizacion'] !== null && $datos['id_cotizacion'] !== '';
        $hasCita = isset($datos['id_cita']) && $datos['id_cita'] !== null && $datos['id_cita'] !== '';

        $columns = [];
        $placeholders = [];
        $params = [];

        if ($hasCita) {
            $columns[] = 'id_cita';
            $placeholders[] = ':cita';
            $params[':cita'] = $datos['id_cita'];
        }

        // campos obligatorios
        $columns[] = 'monto'; $placeholders[] = ':monto'; $params[':monto'] = $datos['monto'];
        $columns[] = 'descuento'; $placeholders[] = ':descuento'; $params[':descuento'] = $datos['descuento'];
        $columns[] = 'metodo_pago'; $placeholders[] = ':metodo'; $params[':metodo'] = $datos['metodo_pago'];
        $columns[] = 'observaciones'; $placeholders[] = ':obs'; $params[':obs'] = $datos['observaciones'];

        if ($hasCot) {
            $columns[] = 'id_cotizacion';
            $placeholders[] = ':idcot';
            $params[':idcot'] = $datos['id_cotizacion'];
        }

        $query = "INSERT INTO " . $this->table . " (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->conn->prepare($query);
        // ligar parámetros dinámicamente
        foreach ($params as $key => $val) {
            if ($key === ':idcot' || $key === ':cita') $stmt->bindValue($key, $val, PDO::PARAM_INT);
            else $stmt->bindValue($key, $val);
        }
        return $stmt->execute();
    }

    // 2. NUEVO: LISTAR PAGOS (Historial de Caja)
    public function listar($inicio = null, $fin = null) {
        // Consultar pagos incluyendo resumen de cuotas y conceptos (servicio/producto)
        // Usar subconsultas para evitar duplicados por joins y garantizar conteos correctos
        $query = "SELECT p.id_pago, p.monto, p.metodo_pago, p.fecha_pago, p.observaciones,
                         u.nombre as paciente,
                         c.id_cita as id_cita,
                         (
                            SELECT GROUP_CONCAT(DISTINCT COALESCE(s2.nombre_servicio, pd2.descripcion) SEPARATOR ', ')
                            FROM pagos_detalle pd2
                            LEFT JOIN servicios s2 ON pd2.tipo = 'Servicio' AND pd2.id_referencia = s2.id_servicio
                            WHERE pd2.id_pago = p.id_pago
                         ) as conceptos,
                         (SELECT COUNT(*) FROM pagos_cuotas pc2 WHERE pc2.id_pago = p.id_pago) as total_cuotas,
                         (SELECT COUNT(*) FROM pagos_cuotas pc2 WHERE pc2.id_pago = p.id_pago AND pc2.pagada = 1) as cuotas_pagadas_total,
                         (SELECT COUNT(*) FROM pagos_cuotas pc2 WHERE pc2.id_pago = p.id_pago AND pc2.pagada = 1 AND DATE(pc2.fecha_pago) BETWEEN :inicio AND :fin) as cuotas_pagadas_en_rango,
                         (SELECT COALESCE(SUM(pc2.monto),0) FROM pagos_cuotas pc2 WHERE pc2.id_pago = p.id_pago AND pc2.pagada = 1 AND DATE(pc2.fecha_pago) BETWEEN :inicio AND :fin) as monto_cuotas_en_rango
                  FROM " . $this->table . " p
                  LEFT JOIN citas c ON p.id_cita = c.id_cita
                  LEFT JOIN usuarios u ON c.id_paciente = u.id_usuario
                  LEFT JOIN servicios s ON c.id_servicio = s.id_servicio
                  LEFT JOIN pagos_detalle pd ON p.id_pago = pd.id_pago
                  ";

        $condiciones = [];
        if ($inicio && $fin) {
            $condiciones[] = "DATE(p.fecha_pago) BETWEEN :inicio AND :fin";
        }

        if (count($condiciones) > 0) {
            $query .= " WHERE " . implode(' AND ', $condiciones);
        }

        $query .= " GROUP BY p.id_pago ORDER BY p.fecha_pago DESC";

        $stmt = $this->conn->prepare($query);
        // ligar parámetros para las subconsultas y filtros
        if ($inicio && $fin) {
            $stmt->bindParam(':inicio', $inicio);
            $stmt->bindParam(':fin', $fin);
        } else {
            $dummyStart = date('Y-m-d');
            $dummyEnd = date('Y-m-d');
            $stmt->bindParam(':inicio', $dummyStart);
            $stmt->bindParam(':fin', $dummyEnd);
        }

        $stmt->execute();
        return $stmt;
    }

    // Obtener pagos por id_cita
    public function obtenerPorCita($id_cita) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_cita = :id_cita ORDER BY fecha_pago ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cita', $id_cita);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. NUEVO: ELIMINAR PAGO (Anular cobro)
    public function eliminar($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id_pago = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Total recaudado en un rango (o total general si no hay rango)
    public function totalRecaudado($inicio = null, $fin = null) {
        // Total recaudado por rango basado en fechas de cuotas:
        // 1) sumar montos de cuotas pagadas cuya fecha esté entre inicio/fin
        // 2) sumar montos de pagos que no tienen cuotas (pagos únicos) cuya fecha esté entre inicio/fin
        $query = "SELECT 
                    (SELECT COALESCE(SUM(pc.monto),0) FROM pagos_cuotas pc WHERE pc.pagada = 1 AND DATE(pc.fecha_pago) BETWEEN :inicio AND :fin) 
                    + (SELECT COALESCE(SUM(p2.monto),0) FROM " . $this->table . " p2 LEFT JOIN pagos_cuotas pc2 ON p2.id_pago = pc2.id_pago WHERE pc2.id_cuota IS NULL AND DATE(p2.fecha_pago) BETWEEN :inicio AND :fin) 
                    as total";

        $stmt = $this->conn->prepare($query);
        if ($inicio && $fin) {
            $stmt->bindParam(':inicio', $inicio);
            $stmt->bindParam(':fin', $fin);
        } else {
            $d = date('Y-m-d');
            $stmt->bindParam(':inicio', $d);
            $stmt->bindParam(':fin', $d);
        }
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($res['total']) ? (float)$res['total'] : 0.0;
    }
}