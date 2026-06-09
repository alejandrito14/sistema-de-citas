<?php 
class PagoCuota {
    private $conn;
    private $table = 'pagos_cuotas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar cuota
    public function registrar($id_pago, $cuota) {
        $pagada = (isset($cuota['numero']) && $cuota['numero'] == 1) ? 1 : 0;
        $fecha_pago = null;
        if ($pagada) {
            $fecha_pago = date('Y-m-d H:i:s');
        }
        $query = "INSERT INTO " . $this->table . " (id_pago, numero_cuota, monto, fecha_vencimiento, pagada, fecha_pago) VALUES (:id_pago, :numero_cuota, :monto, :fecha_vencimiento, :pagada, :fecha_pago)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_pago', $id_pago);
        $stmt->bindParam(':numero_cuota', $cuota['numero']);
        $stmt->bindParam(':monto', $cuota['monto']);
        $stmt->bindParam(':fecha_vencimiento', $cuota['fecha']);
        $stmt->bindParam(':pagada', $pagada);
        $stmt->bindParam(':fecha_pago', $fecha_pago);
        return $stmt->execute();
    }

    // Obtener cuotas pagadas y total por id_pago
    public function obtenerResumenCuotas($id_pago) {
        $query = "SELECT COUNT(*) as total, SUM(CASE WHEN pagada = 1 THEN 1 ELSE 0 END) as pagadas FROM " . $this->table . " WHERE id_pago = :id_pago";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_pago', $id_pago);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener todas las cuotas de un pago
    public function obtenerCuotasPorPago($id_pago) {
        $query = "SELECT id_cuota, numero_cuota, monto, fecha_vencimiento, pagada, fecha_pago, metodo_pago, descuento, observaciones, comprobante_transferencia FROM " . $this->table . " WHERE id_pago = :id_pago ORDER BY numero_cuota ASC";
       
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_pago', $id_pago);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Marcar cuotas como pagadas (con comprobante y observaciones)
    public function marcarCuotasPagadas($ids, $metodo, $descuento = 0, $observaciones = null, $comprobante = null) {
        if (!is_array($ids) || count($ids) === 0) return false;
        $in  = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE " . $this->table . " SET pagada = 1, fecha_pago = NOW(), metodo_pago = ?, descuento = ?, observaciones = ?, comprobante_transferencia = ? WHERE id_cuota IN ($in)";
        $params = array_merge([$metodo, $descuento, $observaciones, $comprobante], $ids);
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }
}
