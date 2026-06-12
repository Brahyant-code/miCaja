<?php
namespace App\Controllers;

use Core\Controller;

class HomeController extends Controller {
    
    public function index() {
        // En vez de una vista, mandamos un JSON que Vue va a leer
        $this->exito([
            'framework' => 'Nova Framework + Vue.js',
            'autor'     => 'Brahyant',
        ], 'Backend conectado con éxito');
    }
}