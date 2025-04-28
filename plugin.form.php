<?php
/*
Plugin Name: Plugin Form
Description: Plugin para crear un formulario
Author: Daniel Mahecha
Version: 1.0
License: GPL2
*/

// Evitar el acceso directo al archivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Código para mostrar el formulario y procesar la entrada
function plugin.form() 
{

    ?>
    <form method="POST" action="">
        <label for="nombre">Nombre Completo:</label>
        <input type="text" id="nombre" name="nombre" required>
        <label for="correo">Correo Electrónico:</label>
        <input type="email" id="correo" name="correo" required>
        <label for="respuesta">Respuesta Examen:</label>
        <textarea id="respuesta" name="respuesta" required></textarea>
        <button type="submit" name="enviar_examen">Enviar</button>
    </form>
    <?php
}

function procesar_plugin.form() {
    if ( isset( $_POST['enviar_examen'] ) ) {
        $nombre = sanitize_text_field( $_POST['nombre'] );
        $correo = sanitize_email( $_POST['correo'] );
        $respuesta = sanitize_textarea_field( $_POST['respuesta'] );

        // Aquí puedes guardar los datos en la base de datos, o enviarlos por correo electrónico
        $to = 'admin@tusitio.com';
        $subject = 'Nuevo formulario de examen';
        $message = "Nombre: $nombre\nCorreo: $correo\nRespuesta: $respuesta";
        $headers = 'From: webmaster@tusitio.com' . "\r\n";
        
        // Enviar el correo
        wp_mail( $to, $subject, $message, $headers );

        echo '<p>Gracias por enviar tu examen. Nos pondremos en contacto contigo pronto.</p>';
    }
}

add_shortcode( 'plugin.form', 'eplugin.form' );
add_action( 'wp_head', 'procesar_plugin.form' );
?>