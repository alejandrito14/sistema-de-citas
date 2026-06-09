<?php
class Paciente {
    private $conn;
    private $table = 'usuarios';

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. LISTAR PACIENTES
    public function leer() {
        $query = 'SELECT id_usuario, nombre, documento_identidad, email, telefono, 
                         grupo_sanguineo, alergias, enfermedades_cronicas, 
                         fecha_creacion 
                  FROM ' . $this->table . ' 
                  WHERE id_rol = 3 
                  ORDER BY nombre ASC';
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // 2. OBTENER POR ID
    public function obtenerPorId($id) {
        $query = 'SELECT id_usuario, nombre, documento_identidad, email, telefono, 
                         grupo_sanguineo, alergias, enfermedades_cronicas, 
                         fecha_creacion 
                  FROM ' . $this->table . ' 
                  WHERE id_usuario = :id AND id_rol = 3';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 10. BÚSQUEDA para select2/ajax
    public function buscar($q, $limit = 20) {
        $term = '%' . $q . '%';
        $query = 'SELECT id_usuario, nombre, telefono, documento_identidad FROM ' . $this->table . ' WHERE id_rol = 3 AND (nombre LIKE :term OR telefono LIKE :term OR documento_identidad LIKE :term) ORDER BY nombre ASC LIMIT :lim';
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':term', $term);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. OBTENER HISTORIAL CLÍNICO
    public function obtenerHistorial($id_paciente) {
        $query = "SELECT c.fecha_cita, c.motivo, c.diagnostico, c.prescripcion, c.estado,
                         c.peso, c.talla, c.temperatura, c.presion_arterial,
                         m_u.nombre as medico, e.nombre as especialidad
                  FROM citas c
                  JOIN medicos m ON c.id_medico = m.id_medico
                  JOIN usuarios m_u ON m.id_usuario = m_u.id_usuario
                  JOIN especialidades e ON m.id_especialidad = e.id_especialidad
                  WHERE c.id_paciente = :id
                  ORDER BY c.fecha_cita DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_paciente);
        $stmt->execute();
        return $stmt;
    }

    // 4. OBTENER EVOLUCIÓN
    public function obtenerEvolucion($id_paciente) {
        $query = "SELECT fecha_cita, peso, temperatura, presion_arterial 
                  FROM citas 
                  WHERE id_paciente = :id 
                  AND estado = 'Finalizada' 
                  AND (peso > 0 OR temperatura > 0)
                  ORDER BY fecha_cita ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_paciente);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. REGISTRAR ARCHIVO
    public function registrarArchivo($id_paciente, $nombre, $ruta, $tipo) {
        $query = "INSERT INTO archivos_paciente (id_paciente, nombre_archivo, ruta_archivo, tipo_archivo) 
                  VALUES (:id, :nombre, :ruta, :tipo)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_paciente);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':ruta', $ruta);
        $stmt->bindParam(':tipo', $tipo);
        return $stmt->execute();
    }

    // 6. OBTENER ARCHIVOS ADJUNTOS
    public function obtenerArchivos($id_paciente) {
        $query = "SELECT * FROM archivos_paciente WHERE id_paciente = :id ORDER BY fecha_subida DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_paciente);
        $stmt->execute();
        return $stmt;
    }

    // 7. CREAR PACIENTE (CON DNI)
    public function crear($datos) {
        $query = 'INSERT INTO ' . $this->table . ' (nombre, documento_identidad, email, telefono, grupo_sanguineo, alergias, enfermedades_cronicas, password, id_rol) 
                  VALUES (:nombre, :dni, :email, :telefono, :sangre, :alergias, :cronicas, :password, 3)';
        
        $stmt = $this->conn->prepare($query);
        $passwordHash = password_hash($datos['password'], PASSWORD_BCRYPT);
        
        $stmt->bindParam(':nombre', $datos['nombre']);
        $stmt->bindParam(':dni', $datos['dni']);
        $stmt->bindParam(':email', $datos['email']);
        $stmt->bindParam(':telefono', $datos['telefono']);
        $stmt->bindParam(':sangre', $datos['sangre']);
        $stmt->bindParam(':alergias', $datos['alergias']);
        $stmt->bindParam(':cronicas', $datos['cronicas']);
        $stmt->bindParam(':password', $passwordHash);

        return $stmt->execute();
    }

    // 8. ACTUALIZAR PACIENTE (CON DNI)
    public function actualizar($datos) {
        $sql = 'UPDATE ' . $this->table . ' 
                SET nombre = :nombre, documento_identidad = :dni, email = :email, telefono = :telefono, 
                    grupo_sanguineo = :sangre, alergias = :alergias, enfermedades_cronicas = :cronicas';

        if (!empty($datos['password'])) {
            $sql .= ', password = :password';
        }

        $sql .= ' WHERE id_usuario = :id';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $datos['id']);
        $stmt->bindParam(':nombre', $datos['nombre']);
        $stmt->bindParam(':dni', $datos['dni']);
        $stmt->bindParam(':email', $datos['email']);
        $stmt->bindParam(':telefono', $datos['telefono']);
        $stmt->bindParam(':sangre', $datos['sangre']);
        $stmt->bindParam(':alergias', $datos['alergias']);
        $stmt->bindParam(':cronicas', $datos['cronicas']);

        if (!empty($datos['password'])) {
            $passwordHash = password_hash($datos['password'], PASSWORD_BCRYPT);
            $stmt->bindParam(':password', $passwordHash);
        }

        return $stmt->execute();
    }

    // 9. ELIMINAR PACIENTE
    public function eliminar($id) {
        $query = 'DELETE FROM ' . $this->table . ' WHERE id_usuario = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}