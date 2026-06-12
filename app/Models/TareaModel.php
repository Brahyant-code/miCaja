<?php
namespace App\Models;

use Core\Model;

// Modelo de ejemplo. Hereda de Core\Model, que ya abre la conexión PDO ($this->db)
// y ofrece atajos seguros: find(), all() y query() (todos con prepared statements).
class TareaModel extends Model {

    // Devuelve todas las tareas, la más reciente primero.
    public function listar() {
        return $this->query('SELECT * FROM tareas ORDER BY id DESC');
    }

    // Busca una tarea por su id (devuelve el registro o null).
    public function buscar($id) {
        return $this->find('tareas', $id);
    }

    // Crea una tarea y devuelve su id.
    public function crear($titulo) {
        $stmt = $this->db->prepare('INSERT INTO tareas (titulo) VALUES (?)');
        $stmt->execute([$titulo]);
        return (int) $this->db->lastInsertId();
    }

    // Actualiza el título y el estado de una tarea.
    // Devuelve cuántas filas cambiaron (0 si el id no existe o nada cambió).
    public function actualizar($id, $titulo, $completada) {
        $stmt = $this->db->prepare('UPDATE tareas SET titulo = ?, completada = ? WHERE id = ?');
        $stmt->execute([$titulo, $completada, $id]);
        return $stmt->rowCount();
    }

    // Elimina una tarea. Devuelve cuántas filas se borraron (0 si no existía).
    public function eliminar($id) {
        $stmt = $this->db->prepare('DELETE FROM tareas WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
