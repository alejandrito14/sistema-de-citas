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

        // Normalizar id_referencia: si viene como 'servicio-4' o 'producto-5', extraer solo el número
        $rawRef = $detalle['referencia'] ?? null;
        $id_referencia = null;
        if (is_numeric($rawRef)) {
            $id_referencia = (int)$rawRef;
        } elseif (is_string($rawRef)) {
            // Buscar dígitos al final o la primera ocurrencia de número
            if (preg_match('/(\d+)$/', $rawRef, $m)) {
                $id_referencia = (int)$m[1];
            } else {
                // Intentar separar por guion y tomar la última parte
                $parts = explode('-', $rawRef);
                $last = end($parts);
                if (is_numeric($last)) $id_referencia = (int)$last;
            }
        }

        // Bind values (usar bindValue para tipos correctos)
        $stmt->bindValue(':id_pago', $id_pago, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $detalle['tipo']);
        if ($id_referencia !== null) $stmt->bindValue(':id_referencia', $id_referencia, PDO::PARAM_INT);
        else $stmt->bindValue(':id_referencia', null, PDO::PARAM_NULL);
        $stmt->bindValue(':descripcion', $detalle['descripcion']);
        $stmt->bindValue(':cantidad', $detalle['cantidad']);
        $stmt->bindValue(':precio', $detalle['precio']);
        $stmt->bindValue(':total', $detalle['total']);

        return $stmt->execute();
    }

    // Obtener detalles por id_pago
    public function obtenerPorPago($id_pago) {
        $query = "SELECT descripcion, cantidad, precio, total FROM " . $this->table . " WHERE id_pago = :id_pago";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_pago', $id_pago);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
