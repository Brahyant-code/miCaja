<?php
namespace Core;

class Controller {
    // Respuesta exitosa con el sobre estándar { exito, mensaje, datos, errores }.
    protected function exito($datos = null, $mensaje = '', $codigo = 200) {
        Response::exito($datos, $mensaje, $codigo);
    }

    // Respuesta de error con el mismo sobre estándar.
    protected function error($mensaje = '', $codigo = 400, $errores = null) {
        Response::error($mensaje, $codigo, $errores);
    }
}
