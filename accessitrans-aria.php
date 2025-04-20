<?php
/**
 * Plugin Name: AccessiTrans - ARIA Translator for WPML & Elementor
 * Plugin URI: https://github.com/marioalmonte/accessitrans-aria
 * Description: Traduce atributos ARIA en Elementor utilizando WPML, mejorando la accesibilidad de tu sitio web multilingüe. Desarrollado por un profesional certificado en Accesibilidad Web (CPWA).
 * Version: 0.2.3r
 * Author: Mario Germán Almonte Moreno
 * Author URI: https://www.linkedin.com/in/marioalmonte/
 * Text Domain: accessitrans-aria
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Definir constantes del plugin
define('ACCESSITRANS_VERSION', '0.2.3r');
define('ACCESSITRANS_PATH', plugin_dir_path(__FILE__));
define('ACCESSITRANS_URL', plugin_dir_url(__FILE__));

/**
 * Carga el dominio de texto para la internacionalización
 */
function accessitrans_aria_load_textdomain() {
    load_plugin_textdomain(
        'accessitrans-aria',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'accessitrans_aria_load_textdomain', 10);

// Incluir archivos necesarios
require_once ACCESSITRANS_PATH . 'includes/class-accessitrans-core.php';
require_once ACCESSITRANS_PATH . 'includes/class-accessitrans-admin.php';
require_once ACCESSITRANS_PATH . 'includes/class-accessitrans-capture.php';
require_once ACCESSITRANS_PATH . 'includes/class-accessitrans-translator.php';
require_once ACCESSITRANS_PATH . 'includes/class-accessitrans-diagnostics.php';

// Inicializar el plugin
add_action('plugins_loaded', function() {
    AccessiTrans_ARIA_Translator::get_instance();
}, 20); // Prioridad 20 para asegurarnos que WPML y Elementor estén cargados