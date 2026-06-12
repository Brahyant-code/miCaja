# 🧾 miCaja

**miCaja** es un **sistema de caja (punto de venta)** para un **local de comida rápida**. Permite registrar
y gestionar los **productos** del local —con su **precio**, **descripción**, **cantidad disponible** e
**imagen**— para llevar el control de la venta desde una interfaz simple y rápida.

Está construido sobre **nova**, un micro-framework MVC propio en PHP (backend de API JSON) con un frontend
**Vue 3 + Tailwind** ya integrado.

> **Estado actual:** el proyecto parte de la plantilla del framework (incluye un CRUD de ejemplo de
> *tareas*). El módulo de **productos / caja** descrito abajo es el objetivo en desarrollo.

---

## 🎯 Funcionalidades

El local de comida rápida podrá:

- **Agregar productos** con:
  - 🏷️ **Nombre** y **descripción**
  - 💲 **Precio**
  - 📦 **Cantidad disponible** (stock)
  - 🖼️ **Imagen** del producto
- **Editar y eliminar** productos.
- **Listar el catálogo** de productos disponibles.
- *(Próximamente)* registrar ventas y descontar del stock.

---

## 📦 Stack

- **Backend:** PHP 7.4+ con PDO (MySQL / MariaDB)
- **Frontend:** Vue 3 + Tailwind CSS v4, compilado con Vite
- **Autoload:** Composer (PSR-4)
- **Entorno:** Laragon (Apache + MySQL)
- **Iconos:** Heroicons · **Modo oscuro** incluido

---

## 🗂️ Estructura del proyecto

```
miCaja/
├── app/                      # TU código
│   ├── Controllers/          #   Controladores (reciben la petición, responden JSON)
│   └── Models/               #   Modelos (hablan con la base de datos)
│
├── core/                     # El núcleo del framework nova (normalmente no lo tocas)
│   ├── Router.php            #   Resuelve la URL -> Controlador@metodo
│   ├── Controller.php        #   Clase base de los controladores (helpers exito()/error())
│   ├── Model.php             #   Clase base de los modelos ($this->db, find/all/query)
│   ├── Database.php          #   Conexión PDO (singleton, prepared statements)
│   ├── Request.php           #   Lee la entrada: body(), input(), all(), validar()
│   ├── Validator.php         #   Reglas de validación
│   ├── Response.php          #   Punto único de salida JSON
│   └── ErrorHandler.php      #   Manejo central de errores
│
├── config/
│   ├── app.php               # debug + cors_origin
│   ├── database.example.php  # Plantilla de credenciales (versionada)
│   └── database.php          # TUS credenciales (NO versionado)
│
├── routes/api.php            # Aquí defines TODAS tus rutas
│
├── resources/                # Frontend (código fuente Vue 3 + Tailwind)
│   ├── App.vue
│   ├── main.js, style.css, api.js, theme.js
│   └── components/
│
├── public/                   # Webroot: index.php (API) + build de la SPA
├── package.json, vite.config.js
└── composer.json
```

**La idea en una frase:** una petición entra por `public/index.php` → `Router` busca la ruta en
`routes/api.php` → llama a tu **Controller** → este usa un **Model** para la BD → responde **JSON** → el
frontend **Vue** (la vista) lo muestra.

---

## ✅ Requisitos

