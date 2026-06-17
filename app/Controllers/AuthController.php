<?php
namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use App\Models\UsuarioModel;
use App\Models\LoginAttemptModel;

class AuthController extends Controller {
    private $usuarios;
    private $intentos;

    public function __construct() {
        $this->usuarios = new UsuarioModel();
        $this->intentos = new LoginAttemptModel();
    }

    // POST /login
    public function login() {
        $datos = Request::validar([
            'username' => 'required|string|min:1',
            'password' => 'required|string|min:1',
        ]);

        $username = trim($datos['username']);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $maxFallos = 5;
        $ventanaMinutos = 15;

        $fallos = $this->intentos->contarFallosRecientes($username, $ip, $ventanaMinutos);
        if ($fallos >= $maxFallos) {
            $this->error('Demasiados intentos fallidos. Intenta de nuevo en 15 minutos.', 429);
        }

        $user = $this->usuarios->buscarPorUsername($username);
        $exitoso = false;

        if ($user !== null && isset($user['password_hash']) && password_verify($datos['password'], $user['password_hash'])) {
            $exitoso = true;
        }

        $this->intentos->registrarIntento($username, $ip, $exitoso);

        if (!$exitoso) {
            $this->error('Credenciales inválidas', 401);
        }

        $token = Auth::generarToken($user);
        $this->exito(['token' => $token, 'usuario' => ['id' => (int)$user['id'], 'username' => $user['username'], 'nombre' => $user['nombre'] ]], 'Autenticado');
    }
}
