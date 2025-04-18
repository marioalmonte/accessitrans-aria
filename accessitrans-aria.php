<?php
/**
 * Plugin Name: AccessiTrans - ARIA Translator for WPML & Elementor
 * Plugin URI: https://github.com/marioalmonte/accessitrans-aria
 * Description: Traduce atributos ARIA en Elementor utilizando WPML, mejorando la accesibilidad de tu sitio web multilingüe. Desarrollado por un profesional certificado en Accesibilidad Web (CPWA).
 * Version: 0.2.3
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

class AccessiTrans_ARIA_Translator {
    
    /**
     * Instancia singleton
     */
    private static $instance = null;
    
    /**
     * Control de inicialización para evitar logs duplicados
     */
    private static $initialization_logged = false;
    
    /**
     * Contexto para todas las traducciones ARIA
     */
    private $context = 'AccessiTrans ARIA Attributes';
    
    /**
     * Opciones del plugin
     */
    private $options;
    
    /**
     * Lista de atributos ARIA que contienen texto traducible
     */
    private $traducible_attrs = [
        'aria-label',
        'aria-description',
        'aria-roledescription',
        'aria-placeholder',
        'aria-valuetext'
    ];
    
    /**
     * Cache para evitar procesar múltiples veces el mismo valor
     */
    private $processed_values = [];
    
    /**
     * Cache para evitar procesar múltiples veces el mismo HTML
     */
    private $processed_html = [];
    
    /**
     * Caché de traducciones encontradas para mejorar rendimiento
     * Se guarda como opción en la base de datos para persistencia
     */
    private $translation_cache = [];
    
    /**
     * Versión del plugin (para gestión de caché)
     */
    private $version = '0.2.3';
    
    /**
     * Constructor privado
     */
    private function __construct() {
        // Inicializar valores por defecto para nuevas instalaciones
        if (!get_option('accessitrans_aria_options')) {
            update_option('accessitrans_aria_options', [
                'captura_total' => true,
                'captura_elementor' => true,
                'procesar_templates' => true,
                'procesar_elementos' => true,
                'modo_debug' => false,
                'solo_admin' => true,
                'captura_en_idioma_principal' => true
            ]);
        }
        
        // Cargar opciones
        $this->options = get_option('accessitrans_aria_options', [
            'captura_total' => true,
            'captura_elementor' => true,
            'procesar_templates' => true,
            'procesar_elementos' => true,
            'modo_debug' => false,
            'solo_admin' => true,
            'captura_en_idioma_principal' => true
        ]);
        
        // Cargar caché de traducciones
        $this->translation_cache = get_option('accessitrans_translation_cache', []);
        
        // Registrar hooks de activación y desactivación
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Verificar dependencias básicas
        if (!did_action('elementor/loaded') || !defined('ICL_SITEPRESS_VERSION')) {
            add_action('admin_notices', [$this, 'show_dependencies_notice']);
            return;
        }
        
        // Registrar ajustes
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Añadir enlaces en la página de plugins
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_action_links']);
        add_action('after_plugin_row', [$this, 'after_plugin_row'], 10, 3);
        
        // Inicializar métodos de captura según las opciones configuradas
        $this->init_capture_methods();
        
        // Agregar funciones AJAX
        add_action('wp_ajax_accessitrans_force_refresh', [$this, 'force_refresh_callback']);
        add_action('wp_ajax_accessitrans_diagnostics', [$this, 'diagnostics_callback']);
        add_action('wp_ajax_accessitrans_check_health', [$this, 'check_health_callback']);
        
        // Borrar caché al guardar configuración de Elementor
        add_action('elementor/editor/after_save', [$this, 'clear_cache_after_elementor_save']);
    }
    
    /**
     * Inicializa los métodos de captura según la configuración
     */
    private function init_capture_methods() {
        // MÉTODO 1: Capturar el HTML completo si está habilitado
        if ($this->options['captura_total']) {
            add_action('wp_footer', [$this, 'capture_full_html'], 999);
        }
        
        // MÉTODO 2: Hook para procesar el contenido de Elementor
        if ($this->options['captura_elementor']) {
            add_filter('elementor/frontend/the_content', [$this, 'capture_aria_in_content'], 999);
            
            // También aplicar traducciones al contenido final
            add_filter('elementor/frontend/the_content', [$this, 'translate_aria_attributes'], 1000);
            add_filter('the_content', [$this, 'translate_aria_attributes'], 1000);
        }
        
        // MÉTODO 3: Hooks de Elementor para capturar widgets y elementos
        if ($this->options['procesar_elementos']) {
            add_action('elementor/frontend/widget/before_render_content', [$this, 'process_element_attributes'], 10, 1);
            add_action('elementor/frontend/before_render', [$this, 'process_element_attributes'], 10, 1);
        }
        
        // MÉTODO 4: Hook para templates de Elementor
        if ($this->options['procesar_templates']) {
            add_action('elementor/frontend/builder_content_data', [$this, 'process_template_data'], 10, 2);
        }
        
        // Registro de debug (solo una vez)
        if ($this->options['modo_debug'] && !self::$initialization_logged) {
            $this->log_debug('Plugin inicializado - Versión ' . $this->version);
            self::$initialization_logged = true;
        }
    }
    
    /**
     * Método singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        // Asegurarse de que las opciones predeterminadas están configuradas
        if (!get_option('accessitrans_aria_options')) {
            update_option('accessitrans_aria_options', [
                'captura_total' => true,
                'captura_elementor' => true,
                'procesar_templates' => true,
                'procesar_elementos' => true,
                'modo_debug' => false,
                'solo_admin' => true,
                'captura_en_idioma_principal' => true
            ]);
        }
        
        // Reiniciar flags estáticos
        self::$initialization_logged = false;
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Limpiar archivos temporales o caché si es necesario
    }
    
    /**
     * Muestra notificación de dependencias faltantes
     */
    public function show_dependencies_notice() {
        $class = 'notice notice-error';
        $message = __('AccessiTrans requiere que Elementor y WPML estén instalados y activados.', 'accessitrans-aria');
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }
    
    /**
     * Añade página de ajustes al menú de administración
     */
    public function add_settings_page() {
        add_submenu_page(
            'options-general.php',
            __('Configuración de', 'accessitrans-aria') . ' AccessiTrans',
            __('AccessiTrans', 'accessitrans-aria'),
            'manage_options',
            'accessitrans-aria',
            [$this, 'settings_page']
        );
    }
    
    /**
     * Registra los ajustes del plugin
     */
    public function register_settings() {
        register_setting('accessitrans_aria', 'accessitrans_aria_options');
        
        add_settings_section(
            'accessitrans_aria_main',
            __('Configuración de métodos de captura', 'accessitrans-aria'),
            [$this, 'section_callback'],
            'accessitrans-aria'
        );
        
        add_settings_field(
            'captura_total',
            __('Captura total de HTML', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_main',
            ['label_for' => 'captura_total', 'descripcion' => __('Captura todo el HTML de la página. Altamente efectivo pero puede afectar al rendimiento.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'captura_elementor',
            __('Filtro de contenido de Elementor', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_main',
            ['label_for' => 'captura_elementor', 'descripcion' => __('Procesa el contenido generado por Elementor.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'procesar_templates',
            __('Procesar templates de Elementor', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_main',
            ['label_for' => 'procesar_templates', 'descripcion' => __('Procesa los datos de templates de Elementor.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'procesar_elementos',
            __('Procesar elementos individuales', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_main',
            ['label_for' => 'procesar_elementos', 'descripcion' => __('Procesa cada widget y elemento de Elementor individualmente.', 'accessitrans-aria')]
        );
        
        add_settings_section(
            'accessitrans_aria_advanced',
            __('Configuración avanzada', 'accessitrans-aria'),
            [$this, 'section_advanced_callback'],
            'accessitrans-aria'
        );
        
        add_settings_field(
            'modo_debug',
            __('Modo de depuración', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_advanced',
            ['label_for' => 'modo_debug', 'descripcion' => __('Activa el registro detallado de eventos. Se almacena en wp-content/debug-aria-wpml.log', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'solo_admin',
            __('Captura solo para administradores', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_advanced',
            ['label_for' => 'solo_admin', 'descripcion' => __('Solo procesa la captura total cuando un administrador está conectado.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'captura_en_idioma_principal',
            __('Capturar solo en idioma principal', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_advanced',
            ['label_for' => 'captura_en_idioma_principal', 'descripcion' => __('Solo captura cadenas cuando se navega en el idioma principal. Previene duplicados.', 'accessitrans-aria')]
        );
    }
    
    /**
     * Callback para la sección principal de ajustes
     */
    public function section_callback() {
        echo '<p>' . esc_html__('Configura los métodos de captura de atributos ARIA. Puedes activar varios métodos simultáneamente para una detección más robusta.', 'accessitrans-aria') . '</p>';
    }
    
    /**
     * Callback para la sección avanzada
     */
    public function section_advanced_callback() {
        echo '<p>' . esc_html__('Configuración avanzada para rendimiento y depuración.', 'accessitrans-aria') . '</p>';
    }
    
    /**
     * Callback para campos checkbox
     */
    public function checkbox_callback($args) {
        $option_name = $args['label_for'];
        $descripcion = $args['descripcion'];
        $checked = isset($this->options[$option_name]) && $this->options[$option_name] ? 'checked' : '';
        
        echo '<input type="checkbox" id="' . esc_attr($option_name) . '" name="accessitrans_aria_options[' . esc_attr($option_name) . ']" value="1" ' . $checked . ' />';
        echo '<label for="' . esc_attr($option_name) . '">' . esc_html($descripcion) . '</label>';
    }
    
    /**
     * Renderiza la página de ajustes
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Guardar opciones si se ha enviado el formulario
        if (isset($_POST['submit'])) {
            check_admin_referer('accessitrans_aria_settings');
            
            $options = isset($_POST['accessitrans_aria_options']) ? $_POST['accessitrans_aria_options'] : [];
            $sanitized_options = [
                'captura_total' => isset($options['captura_total']),
                'captura_elementor' => isset($options['captura_elementor']),
                'procesar_templates' => isset($options['procesar_templates']),
                'procesar_elementos' => isset($options['procesar_elementos']),
                'modo_debug' => isset($options['modo_debug']),
                'solo_admin' => isset($options['solo_admin']),
                'captura_en_idioma_principal' => isset($options['captura_en_idioma_principal'])
            ];
            
            update_option('accessitrans_aria_options', $sanitized_options);
            $this->options = $sanitized_options;
            
            // Mensaje de éxito con atributos para lectores de pantalla
            echo '<div class="notice notice-success is-dismissible" role="alert" aria-live="polite"><p>' . esc_html__('Configuración guardada correctamente.', 'accessitrans-aria') . '</p></div>';
        }
        
        // Incluir scripts para las herramientas interactivas
        $this->enqueue_admin_scripts();
        
        // Contar cadenas registradas
        $strings_count = 0;
        if (function_exists('icl_get_string_translations')) {
            global $wpdb;
            $strings_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings WHERE context = %s",
                $this->context
            ));
        }
        
        // Mostrar formulario de ajustes
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Configuración de', 'accessitrans-aria') . ' AccessiTrans'); ?></h1>
            
            <div class="notice-wrapper">
                <p class="plugin-description"><?php _e('Este plugin permite traducir atributos ARIA en sitios desarrollados con Elementor y WPML.', 'accessitrans-aria'); ?></p>
                
                <div class="notice notice-info" role="region" aria-label="<?php esc_attr_e('Estado actual', 'accessitrans-aria'); ?>">
                    <p>
                        <?php 
                        printf(
                            __('Cadenas registradas: %d', 'accessitrans-aria'),
                            $strings_count
                        ); 
                        ?>
                    </p>
                </div>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('accessitrans_aria');
                do_settings_sections('accessitrans-aria');
                wp_nonce_field('accessitrans_aria_settings');
                submit_button();
                ?>
            </form>
            
            <section class="card" aria-labelledby="herramientas-titulo" style="max-width:800px;">
                <h2 id="herramientas-titulo"><?php _e('Herramientas de mantenimiento', 'accessitrans-aria'); ?></h2>
                
                <div class="tool-section">
                    <h3 id="actualizacion-titulo"><?php _e('Forzar actualización de traducciones', 'accessitrans-aria'); ?></h3>
                    <p><?php _e('Si encuentras problemas con las traducciones, puedes intentar forzar una actualización que limpiará las cachés internas y renovará el estado del plugin.', 'accessitrans-aria'); ?></p>
                    <button id="accessitrans-force-refresh" class="button button-secondary" aria-describedby="actualizacion-descripcion">
                        <?php _e('Forzar actualización', 'accessitrans-aria'); ?>
                    </button>
                    <span id="refresh-status" aria-live="polite"></span>
                    <p id="actualizacion-descripcion" class="description">
                        <?php _e('Esta operación limpia todas las cachés de WordPress y WPML y puede resolver problemas donde las traducciones están registradas pero no se muestran correctamente.', 'accessitrans-aria'); ?>
                    </p>
                </div>
                
                <div class="tool-section">
                    <h3 id="diagnostico-titulo"><?php _e('Diagnóstico de traducciones', 'accessitrans-aria'); ?></h3>
                    <p><?php _e('Si una cadena específica no se traduce correctamente, puedes diagnosticar el problema aquí:', 'accessitrans-aria'); ?></p>
                    
                    <form class="diagnostics-form" onsubmit="runDiagnostic(event)">
                        <label for="string-to-check"><?php _e('Cadena a verificar:', 'accessitrans-aria'); ?></label>
                        <input type="text" id="string-to-check" class="regular-text" placeholder="<?php esc_attr_e('Ejemplo: Next', 'accessitrans-aria'); ?>" />
                        <button id="accessitrans-diagnostic" type="submit" class="button button-secondary" aria-describedby="diagnostico-descripcion">
                            <?php _e('Ejecutar diagnóstico', 'accessitrans-aria'); ?>
                        </button>
                        <div role="status" id="diagnostico-proceso" class="screen-reader-text" aria-live="polite"></div>
                    </form>
                    
                    <div id="diagnostic-results" class="diagnostic-results" aria-live="polite"></div>
                    
                    <p id="diagnostico-descripcion" class="description">
                        <?php _e('Esta herramienta verifica si una cadena está correctamente registrada para traducción y si tiene traducciones asignadas.', 'accessitrans-aria'); ?>
                    </p>
                </div>
                
                <div class="tool-section">
                    <h3 id="salud-titulo"><?php _e('Verificar salud del sistema', 'accessitrans-aria'); ?></h3>
                    <p><?php _e('Comprueba el estado general de las traducciones y la configuración del plugin:', 'accessitrans-aria'); ?></p>
                    
                    <button id="accessitrans-check-health" class="button button-secondary" aria-describedby="salud-descripcion">
                        <?php _e('Verificar salud', 'accessitrans-aria'); ?>
                    </button>
                    <span class="screen-reader-text" id="salud-proceso" aria-live="polite"></span>
                    
                    <div id="health-results" class="health-results" aria-live="polite"></div>
                    
                    <p id="salud-descripcion" class="description">
                        <?php _e('Esta herramienta verifica la configuración general del sistema y muestra estadísticas sobre las traducciones registradas.', 'accessitrans-aria'); ?>
                    </p>
                </div>
            </section>
            
            <section class="card" aria-labelledby="instrucciones-uso-titulo" style="max-width:800px;">
                <h2 id="instrucciones-uso-titulo"><?php _e('Instrucciones de uso', 'accessitrans-aria'); ?></h2>
                <p><?php _e('Para agregar atributos ARIA en Elementor:', 'accessitrans-aria'); ?></p>
                <ol>
                    <li><?php _e('Edita cualquier elemento en Elementor', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Ve a la pestaña "Avanzado"', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Encuentra la sección "Atributos personalizados"', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Añade los atributos ARIA que desees traducir (ej: aria-label|Texto a traducir)', 'accessitrans-aria'); ?></li>
                </ol>
                <p><?php _e('Para traducir los atributos:', 'accessitrans-aria'); ?></p>
                <ol>
                    <li><?php _e('Ve a WPML → Traducción de cadenas', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Filtra por el contexto "AccessiTrans ARIA Attributes"', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Traduce las cadenas como lo harías normalmente en WPML', 'accessitrans-aria'); ?></li>
                </ol>
                <p><?php _e('Prácticas recomendadas:', 'accessitrans-aria'); ?></p>
                <ul>
                    <li><?php _e('Navega por el sitio en el idioma principal mientras capturas cadenas para evitar duplicados', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Si una traducción no aparece, utiliza la herramienta "Forzar actualización" o el diagnóstico', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Una vez capturadas todas las cadenas, puedes desactivar los métodos de captura para mejorar el rendimiento', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Si cambias un texto en el idioma original, deberás traducirlo nuevamente en WPML', 'accessitrans-aria'); ?></li>
                </ul>
            </section>
            
            <section class="card" aria-labelledby="acerca-autor-titulo" style="max-width:800px;">
                <h2 id="acerca-autor-titulo"><?php _e('Acerca del autor', 'accessitrans-aria'); ?></h2>
                <p><?php _e('Desarrollado por', 'accessitrans-aria'); ?> Mario Germán Almonte Moreno:</p>
                <ul>
                    <li><?php _e('Miembro de IAAP (International Association of Accessibility Professionals)', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Certificado CPWA (CPACC y WAS)', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Profesor en el Curso de especialización en Accesibilidad Digital (Universidad de Lleida)', 'accessitrans-aria'); ?></li>
                </ul>
                <h3><?php _e('Servicios Profesionales:', 'accessitrans-aria'); ?></h3>
                <ul>
                    <li><?php _e('Formación y consultoría en Accesibilidad Web y eLearning', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Auditorías de accesibilidad web según EN 301 549 (WCAG 2.2, ATAG 2.0)', 'accessitrans-aria'); ?></li>
                </ul>
                <p><a href="https://www.linkedin.com/in/marioalmonte/" target="_blank"><?php _e('Visita mi perfil de LinkedIn', 'accessitrans-aria'); ?></a></p>
                <p><a href="https://aprendizajeenred.es" target="_blank"><?php _e('Sitio web y blog', 'accessitrans-aria'); ?></a></p>
            </section>
            
            <style>
                .tool-section {
                    margin-bottom: 20px;
                    padding-bottom: 20px;
                    border-bottom: 1px solid #eee;
                }
                .tool-section:last-child {
                    border-bottom: none;
                }
                .diagnostics-form {
                    margin-bottom: 10px;
                    display: flex;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 10px;
                }
                .diagnostic-results, .health-results {
                    margin-top: 15px;
                    padding: 10px;
                    background: #f8f8f8;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    max-height: 300px;
                    overflow-y: auto;
                    display: none;
                }
                .diagnostic-results.active, .health-results.active {
                    display: block;
                }
                .diagnostic-item {
                    margin-bottom: 5px;
                    padding: 5px;
                    border-bottom: 1px dotted #eee;
                }
                .diagnostic-success {
                    color: green;
                }
                .diagnostic-error {
                    color: #d63638;
                }
                #refresh-status {
                    margin-left: 10px;
                    display: inline-block;
                }
                .screen-reader-text {
                    position: absolute;
                    width: 1px;
                    height: 1px;
                    padding: 0;
                    margin: -1px;
                    overflow: hidden;
                    clip: rect(0, 0, 0, 0);
                    white-space: nowrap;
                    border: 0;
                }
            </style>
        </div>
        <?php
    }
    
    /**
     * Registra e incluye scripts para la página de administración
     */
    private function enqueue_admin_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Forzar actualización de traducciones
            $('#accessitrans-force-refresh').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $status = $('#refresh-status');
                
                $button.prop('disabled', true);
                $status.html('<?php echo esc_js(__('Procesando...', 'accessitrans-aria')); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'accessitrans_force_refresh',
                        nonce: '<?php echo wp_create_nonce('accessitrans-force-refresh'); ?>'
                    },
                    success: function(response) {
                        $status.html(response.data);
                        $button.prop('disabled', false);
                    },
                    error: function() {
                        $status.html('<?php echo esc_js(__('Error al procesar la solicitud.', 'accessitrans-aria')); ?>');
                        $button.prop('disabled', false);
                    }
                });
            });
            
            // Diagnóstico de traducciones
            window.runDiagnostic = function(e) {
                e.preventDefault();
                
                const $button = $('#accessitrans-diagnostic');
                const $results = $('#diagnostic-results');
                const $proceso = $('#diagnostico-proceso');
                const stringToCheck = $('#string-to-check').val().trim();
                
                if (!stringToCheck) {
                    $results.html('<div class="diagnostic-error"><?php echo esc_js(__('Por favor, ingresa una cadena para verificar.', 'accessitrans-aria')); ?></div>');
                    $results.addClass('active');
                    return;
                }
                
                $button.prop('disabled', true);
                $results.html('<?php echo esc_js(__('Analizando...', 'accessitrans-aria')); ?>');
                $results.addClass('active');
                $proceso.text('<?php echo esc_js(__('Analizando cadena...', 'accessitrans-aria')); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'accessitrans_diagnostics',
                        nonce: '<?php echo wp_create_nonce('accessitrans-diagnostics'); ?>',
                        string: stringToCheck
                    },
                    success: function(response) {
                        $results.empty();
                        
                        if (response.success) {
                            const data = response.data;
                            
                            $results.append('<h4><?php echo esc_js(__('Resultados del diagnóstico:', 'accessitrans-aria')); ?></h4>');
                            
                            $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Texto original:', 'accessitrans-aria')); ?></strong> ' + data.string + '</div>');
                            
                            // Verificación de idioma
                            let languageStatus = data.is_default_language ? 
                                '<span class="diagnostic-success"><?php echo esc_js(__('✓ Idioma correcto', 'accessitrans-aria')); ?></span>' : 
                                '<span class="diagnostic-error"><?php echo esc_js(__('✗ No es idioma principal', 'accessitrans-aria')); ?></span>';
                            
                            $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Idioma:', 'accessitrans-aria')); ?></strong> ' + 
                                data.current_language + ' (' + languageStatus + ')</div>');
                            
                            // Información sobre esta cadena en WPML
                            if (data.found_in_wpml) {
                                $results.append('<div class="diagnostic-item diagnostic-success"><strong><?php echo esc_js(__('✓ Encontrada en WPML', 'accessitrans-aria')); ?></strong></div>');
                                
                                if (data.string_forms && data.string_forms.length > 0) {
                                    $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Formatos registrados:', 'accessitrans-aria')); ?></strong></div>');
                                    
                                    $.each(data.string_forms, function(index, form) {
                                        $results.append('<div class="diagnostic-item" style="margin-left: 15px;">' + 
                                            '<strong>' + form.name + '</strong> (ID: ' + form.id + ')</div>');
                                    });
                                }
                                
                                if (data.has_translation) {
                                    $results.append('<div class="diagnostic-item diagnostic-success"><strong><?php echo esc_js(__('✓ Tiene traducciones', 'accessitrans-aria')); ?></strong></div>');
                                    
                                    $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Traducciones disponibles:', 'accessitrans-aria')); ?></strong></div>');
                                    
                                    // Mostrar traducciones disponibles
                                    $.each(data.translations, function(lang, translation) {
                                        let langDisplay = lang === data.current_language ? 
                                            '<strong>' + lang + ' (idioma actual):</strong>' : 
                                            '<strong>' + lang + ':</strong>';
                                        
                                        $results.append('<div class="diagnostic-item" style="margin-left: 15px;">' + 
                                            langDisplay + ' ' + translation + '</div>');
                                    });
                                    
                                    // Verificar si la traducción al idioma actual existe
                                    if (!data.has_current_language_translation) {
                                        $results.append('<div class="diagnostic-item diagnostic-error"><strong><?php echo esc_js(__('✗ No hay traducción para el idioma actual', 'accessitrans-aria')); ?></strong></div>');
                                        $results.append('<div class="diagnostic-item"><?php echo esc_js(__('Acción recomendada: Traduce esta cadena al idioma actual en WPML → String Translation.', 'accessitrans-aria')); ?></div>');
                                    }
                                } else {
                                    $results.append('<div class="diagnostic-item diagnostic-error"><strong><?php echo esc_js(__('✗ No tiene traducciones', 'accessitrans-aria')); ?></strong></div>');
                                    $results.append('<div class="diagnostic-item"><?php echo esc_js(__('Acción recomendada: Traduce esta cadena en WPML → String Translation.', 'accessitrans-aria')); ?></div>');
                                }
                            } else {
                                $results.append('<div class="diagnostic-item diagnostic-error"><strong><?php echo esc_js(__('✗ No encontrada en WPML', 'accessitrans-aria')); ?></strong></div>');
                                
                                if (!data.is_default_language) {
                                    $results.append('<div class="diagnostic-item"><?php echo esc_js(__('⚠️ Estás navegando en un idioma que no es el principal. Cambia al idioma principal para registrar cadenas.', 'accessitrans-aria')); ?></div>');
                                } else {
                                    $results.append('<div class="diagnostic-item"><?php echo esc_js(__('Acción recomendada: Navega por tu sitio con los métodos de captura activados o edita el elemento en Elementor para registrar esta cadena.', 'accessitrans-aria')); ?></div>');
                                }
                            }
                            
                            // Consejos adicionales
                            $results.append('<h4><?php echo esc_js(__('Consejos para solucionar problemas:', 'accessitrans-aria')); ?></h4>');
                            $results.append('<ul>' +
                                '<li><?php echo esc_js(__('Asegúrate de navegar en el idioma principal del sitio al capturar cadenas.', 'accessitrans-aria')); ?></li>' +
                                '<li><?php echo esc_js(__('Prueba a utilizar "Forzar actualización" para limpiar todas las cachés.', 'accessitrans-aria')); ?></li>' +
                                '<li><?php echo esc_js(__('Si has cambiado el texto en el idioma original, necesitarás traducirlo nuevamente en WPML.', 'accessitrans-aria')); ?></li>' +
                                '</ul>');
                            
                            // Información técnica para depuración
                            if (data.debug_info) {
                                $results.append('<details><summary><?php echo esc_js(__('Información técnica avanzada', 'accessitrans-aria')); ?></summary>' +
                                    '<pre style="font-size: 11px; overflow: auto; max-height: 150px;">' + JSON.stringify(data.debug_info, null, 2) + '</pre>' +
                                    '</details>');
                            }
                        } else {
                            $results.html('<div class="diagnostic-error">' + response.data + '</div>');
                        }
                        
                        $button.prop('disabled', false);
                        $proceso.text('<?php echo esc_js(__('Análisis completado.', 'accessitrans-aria')); ?>');
                    },
                    error: function() {
                        $results.html('<div class="diagnostic-error"><?php echo esc_js(__('Error al procesar la solicitud.', 'accessitrans-aria')); ?></div>');
                        $button.prop('disabled', false);
                        $proceso.text('<?php echo esc_js(__('Error al realizar el análisis.', 'accessitrans-aria')); ?>');
                    }
                });
            };
            
            // Permitir ejecutar el diagnóstico al presionar Enter en el campo de texto
            $('#string-to-check').on('keydown', function(e) {
                if (e.keyCode === 13) {
                    e.preventDefault();
                    window.runDiagnostic(e);
                }
            });
            
            // Verificar salud del sistema
            $('#accessitrans-check-health').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $results = $('#health-results');
                const $proceso = $('#salud-proceso');
                
                $button.prop('disabled', true);
                $results.html('<?php echo esc_js(__('Verificando...', 'accessitrans-aria')); ?>');
                $results.addClass('active');
                $proceso.text('<?php echo esc_js(__('Verificando estado del sistema...', 'accessitrans-aria')); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'accessitrans_check_health',
                        nonce: '<?php echo wp_create_nonce('accessitrans-check-health'); ?>'
                    },
                    success: function(response) {
                        $results.empty();
                        
                        if (response.success) {
                            const data = response.data;
                            
                            $results.append('<h4><?php echo esc_js(__('Estado del sistema:', 'accessitrans-aria')); ?></h4>');
                            
                            // WPML e instalación
                            let wpmlStatus = data.wpml_active ? 
                                '<span class="diagnostic-success">✓ <?php echo esc_js(__('Activo', 'accessitrans-aria')); ?></span>' : 
                                '<span class="diagnostic-error">✗ <?php echo esc_js(__('Inactivo', 'accessitrans-aria')); ?></span>';
                            
                            let elementorStatus = data.elementor_active ? 
                                '<span class="diagnostic-success">✓ <?php echo esc_js(__('Activo', 'accessitrans-aria')); ?></span>' : 
                                '<span class="diagnostic-error">✗ <?php echo esc_js(__('Inactivo', 'accessitrans-aria')); ?></span>';
                            
                            $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('WPML:', 'accessitrans-aria')); ?></strong> ' + wpmlStatus + '</div>');
                            $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Elementor:', 'accessitrans-aria')); ?></strong> ' + elementorStatus + '</div>');
                            
                            // Estadísticas
                            $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Cadenas registradas:', 'accessitrans-aria')); ?></strong> ' + data.string_count + '</div>');
                            $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Traducciones disponibles:', 'accessitrans-aria')); ?></strong> ' + data.translation_count + '</div>');
                            
                            // Idiomas
                            if (data.languages) {
                                $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Idioma principal:', 'accessitrans-aria')); ?></strong> ' + data.languages.default + '</div>');
                                $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Idioma actual:', 'accessitrans-aria')); ?></strong> ' + data.languages.current + '</div>');
                                
                                let langList = '<div class="diagnostic-item"><strong><?php echo esc_js(__('Idiomas disponibles:', 'accessitrans-aria')); ?></strong> ';
                                $.each(data.languages.available, function(code, name) {
                                    langList += code + ' (' + name + '), ';
                                });
                                langList = langList.slice(0, -2); // Eliminar última coma
                                langList += '</div>';
                                $results.append(langList);
                            }
                            
                            // Configuración del plugin
                            $results.append('<h4><?php echo esc_js(__('Configuración actual:', 'accessitrans-aria')); ?></h4>');
                            
                            $.each(data.options, function(option, value) {
                                let formattedOption = option.replace(/_/g, ' ');
                                formattedOption = formattedOption.charAt(0).toUpperCase() + formattedOption.slice(1);
                                
                                let statusIcon = value ? '✓' : '✗';
                                let statusClass = value ? 'diagnostic-success' : '';
                                
                                $results.append('<div class="diagnostic-item"><strong>' + formattedOption + ':</strong> <span class="' + statusClass + '">' + statusIcon + '</span></div>');
                            });
                            
                            // Recomendaciones
                            $results.append('<h4><?php echo esc_js(__('Recomendaciones:', 'accessitrans-aria')); ?></h4>');
                            
                            if (data.string_count === 0) {
                                $results.append('<div class="diagnostic-item diagnostic-error"><?php echo esc_js(__('• No hay cadenas registradas. Navega por tu sitio con los métodos de captura activados.', 'accessitrans-aria')); ?></div>');
                            }
                            
                            if (data.translation_count === 0 && data.string_count > 0) {
                                $results.append('<div class="diagnostic-item diagnostic-error"><?php echo esc_js(__('• Hay cadenas registradas pero sin traducciones. Visita WPML → String Translation para traducirlas.', 'accessitrans-aria')); ?></div>');
                            }
                            
                            if (data.languages && data.languages.current !== data.languages.default) {
                                $results.append('<div class="diagnostic-item diagnostic-error"><?php echo esc_js(__('• Estás navegando en un idioma que no es el principal. Si quieres registrar nuevas cadenas, cambia al idioma principal.', 'accessitrans-aria')); ?></div>');
                            }
                            
                            // Información del sistema
                            $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Fecha del servidor:', 'accessitrans-aria')); ?></strong> ' + data.system_time + '</div>');
                            $results.append('<div class="diagnostic-item"><strong><?php echo esc_js(__('Versión del plugin:', 'accessitrans-aria')); ?></strong> ' + data.plugin_version + '</div>');
                        } else {
                            $results.html('<div class="diagnostic-error">' + response.data + '</div>');
                        }
                        
                        $button.prop('disabled', false);
                        $proceso.text('<?php echo esc_js(__('Verificación completada.', 'accessitrans-aria')); ?>');
                    },
                    error: function() {
                        $results.html('<div class="diagnostic-error"><?php echo esc_js(__('Error al procesar la solicitud.', 'accessitrans-aria')); ?></div>');
                        $button.prop('disabled', false);
                        $proceso.text('<?php echo esc_js(__('Error al realizar la verificación.', 'accessitrans-aria')); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Callback para la acción AJAX de forzar actualización
     */
    public function force_refresh_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-force-refresh', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'accessitrans-aria'));
            return;
        }
        
        // Limpiar cachés internas
        $this->processed_values = [];
        $this->processed_html = [];
        $this->translation_cache = [];
        
        // Actualizar la caché persistente
        update_option('accessitrans_translation_cache', []);
        
        // Limpiar caché de WPML si está disponible
        if (function_exists('icl_cache_clear')) {
            icl_cache_clear();
        }
        
        if (function_exists('wpml_st_flush_caches')) {
            wpml_st_flush_caches();
        }
        
        // Limpiar caché de objetos de WordPress
        wp_cache_flush();
        
        // Limpiar opciones transientes
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
        
        // Reiniciar el flag de inicialización
        self::$initialization_logged = false;
        
        // Registrar en el log
        if ($this->options['modo_debug']) {
            $this->log_debug("Forzada actualización manual de traducciones y caché");
        }
        
        // Responder al AJAX
        wp_send_json_success(__('Actualización completada correctamente.', 'accessitrans-aria'));
    }
    
    /**
     * Callback para la acción AJAX de diagnóstico
     */
    public function diagnostics_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-diagnostics', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'accessitrans-aria'));
            return;
        }
        
        $string_to_check = isset($_POST['string']) ? sanitize_text_field($_POST['string']) : '';
        
        if (empty($string_to_check)) {
            wp_send_json_error(__('Por favor, proporciona una cadena para verificar.', 'accessitrans-aria'));
            return;
        }
        
        // Realizar diagnóstico mejorado
        $diagnostic_results = $this->diagnose_translation_improved($string_to_check);
        
        // Responder al AJAX
        wp_send_json_success($diagnostic_results);
    }
    
    /**
     * Callback para la acción AJAX de verificar salud
     */
    public function check_health_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-check-health', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'accessitrans-aria'));
            return;
        }
        
        // Realizar verificación de salud
        $health_results = $this->check_translation_health();
        
        // Responder al AJAX
        wp_send_json_success($health_results);
    }
    
    /**
     * Diagnóstica problemas con una traducción específica (método mejorado)
     * Este método busca la cadena directamente en la base de datos de WPML
     */
    private function diagnose_translation_improved($string_to_check) {
        global $wpdb;
        
        // Preparar la cadena (normalización)
        $string_to_check = $this->prepare_string($string_to_check);
        
        // Información sobre idiomas
        $current_language = apply_filters('wpml_current_language', null);
        $default_language = apply_filters('wpml_default_language', null);
        $is_default_language = ($current_language === $default_language);
        
        $found_strings = [];
        $has_translations = false;
        $translations = [];
        $has_current_language_translation = false;
        
        // Búsqueda directa por valor
        $exact_match_sql = $wpdb->prepare(
            "SELECT s.id, s.name, s.value 
             FROM {$wpdb->prefix}icl_strings s
             WHERE s.value = %s 
             AND s.context = %s",
            $string_to_check,
            $this->context
        );
        
        $exact_results = $wpdb->get_results($exact_match_sql);
        
        // Búsqueda por nombre (formato de prefijo)
        $prefix_results = [];
        foreach ($this->traducible_attrs as $attr) {
            $prefixed_name = "{$attr}_{$string_to_check}";
            
            $prefix_match_sql = $wpdb->prepare(
                "SELECT s.id, s.name, s.value 
                 FROM {$wpdb->prefix}icl_strings s
                 WHERE s.name = %s 
                 AND s.context = %s",
                $prefixed_name,
                $this->context
            );
            
            $result = $wpdb->get_results($prefix_match_sql);
            if (!empty($result)) {
                $prefix_results = array_merge($prefix_results, $result);
            }
        }
        
        // Búsqueda parcial (para textos que contienen la cadena buscada)
        $partial_match_sql = $wpdb->prepare(
            "SELECT s.id, s.name, s.value 
             FROM {$wpdb->prefix}icl_strings s
             WHERE (s.value LIKE %s OR s.name LIKE %s)
             AND s.context = %s
             LIMIT 10", // Limitamos para evitar muchos resultados irrelevantes
            "%{$string_to_check}%",
            "%{$string_to_check}%",
            $this->context
        );
        
        $partial_results = $wpdb->get_results($partial_match_sql);
        
        // Combinamos todos los resultados, priorizando las coincidencias exactas
        $all_results = array_merge($exact_results, $prefix_results);
        
        // Solo añadir resultados parciales si no hay coincidencias exactas
        if (empty($all_results)) {
            $all_results = $partial_results;
        }
        
        // Procesamos los resultados encontrados
        if (!empty($all_results)) {
            foreach ($all_results as $result) {
                $found_strings[] = [
                    'id' => $result->id,
                    'name' => $result->name,
                    'value' => $result->value
                ];
                
                // Verificar si tiene traducciones
                $trans_sql = $wpdb->prepare(
                    "SELECT st.language, st.value 
                     FROM {$wpdb->prefix}icl_string_translations st
                     WHERE st.string_id = %d",
                    $result->id
                );
                
                $trans_results = $wpdb->get_results($trans_sql);
                
                if (!empty($trans_results)) {
                    $has_translations = true;
                    foreach ($trans_results as $trans) {
                        $translations[$trans->language] = $trans->value;
                        if ($trans->language === $current_language) {
                            $has_current_language_translation = true;
                        }
                    }
                }
            }
        }
        
        // Información de depuración avanzada (solo para admin)
        $debug_info = null;
        if (current_user_can('manage_options')) {
            $debug_info = [
                'query_exact' => $exact_match_sql,
                'exact_count' => count($exact_results),
                'prefix_count' => count($prefix_results),
                'partial_count' => count($partial_results),
                'total_matches' => count($all_results),
                'translation_cache_size' => count($this->translation_cache),
                'default_language' => $default_language,
                'processed_values_count' => count($this->processed_values)
            ];
        }
        
        return [
            'string' => $string_to_check,
            'current_language' => $current_language,
            'default_language' => $default_language,
            'is_default_language' => $is_default_language,
            'found_in_wpml' => !empty($found_strings),
            'string_forms' => $found_strings,
            'has_translation' => $has_translations,
            'has_current_language_translation' => $has_current_language_translation,
            'translations' => $translations,
            'debug_info' => $debug_info
        ];
    }
    
    /**
     * Verifica el estado general de las traducciones y el plugin
     */
    private function check_translation_health() {
        global $wpdb;
        
        // Contar cadenas registradas
        $string_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings WHERE context = %s",
            $this->context
        ));
        
        // Contar traducciones
        $translation_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}icl_string_translations st 
             JOIN {$wpdb->prefix}icl_strings s ON st.string_id = s.id
             WHERE s.context = %s",
            $this->context
        ));
        
        // Obtener idiomas
        $languages = [];
        if (function_exists('icl_get_languages')) {
            $languages = [
                'default' => apply_filters('wpml_default_language', null),
                'current' => apply_filters('wpml_current_language', null),
                'available' => []
            ];
            
            $wpml_languages = apply_filters('wpml_active_languages', []);
            foreach ($wpml_languages as $lang) {
                $languages['available'][$lang['code']] = $lang['native_name'];
            }
        }
        
        return [
            'string_count' => (int)$string_count,
            'translation_count' => (int)$translation_count,
            'wpml_active' => defined('ICL_SITEPRESS_VERSION'),
            'elementor_active' => did_action('elementor/loaded'),
            'languages' => $languages,
            'options' => $this->options,
            'system_time' => current_time('mysql'),
            'plugin_version' => $this->version
        ];
    }
    
    /**
     * Añade enlaces a la página de configuración en la lista de plugins
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=accessitrans-aria') . '">' . __('Settings', 'accessitrans-aria') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Captura el HTML completo de la página para buscar atributos ARIA
     */
    public function capture_full_html() {
        // Verificar si debemos procesar según la configuración
        if ($this->options['solo_admin'] && !current_user_can('manage_options')) {
            return;
        }
        
        // Verificar si debemos capturar solo en idioma principal
        if ($this->options['captura_en_idioma_principal']) {
            $current_language = apply_filters('wpml_current_language', null);
            $default_language = apply_filters('wpml_default_language', null);
            
            if ($current_language !== $default_language) {
                return;
            }
        }
        
        // Iniciar captura de salida
        ob_start();
        
        // Al finalizar la página, procesar el HTML completo
        add_action('shutdown', function() {
            $html_completo = ob_get_clean();
            if (!empty($html_completo)) {
                $this->extract_aria_from_html($html_completo);
                echo $html_completo; // Devolver el HTML al navegador
            }
        }, 0);
    }
    
    /**
     * Extrae y registra todos los atributos ARIA del HTML
     */
    private function extract_aria_from_html($html) {
        if (empty($html) || !is_string($html)) {
            return;
        }
        
        // Crear una huella única del HTML para evitar reprocesar el mismo contenido
        $html_hash = md5($html);
        if (isset($this->processed_html[$html_hash])) {
            return;
        }
        $this->processed_html[$html_hash] = true;
        
        if ($this->options['modo_debug']) {
            $this->log_debug("Procesando HTML para buscar atributos ARIA");
        }
        
        // Verificamos que estamos en el idioma principal
        $current_language = apply_filters('wpml_current_language', null);
        $default_language = apply_filters('wpml_default_language', null);
        
        if ($current_language !== $default_language) {
            if ($this->options['modo_debug']) {
                $this->log_debug("Saltando procesamiento de HTML - Idioma actual no es el principal ({$current_language} != {$default_language})");
            }
            return;
        }
        
        foreach ($this->traducible_attrs as $attr) {
            // Patrón para buscar el atributo con cualquier tipo de comillas
            $pattern = '/' . preg_quote($attr, '/') . '\s*=\s*([\'"])((?:(?!\1).)*)\1/is';
            
            if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $attr_value = $match[2];
                    
                    // No procesar valores vacíos o puramente numéricos
                    if (empty($attr_value) || is_numeric($attr_value)) {
                        continue;
                    }
                    
                    // Normalizar el valor
                    $attr_value = $this->prepare_string($attr_value);
                    
                    // Buscar el ID del elemento que contiene el atributo
                    $element_id = $this->extract_element_id_from_context($html, $match[0]);
                    
                    // Verificar que la cadena no es una traducción en WPML
                    if (!$this->is_wpml_translation($attr_value)) {
                        // Registrar el valor para traducción
                        $this->register_value_for_translation($attr, $attr_value, $element_id);
                        
                        if ($this->options['modo_debug']) {
                            $this->log_debug("CAPTURA - {$attr} = \"{$attr_value}\"" . ($element_id ? " (ID: {$element_id})" : ""));
                        }
                    } else if ($this->options['modo_debug']) {
                        $this->log_debug("OMITIENDO - {$attr} = \"{$attr_value}\" - Es una traducción existente en WPML");
                    }
                }
            }
        }
    }
    
    /**
     * Verifica si una cadena ya existe como traducción en WPML
     * Este método reemplaza la anterior función is_likely_translation
     * para evitar suposiciones sobre idiomas específicos
     */
    private function is_wpml_translation($value) {
        global $wpdb;
        
        // Si está vacío, no es una traducción
        if (empty($value)) {
            return false;
        }
        
        // Verificar si existe como traducción en WPML
        $translation_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}icl_string_translations 
             WHERE value = %s",
            $value
        ));
        
        return ($translation_exists > 0);
    }
    
    /**
     * Prepara y normaliza una cadena para consistencia
     */
    private function prepare_string($string) {
        // Eliminar espacios innecesarios
        $string = trim($string);
        // Normalizar codificación
        $string = htmlspecialchars_decode($string);
        return $string;
    }
    
    /**
     * Extrae el ID del elemento que contiene el atributo ARIA
     */
    private function extract_element_id_from_context($html, $attr_match) {
        // Buscar la posición del atributo en el HTML
        $pos = strpos($html, $attr_match);
        if ($pos === false) {
            return '';
        }
        
        // Extraer un fragmento de HTML antes del atributo
        $start = max(0, $pos - 200);
        $fragment = substr($html, $start, 400);
        
        // Buscar el atributo "id" o "data-id" en este fragmento
        $id_pattern = '/\s(?:id|data-id|data-element-id)=[\'"]([^\'"]+)[\'"]/i';
        if (preg_match($id_pattern, $fragment, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
    
    /**
     * Procesa el contenido de Elementor para buscar atributos ARIA
     */
    public function capture_aria_in_content($content) {
        // Verificar si debemos capturar solo en idioma principal
        if ($this->options['captura_en_idioma_principal']) {
            $current_language = apply_filters('wpml_current_language', null);
            $default_language = apply_filters('wpml_default_language', null);
            
            if ($current_language !== $default_language) {
                return $content;
            }
        }
        
        if (!empty($content) && is_string($content)) {
            $this->extract_aria_from_html($content);
        }
        return $content;
    }
    
    /**
     * Procesa cualquier elemento de Elementor
     */
    public function process_element_attributes($element) {
        // Verificar si debemos capturar solo en idioma principal
        if ($this->options['captura_en_idioma_principal']) {
            $current_language = apply_filters('wpml_current_language', null);
            $default_language = apply_filters('wpml_default_language', null);
            
            if ($current_language !== $default_language) {
                return;
            }
        }
        
        if (!is_object($element)) {
            return;
        }
        
        try {
            // Intentar obtener settings
            $settings = null;
            if (method_exists($element, 'get_settings_for_display')) {
                $settings = $element->get_settings_for_display();
            } elseif (method_exists($element, 'get_settings')) {
                $settings = $element->get_settings();
            }
            
            if (!is_array($settings)) {
                return;
            }
            
            // Obtener ID del elemento
            $element_id = method_exists($element, 'get_id') ? $element->get_id() : '';
            if (empty($element_id)) {
                return;
            }
            
            // Procesar custom_attributes si existen
            if (isset($settings['custom_attributes'])) {
                $this->process_custom_attributes($settings['custom_attributes'], $element_id);
            }
            
            // También buscar en otras propiedades del elemento
            foreach ($settings as $key => $value) {
                // Buscar propiedades que podrían contener atributos ARIA
                if (is_string($key) && (
                    strpos($key, 'aria_') === 0 || 
                    strpos($key, 'aria-') === 0 || 
                    strpos($key, 'role') === 0 ||
                    strpos($key, 'accessibility') !== false
                )) {
                    // Si es un valor string, registrarlo para traducción
                    if (is_string($value) && !empty($value)) {
                        // Determinar el nombre del atributo
                        $attr_name = str_replace('_', '-', $key);
                        if (strpos($attr_name, 'aria-') !== 0) {
                            $attr_name = 'aria-label'; // Default
                        }
                        
                        // Normalizar el valor
                        $value = $this->prepare_string($value);
                        
                        // Verificar que no sea una traducción
                        if (!$this->is_wpml_translation($value)) {
                            // Registrar el valor para traducción
                            $this->register_value_for_translation($attr_name, $value, $element_id);
                            
                            if ($this->options['modo_debug']) {
                                $this->log_debug("Encontrado en settings: {$attr_name} = \"{$value}\" (ID: {$element_id})");
                            }
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            if ($this->options['modo_debug']) {
                $this->log_debug("Error en process_element_attributes: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Procesa datos de template de Elementor
     */
    public function process_template_data($data, $post_id) {
        // Verificar si debemos capturar solo en idioma principal
        if ($this->options['captura_en_idioma_principal']) {
            $current_language = apply_filters('wpml_current_language', null);
            $default_language = apply_filters('wpml_default_language', null);
            
            if ($current_language !== $default_language) {
                return $data;
            }
        }
        
        if (empty($data) || !is_array($data)) {
            return $data;
        }
        
        if ($this->options['modo_debug']) {
            $this->log_debug("Procesando template data para post ID: {$post_id}");
        }
        
        $this->process_template_elements($data);
        
        return $data;
    }
    
    /**
     * Procesa recursivamente elementos de template
     */
    private function process_template_elements($elements) {
        if (!is_array($elements)) {
            return;
        }
        
        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }
            
            // Procesar settings si existen
            if (isset($element['settings']) && is_array($element['settings']) && 
                isset($element['settings']['custom_attributes'])) {
                
                $element_id = isset($element['id']) ? $element['id'] : '';
                if (!empty($element_id)) {
                    $this->process_custom_attributes($element['settings']['custom_attributes'], $element_id);
                }
            }
            
            // Procesar elementos hijos
            if (isset($element['elements']) && is_array($element['elements'])) {
                $this->process_template_elements($element['elements']);
            }
        }
    }
    
    /**
     * Procesa atributos personalizados de Elementor
     */
    private function process_custom_attributes($custom_attributes, $element_id) {
        if (empty($custom_attributes)) {
            return;
        }
        
        // CASO 1: Array de objetos (formato normal)
        if (is_array($custom_attributes)) {
            foreach ($custom_attributes as $attribute) {
                if (is_array($attribute) && isset($attribute['key']) && isset($attribute['value'])) {
                    $key = $attribute['key'];
                    $value = $attribute['value'];
                    
                    if (in_array($key, $this->traducible_attrs)) {
                        // Normalizar el valor
                        $value = $this->prepare_string($value);
                        
                        // Verificar que no sea una traducción
                        if (!$this->is_wpml_translation($value)) {
                            $this->register_value_for_translation($key, $value, $element_id);
                            
                            if ($this->options['modo_debug']) {
                                $this->log_debug("Encontrado atributo: {$key} = \"{$value}\" (ID: {$element_id})");
                            }
                        }
                    }
                }
                // Formato key|value como string
                elseif (is_string($attribute) && strpos($attribute, '|') !== false) {
                    list($key, $value) = explode('|', $attribute, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    if (in_array($key, $this->traducible_attrs)) {
                        // Normalizar el valor
                        $value = $this->prepare_string($value);
                        
                        // Verificar que no sea una traducción
                        if (!$this->is_wpml_translation($value)) {
                            $this->register_value_for_translation($key, $value, $element_id);
                            
                            if ($this->options['modo_debug']) {
                                $this->log_debug("Encontrado atributo pipe: {$key} = \"{$value}\" (ID: {$element_id})");
                            }
                        }
                    }
                }
            }
        }
        // CASO 2: String multilínea
        elseif (is_string($custom_attributes)) {
            $lines = preg_split('/\r\n|\r|\n/', $custom_attributes);
            
            foreach ($lines as $line) {
                if (strpos($line, '|') !== false) {
                    list($key, $value) = explode('|', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    if (in_array($key, $this->traducible_attrs)) {
                        // Normalizar el valor
                        $value = $this->prepare_string($value);
                        
                        // Verificar que no sea una traducción
                        if (!$this->is_wpml_translation($value)) {
                            $this->register_value_for_translation($key, $value, $element_id);
                            
                            if ($this->options['modo_debug']) {
                                $this->log_debug("Encontrado atributo multilínea: {$key} = \"{$value}\" (ID: {$element_id})");
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Registra un valor para traducción con formato de prefijo
     */
    private function register_value_for_translation($attr, $value, $element_id = '') {
        if (empty($value)) {
            return;
        }
        
        // Verificar si estamos en el idioma principal (doble verificación)
        if ($this->options['captura_en_idioma_principal']) {
            $current_language = apply_filters('wpml_current_language', null);
            $default_language = apply_filters('wpml_default_language', null);
            
            if ($current_language !== $default_language) {
                return;
            }
        }
        
        // Verificar si esta cadena ya existe como traducción en WPML
        if ($this->is_wpml_translation($value)) {
            if ($this->options['modo_debug']) {
                $this->log_debug("Omitiendo registro de cadena que ya existe como traducción: {$value}");
            }
            return;
        }
        
        // Usar el cache para evitar procesar el mismo valor varias veces
        $cache_key = md5($attr . '_' . $value);
        if (isset($this->processed_values[$cache_key])) {
            return;
        }
        $this->processed_values[$cache_key] = true;
        
        // Registrar con formato de prefijo
        $prefixed_name = "{$attr}_{$value}";
        do_action('wpml_register_single_string', $this->context, $prefixed_name, $value);
        
        if ($this->options['modo_debug']) {
            $this->log_debug("Registrado: \"{$prefixed_name}\" → \"{$value}\"");
        }
    }
    
    /**
     * Obtiene la traducción de una cadena desde WPML o caché
     * Realiza múltiples comprobaciones para asegurar que se encuentra la traducción correcta
     */
    private function get_translation($attr_name, $attr_value, $default = null) {
        // Si no hay valor, devolver el valor por defecto
        if (empty($attr_value)) {
            return $default !== null ? $default : $attr_value;
        }
        
        // Verificar en la caché primero
        $cache_key = md5($attr_name . '_' . $attr_value . '_' . apply_filters('wpml_current_language', null));
        if (isset($this->translation_cache[$cache_key])) {
            return $this->translation_cache[$cache_key];
        }
        
        // 1. Intentar con el formato de prefijo (más común)
        $prefixed_name = "{$attr_name}_{$attr_value}";
        $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $prefixed_name);
        
        // 2. Si no funciona, intentar obtener la traducción directamente desde la base de datos
        if ($translated === $attr_value) {
            global $wpdb;
            $current_language = apply_filters('wpml_current_language', null);
            
            // Buscar la cadena por prefijo
            $string_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}icl_strings 
                 WHERE name = %s AND context = %s",
                $prefixed_name,
                $this->context
            ));
            
            if ($string_id) {
                // Buscar traducción para el idioma actual
                $db_translation = $wpdb->get_var($wpdb->prepare(
                    "SELECT value FROM {$wpdb->prefix}icl_string_translations 
                     WHERE string_id = %d AND language = %s AND status = 1",
                    $string_id,
                    $current_language
                ));
                
                if ($db_translation) {
                    $translated = $db_translation;
                }
            }
        }
        
        // 3. Como último recurso, intentar con el valor exacto
        if ($translated === $attr_value) {
            global $wpdb;
            $current_language = apply_filters('wpml_current_language', null);
            
            // Buscar por valor exacto
            $string_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}icl_strings 
                 WHERE value = %s AND context = %s",
                $attr_value,
                $this->context
            ));
            
            if ($string_id) {
                // Buscar traducción para el idioma actual
                $db_translation = $wpdb->get_var($wpdb->prepare(
                    "SELECT value FROM {$wpdb->prefix}icl_string_translations 
                     WHERE string_id = %d AND language = %s AND status = 1",
                    $string_id,
                    $current_language
                ));
                
                if ($db_translation) {
                    $translated = $db_translation;
                }
            }
        }
        
        // Almacenar en caché y en opción persistente
        if ($translated !== $attr_value) {
            $this->translation_cache[$cache_key] = $translated;
            
            // Actualizar la caché persistente (con límite para evitar que crezca demasiado)
            if (count($this->translation_cache) <= 1000) {
                update_option('accessitrans_translation_cache', $this->translation_cache);
            }
            
            if ($this->options['modo_debug']) {
                $this->log_debug("TRADUCIDO: {$attr_value} → {$translated}");
            }
        } else if ($this->options['modo_debug']) {
            $this->log_debug("NO TRADUCIDO: {$attr_value}");
        }
        
        return $translated;
    }
    
    /**
     * Traduce atributos ARIA en el HTML final
     */
    public function translate_aria_attributes($content) {
        if (empty($content) || !is_string($content)) {
            return $content;
        }
        
        // Verificar si hay algún atributo ARIA
        $encontrado = false;
        foreach ($this->traducible_attrs as $attr) {
            if (strpos($content, $attr) !== false) {
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            return $content;
        }
        
        // Crear patrón regex para todos los atributos traducibles
        $attrs_pattern = implode('|', array_map(function($attr) {
            return preg_quote($attr, '/');
        }, $this->traducible_attrs));
        
        $pattern = '/\s(' . $attrs_pattern . ')\s*=\s*([\'"])((?:(?!\2).)*)\2/is';
        
        // Traducir atributos
        $result = preg_replace_callback($pattern, function($matches) {
            $attr_name = $matches[1];
            $quote_type = $matches[2];
            $attr_value = $matches[3];
            
            if (empty($attr_value)) {
                return $matches[0];
            }
            
            // Normalizar el valor
            $attr_value = $this->prepare_string($attr_value);
            
            // Obtener traducción usando nuestro método mejorado
            $translated = $this->get_translation($attr_name, $attr_value, $attr_value);
            
            return " {$attr_name}={$quote_type}{$translated}{$quote_type}";
        }, $content);
        
        return $result !== null ? $result : $content;
    }
    
    /**
     * Borra la caché después de guardar en Elementor
     */
    public function clear_cache_after_elementor_save() {
        // Limpiar cachés internas
        $this->processed_values = [];
        $this->processed_html = [];
        
        // No limpiamos la caché de traducciones para mantener rendimiento
        // Pero sí limpiamos la caché relacionada con el contenido
        
        if ($this->options['modo_debug']) {
            $this->log_debug("Caché limpiada después de guardar en Elementor");
        }
    }
    
    /**
     * Añade información en la lista de plugins
     */
    public function after_plugin_row($plugin_file, $plugin_data, $status) {
        if (plugin_basename(__FILE__) == $plugin_file) {
            echo '<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange"><div class="notice inline notice-info" style="margin:0; padding:5px;">';
            echo '<strong>' . __('Compatibilidad verificada:', 'accessitrans-aria') . '</strong> WordPress 6.7-6.8, Elementor 3.28.3, WPML Multilingual CMS 4.7.3, WPML String Translation 3.3.2.';
            echo '</div></td></tr>';
        }
    }
    
    /**
     * Logger para debug optimizado
     */
    private function log_debug($message) {
        if (!$this->options['modo_debug']) {
            return;
        }
        
        $log_file = WP_CONTENT_DIR . '/debug-aria-wpml.log';
        
        if (is_array($message) || is_object($message)) {
            error_log(date('[Y-m-d H:i:s] ') . print_r($message, true) . PHP_EOL, 3, $log_file);
        } else {
            error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, $log_file);
        }
    }
}

// Inicializar el plugin
add_action('plugins_loaded', function() {
    AccessiTrans_ARIA_Translator::get_instance();
}, 20); // Prioridad 20 para asegurarnos que WPML y Elementor estén cargados