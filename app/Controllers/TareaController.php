<?php
namespace App\Controllers;

use Core\Controller;
use Core\Request;
use App\Models\TareaModel;

// Controlador de ejemplo: un CRUD básico de "tareas".
// Muestra cómo combinar Controller + Model + Validator + manejo de error central.
class TareaController extends Controller {

    private $tareas;

    public function __construct() {
        $this->tareas = new TareaModel();
    }

    // GET /tareas  ->  lista todas las tareas
    public function index() {
        $this->exito($this->tareas->listar());
    }

    // GET /tareas/{id}  ->  muestra una tarea
    public function ver($id) {
        $tarea = $this->tareas->buscar($id);

        if ($tarea === null) {
            $this->error('Tarea no encontrada', 404);
        }

        $this->exito($tarea);
    }

    // POST /tareas  ->  crea una tarea
    public function crear() {
        // Si la validación falla, el framework responde 422 automáticamente
        // y este método ni siquiera continúa.
        $datos = Request::validar([
            'titulo' => 'required|min:3|max:255',
        ]);

        $id = $this->tareas->crear($datos['titulo']);

        $this->exito($this->tareas->buscar($id), 'Tarea creada', 201);
    }

    // PUT /tareas/{id}  ->  edita una tarea
    public function editar($id) {
        // Primero comprobamos que la tarea exista (404 si no).
        $tarea = $this->tareas->buscar($id);

        if ($tarea === null) {
            $this->error('Tarea no encontrada', 404);
        }

        // "titulo" es obligatorio; "completada" (0 o 1) es opcional.
        // Si la validación falla, el framework responde 422 automáticamente.
        $datos = Request::validar([
            'titulo'     => 'required|min:3|max:255',
            'completada' => 'numeric|min:0|max:1',
        ]);

        // Si no enviaron "completada", conservamos el valor actual.
        $completada = array_key_exists('completada', $datos)
            ? (int) $datos['completada']
            : (int) $tarea['completada'];

        $this->tareas->actualizar($id, $datos['titulo'], $completada);

        // Devolvemos la tarea ya actualizada.
        $this->exito($this->tareas->buscar($id), 'Tarea actualizada');
    }

    // DELETE /tareas/{id}  ->  elimina una tarea
    public function eliminar($id) {
        $borradas = $this->tareas->eliminar($id);

        if ($borradas === 0) {
            $this->error('Tarea no encontrada', 404);
        }

        $this->exito(null, 'Tarea eliminada');
    }
}
