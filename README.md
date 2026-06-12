# Nova Framework

**Nova** es un micro-framework MVC en PHP, pensado como **backend de API JSON** con un **frontend Vue 3 +
Tailwind** ya integrado. Es ligero y fácil de entender: rutas explícitas, validación de datos, manejo de
errores central y acceso a base de datos con PDO. Ideal para aprender MVC o levantar proyectos pequeños y
medianos rápido.

---

## 📦 Stack

- **Backend:** PHP 7.4+ con PDO (MySQL / MariaDB)
- **Frontend:** Vue 3 + Tailwind CSS v4, compilado con Vite
- **Autoload:** Composer (PSR-4)
- **Entorno:** Laragon (Apache + MySQL)

---

## 🗂️ Estructura del proyecto

```
nova/
├── app/                      # TU código
│   ├── Controllers/          #   Controladores (reciben la petición, responden JSON)
│   │   ├── HomeController.php
│   │   └── TareaController.php
│   └── Models/               #   Modelos (hablan con la base de datos)
│       └── TareaModel.php
│
├── core/                     # El núcleo del framework (normalmente no lo tocas)
│   ├── Router.php            #   Resuelve la URL -> Controlador@metodo
│   ├── Controller.php        #   Clase base de los controladores (helper json())
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
├── database/nova.sql         # SQL de ejemplo (tabla "tareas")
│
├── resources/                # Frontend (código fuente Vue 3 + Tailwind)
│   ├── App.vue
│   ├── main.js, style.css, api.js
│   └── components/
│       ├── ConexionBackend.vue
│       └── Tareas.vue
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
   git clone https://github.com/Brahyant-code/nova.git
   cd nova
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

4. **Importa el SQL de ejemplo** (crea la BD y la tabla `tareas` con datos de prueba):
   ```bash
   mysql -u root < database/nova.sql
   ```
   *(o impórtalo desde phpMyAdmin en Laragon).*

5. **Frontend** — instala las dependencias:
   ```bash
   npm install
   ```

6. **Levanta el frontend** (elige una opción):
   - **Desarrollo con recarga en caliente:**
     ```bash
     npm run dev          # abre http://localhost:5173
     ```
   - **Compilar para servir vía Laragon:**
     ```bash
     npm run build        # luego abre http://localhost/nova/
     ```

✅ Listo. Deberías ver la página con el estado de conexión y el CRUD de tareas funcionando.

---

## 🛣️ Rutas

Defines todas tus rutas en [routes/api.php](routes/api.php) con su **verbo HTTP** y destino
`Controlador@metodo`. Soportan parámetros dinámicos con `{...}`:

```php
$router->get('/',              'HomeController@index');
$router->get('/tareas',        'TareaController@index');
$router->get('/tareas/{id}',   'TareaController@ver');
$router->post('/tareas',       'TareaController@crear');
$router->delete('/tareas/{id}','TareaController@eliminar');
```

> ⚠️ Se evalúan **en orden**: la primera que coincide gana. Pon las rutas fijas antes que las que tienen
> `{parametro}`. Si ninguna coincide, Nova responde `404` en JSON.

---

## 📐 Formato de respuesta estándar

**Todas** las respuestas del API usan el mismo sobre: `mensaje` + `datos`. La llave `errores` **solo
aparece cuando hay un detalle de error que mostrar** (por ejemplo, validación).

```json
// Respuesta exitosa
{
  "mensaje": "Tarea creada",
  "datos": { "id": 4, "titulo": "..." }
}

