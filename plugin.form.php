<?php
/*
Plugin Name: Formulario
Description: Plugin para crear un formulario.
Version: 1.1
Author: Daniel Mahecha
*/

// Al activar el plugin, se ejecuta la función que crea la tabla de datos en la base de datos
register_activation_hook( __FILE__, "Formulario_Datos" ); 

function Formulario_Datos() {
    global $wpdb; // Se accede al objeto global $wpdb para interactuar con la base de datos de WordPress
    $tabla_datos = $wpdb->prefix . "Datos"; // Se define el nombre de la tabla con el prefijo de WP
    $charle_collate = $wpdb->get_charset_collate(); // Se obtiene la codificación y cotejamiento de la base de datos

    // Se construye la consulta SQL para crear la tabla si no existe
    $query = "CREATE TABLE IF NOT EXISTS $tabla_datos (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        documento varchar(40) NOT NULL,
        nombre varchar(40) NOT NULL,
        apellido varchar(100) NOT NULL,
        correo varchar(100) NOT NULL,
        telefono varchar(100) NOT NULL,
        direccion varchar(100) NOT NULL,
        mensaje text NOT NULL,
        created_at datetime NOT NULL,
        UNIQUE (id)
    ) $charle_collate;"; 

    // Se incluye el archivo necesario para ejecutar dbDelta(), que actualiza o crea tablas en la base de datos
    include_once ABSPATH . "wp-admin/includes/upgrade.php";    
    dbDelta($query); // Se ejecuta la consulta de creación de tabla
    
    // Se registra cualquier error en el log, útil para depuración
    if ($wpdb->last_error) {
        error_log('Error al crear la tabla: ' . $wpdb->last_error);
    } else {
        error_log('Tabla creada correctamente.');
    }
}

// Se define un shortcode [formulario] para insertar el formulario en cualquier página o entrada
add_shortcode( 'formulario', 'formulario_html' );

function formulario_html() {

    $mensaje_error = ''; // Variable para almacenar mensajes de error o confirmación

    // Si el formulario ha sido enviado
    if (isset($_POST['enviar_formulario'])) {

        // Se valida el nonce para evitar ataques CSRF
        if (!isset($_POST['formulario_nonce']) || !wp_verify_nonce($_POST['formulario_nonce'], 'enviar_formulario')) {
            $mensaje_error = 'Error de seguridad. Por favor, inténtalo de nuevo.';
        } else {
            // Se limpian y validan los datos del formulario
            $documento = sanitize_text_field($_POST['documento']);
            $nombre = sanitize_text_field($_POST['nombre']);
            $apellido = sanitize_text_field($_POST['apellido']);
            $correo = sanitize_email($_POST['correo']);
            $telefono = sanitize_text_field($_POST['telefono']);
            $direccion = sanitize_text_field($_POST['direccion']);
            $mensaje = sanitize_textarea_field($_POST['mensaje']);

            // Se verifica que todos los campos estén completos
            if (empty($documento) || empty($nombre) || empty($apellido) || empty($correo) || empty($telefono) || empty($direccion) || empty($mensaje)) {
                $mensaje_error = 'Por favor, complete todos los campos del formulario.';
            } else {
                global $wpdb;
                $tabla_datos = $wpdb->prefix . "Datos";

                // Se insertan los datos en la base de datos
                $wpdb->insert(
                    $tabla_datos,
                    array(
                        'documento' => $documento,
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'correo' => $correo,
                        'telefono' => $telefono,
                        'direccion' => $direccion,
                        'mensaje' => $mensaje,
                        'created_at' => current_time('mysql'),
                    )
                );

                // Se registra si hubo un error al insertar
                if ($wpdb->last_error) {
                    error_log('Error al insertar los datos: ' . $wpdb->last_error);
                    $mensaje_error = 'Hubo un problema al guardar los datos. Por favor, inténtelo nuevamente.';
                } else {
                    $mensaje_error = 'Formulario enviado correctamente. ¡Gracias por registrar tus datos, en breve nos contactaremos contigo!';
                }
            }
        }
    }

    // Se inicia el buffer de salida para capturar el contenido del formulario
    ob_start();
    ?>

    <!-- Si hay mensaje de error o confirmación, se muestra -->
    <?php if ($mensaje_error) : ?>
        <div class="form-error"><?php echo esc_html($mensaje_error); ?></div>
    <?php endif; ?>

    <!-- Formulario HTML -->
    <form action="<?php echo esc_url(get_the_permalink()); ?>" method="POST" class="Formulario">
        
        <!-- Campo oculto de seguridad -->
        <?php wp_nonce_field('enviar_formulario', 'formulario_nonce'); ?>

        <!-- Cada bloque representa un campo del formulario -->
        <div class="form-input">
            <label for="documento">Documento</label>
            <input type="text" name="documento" id="documento" value="<?php echo isset($_POST['documento']) ? esc_attr($_POST['documento']) : ''; ?>" required>
        </div>

        <div class="form-input">
            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" id="nombre" value="<?php echo isset($_POST['nombre']) ? esc_attr($_POST['nombre']) : ''; ?>" required>
        </div>

        <div class="form-input">
            <label for="apellido">Apellido</label>
            <input type="text" name="apellido" id="apellido" value="<?php echo isset($_POST['apellido']) ? esc_attr($_POST['apellido']) : ''; ?>" required>
        </div>

        <div class="form-input">
            <label for="correo">Correo Electrónico</label>
            <input type="email" name="correo" id="correo" value="<?php echo isset($_POST['correo']) ? esc_attr($_POST['correo']) : ''; ?>" required>
        </div>

        <div class="form-input">
            <label for="telefono">Teléfono</label>
            <input type="tel" name="telefono" id="telefono" value="<?php echo isset($_POST['telefono']) ? esc_attr($_POST['telefono']) : ''; ?>" required>
        </div>

        <div class="form-input">
            <label for="direccion">Dirección</label>
            <input type="text" name="direccion" id="direccion" value="<?php echo isset($_POST['direccion']) ? esc_attr($_POST['direccion']) : ''; ?>" required>
        </div>

        <div class="form-input">
            <label for="mensaje">Mensaje</label>
            <textarea name="mensaje" id="mensaje" required><?php echo isset($_POST['mensaje']) ? esc_textarea($_POST['mensaje']) : ''; ?></textarea>
        </div>

        <div class="form-input">
            <button type="submit" name="enviar_formulario">Enviar</button>
        </div>
    </form>
    <?php

    // Se retorna el contenido del formulario
    return ob_get_clean();
}

