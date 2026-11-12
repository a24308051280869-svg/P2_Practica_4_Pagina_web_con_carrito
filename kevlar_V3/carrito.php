<?php
// CARRITO: muestra lo que agregué, deja cambiar cantidades, quitar y vaciar.
// Es solo un carrito, no hay sistema de compra.

include "conexion.php";
include "funciones.php";

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = array();
}

// Esto pasa cuando le dan clic a algún botón del carrito
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'];
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($accion == 'sumar' && isset($_SESSION['carrito'][$id])) {
        // Reviso el stock para no pasarme
        $consulta = mysqli_prepare($conexion, "SELECT stock FROM productos WHERE id = ?");
        mysqli_stmt_bind_param($consulta, "i", $id);
        mysqli_stmt_execute($consulta);
        $producto = mysqli_fetch_assoc(mysqli_stmt_get_result($consulta));

        if ($producto && $_SESSION['carrito'][$id] < $producto['stock']) {
            $_SESSION['carrito'][$id]++;
        }
    }

    if ($accion == 'restar' && isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id]--;
        // Si llega a 0 lo quito del carrito
        if ($_SESSION['carrito'][$id] <= 0) {
            unset($_SESSION['carrito'][$id]);
        }
    }

    if ($accion == 'quitar') {
        unset($_SESSION['carrito'][$id]);
    }

    if ($accion == 'vaciar') {
        $_SESSION['carrito'] = array();
    }

    // Recargo la página para que no se repita la acción al actualizar
    header("Location: carrito.php");
    exit;
}

$titulo = "Carrito";
$pagina = "carrito";
include "encabezado.php";

// Saco de la base de datos los productos que están en el carrito
$productos = array();
$total = 0;

if (count($_SESSION['carrito']) > 0) {
    // Armo los ? para el IN (uno por cada producto)
    $ids = array_keys($_SESSION['carrito']);
    $signos = implode(",", array_fill(0, count($ids), "?"));
    $consulta = mysqli_prepare($conexion, "SELECT * FROM productos WHERE id IN ($signos)");
    mysqli_stmt_bind_param($consulta, str_repeat("i", count($ids)), ...$ids);
    mysqli_stmt_execute($consulta);
    $resultado = mysqli_stmt_get_result($consulta);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['cantidad'] = $_SESSION['carrito'][$fila['id']];
        $fila['subtotal'] = $fila['precio'] * $fila['cantidad'];
        $total += $fila['subtotal'];
        $productos[] = $fila;
    }
}
?>

<section class="encabezado-pagina">
    <div class="contenido">
        <h1>Mi <span class="rojo-texto">carrito</span></h1>
        <p>Revisa lo que agregaste antes de seguir viendo la tienda.</p>
    </div>
</section>

<section class="seccion">
    <div class="contenido">

        <?php if (count($productos) == 0) { ?>
            <!-- Carrito vacío -->
            <div class="carrito-vacio">
                <p>Tu carrito está vacío.</p>
                <a class="boton rojo grande" href="trajes.php">Ver productos</a>
            </div>

        <?php } else { ?>
            <!-- Una fila por cada producto del carrito -->
            <div class="carrito-lista">
                <?php foreach ($productos as $p) { ?>
                    <div class="carrito-fila">
                        <img src="<?php echo htmlspecialchars($p['imagen']); ?>" alt="" onerror="this.style.display='none'">

                        <div class="carrito-datos">
                            <h3><?php echo htmlspecialchars($p['nombre']); ?></h3>
                            <span class="gris"><?php echo htmlspecialchars($p['categoria']); ?> · <?php echo htmlspecialchars($p['heroe']); ?></span>
                            <span class="precio">$<?php echo number_format($p['precio'], 2); ?> MXN</span>
                        </div>

                        <!-- Botones - y + para cambiar la cantidad -->
                        <div class="carrito-cantidad">
                            <form method="post" action="carrito.php">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="accion" value="restar">
                                <button type="submit">-</button>
                            </form>
                            <span><?php echo $p['cantidad']; ?></span>
                            <form method="post" action="carrito.php">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="accion" value="sumar">
                                <button type="submit">+</button>
                            </form>
                        </div>

                        <span class="carrito-subtotal">$<?php echo number_format($p['subtotal'], 2); ?></span>

                        <form method="post" action="carrito.php">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="accion" value="quitar">
                            <button class="boton blanco" type="submit">Quitar</button>
                        </form>
                    </div>
                <?php } ?>
            </div>

            <!-- Total y botón de vaciar -->
            <div class="carrito-total">
                <form method="post" action="carrito.php">
                    <input type="hidden" name="accion" value="vaciar">
                    <button class="boton blanco" type="submit">Vaciar carrito</button>
                </form>
                <div class="total">Total: <b>$<?php echo number_format($total, 2); ?> MXN</b></div>
            </div>
        <?php } ?>

    </div>
</section>

<?php footer(); ?>
</body>
</html>
