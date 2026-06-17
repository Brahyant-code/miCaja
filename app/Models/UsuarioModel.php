<?php
namespace App\Models;

use Core\Model;

class UsuarioModel extends Model {
    protected $tabla = 'usuarios';

    public function buscarPorUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    public function crear($username, $passwordPlain) {
        $hash = password_hash($passwordPlain, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO usuarios (username, password_hash, nombre, creado_en) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$username, $hash, $username]);
        return $this->db->lastInsertId();
    }
}
