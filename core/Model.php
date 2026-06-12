<?php
namespace Core;

use Core\Database;

class Model {
    protected $db;

    public function __construct() {
        // Al heredar este modelo, la conexión se activa sola de forma segura
        $this->db = Database::getConnection();
    }

    // Ejecuta una consulta con prepared statement y devuelve todas las filas.
    // $params son los valores que reemplazan los placeholders (? o :nombre).
    protected function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Devuelve todas las filas de una tabla.
    // Nota: $tabla NO viene del usuario; debe ser un nombre fijo definido en tu modelo.
    protected function all($tabla) {
        return $this->query("SELECT * FROM {$tabla}");
    }

    // Busca una fila por su id. Devuelve el registro o null si no existe.
    protected function find($tabla, $id) {
        $stmt = $this->db->prepare("SELECT * FROM {$tabla} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }
}