// Error de validación (aquí sí aparece "errores")
{
  "mensaje": "Datos inválidos",
  "datos": null,
  "errores": { "titulo": ["El campo titulo es obligatorio."] }
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

## 🧩 Ejemplo completo: Controller + Model + Vista

El proyecto incluye un CRUD de **tareas** listo y funcionando. Úsalo como plantilla.

### 1) El Modelo — habla con la base de datos

[app/Models/TareaModel.php](app/Models/TareaModel.php). Hereda de `Core\Model`, que ya te da la conexión
PDO (`$this->db`) y atajos seguros: `find()`, `all()` y `query()` (todos con prepared statements).

```php
<?php
namespace App\Models;

use Core\Model;

class TareaModel extends Model {
    public function listar() {
        return $this->query('SELECT * FROM tareas ORDER BY id DESC');
    }
    public function buscar($id) {
        return $this->find('tareas', $id);   // SELECT por id, o null
    }
    public function crear($titulo) {
        $stmt = $this->db->prepare('INSERT INTO tareas (titulo) VALUES (?)');
        $stmt->execute([$titulo]);
        return (int) $this->db->lastInsertId();
    }
}
```

### 2) El Controlador — recibe la petición y responde

[app/Controllers/TareaController.php](app/Controllers/TareaController.php). Hereda de `Core\Controller`
(te da `$this->exito()` y `$this->error()`). Usa el Modelo y valida la entrada.

```php
<?php
namespace App\Controllers;

use Core\Controller;
use Core\Request;
use App\Models\TareaModel;

class TareaController extends Controller {
    private $tareas;
    public function __construct() {
        $this->tareas = new TareaModel();
    }

    public function index() {                       // GET /tareas
        $this->exito($this->tareas->listar());
    }

    public function crear() {                        // POST /tareas
        $datos = Request::validar(['titulo' => 'required|min:3|max:255']);
        $id = $this->tareas->crear($datos['titulo']);
        $this->exito($this->tareas->buscar($id), 'Tarea creada', 201);
    }
}
```

### 3) La Vista — Vue muestra los datos

[resources/components/Tareas.vue](resources/components/Tareas.vue) consume la API con el helper
[resources/api.js](resources/api.js) (`get/post/put/del`):

```js
import api from '../api.js'

const tareas = await api.get('tareas')              // GET
await api.post('tareas', { titulo: 'Nueva tarea' }) // POST
await api.del(`tareas/${id}`)                        // DELETE
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
    <PlusIcon class="w-5 h-5" /> Agregar
  </button>
</template>
```

Mira [resources/components/Tareas.vue](resources/components/Tareas.vue) para ejemplos reales.

### Modo oscuro — ya incluido

El framework trae **modo oscuro** configurado y funcionando, con un botón (sol/luna) en el encabezado.
Respeta la preferencia del sistema la primera vez y recuerda la elección del usuario en `localStorage`
(sin parpadeo al cargar).

Para que tus elementos cambien en oscuro, agrega variantes `dark:` de Tailwind:

```vue
<div class="bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-100">…</div>
```

La lógica vive en [resources/theme.js](resources/theme.js) (`alternarTema()`, `oscuro`) y el modo por
clase está activado en [resources/style.css](resources/style.css) con `@custom-variant dark`.

---

## 🛡️ Validación de datos (`Request::validar`)

En cualquier controlador, validas la entrada con un arreglo de reglas. **Si algo falla, Nova responde
`422` con los errores automáticamente** y tu método no continúa:

```php
$datos = Request::validar([
    'nombre' => 'required',
    'email'  => 'required|email',
    'edad'   => 'numeric|min:18',
    'bio'    => 'string|max:200',
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

Las reglas se combinan con `|`. Respuesta cuando falla (HTTP 422):

```json
{
  "mensaje": "Datos inválidos",
  "datos": null,
  "errores": {
    "titulo": ["El campo titulo debe tener al menos 3 caracteres."]
  }
}
```

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

```json
{
  "mensaje": "SQLSTATE[42S02]: Base table or view not found...",
  "datos": null,
  "errores": {
    "archivo": "C:\\laragon\\www\\nova\\core\\Database.php",
    "linea": 18
  }
}
```

Así nunca filtras información sensible al usuario, pero como desarrollador ves todo lo que necesitas.

---

## 📋 Endpoints del ejemplo

| Método | Ruta            | Acción                          |
|--------|-----------------|---------------------------------|
| GET    | `/home`         | Datos de conexión (HomeController) |
| GET    | `/tareas`       | Lista todas las tareas          |
| GET    | `/tareas/{id}`  | Muestra una tarea (404 si no existe) |
| POST   | `/tareas`       | Crea una tarea (valida `titulo`)|
| DELETE | `/tareas/{id}`  | Elimina una tarea               |

---

## 👤 Autor

**Brahyant** — [@Brahyant-code](https://github.com/Brahyant-code)
