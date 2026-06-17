<?php
namespace App\Controllers;

use Core\Controller;
use Core\Request;
use App\Models\VentaModel;

// Registro y consulta de ventas. crear() valida manualmente el enum metodo_pago
// y el arreglo anidado "items", que el Validator del framework no cubre.
class VentaController extends Controller {

    private $ventas;
    private $metodosValidos = ['efectivo', 'tarjeta', 'transferencia'];

    public function __construct() {
        $this->ventas = new VentaModel();
    }

    // GET /ventas
    public function index() {
        $this->exito($this->ventas->listar());
    }

    // GET /ventas/{id}  -> cabecera + líneas de detalle
    public function ver($id) {
        $venta = $this->ventas->buscar($id);

        if ($venta === null) {
            $this->error('Venta no encontrada', 404);
        }

        $this->exito($venta);
    }

    // POST /ventas  -> registra una venta de forma atómica
    public function crear() {
        // Campos escalares con el Validator.
        $datos = Request::validar([
            'metodo_pago'  => 'required|string',
            'monto_pagado' => 'required|numeric',
            'cajero'       => 'string|max:100',
            'cliente'      => 'string|max:120',
            'nota'         => 'string|max:1000',
        ]);

        $errores = [];

        // Enum metodo_pago (no existe regla "in" en el Validator).
        if (!in_array($datos['metodo_pago'], $this->metodosValidos, true)) {
            $errores['metodo_pago'][] = 'Método de pago inválido. Use: '
                . implode(', ', $this->metodosValidos) . '.';
        }

        // Arreglo "items" (el Validator no valida estructuras anidadas).
        $items = Request::input('items');
        if (!is_array($items) || count($items) === 0) {
            $errores['items'][] = 'Debe incluir al menos un producto.';
        } else {
            foreach ($items as $i => $item) {
                if (!is_array($item) || !isset($item['producto_id'], $item['cantidad'])) {
                    $errores['items'][] = "El ítem #{$i} es inválido.";
                    continue;
                }
                if (!$this->esEnteroPositivo($item['producto_id'])) {
                    $errores['items'][] = "El producto del ítem #{$i} es inválido.";
                }
                if (!$this->esEnteroPositivo($item['cantidad'])) {
                    $errores['items'][] = "La cantidad del ítem #{$i} debe ser un entero >= 1.";
                }
            }
        }

        if ($errores) {
            $this->error('Datos inválidos', 422, $errores);
        }

        $payload = [
            'metodo_pago'  => $datos['metodo_pago'],
            'monto_pagado' => (float) $datos['monto_pagado'],
            'cajero'       => $datos['cajero'] ?? null,
            'cliente'      => $datos['cliente'] ?? null,
            'nota'         => $datos['nota'] ?? null,
            'items'        => array_map(function ($it) {
                return [
                    'producto_id' => (int) $it['producto_id'],
                    'cantidad'    => (int) $it['cantidad'],
                ];
            }, $items),
        ];

        try {
            $id = $this->ventas->registrar($payload);
        } catch (\RuntimeException $e) {
            // Errores de negocio (stock/producto/pago): 409 Conflict.
            $this->error($e->getMessage(), 409);
        }
        // Cualquier \PDOException sube al ErrorHandler central (500).

        $this->exito($this->ventas->buscar($id), 'Venta registrada', 201);
    }

    // DELETE /ventas/{id} -> anula la venta (no la borra físicamente)
    public function anular($id) {
        $venta = $this->ventas->buscar($id);

        if ($venta === null) {
            $this->error('Venta no encontrada', 404);
        }

        $afectadas = $this->ventas->anular($id);

        if ($afectadas === 0) {
            $this->error('La venta ya estaba anulada', 409);
        }

        $this->exito($this->ventas->buscar($id), 'Venta anulada');
    }

    // Acepta "3" o 3; rechaza "3.5", "abc", 0 y negativos.
    private function esEnteroPositivo($valor) {
        return (string) (int) $valor === (string) $valor && (int) $valor >= 1;
    }
}
