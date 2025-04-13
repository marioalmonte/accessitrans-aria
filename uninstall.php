<?php
/**
 * Uninstall Elementor ARIA Translator for WPML
 *
 * Eliminación de datos cuando el plugin es desinstalado.
 */

// Si se accede directamente, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Eliminar opciones de configuración del plugin
delete_option('elementor_aria_translator_options');

// Registrar en el log que se ha desinstalado (opcional)
if (WP_DEBUG) {
    error_log('Elementor ARIA Translator for WPML: Datos eliminados durante la desinstalación');
}