<?php
namespace App\Controllers;

use Core\Controller;
use Core\Request;
use App\Models\ProductoModel;

// CRUD de productos (JSON) + subida de imagen (multipart, vía $_FILES).
class ProductoController extends Controller {

    private $productos;

    // MIME permitidos -> extensión canónica para el nombre del archivo guardado.
    private $mimePermitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    private $maxBytes = 2097152; // 2 MB

    public function __construct() {
        $this->productos = new ProductoModel();
    }

    // GET /productos
    // GET /productos?activo=1&categoria_id=3
    public function index() {
        $soloActivos = Request::input('activo') == '1';

        $categoriaId = Request::input('categoria_id');
        $categoriaId = ($categoriaId === null || $categoriaId === '')
            ? null
            : (int) $categoriaId;

        $this->exito($this->productos->listar($soloActivos, $categoriaId));
    }

    // GET /productos/{id}
    public function ver($id) {
        $producto = $this->productos->buscar($id);

        if ($producto === null) {
            $this->error('Producto no encontrado', 404);
        }

        $this->exito($producto);
    }

    // POST /productos
    public function crear() {
        $datos = Request::validar([
            'categoria_id' => 'required|numeric',
            'nombre'       => 'required|string|min:2|max:150',
            'descripcion'  => 'string|max:1000',
            'precio'       => 'required|numeric|min:1|max:9999999',
            'stock'        => 'numeric|min:0|max:9999',
            'activo'       => 'numeric|min:0|max:1',
        ]);

        $errores = $this->validarReglasNegocio($datos);
        if ($errores) {
            $this->error('Datos inválidos', 422, $errores);
        }

        $payload = $this->armarPayload($datos);

        $id = $this->productos->crear($payload);
        $this->guardarImagenSiHayArchivo($id);

        $this->exito($this->productos->buscar($id), 'Producto creado', 201);
    }

    // PUT /productos/{id}
    public function editar($id) {
        $producto = $this->productos->buscar($id);

        if ($producto === null) {
            $this->error('Producto no encontrado', 404);
        }

        $datos = Request::validar([
            'categoria_id' => 'required|numeric',
            'nombre'       => 'required|string|min:2|max:150',
            'descripcion'  => 'string|max:1000',
            'precio'       => 'required|numeric|min:1|max:9999999',
            'stock'        => 'numeric|min:0|max:9999',
            'activo'       => 'numeric|min:0|max:1',
        ]);

        $errores = $this->validarReglasNegocio($datos);
        if ($errores) {
            $this->error('Datos inválidos', 422, $errores);
        }

        $payload = $this->armarPayload($datos, $producto);

        $this->productos->actualizar($id, $payload);
        $this->guardarImagenSiHayArchivo($id);

        $this->exito($this->productos->buscar($id), 'Producto actualizado');
    }

    // DELETE /productos/{id}
    public function eliminar($id) {
        $borrados = $this->productos->eliminar($id);

        if ($borrados === 0) {
            $this->error('Producto no encontrado', 404);
        }

        $this->exito(null, 'Producto eliminado');
    }

