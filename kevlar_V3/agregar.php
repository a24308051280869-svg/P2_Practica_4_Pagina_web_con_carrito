<?php
// AGREGAR: mete un producto al carrito y regresa a la página donde estaba.
// No dibuja nada, solo guarda en la sesión y redirige.

include "conexion.php";

// Si no existe el carrito en la sesión lo creo vacío
// El carrito es un arreglo: [id del producto => cantidad]
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = array();
}

// Esto solo pasa cuando le dan clic a "Agregar al carrito"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int) $_POST['id'];

    // Busco el producto para saber cuánto stock tiene
    $consulta = mysqli_prepare($conexion, "SELECT stock FROM productos WHERE id = ?");
    mysqli_stmt_bind_param($consulta, "i", $id);
    mysqli_stmt_execute($consulta);
    $resultado = mysqli_stmt_get_result($consulta);
    $producto = mysqli_fetch_assoc($resultado);

    if ($producto) {
        // Cuántos ya tengo en el carrito de este producto
        $actual = isset($_SESSION['carrito'][$id]) ? $_SESSION['carrito'][$id] : 0;

        // Solo agrego si todavía hay piezas disponibles
        if ($actual < $producto['stock']) {
            $_SESSION['carrito'][$id] = $actual + 1;
        }
    }
}

// Regreso a la página de donde vino (si no sé cuál, al inicio)
$regreso = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "index.php";
header("Location: " . $regreso);
exit;
