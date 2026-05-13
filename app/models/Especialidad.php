<?php
class Especialidad {
    private $conn;
    private $table = 'especialidades';

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. LISTAR (Con conteo de médicos por especialidad)
    public function leer() {
        $query = "SELECT e.*, 
                         (SELECT COUNT(*) FROM medicos m WHERE m.id_especialidad = e.id_especialidad) as total_medicos
                  FROM " . $this->table . " e 
                  ORDER BY e.nombre ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // 2. CREAR
    public function crear($nombre) {
        $query = "INSERT INTO " . $this->table . " (nombre) VALUES (:nombre)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        return $stmt->execute();
    }

    // 3. ACTUALIZAR
    public function actualizar($id, $nombre) {
        $query = "UPDATE " . $this->table . " SET nombre = :nombre WHERE id_especialidad = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre', $nombre);
        return $stmt->execute();
    }

    // 4. ELIMINAR (Validamos que no tenga médicos asignados antes de borrar)
    public function eliminar($id) {
        // Primero verificamos si hay médicos usándola
        $checkQuery = "SELECT COUNT(*) FROM medicos WHERE id_especialidad = :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        
        if($checkStmt->fetchColumn() > 0) {
            return false; // No borrar si tiene médicos
        }

        $query = "DELETE FROM " . $this->table . " WHERE id_especialidad = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}