    // POST /productos/importar
    // Importación inteligente (upsert). Recibe JSON { "productos": [ {id?, nombre,
    // precio, stock, descripcion?, activo?, categoria_id? | categoria?}, ... ] }.
    // - Fila con 'id' de un producto existente -> se ACTUALIZA si cambió (o se deja igual).
    // - Fila sin 'id' (o con id inexistente)    -> se INSERTA como nuevo.
    // Devuelve un resumen: creados / actualizados / sin_cambios / errores por fila.
    public function importar() {
        $filas = Request::input('productos');

        if (!is_array($filas) || count($filas) === 0) {
            $this->error('No se recibieron productos para importar.', 422);
        }
        if (count($filas) > 1000) {
            $this->error('Demasiadas filas. Importa máximo 1000 a la vez.', 422);
        }

        $mapaCategorias = $this->productos->mapaCategoriasPorNombre();
        $idsValidos     = $this->productos->idsCategorias();
        $existentes     = $this->productos->indexadosPorId();

        $creados = 0;
        $actualizados = 0;
        $sinCambios = 0;
        $errores = [];

        foreach ($filas as $i => $fila) {
            $numFila = $i + 1; // 1-indexado para mensajes legibles
            $nombre  = isset($fila['nombre']) ? trim((string) $fila['nombre']) : '';

            // id de la fila (para reconocer un producto existente).
            $id = (isset($fila['id']) && $fila['id'] !== '') ? (int) $fila['id'] : null;

            // Resolver la categoría: por categoria_id o por nombre de categoría.
            $categoriaId = null;
            if (isset($fila['categoria_id']) && $fila['categoria_id'] !== '') {
                $cid = (int) $fila['categoria_id'];
                if (in_array($cid, $idsValidos, true)) {
                    $categoriaId = $cid;
                }
            } elseif (isset($fila['categoria']) && trim((string) $fila['categoria']) !== '') {
                $clave = mb_strtolower(trim((string) $fila['categoria']));
                $categoriaId = $mapaCategorias[$clave] ?? null;
            }

            // Validaciones por fila.
            $errFila = [];
            if ($nombre === '' || mb_strlen($nombre) < 2) {
                $errFila[] = 'nombre inválido';
            }
            if (!isset($fila['precio']) || !is_numeric($fila['precio']) || (float) $fila['precio'] <= 0) {
                $errFila[] = 'precio inválido (debe ser > 0)';
            }
            if ($categoriaId === null) {
                $errFila[] = 'categoría no encontrada';
            }

            $stock = 0;
            if (isset($fila['stock']) && $fila['stock'] !== '') {
                if (!$this->esEnteroNoNegativo($fila['stock'])) {
                    $errFila[] = 'stock inválido (entero >= 0)';
                } else {
                    $stock = (int) $fila['stock'];
                }
            }

            if ($errFila) {
                $errores[] = [
                    'fila'    => $numFila,
                    'nombre'  => $nombre,
                    'mensaje' => implode(', ', $errFila),
                ];
                continue;
            }

            $payload = [
                'categoria_id' => $categoriaId,
                'nombre'       => $nombre,
                'descripcion'  => isset($fila['descripcion']) ? trim((string) $fila['descripcion']) : null,
                'precio'       => (float) $fila['precio'],
                'stock'        => $stock,
                'activo'       => array_key_exists('activo', $fila) ? (int) (bool) $fila['activo'] : 1,
            ];

            // ¿Producto existente? -> actualizar si cambió; si no, insertar.
            if ($id !== null && isset($existentes[$id])) {
                if ($this->sinCambios($payload, $existentes[$id])) {
                    $sinCambios++;
                } else {
                    $this->productos->actualizar($id, $payload);
                    $actualizados++;
                }
            } else {
                $this->productos->crear($payload);
                $creados++;
            }
        }

        $this->exito([
            'creados'      => $creados,
            'actualizados' => $actualizados,
            'sin_cambios'  => $sinCambios,
            'total'        => count($filas),
            'errores'      => $errores,
        ], "Importación: {$creados} creados, {$actualizados} actualizados, {$sinCambios} sin cambios.");
    }

    // Compara el payload nuevo contra la fila existente (normalizando tipos).
    // Devuelve true si NO hay diferencias en los campos relevantes.
    private function sinCambios($payload, $actual) {
        return (int) $payload['categoria_id'] === (int) $actual['categoria_id']
            && trim((string) $payload['nombre']) === trim((string) $actual['nombre'])
            && (string) ($payload['descripcion'] ?? '') === (string) ($actual['descripcion'] ?? '')
            && (float) $payload['precio'] === (float) $actual['precio']
            && (int) $payload['stock'] === (int) $actual['stock']
            && (int) $payload['activo'] === (int) $actual['activo'];
    }

    // POST /productos/{id}/imagen  (multipart/form-data, campo de archivo: "imagen")
    // Sube/reemplaza la imagen del producto. Lee $_FILES directamente porque
    // Request::body() solo decodifica JSON desde php://input.
    public function subirImagen($id) {
        $producto = $this->productos->buscar($id);

        if ($producto === null) {
            $this->error('Producto no encontrado', 404);
        }

        // 1. Verificar que llegó un archivo bajo el campo "imagen".
        if (!isset($_FILES['imagen']) || !is_array($_FILES['imagen'])) {
            $this->error('No se recibió ningún archivo en el campo "imagen".', 422);
        }
        $archivo = $_FILES['imagen'];

        // 2. Revisar el código de error de la subida.
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $excedeTam = in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            $mensaje = $excedeTam
                ? 'El archivo excede el tamaño permitido.'
                : 'Error al subir el archivo (código ' . $archivo['error'] . ').';
            $this->error($mensaje, 422);
        }

        // 3. Confirmar que es realmente una subida HTTP (anti-spoofing).
        if (!is_uploaded_file($archivo['tmp_name'])) {
            $this->error('Subida inválida.', 422);
        }

        // 4. Validar tamaño.
        if ($archivo['size'] > $this->maxBytes) {
            $this->error('La imagen no debe superar los 2 MB.', 422);
        }

        // 5. Validar el MIME real del contenido (no confiar en la extensión enviada).
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($archivo['tmp_name']);
        if (!isset($this->mimePermitidos[$mime])) {
            $this->error('Formato no permitido. Use JPG, PNG o WEBP.', 422);
        }
        $extension = $this->mimePermitidos[$mime];

