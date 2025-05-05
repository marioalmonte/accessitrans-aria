<?php
/**
 * Uninstall AccessiTrans - ARIA Translator for WPML & Elementor
 *
 * Eliminación de datos cuando el plugin es desinstalado.
 */

// Si se accede directamente, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Eliminar opciones de configuración del plugin
delete_option('accessitrans_aria_options');

// Eliminar caché de traducciones
delete_option('accessitrans_translation_cache');