// Se añade una página al menú de administración de WordPress para visualizar los datos ingresados
add_action("admin_menu", "formulario_datos_menu");

function formulario_datos_menu() {
    add_menu_page(
        "Form Datos", // Título de la página
        "Datos", // Título en el menú
        "manage_options", // Capacidad requerida
        "formulario_datos_menu", // Slug
        "form_datos_admin", // Función que mostrará el contenido
        "dashicons-feedback", // Ícono del menú
        75 // Posición en el menú
    );
}

// Esta función renderiza la tabla con los datos guardados del formulario
function form_datos_admin() {
    global $wpdb;
    $tabla_datos = $wpdb->prefix . "Datos";

    // Se obtienen todos los registros ordenados por fecha
    $resultados = $wpdb->get_results("SELECT * FROM $tabla_datos ORDER BY created_at DESC");

    echo "<div class='wrap'>";
    echo "<h1>Datos del Formulario</h1>";

    // Se muestra una tabla con los resultados si existen
    if (!empty($resultados)) {
        echo "<table class='widefat fixed striped'>";
        echo "<thead>
                <tr>
                    <th>ID</th>
                    <th>Documento</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Mensaje</th>
                    <th>Fecha de Registro</th>
                </tr>
              </thead>";
        echo "<tbody>";

        // Se recorre cada fila de datos y se muestra en la tabla
        foreach ($resultados as $fila) {
            echo "<tr>";
            echo "<td>" . esc_html($fila->id) . "</td>";
            echo "<td>" . esc_html($fila->documento) . "</td>";
            echo "<td>" . esc_html($fila->nombre) . "</td>";
            echo "<td>" . esc_html($fila->apellido) . "</td>";
            echo "<td>" . esc_html($fila->correo) . "</td>";
            echo "<td>" . esc_html($fila->telefono) . "</td>";
            echo "<td>" . esc_html($fila->direccion) . "</td>";
            echo "<td>" . esc_html($fila->mensaje) . "</td>";
            echo "<td>" . esc_html($fila->created_at) . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
    } else {
        // Si no hay registros, se muestra un mensaje
        echo "<p>No hay registros en la base de datos.</p>";
    }

    echo "</div>";
}
?>