        // 6. Carpeta destino física dentro de public/ (servida como estática).
        $directorioPublic  = realpath(__DIR__ . '/../../public');
        $subcarpeta        = 'uploads/productos';
        $directorioDestino = $directorioPublic . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $subcarpeta);

        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        // 7. Nombre único y seguro: <id>-<aleatorio>.<ext> (no usar el nombre del cliente).
        $nombreArchivo = ((int) $id) . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $rutaAbsoluta  = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;
        $rutaRelativa  = $subcarpeta . '/' . $nombreArchivo; // p.ej. uploads/productos/5-ab12.jpg

        // 8. Mover el archivo desde el temporal.
        if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
            $this->error('No se pudo guardar la imagen en el servidor.', 500);
        }

        // 9. Borrar la imagen anterior si existía (limpieza).
        if (!empty($producto['imagen_url'])) {
            $anterior = $directorioPublic . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $producto['imagen_url']);
            $anteriorReal = realpath($anterior);
            if ($anteriorReal !== false
                && is_file($anteriorReal)
                && strpos($anteriorReal, $directorioDestino) === 0) {
                @unlink($anteriorReal);
            }
        }

        // 10. Guardar la ruta relativa en BD y devolver el producto actualizado.
        $this->productos->actualizarImagen($id, $rutaRelativa);

        $this->exito($this->productos->buscar($id), 'Imagen actualizada');
    }

    // --- Validaciones que el Validator no cubre (no hay reglas integer / > 0) ---
    private function validarReglasNegocio($datos) {
        $errores = [];

        if (isset($datos['precio']) && (float) $datos['precio'] <= 0) {
            $errores['precio'][] = 'El precio debe ser mayor que 0.';
        }
        if (isset($datos['precio']) && (float) $datos['precio'] > 9999999) {
            $errores['precio'][] = 'El precio no puede superar 9.999.999.';
        }

        if (array_key_exists('stock', $datos) && !$this->esEnteroNoNegativo($datos['stock'])) {
            $errores['stock'][] = 'El stock debe ser un entero mayor o igual a 0.';
        }
        if (array_key_exists('stock', $datos) && (int) $datos['stock'] > 9999) {
            $errores['stock'][] = 'El stock no puede superar 9.999 unidades.';
        }

        if (isset($datos['categoria_id'])
            && (!$this->esEnteroNoNegativo($datos['categoria_id']) || (int) $datos['categoria_id'] < 1)) {
            $errores['categoria_id'][] = 'La categoría debe ser un identificador válido.';
        }

        return $errores;
    }

    // Guarda una imagen de producto si viene en la petición multipart/form-data.
    private function guardarImagenSiHayArchivo($id) {
        if (!isset($_FILES['imagen']) || !is_array($_FILES['imagen']) || !isset($_FILES['imagen']['error'])) {
            return;
        }
        if ($_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $archivo = $_FILES['imagen'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $excedeTam = in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            $mensaje = $excedeTam
                ? 'El archivo excede el tamaño permitido.'
                : 'Error al subir el archivo (código ' . $archivo['error'] . ').';
            $this->error($mensaje, 422);
        }

        if (!is_uploaded_file($archivo['tmp_name'])) {
            $this->error('Subida inválida.', 422);
        }

        if ($archivo['size'] > $this->maxBytes) {
            $this->error('La imagen no debe superar los 2 MB.', 422);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($archivo['tmp_name']);
        if (!isset($this->mimePermitidos[$mime])) {
            $this->error('Formato no permitido. Use JPG, PNG o WEBP.', 422);
        }
        $extension = $this->mimePermitidos[$mime];

        $directorioPublic  = realpath(__DIR__ . '/../../public');
        $subcarpeta        = 'uploads/productos';
        $directorioDestino = $directorioPublic . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $subcarpeta);

        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        $nombreArchivo = ((int) $id) . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $rutaAbsoluta  = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;
        $rutaRelativa  = $subcarpeta . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
            $this->error('No se pudo guardar la imagen en el servidor.', 500);
        }

        $producto = $this->productos->buscar($id);
        if (!empty($producto['imagen_url'])) {
            $anterior = $directorioPublic . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $producto['imagen_url']);
            $anteriorReal = realpath($anterior);
            if ($anteriorReal !== false
                && is_file($anteriorReal)
                && strpos($anteriorReal, $directorioDestino) === 0) {
                @unlink($anteriorReal);
            }
        }

        $this->productos->actualizarImagen($id, $rutaRelativa);
    }

    // Acepta "10" o 10; rechaza "3.5", "abc", negativos.
    private function esEnteroNoNegativo($valor) {
        return (string) (int) $valor === (string) $valor && (int) $valor >= 0;
    }

    // Construye el arreglo saneado para el modelo. Si se pasa $actual, usa sus
    // valores como respaldo para los campos opcionales (caso edición).
    private function armarPayload($datos, $actual = null) {
        return [
            'categoria_id' => (int) $datos['categoria_id'],
            'nombre'       => $datos['nombre'],
            'descripcion'  => $datos['descripcion'] ?? ($actual['descripcion'] ?? null),
            'precio'       => (float) $datos['precio'],
            'stock'        => array_key_exists('stock', $datos)
                ? (int) $datos['stock']
                : (int) ($actual['stock'] ?? 0),
            'activo'       => array_key_exists('activo', $datos)
                ? (int) $datos['activo']
                : (int) ($actual['activo'] ?? 1),
        ];
    }
}
