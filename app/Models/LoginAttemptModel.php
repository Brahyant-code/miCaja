<?php
namespace App\Models;

use Core\Model;

class LoginAttemptModel extends Model {
    public function registrarIntento(string $username, string $ip, bool $exitoso) {
        $stmt = $this->db->prepare(
            "INSERT INTO login_intentos (username, ip, exitoso, creado_en) VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$username, $ip, $exitoso ? 1 : 0]);
    }

    public function contarFallosRecientes(string $username, string $ip, int $minutos = 15) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM login_intentos
             WHERE username = ? AND ip = ? AND exitoso = 0 AND creado_en >= DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$username, $ip, $minutos]);
        return (int) $stmt->fetchColumn();
    }

    public function ultimoExito(string $username, string $ip) {
        $stmt = $this->db->prepare(
            "SELECT creado_en FROM login_intentos
             WHERE username = ? AND ip = ? AND exitoso = 1
             ORDER BY creado_en DESC LIMIT 1"
        );
        $stmt->execute([$username, $ip]);
        $fila = $stmt->fetch();
        return $fila ? $fila['creado_en'] : null;
    }
}
