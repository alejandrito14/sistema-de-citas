<?php
class Cita {
    private $conn;
    private $table = 'citas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. LISTAR CITAS (Completo con todos los Joins)
    public function leer($fecha = null, $estado = null, $id_medico = null, $id_paciente = null) {
        $query = 'SELECT c.id_cita, c.fecha_cita, c.motivo, c.estado, c.id_medico, c.id_paciente, c.id_servicio,
                         c.diagnostico, c.prescripcion, 
                         c.peso, c.talla, c.temperatura, c.presion_arterial,
                         c.dias_reposo, c.fecha_fin_reposo,
                         u.nombre as paciente, u.email as paciente_email, u.telefono as paciente_telefono, u.documento_identidad,
                         m_u.nombre as medico, e.nombre as especialidad, m.colegiatura,
                         s.nombre_servicio, s.precio,
                         p.id_pago, p.metodo_pago, p.fecha_pago
                  FROM ' . $this->table . ' c
                  JOIN usuarios u ON c.id_paciente = u.id_usuario
                  JOIN medicos m ON c.id_medico = m.id_medico
                  JOIN usuarios m_u ON m.id_usuario = m_u.id_usuario
                  JOIN especialidades e ON m.id_especialidad = e.id_especialidad
                  LEFT JOIN servicios s ON c.id_servicio = s.id_servicio
                  LEFT JOIN pagos p ON c.id_cita = p.id_cita';
        
        $condiciones = [];
        
        if ($fecha) $condiciones[] = 'DATE(c.fecha_cita) = :fecha';
        if ($estado) $condiciones[] = 'c.estado = :estado';
        if ($id_medico) $condiciones[] = 'c.id_medico = :medico_id';
        if ($id_paciente) $condiciones[] = 'c.id_paciente = :paciente_id';

        if (count($condiciones) > 0) {
            $query .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $query .= ' ORDER BY c.fecha_cita ASC';
        
        $stmt = $this->conn->prepare($query);
        
        if ($fecha) $stmt->bindParam(':fecha', $fecha);
        if ($estado) $stmt->bindParam(':estado', $estado);
        if ($id_medico) $stmt->bindParam(':medico_id', $id_medico);
        if ($id_paciente) $stmt->bindParam(':paciente_id', $id_paciente);

        $stmt->execute();
        return $stmt;
    }

    // Obtener cita por id
    public function obtenerPorId($id_cita) {
        $query = 'SELECT c.id_cita, c.fecha_cita, c.motivo, c.estado, c.id_medico, c.id_paciente, c.id_servicio,
                         u.nombre as paciente, m_u.nombre as medico, e.nombre as especialidad, s.nombre_servicio, s.precio
                  FROM ' . $this->table . ' c
                  JOIN usuarios u ON c.id_paciente = u.id_usuario
                  JOIN medicos m ON c.id_medico = m.id_medico
                  JOIN usuarios m_u ON m.id_usuario = m_u.id_usuario
                  JOIN especialidades e ON m.id_especialidad = e.id_especialidad
                  LEFT JOIN servicios s ON c.id_servicio = s.id_servicio
                  WHERE c.id_cita = :id_cita'
        ;
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cita', $id_cita);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. ESTADÍSTICAS
    public function obtenerEstadisticasEstado($id_medico = null, $id_paciente = null) {
        $query = "SELECT estado, COUNT(*) as cantidad FROM " . $this->table;
        $condiciones = [];
        if ($id_medico) $condiciones[] = "id_medico = :medico";
        if ($id_paciente) $condiciones[] = "id_paciente = :paciente";
        if (count($condiciones) > 0) $query .= " WHERE " . implode(' AND ', $condiciones);
        $query .= " GROUP BY estado";
        $stmt = $this->conn->prepare($query);
        if ($id_medico) $stmt->bindParam(':medico', $id_medico);
        if ($id_paciente) $stmt->bindParam(':paciente', $id_paciente);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 3. CONTEO TOTAL
    public function contarTotal($id_medico = null, $id_paciente = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $condiciones = [];
        if ($id_medico) $condiciones[] = "id_medico = :medico";
        if ($id_paciente) $condiciones[] = "id_paciente = :paciente";
        if (count($condiciones) > 0) $query .= " WHERE " . implode(' AND ', $condiciones);
        $stmt = $this->conn->prepare($query);
        if ($id_medico) $stmt->bindParam(':medico', $id_medico);
        if ($id_paciente) $stmt->bindParam(':paciente', $id_paciente);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // 4. VERIFICAR DISPONIBILIDAD
    public function verificarDisponibilidad($id_medico, $fecha_cita, $id_cita_excluir = null) {
        $sql = "SELECT COUNT(*) FROM " . $this->table . " 
                WHERE id_medico = :medico AND fecha_cita = :fecha 
                AND estado != 'Cancelada' AND estado != 'Finalizada'";
        if($id_cita_excluir) $sql .= " AND id_cita != :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':medico', $id_medico);
        $stmt->bindParam(':fecha', $fecha_cita);
        if($id_cita_excluir) $stmt->bindParam(':id', $id_cita_excluir);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    // 5. CREAR
    public function crear($datos) {
        $query = 'INSERT INTO ' . $this->table . ' (id_paciente, id_medico, id_servicio, fecha_cita, motivo) 
                  VALUES (:paciente, :medico, :servicio, :fecha, :motivo)';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':paciente', $datos['id_paciente']);
        $stmt->bindParam(':medico', $datos['id_medico']);
        $stmt->bindParam(':servicio', $datos['id_servicio']);
        $stmt->bindParam(':fecha', $datos['fecha_cita']);
        $stmt->bindParam(':motivo', $datos['motivo']);
        return $stmt->execute();
    }

    // 6. ACTUALIZAR
    public function actualizar($datos) {
        $query = 'UPDATE ' . $this->table . ' 
                  SET id_medico = :medico, id_servicio = :servicio, fecha_cita = :fecha, motivo = :motivo, estado = :estado 
                  WHERE id_cita = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $datos['id_cita']);
        $stmt->bindParam(':medico', $datos['id_medico']);
        $stmt->bindParam(':servicio', $datos['id_servicio']);
        $stmt->bindParam(':fecha', $datos['fecha_cita']);
        $stmt->bindParam(':motivo', $datos['motivo']);
        $stmt->bindParam(':estado', $datos['estado']);
        return $stmt->execute();
    }

    // 7. FINALIZAR ATENCIÓN (Diagnóstico, Receta, Triaje, Reposo)
    public function finalizarAtencion($id, $diagnostico, $prescripcion, $peso, $talla, $presion, $temp, $dias_reposo, $fecha_fin_reposo) {
        $query = "UPDATE " . $this->table . " 
                  SET diagnostico = :diagnostico, 
                      prescripcion = :prescripcion, 
                      peso = :peso, 
                      talla = :talla, 
                      presion_arterial = :presion, 
                      temperatura = :temp,
                      dias_reposo = :dias,
                      fecha_fin_reposo = :fecha_fin,
                      estado = 'Finalizada' 
                  WHERE id_cita = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':diagnostico', $diagnostico);
        $stmt->bindParam(':prescripcion', $prescripcion);
        $stmt->bindParam(':peso', $peso);
        $stmt->bindParam(':talla', $talla);
        $stmt->bindParam(':presion', $presion);
        $stmt->bindParam(':temp', $temp);
        $stmt->bindParam(':dias', $dias_reposo);
        $stmt->bindParam(':fecha_fin', $fecha_fin_reposo);
        
        return $stmt->execute();
    }

    // 8. ELIMINAR
    public function eliminar($id) {
        $query = 'DELETE FROM ' . $this->table . ' WHERE id_cita = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}