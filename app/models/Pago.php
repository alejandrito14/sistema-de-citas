<?php
class Pago {
    private $conn;
    private $table = 'pagos';

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. REGISTRAR UN PAGO (Ya lo tenías, lo mantenemos)
    public function registrar($datos) {
        // Intentar insertar incluyendo id_cotizacion si fue provisto. Si falla (columna no existe), hacer fallback sin la columna.
        $hasCot = isset($datos['id_cotizacion']) && $datos['id_cotizacion'] !== null && $datos['id_cotizacion'] !== '';
        if ($hasCot) {
            try {
                $query = "INSERT INTO " . $this->table . " (id_cita, monto, descuento, metodo_pago, observaciones, id_cotizacion) 
                      VALUES (:cita, :monto, :descuento, :metodo, :obs, :idcot)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':cita', $datos['id_cita']);
                $stmt->bindParam(':monto', $datos['monto']);
                $stmt->bindParam(':descuento', $datos['descuento']);
                $stmt->bindParam(':metodo', $datos['metodo_pago']);
                $stmt->bindParam(':obs', $datos['observaciones']);
                $stmt->bindParam(':idcot', $datos['id_cotizacion'], PDO::PARAM_INT);
                return $stmt->execute();
            } catch (Exception $e) {
                // intentar fallback sin id_cotizacion
            }
        }

        $query = "INSERT INTO " . $this->table . " (id_cita, monto, descuento, metodo_pago, observaciones) 
              VALUES (:cita, :monto, :descuento, :metodo, :obs)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cita', $datos['id_cita']);
        $stmt->bindParam(':monto', $datos['monto']);
        $stmt->bindParam(':descuento', $datos['descuento']);
        $stmt->bindParam(':metodo', $datos['metodo_pago']);
        $stmt->bindParam(':obs', $datos['observaciones']);

        return $stmt->execute();
    }

    // 2. NUEVO: LISTAR PAGOS (Historial de Caja)
    public function listar($inicio = null, $fin = null) {
        // Consultar pagos incluyendo resumen de cuotas y conceptos (servicio/producto)
        $query = "SELECT p.id_pago, p.monto, p.metodo_pago, p.fecha_pago, p.observaciones, 
                    u.nombre as paciente, 
                 c.id_cita as id_cita,
                    GROUP_CONCAT(DISTINCT COALESCE(s.nombre_servicio, pd.descripcion) SEPARATOR ', ') as conceptos,
                    COUNT(DISTINCT pc.id_cuota) as total_cuotas,
                    SUM(CASE WHEN pc.pagada = 1 THEN 1 ELSE 0 END) as cuotas_pagadas_total,
                         SUM(CASE WHEN pc.pagada = 1 AND DATE(pc.fecha_pago) BETWEEN :inicio AND :fin THEN 1 ELSE 0 END) as cuotas_pagadas_en_rango,
                         SUM(CASE WHEN pc.pagada = 1 AND DATE(pc.fecha_pago) BETWEEN :inicio AND :fin THEN pc.monto ELSE 0 END) as monto_cuotas_en_rango
                FROM " . $this->table . " p
                LEFT JOIN citas c ON p.id_cita = c.id_cita
                LEFT JOIN usuarios u ON c.id_paciente = u.id_usuario
                LEFT JOIN servicios s ON c.id_servicio = s.id_servicio
                LEFT JOIN pagos_detalle pd ON p.id_pago = pd.id_pago
                LEFT JOIN pagos_cuotas pc ON p.id_pago = pc.id_pago";

        $condiciones = [];
        if ($inicio && $fin) {
            $condiciones[] = "DATE(p.fecha_pago) BETWEEN :inicio AND :fin";
        }

        if (count($condiciones) > 0) {
            // incluir también pagos que tengan cuotas pagadas dentro del rango
            $where = "(" . implode(' AND ', $condiciones) . ") OR (pc.pagada = 1 AND DATE(pc.fecha_pago) BETWEEN :inicio AND :fin)";
            $query .= " WHERE " . $where;
        }

        $query .= " GROUP BY p.id_pago ORDER BY p.fecha_pago DESC";

        $stmt = $this->conn->prepare($query);

        if ($inicio && $fin) {
            $stmt->bindParam(':inicio', $inicio);
            $stmt->bindParam(':fin', $fin);
        } else {
            // evitar error al no ligar parámetros usados en SUM(... BETWEEN :inicio AND :fin)
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