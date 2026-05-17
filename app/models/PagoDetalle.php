<?php
class PagoDetalle {
    private $conn;
    private $table = 'pagos_detalle';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar detalle de pago
    public function registrar($id_pago, $detalle) {
        $query = "INSERT INTO " . $this->table . " (id_pago, tipo, id_referencia, descripcion, cantidad, precio, total) VALUES (:id_pago, :tipo, :id_referencia, :descripcion, :cantidad, :precio, :total)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_pago', $id_pago);
        $stmt->bindParam(':tipo', $detalle['tipo']);
        $stmt->bindParam(':id_referencia', $detalle['referencia']);
        $stmt->bindParam(':descripcion', $detalle['descripcion']);
        $stmt->bindParam(':cantidad', $detalle['cantidad']);
        $stmt->bindParam(':precio', $detalle['precio']);
        $stmt->bindParam(':total', $detalle['total']);
        return $stmt->execute();
    }
}