- [Laragon](https://laragon.org/) (o Apache + PHP + MySQL) con `mod_rewrite` activado
- PHP 7.4+ y [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (para el frontend)

---

## 🚀 Instalación paso a paso

1. **Clona** el repo dentro de la carpeta `www` de Laragon:
   ```bash
   git clone https://github.com/Brahyant-code/miCaja.git
   cd miCaja
   ```

2. **Backend** — instala el autoload de Composer:
   ```bash
   composer install
   ```

3. **Configura la base de datos** — copia la plantilla y ajusta tus credenciales:
   ```bash
   cp config/database.example.php config/database.php
   ```
   Edita [config/database.php](config/database.php) con el nombre de tu BD, usuario y contraseña.

4. **Frontend** — instala las dependencias:
   ```bash
   npm install
   ```

5. **Levanta el frontend** (elige una opción):
   - **Desarrollo con recarga en caliente:**
     ```bash
     npm run dev          # abre http://localhost:5173
     ```
   - **Compilar para servir vía Laragon:**
     ```bash
     npm run build        # luego abre http://localhost/miCaja/
     ```

---

## 🛣️ Rutas

Defines todas tus rutas en [routes/api.php](routes/api.php) con su **verbo HTTP** y destino
`Controlador@metodo`. Soportan parámetros dinámicos con `{...}`. Así se verá el módulo de productos:

```php
$router->get('/productos',         'ProductoController@index');    // listar catálogo
$router->get('/productos/{id}',    'ProductoController@ver');       // ver uno
$router->post('/productos',        'ProductoController@crear');     // crear (con imagen)
$router->put('/productos/{id}',    'ProductoController@editar');    // editar
$router->delete('/productos/{id}', 'ProductoController@eliminar');  // eliminar
```

> ⚠️ Se evalúan **en orden**: la primera que coincide gana. Pon las rutas fijas antes que las que tienen
> `{parametro}`. Si ninguna coincide, se responde `404` en JSON.

---

## 📐 Formato de respuesta estándar

**Todas** las respuestas del API usan el mismo sobre: `mensaje` + `datos`. La llave `errores` **solo
aparece cuando hay un detalle de error que mostrar** (por ejemplo, validación).

```json
// Respuesta exitosa
{
  "mensaje": "Producto creado",
  "datos": { "id": 4, "nombre": "Hamburguesa", "precio": 4500, "stock": 20 }
}

// Error de validación (aquí sí aparece "errores")
{
  "mensaje": "Datos inválidos",
  "datos": null,
  "errores": { "precio": ["El campo precio es obligatorio."] }
}
```

- `mensaje`: texto legible (puede ir vacío en algunas respuestas).
- `datos`: el contenido de la respuesta (objeto, arreglo o `null`). En errores va `null`.
- `errores`: solo presente cuando hay detalle (validación, o ubicación del error en modo debug).

Desde tus controladores produces este sobre con dos helpers heredados de `Core\Controller`:

```php
$this->exito($datos, $mensaje = '', $codigo = 200);     // respuesta correcta
$this->error($mensaje, $codigo = 400, $errores = null); // respuesta de error
```

Los 404 del enrutador, los 422 de validación y los 500 del manejador de errores ya devuelven este mismo
formato automáticamente.

---

## 🧩 Cómo crear un módulo (Controller + Model + Vista)

El proyecto incluye un CRUD de ejemplo (**tareas**) listo y funcionando. Úsalo como plantilla para crear
el módulo de **productos**.

### 1) El Modelo — habla con la base de datos

El modelo hereda de `Core\Model`, que ya te da la conexión PDO (`$this->db`) y atajos seguros: `find()`,
`all()` y `query()` (todos con prepared statements).

```php
<?php
namespace App\Models;

use Core\Model;

class ProductoModel extends Model {
    public function listar() {
        return $this->query('SELECT * FROM productos ORDER BY id DESC');
    }
    public function buscar($id) {
        return $this->find('productos', $id);   // SELECT por id, o null
    }
    public function crear($d) {
        $stmt = $this->db->prepare(
            'INSERT INTO productos (nombre, descripcion, precio, stock, imagen)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$d['nombre'], $d['descripcion'], $d['precio'], $d['stock'], $d['imagen']]);
        return (int) $this->db->lastInsertId();
    }
}
```

### 2) El Controlador — recibe la petición y responde

Hereda de `Core\Controller` (te da `$this->exito()` y `$this->error()`). Usa el Modelo y valida la entrada.

```php
<?php
namespace App\Controllers;

use Core\Controller;
use Core\Request;
use App\Models\ProductoModel;

class ProductoController extends Controller {
    private $productos;
    public function __construct() {
        $this->productos = new ProductoModel();
    }

    public function index() {                        // GET /productos
        $this->exito($this->productos->listar());
    }

    public function crear() {                         // POST /productos
        $datos = Request::validar([
            'nombre'      => 'required|min:2|max:120',
            'descripcion' => 'string|max:500',
            'precio'      => 'required|numeric|min:0',
            'stock'       => 'required|numeric|min:0',
        ]);
        $id = $this->productos->crear($datos);
        $this->exito($this->productos->buscar($id), 'Producto creado', 201);
    }
}
```

### 3) La Vista — Vue muestra los datos

Los componentes consumen la API con el helper [resources/api.js](resources/api.js) (`get/post/put/del`):

```js
import api from '../api.js'

const productos = await api.get('productos')                                   // GET
await api.post('productos', { nombre: 'Hamburguesa', precio: 4500, stock: 20 }) // POST
await api.del(`productos/${id}`)                                                // DELETE
```

En el template se renderiza con Tailwind y se muestran los errores de validación que devuelve el backend.

### Iconos (Heroicons) — ya incluidos

El proyecto trae [**Heroicons**](https://heroicons.com/) (`@heroicons/vue`) instalado y listo para usar.
Son SVG: se dimensionan y colorean con clases de Tailwind, y solo entran al bundle los que importas.

```vue
<script setup>
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'  // o /24/solid
</script>

<template>
  <button class="flex items-center gap-1 text-emerald-600">
    <PlusIcon class="w-5 h-5" /> Agregar producto
  </button>
</template>
```

### Modo oscuro — ya incluido

El proyecto trae **modo oscuro** configurado y funcionando, con un botón (sol/luna) en el encabezado.
Respeta la preferencia del sistema la primera vez y recuerda la elección del usuario en `localStorage`
(sin parpadeo al cargar). La lógica vive en [resources/theme.js](resources/theme.js) y el modo por clase
está activado en [resources/style.css](resources/style.css) con `@custom-variant dark`.

---

## 🛡️ Validación de datos (`Request::validar`)

En cualquier controlador, validas la entrada con un arreglo de reglas. **Si algo falla, se responde `422`
con los errores automáticamente** y tu método no continúa:

```php
$datos = Request::validar([
    'nombre' => 'required',
    'precio' => 'required|numeric|min:0',
    'stock'  => 'required|numeric|min:0',
]);
// Si llegas aquí, $datos ya está validado.
```

**Reglas disponibles:**

| Regla        | Qué valida                                                        |
|--------------|-------------------------------------------------------------------|
| `required`   | El campo no puede estar vacío                                     |
| `email`      | Debe ser un correo válido                                        |
| `numeric`    | Debe ser un número                                               |
| `string`     | Debe ser texto                                                    |
| `min:n`      | Número ≥ n, o texto con al menos n caracteres                    |
| `max:n`      | Número ≤ n, o texto con máximo n caracteres                      |

Las reglas se combinan con `|`.

> ¿Necesitas otra regla? Agrega un método `reglaX()` en [core/Validator.php](core/Validator.php) y ya
> podrás usar `'x'`.

---

## 💥 Manejo de errores central

No necesitas escribir `try/catch` en tus controladores. Si algo lanza una excepción (una consulta falla,
la BD está caída, etc.), el **ErrorHandler** lo captura, **registra el detalle en el log del servidor** y
responde un **error genérico** al cliente:

```json
{ "mensaje": "Error interno del servidor. Inténtalo más tarde.", "datos": null }
```

El nivel de detalle lo controla `debug` en [config/app.php](config/app.php):

- `'debug' => false` (producción): el cliente solo ve el mensaje genérico de arriba.
- `'debug' => true` (desarrollo): `mensaje` trae el error real y `errores` su ubicación.

Así nunca filtras información sensible al usuario, pero como desarrollador ves todo lo que necesitas.

---

## 👤 Autor

**Brahyant** — [@Brahyant-code](https://github.com/Brahyant-code)
