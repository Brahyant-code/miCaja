<?php
namespace App\Models;

use Core\Model;

// Modelo de productos del catálogo. Une el nombre de la categoría en los listados.
class ProductoModel extends Model {

    // SELECT base que adjunta el nombre de la categoría (LEFT JOIN por si fuera NULL).
    private $selectBase =
        'SELECT p.*, c.nombre AS categoria_nombre
         FROM productos p
         LEFT JOIN categorias c ON c.id = p.categoria_id';

    // Lista productos con el nombre de su categoría.
    // $soloActivos filtra activo = 1; $categoriaId filtra por categoría si se entrega.
    public function listar($soloActivos = false, $categoriaId = null) {
        $where  = [];
        $params = [];

        if ($soloActivos) {
            $where[] = 'p.activo = 1';
        }
        if ($categoriaId !== null) {
            $where[] = 'p.categoria_id = ?';
            $params[] = (int) $categoriaId;
        }

        $sql = $this->selectBase;
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY p.nombre ASC';

        return $this->query($sql, $params);
    }

    // Busca un producto (con categoria_nombre). Devuelve la fila o null.
    public function buscar($id) {
        $filas = $this->query($this->selectBase . ' WHERE p.id = ? LIMIT 1', [(int) $id]);
        return $filas[0] ?? null;
    }

    // Crea un producto a partir de un arreglo ya saneado. Devuelve su id.
    public function crear($datos) {
        $stmt = $this->db->prepare(
            'INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, activo)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $datos['categoria_id'],
            $datos['nombre'],
            $datos['descripcion'] ?? null,
            $datos['precio'],
            $datos['stock'],
            $datos['activo'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    // Actualiza los campos de un producto. Devuelve cuántas filas cambiaron.
    public function actualizar($id, $datos) {
        $stmt = $this->db->prepare(
            'UPDATE productos SET
                categoria_id = ?, nombre = ?, descripcion = ?,
                precio = ?, stock = ?, activo = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $datos['categoria_id'],
            $datos['nombre'],
            $datos['descripcion'] ?? null,
            $datos['precio'],
            $datos['stock'],
            $datos['activo'],
            (int) $id,
        ]);
        return $stmt->rowCount();
    }

    // Guarda la ruta relativa de la imagen (p. ej. "uploads/productos/5-ab12.jpg").
    public function actualizarImagen($id, $rutaRelativa) {
        $stmt = $this->db->prepare('UPDATE productos SET imagen_url = ? WHERE id = ?');
        $stmt->execute([$rutaRelativa, (int) $id]);
        return $stmt->rowCount();
    }

    // Devuelve un mapa [nombre_categoria_en_minúsculas => id] para resolver
    // categorías por nombre durante la importación masiva.
    public function mapaCategoriasPorNombre() {
        $filas = $this->query('SELECT id, nombre FROM categorias');
        $mapa = [];
        foreach ($filas as $f) {
            $mapa[mb_strtolower(trim($f['nombre']))] = (int) $f['id'];
        }
        return $mapa;
    }

    // Devuelve todos los productos indexados por id (para la importación upsert).
    public function indexadosPorId() {
        $filas = $this->query('SELECT * FROM productos');
        $mapa = [];
        foreach ($filas as $f) {
            $mapa[(int) $f['id']] = $f;
        }
        return $mapa;
    }

    // Devuelve el conjunto de ids de categoría existentes (para validar categoria_id).
    public function idsCategorias() {
        $filas = $this->query('SELECT id FROM categorias');
        return array_map(function ($f) { return (int) $f['id']; }, $filas);
    }

    // Elimina un producto. Devuelve cuántas filas se borraron (0 si no existía).
    // Nota: venta_detalle.producto_id es FK RESTRICT: si el producto ya fue vendido,
    // el DELETE lanza excepción; en ese caso conviene desactivarlo (activo = 0).
    public function eliminar($id) {
        $stmt = $this->db->prepare('DELETE FROM productos WHERE id = ?');
        $stmt->execute([(int) $id]);
        return $stmt->rowCount();
    }
}
