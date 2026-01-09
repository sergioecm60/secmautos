<?php

class UsuarioService {

    public static function obtenerSucursalesActivas($pdo) {
        return $pdo->query("SELECT id, nombre FROM sucursales WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerTodosConDetalles($pdo) {
        return $pdo->query("SELECT u.*, r.nombre as rol_nombre, r.nivel as rol_nivel, GROUP_CONCAT(s.nombre SEPARATOR ', ') as sucursal_nombre
                       FROM usuarios u 
                       JOIN roles r ON u.rol_id = r.id 
                       LEFT JOIN usuario_sucursales us ON u.id = us.usuario_id
                       LEFT JOIN sucursales s ON us.sucursal_id = s.id
                       GROUP BY u.id
                       ORDER BY r.nivel DESC, u.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerSesionesActivas($pdo) {
        return $pdo->query("SELECT s.*, u.username, u.nombre, u.apellido 
                       FROM sesiones s 
                       JOIN usuarios u ON s.usuario_id = u.id 
                       WHERE s.activa = 1 AND s.expires_at > NOW() 
                       ORDER BY s.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function contarLogsActividad($pdo) {
        return $pdo->query("SELECT COUNT(*) FROM logs_actividad")->fetchColumn();
    }

    public static function obtenerLogsActividad($pdo, $pagina, $por_pagina) {
        $offset = ($pagina - 1) * $por_pagina;
        $stmt = $pdo->prepare("SELECT l.*, u.username 
                   FROM logs_actividad l 
                   LEFT JOIN usuarios u ON l.usuario_id = u.id 
                   ORDER BY l.created_at DESC 
                   LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Obtiene un usuario por su ID, incluyendo las sucursales asignadas.
     * @param PDO $pdo
     * @param int $id
     * @return array|null
     */
    public static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return null;
        }

        // Obtener las sucursales asignadas desde la tabla pivote
        $stmt_sucursales = $pdo->prepare("SELECT sucursal_id FROM usuario_sucursales WHERE usuario_id = ?");
        $stmt_sucursales->execute([$id]);
        $sucursal_ids = $stmt_sucursales->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // El frontend espera strings para la comparación con los valores de los checkboxes
        $usuario['sucursal_ids'] = array_map('strval', $sucursal_ids);

        return $usuario;
    }

    /**
     * Crea un nuevo usuario y asigna sus sucursales.
     * @param PDO $pdo
     * @param array $data
     * @return array
     * @throws Exception
     */
    public static function crear($pdo, $data) {
        // Validaciones
        if (empty($data['username']) || empty($data['password']) || empty($data['nombre']) || empty($data['apellido']) || empty($data['rol_id'])) {
            throw new Exception("Los campos de usuario, contraseña, nombre, apellido y rol son obligatorios.");
        }
        if (strlen($data['password']) < 8) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres.");
        }
        $sucursales = $data['sucursales'] ?? [];
        if (empty($sucursales)) {
            throw new Exception("Debe seleccionar al menos una sucursal para el usuario.");
        }

        $pdo->beginTransaction();
        try {
            // Insertar usuario
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (username, email, password, nombre, apellido, telefono, rol_id, observaciones) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                trim($data['username']),
                empty($data['email']) ? null : trim($data['email']),
                password_hash($data['password'], PASSWORD_DEFAULT),
                trim($data['nombre']),
                trim($data['apellido']),
                trim($data['telefono']),
                $data['rol_id'],
                trim($data['observaciones'])
            ]);
            $usuario_id = $pdo->lastInsertId();

            // Asignar sucursales
            $stmt_suc = $pdo->prepare("INSERT INTO usuario_sucursales (usuario_id, sucursal_id) VALUES (?, ?)");
            foreach ($sucursales as $sucursal_id) {
                $stmt_suc->execute([$usuario_id, $sucursal_id]);
            }

            $pdo->commit();
            return ['success' => true, 'message' => 'Usuario creado exitosamente.'];

        } catch (PDOException $e) {
            $pdo->rollBack();
            // El código de error '23000' indica una violación de integridad (ej. clave duplicada)
            if ($e->getCode() == '23000') {
                $errorMessage = $e->getMessage();
                if (str_contains($errorMessage, "'username'")) {
                    throw new Exception("El nombre de usuario '{$data['username']}' ya está en uso.");
                }
                // Si no es el username, podría ser el email (si hay más claves únicas, se puede mejorar)
                if (!empty($data['email']) && str_contains($errorMessage, "'email'")) {
                    throw new Exception("El email '{$data['email']}' ya está en uso.");
                }
            }
            throw new Exception("Error al crear el usuario: " . $e->getMessage());
        }
    }

    /**
     * Edita un usuario existente y actualiza sus sucursales.
     * @param PDO $pdo
     * @param array $data
     * @param int $current_user_id ID del usuario que realiza la acción.
     * @return array
     */
    public static function editar($pdo, $data, $current_user_id) {
        $usuario_id = filter_var($data['usuario_id'], FILTER_VALIDATE_INT);
        if (!$usuario_id) {
            return ['success' => false, 'message' => 'ID de usuario no válido.'];
        }
        if ($usuario_id == 1 && $current_user_id != 1) {
            return ['success' => false, 'message' => 'Solo el SuperAdmin principal puede editarse a sí mismo.'];
        }
        
        $sucursales = $data['sucursales'] ?? [];
        if (empty($sucursales)) {
            return ['success' => false, 'message' => 'Debe seleccionar al menos una sucursal.'];
        }

        $pdo->beginTransaction();
        try {
            // Actualizar datos principales del usuario
            $sql_parts = ['username = ?', 'email = ?', 'nombre = ?', 'apellido = ?', 'telefono = ?', 'rol_id = ?', 'observaciones = ?'];
            $params = [trim($data['username']), empty($data['email']) ? null : trim($data['email']), trim($data['nombre']), trim($data['apellido']), trim($data['telefono']), $data['rol_id'], trim($data['observaciones'])];

            // Manejar el estado activo/inactivo desde el formulario de edición
            $sql_parts[] = 'activo = ?';
            $params[] = isset($data['activo']) ? 1 : 0;
            
            if (!empty($data['password'])) {
                if (strlen($data['password']) < 8) {
                    throw new Exception("La nueva contraseña debe tener al menos 8 caracteres.");
                }
                $sql_parts[] = 'password = ?';
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $sql = "UPDATE usuarios SET " . implode(', ', $sql_parts) . " WHERE id = ?";
            $params[] = $usuario_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Actualizar sucursales (borrar y re-insertar)
            $stmt_delete = $pdo->prepare("DELETE FROM usuario_sucursales WHERE usuario_id = ?");
            $stmt_delete->execute([$usuario_id]);

            $stmt_insert = $pdo->prepare("INSERT INTO usuario_sucursales (usuario_id, sucursal_id) VALUES (?, ?)");
            foreach ($sucursales as $sucursal_id) {
                $stmt_insert->execute([$usuario_id, $sucursal_id]);
            }

            $pdo->commit();
            return ['success' => true, 'message' => 'Usuario actualizado exitosamente.'];

        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Error al actualizar el usuario: ' . $e->getMessage()];
        }
    }

    public static function cambiarEstado($pdo, $id, $estado, $current_user_id) {
        if (!$id) { return ['success' => false, 'message' => 'ID de usuario no proporcionado.']; }
        if ($id == 1) { return ['success' => false, 'message' => 'No se puede desactivar al usuario principal.']; }
        if ($id == $current_user_id) { return ['success' => false, 'message' => 'No puede cambiar su propio estado.']; }
        $stmt = $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
        $stmt->execute([$estado ? 1 : 0, $id]);
        return ['success' => true, 'message' => 'Estado del usuario actualizado correctamente.'];
    }

    /**
     * Elimina permanentemente un usuario de la base de datos.
     * ¡ACCIÓN DESTRUCTIVA! Usar con precaución.
     *
     * @param PDO $pdo Conexión a la base de datos.
     * @param int $id ID del usuario a eliminar.
     * @param int $current_user_id ID del usuario que realiza la acción.
     * @return array Resultado de la operación.
     */
    public static function eliminar($pdo, $id, $current_user_id) {
        if (empty($id)) {
            return ['success' => false, 'message' => 'No se proporcionó el ID del usuario.'];
        }

        // Proteger al usuario SuperAdmin principal (asumiendo que es el ID 1)
        if ($id == 1) { 
            return ['success' => false, 'message' => 'No se puede eliminar al usuario principal del sistema.'];
        }
        
        // Un usuario no puede eliminarse a sí mismo
        if ($id == $current_user_id) { 
            return ['success' => false, 'message' => 'No puede eliminarse a sí mismo.'];
        }

        try {
            $pdo->beginTransaction();

            // Desvincular de tablas relacionadas para evitar errores de FK (ej. usuario_sucursales)
            $stmt_sucursales = $pdo->prepare("DELETE FROM usuario_sucursales WHERE usuario_id = ?");
            $stmt_sucursales->execute([$id]);

            // Consulta principal de eliminación
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                return ['success' => true, 'message' => 'Usuario eliminado permanentemente.'];
            } else {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'El usuario no fue encontrado o ya fue eliminado.'];
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Error: No se puede eliminar el usuario porque tiene registros asociados (logs, ventas, etc.). Considere desactivarlo en su lugar.'];
        }
    }
}