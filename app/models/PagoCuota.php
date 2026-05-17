<?php
class PagoCuota {
    private $conn;
    private $table = 'pagos_cuotas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar cuota
    public function registrar($id_pago, $cuota) {
        // Si la cuota es la primera, marcar como pagada y poner fecha_pago
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
}
