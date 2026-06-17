<?php
namespace App\Models;

use Core\Model;

// Modelo de categorías. Agrupa los productos del local (Combos, Bebidas, etc.).
// Hereda de Core\Model: $this->db (PDO) + atajos query()/all()/find().
class CategoriaModel extends Model {

    // Lista categorías ordenadas por nombre. Si $soloActivas, filtra activo = 1.
    public function listar($soloActivas = false) {
        if ($soloActivas) {
            return $this->query('SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC');
        }
        return $this->query('SELECT * FROM categorias ORDER BY nombre ASC');
    }

    // Busca una categoría por su id (devuelve el registro o null).
    public function buscar($id) {
        return $this->find('categorias', $id);
    }

    // Crea una categoría y devuelve su id.
    public function crear($nombre, $activo = 1) {
        $stmt = $this->db->prepare('INSERT INTO categorias (nombre, activo) VALUES (?, ?)');
        $stmt->execute([$nombre, $activo]);
        return (int) $this->db->lastInsertId();
    }

    // Actualiza nombre y estado. Devuelve cuántas filas cambiaron.
    public function actualizar($id, $nombre, $activo) {
        $stmt = $this->db->prepare('UPDATE categorias SET nombre = ?, activo = ? WHERE id = ?');
        $stmt->execute([$nombre, $activo, $id]);
        return $stmt->rowCount();
    }

    // Elimina una categoría. Devuelve cuántas filas se borraron (0 si no existía).
    // Nota: si la categoría tiene productos (FK RESTRICT), el DELETE lanza excepción;
    // en ese caso conviene desactivarla (activo = 0) en vez de borrarla.
    public function eliminar($id) {
        $stmt = $this->db->prepare('DELETE FROM categorias WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
