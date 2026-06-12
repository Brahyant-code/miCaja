<?php
namespace Core;

class Validator {

    private $datos;
    private $reglas;
    private $errores = [];
    private $validados = [];

    public function __construct(array $datos, array $reglas) {
        $this->datos  = $datos;
        $this->reglas = $reglas;
    }

    // Ejecuta todas las reglas. Devuelve true si todo pasó.
    public function pasa() {
        foreach ($this->reglas as $campo => $cadenaReglas) {
            $valor = $this->datos[$campo] ?? null;
            $reglas = explode('|', $cadenaReglas);

            foreach ($reglas as $regla) {
                // Soporta parámetros: "min:18" -> nombre="min", parametro="18"
                $parametro = null;
                if (strpos($regla, ':') !== false) {
                    list($regla, $parametro) = explode(':', $regla, 2);
                }

                $metodo = 'regla' . ucfirst($regla);
                if (method_exists($this, $metodo)) {
                    $this->$metodo($campo, $valor, $parametro);
                }
            }

            // Solo conservamos los campos que estaban declarados en las reglas
            if (array_key_exists($campo, $this->datos)) {
                $this->validados[$campo] = $valor;
            }
        }

        return empty($this->errores);
    }

    public function errores() {
        return $this->errores;
    }

    // Devuelve solo los campos declarados en las reglas (datos "limpios").
    public function validados() {
        return $this->validados;
    }

    private function agregarError($campo, $mensaje) {
        $this->errores[$campo][] = $mensaje;
    }

    // --- Reglas disponibles (un método privado por regla) ---

    private function reglaRequired($campo, $valor, $parametro) {
        if ($valor === null || $valor === '' || (is_array($valor) && count($valor) === 0)) {
            $this->agregarError($campo, "El campo {$campo} es obligatorio.");
        }
    }

    private function reglaEmail($campo, $valor, $parametro) {
        if ($valor !== null && $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            $this->agregarError($campo, "El campo {$campo} debe ser un correo válido.");
        }
    }

    private function reglaNumeric($campo, $valor, $parametro) {
        if ($valor !== null && $valor !== '' && !is_numeric($valor)) {
            $this->agregarError($campo, "El campo {$campo} debe ser numérico.");
        }
    }

    private function reglaString($campo, $valor, $parametro) {
        if ($valor !== null && !is_string($valor)) {
            $this->agregarError($campo, "El campo {$campo} debe ser texto.");
        }
    }

    // min: para números compara el valor; para texto compara la longitud.
    private function reglaMin($campo, $valor, $parametro) {
        if ($valor === null || $valor === '') {
            return;
        }
        $min = (float) $parametro;
        if (is_numeric($valor)) {
            if ((float) $valor < $min) {
                $this->agregarError($campo, "El campo {$campo} debe ser al menos {$parametro}.");
            }
        } else {
            if (mb_strlen((string) $valor) < $min) {
                $this->agregarError($campo, "El campo {$campo} debe tener al menos {$parametro} caracteres.");
            }
        }
    }

    // max: para números compara el valor; para texto compara la longitud.
    private function reglaMax($campo, $valor, $parametro) {
        if ($valor === null || $valor === '') {
            return;
        }
        $max = (float) $parametro;
        if (is_numeric($valor)) {
            if ((float) $valor > $max) {
                $this->agregarError($campo, "El campo {$campo} no debe ser mayor que {$parametro}.");
            }
        } else {
            if (mb_strlen((string) $valor) > $max) {
                $this->agregarError($campo, "El campo {$campo} no debe tener más de {$parametro} caracteres.");
            }
        }
    }
}
