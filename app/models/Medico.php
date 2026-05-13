<?php
class Medico {
    private $conn;
    private $table = 'medicos';

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. LISTAR MÉDICOS
    public function leer() {
        $query = 'SELECT m.id_medico, u.nombre, u.email, e.nombre as especialidad 
                  FROM ' . $this->table . ' m
                  JOIN usuarios u ON m.id_usuario = u.id_usuario
                  JOIN especialidades e ON m.id_especialidad = e.id_especialidad
                  ORDER BY u.nombre ASC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // 2. OBTENER POR ID
    public function obtenerPorId($id) {
        $query = 'SELECT m.id_medico, u.nombre, e.nombre as especialidad 
                  FROM ' . $this->table . ' m
                  JOIN usuarios u ON m.id_usuario = u.id_usuario
                  JOIN especialidades e ON m.id_especialidad = e.id_especialidad
                  WHERE m.id_medico = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. OBTENER ESPECIALIDADES
    public function obtenerEspecialidades() {
        $query = 'SELECT * FROM especialidades';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // 4. CREAR MÉDICO
    public function crear($datos) {
        try {
            $this->conn->beginTransaction();
            // Insertar Usuario
            $queryUser = "INSERT INTO usuarios (nombre, email, password, id_rol) VALUES (:nombre, :email, :password, 2)";
            $stmtUser = $this->conn->prepare($queryUser);
            $passwordHash = password_hash($datos['password'], PASSWORD_BCRYPT);
            $stmtUser->bindParam(':nombre', $datos['nombre']);
            $stmtUser->bindParam(':email', $datos['email']);
            $stmtUser->bindParam(':password', $passwordHash);
            $stmtUser->execute();
            $idUsuario = $this->conn->lastInsertId();

            // Insertar Médico
            $queryMedico = "INSERT INTO medicos (id_usuario, id_especialidad) VALUES (:id_usuario, :id_especialidad)";
            $stmtMedico = $this->conn->prepare($queryMedico);
            $stmtMedico->bindParam(':id_usuario', $idUsuario);
            $stmtMedico->bindParam(':id_especialidad', $datos['id_especialidad']);
            $stmtMedico->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // --- GESTIÓN DE HORARIOS ---

    // 5. OBTENER HORARIOS DE UN MÉDICO
    public function obtenerHorarios($id_medico) {
        $query = "SELECT * FROM horarios_medicos WHERE id_medico = :id ORDER BY FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'), hora_inicio";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_medico);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 6. AGREGAR HORARIO
    public function agregarHorario($id_medico, $dia, $inicio, $fin) {
        $query = "INSERT INTO horarios_medicos (id_medico, dia_semana, hora_inicio, hora_fin) VALUES (:id, :dia, :inicio, :fin)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_medico);
        $stmt->bindParam(':dia', $dia);
        $stmt->bindParam(':inicio', $inicio);
        $stmt->bindParam(':fin', $fin);
        return $stmt->execute();
    }

    // 7. ELIMINAR HORARIO
    public function eliminarHorario($id_horario) {
        $query = "DELETE FROM horarios_medicos WHERE id_horario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_horario);
        return $stmt->execute();
    }

    // 8. VERIFICAR SI TRABAJA EN UNA FECHA/HORA ESPECÍFICA
    public function verificaHorarioLaboral($id_medico, $fechaHora) {
        // Convertir fecha a día de semana en español
        $dias = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado'];
        $diaIngles = date('l', strtotime($fechaHora));
        $diaSemana = $dias[$diaIngles];
        $horaCita = date('H:i:s', strtotime($fechaHora));

        // Verificar si tiene horarios definidos
        $check = "SELECT COUNT(*) FROM horarios_medicos WHERE id_medico = :id";
        $stmtCheck = $this->conn->prepare($check);
        $stmtCheck->bindParam(':id', $id_medico);
        $stmtCheck->execute();
        
        if ($stmtCheck->fetchColumn() == 0) {
            // Si NO tiene horarios definidos, asumimos horario estándar 08:00 - 20:00
            $horaInt = (int)date('H', strtotime($fechaHora));
            return ($horaInt >= 8 && $horaInt < 20);
        }

        // Si TIENE horarios, verificamos si la hora calza en alguno
        $query = "SELECT COUNT(*) FROM horarios_medicos 
                  WHERE id_medico = :id 
                  AND dia_semana = :dia 
                  AND :hora >= hora_inicio 
                  AND :hora < hora_fin"; // < hora_fin para no agendar justo al cierre
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_medico);
        $stmt->bindParam(':dia', $diaSemana);
        $stmt->bindParam(':hora', $horaCita);
        $stmt->execute();

        return $stmt->fetchColumn() > 0;
    }
}