<?php
class Cotizacion {
    private $conn;
    private $table = 'cotizaciones';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($data) {
        try {
            $this->conn->beginTransaction();
            $query = "INSERT INTO " . $this->table . " (id_paciente, vigencia_dias, subtotal, descuento_tipo, descuento_valor, total, estado, moneda, usuario_id, observaciones) VALUES (:id_paciente, :vigencia_dias, :subtotal, :descuento_tipo, :descuento_valor, :total, :estado, :moneda, :usuario_id, :observaciones)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id_paciente', $data['id_paciente'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':vigencia_dias', $data['vigencia_dias'] ?? 15, PDO::PARAM_INT);
            $stmt->bindValue(':subtotal', $data['subtotal'] ?? 0);
            $stmt->bindValue(':descuento_tipo', $data['descuento_tipo'] ?? 'fijo');
            $stmt->bindValue(':descuento_valor', $data['descuento_valor'] ?? 0);
            $stmt->bindValue(':total', $data['total'] ?? 0);
            $stmt->bindValue(':estado', $data['estado'] ?? 'borrador');
            $stmt->bindValue(':moneda', $data['moneda'] ?? 'S/.');
            $stmt->bindValue(':usuario_id', $data['usuario_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':observaciones', $data['observaciones'] ?? null);
            $stmt->execute();
            $id = $this->conn->lastInsertId();

            // Generar folio con formato YYMMDD + id (ej: 0706261)
            try {
                $folio = date('ymd') . $id;
                $upd = $this->conn->prepare("UPDATE " . $this->table . " SET folio = :folio WHERE id_cotizacion = :id");
                $upd->bindValue(':folio', $folio);
                $upd->bindValue(':id', $id, PDO::PARAM_INT);
                $upd->execute();
            } catch (Exception $e) {
                // Si la columna `folio` no existe o hay error, no interrumpir la creación
            }

            // insertar detalles si existen
            if (!empty($data['detalles']) && is_array($data['detalles'])) {
                $queryDet = "INSERT INTO cotizaciones_detalle (id_cotizacion, tipo, id_referencia, descripcion, cantidad, precio, total) VALUES (:id_cotizacion, :tipo, :id_referencia, :descripcion, :cantidad, :precio, :total)";
                $stmtDet = $this->conn->prepare($queryDet);
                foreach ($data['detalles'] as $det) {
                    $id_ref = null;
                    if (isset($det['referencia'])) {
                        if (is_numeric($det['referencia'])) $id_ref = (int)$det['referencia'];
                        elseif (preg_match('/(\d+)$/', $det['referencia'], $m)) $id_ref = (int)$m[1];
                    }
                    $stmtDet->bindValue(':id_cotizacion', $id, PDO::PARAM_INT);
                    $stmtDet->bindValue(':tipo', $det['tipo'] ?? null);
                    $stmtDet->bindValue(':id_referencia', $id_ref, PDO::PARAM_INT);
                    $stmtDet->bindValue(':descripcion', $det['descripcion'] ?? null);
                    $stmtDet->bindValue(':cantidad', $det['cantidad'] ?? 1, PDO::PARAM_INT);
                    $stmtDet->bindValue(':precio', $det['precio'] ?? 0);
                    $stmtDet->bindValue(':total', $det['total'] ?? 0);
                    $stmtDet->execute();
                }
            }

            $this->conn->commit();
            return $id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_cotizacion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $cot = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cot) {
            $stmt2 = $this->conn->prepare("SELECT tipo, id_referencia, descripcion, cantidad, precio, total FROM cotizaciones_detalle WHERE id_cotizacion = :id");
            $stmt2->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt2->execute();
            $cot['detalles'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        return $cot;
    }

    public function actualizar($id, $data) {
        try {
            $this->conn->beginTransaction();
            $query = "UPDATE " . $this->table . " SET id_paciente = :id_paciente, vigencia_dias = :vigencia_dias, subtotal = :subtotal, descuento_tipo = :descuento_tipo, descuento_valor = :descuento_valor, total = :total, estado = :estado, moneda = :moneda, usuario_id = :usuario_id, observaciones = :observaciones WHERE id_cotizacion = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id_paciente', $data['id_paciente'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':vigencia_dias', $data['vigencia_dias'] ?? 15, PDO::PARAM_INT);
            $stmt->bindValue(':subtotal', $data['subtotal'] ?? 0);
            $stmt->bindValue(':descuento_tipo', $data['descuento_tipo'] ?? 'fijo');
            $stmt->bindValue(':descuento_valor', $data['descuento_valor'] ?? 0);
            $stmt->bindValue(':total', $data['total'] ?? 0);
            $stmt->bindValue(':estado', $data['estado'] ?? 'borrador');
            $stmt->bindValue(':moneda', $data['moneda'] ?? 'S/.');
            $stmt->bindValue(':usuario_id', $data['usuario_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':observaciones', $data['observaciones'] ?? null);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // borrar detalles anteriores
            $del = $this->conn->prepare("DELETE FROM cotizaciones_detalle WHERE id_cotizacion = :id");
            $del->bindValue(':id', $id, PDO::PARAM_INT);
            $del->execute();

            // insertar nuevos detalles
            if (!empty($data['detalles']) && is_array($data['detalles'])) {
                $queryDet = "INSERT INTO cotizaciones_detalle (id_cotizacion, tipo, id_referencia, descripcion, cantidad, precio, total) VALUES (:id_cotizacion, :tipo, :id_referencia, :descripcion, :cantidad, :precio, :total)";
                $stmtDet = $this->conn->prepare($queryDet);
                foreach ($data['detalles'] as $det) {
                    $id_ref = null;
                    if (isset($det['referencia'])) {
                        if (is_numeric($det['referencia'])) $id_ref = (int)$det['referencia'];
                        elseif (preg_match('/(\d+)$/', $det['referencia'], $m)) $id_ref = (int)$m[1];
                    }
                    $stmtDet->bindValue(':id_cotizacion', $id, PDO::PARAM_INT);
                    $stmtDet->bindValue(':tipo', $det['tipo'] ?? null);
                    $stmtDet->bindValue(':id_referencia', $id_ref, PDO::PARAM_INT);
                    $stmtDet->bindValue(':descripcion', $det['descripcion'] ?? null);
                    $stmtDet->bindValue(':cantidad', $det['cantidad'] ?? 1, PDO::PARAM_INT);
                    $stmtDet->bindValue(':precio', $det['precio'] ?? 0);
                    $stmtDet->bindValue(':total', $det['total'] ?? 0);
                    $stmtDet->execute();
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function listar($estado = null) {
        $query = "SELECT * FROM " . $this->table;
        if ($estado) {
            $query .= " WHERE estado = :estado";
        }
        // Ordenar por id descendente para mostrar primero las más recientes
        $query .= " ORDER BY id_cotizacion DESC";
        $stmt = $this->conn->prepare($query);
        if ($estado) $stmt->bindValue(':estado', $estado);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarEstado($id, $estado) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET estado = :estado WHERE id_cotizacion = :id");
        $stmt->bindValue(':estado', $estado);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
