<?php
namespace App\Controllers;

use Core\Controller;
use Core\Request;
use App\Models\CategoriaModel;

// CRUD de categorías. Mismo patrón que TareaController.
class CategoriaController extends Controller {

    private $categorias;

    public function __construct() {
        $this->categorias = new CategoriaModel();
    }

    // GET /categorias            -> todas
    // GET /categorias?activo=1   -> solo activas
    public function index() {
        $soloActivas = Request::input('activo') == '1';
        $this->exito($this->categorias->listar($soloActivas));
    }

    // GET /categorias/{id}
    public function ver($id) {
        $categoria = $this->categorias->buscar($id);

        if ($categoria === null) {
            $this->error('Categoría no encontrada', 404);
        }

        $this->exito($categoria);
    }

    // POST /categorias
    public function crear() {
        $datos = Request::validar([
            'nombre' => 'required|string|min:2|max:80',
            'activo' => 'numeric|min:0|max:1',
        ]);

        $activo = array_key_exists('activo', $datos) ? (int) $datos['activo'] : 1;

        $id = $this->categorias->crear($datos['nombre'], $activo);

        $this->exito($this->categorias->buscar($id), 'Categoría creada', 201);
    }

    // PUT /categorias/{id}
    public function editar($id) {
        $categoria = $this->categorias->buscar($id);

        if ($categoria === null) {
            $this->error('Categoría no encontrada', 404);
        }

        $datos = Request::validar([
            'nombre' => 'required|string|min:2|max:80',
            'activo' => 'numeric|min:0|max:1',
        ]);

        // Si no enviaron "activo", conservamos el valor actual.
        $activo = array_key_exists('activo', $datos)
            ? (int) $datos['activo']
            : (int) $categoria['activo'];

        $this->categorias->actualizar($id, $datos['nombre'], $activo);

        $this->exito($this->categorias->buscar($id), 'Categoría actualizada');
    }

    // DELETE /categorias/{id}
    public function eliminar($id) {
        $borradas = $this->categorias->eliminar($id);

        if ($borradas === 0) {
            $this->error('Categoría no encontrada', 404);
        }

        $this->exito(null, 'Categoría eliminada');
    }
}
