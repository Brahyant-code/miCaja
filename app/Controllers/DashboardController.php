<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\ReporteModel;

// Panel de control: indicadores de ventas y recaudación.
class DashboardController extends Controller {

    private $reportes;

    // Umbral de "stock bajo" (mismo valor que el frontend en utils/config.js).
    const UMBRAL_STOCK_BAJO = 5;

    public function __construct() {
        $this->reportes = new ReporteModel();
    }

    // GET /dashboard  -> resumen (hoy/semana) + series por día y por semana
    public function index() {
        $this->exito([
            'resumen'    => $this->reportes->resumen(),
            'porDia'     => $this->reportes->serieDias(7),
            'porSemana'  => $this->reportes->serieSemanas(8),
            'top'        => $this->reportes->topProductos(3),
            'stock_bajo' => $this->reportes->contarStockBajo(self::UMBRAL_STOCK_BAJO),
        ]);
    }
}
