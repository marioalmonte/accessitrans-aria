<?php
/**
 * Plugin Name: AccessiTrans - ARIA Translator for WPML & Elementor
 * Plugin URI: https://github.com/marioalmonte/accessitrans-aria
 * Description: Traduce atributos ARIA en Elementor utilizando WPML, mejorando la accesibilidad de tu sitio web multilingüe. Desarrollado por un profesional certificado en Accesibilidad Web (CPWA).
 * Version: 0.2.0
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
     * Contexto base para todas las traducciones ARIA
     */
    private $base_context = 'AccessiTrans ARIA Attributes';
    
    /**
     * Contextos específicos para cada método de registro
     */
    private $direct_context;
    private $prefix_context;
    private $id_context;
    
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
     * Cache de traducciones fallidas para reintento
     */
    private $failed_translations = [];
    
    /**
     * Constructor privado
     */
    private function __construct() {
        // Inicializar los contextos
        $this->direct_context = $this->base_context . '_Direct';
        $this->prefix_context = $this->base_context . '_Prefix';
        $this->id_context = $this->base_context . '_ID';
        
        // Inicializar valores por defecto para nuevas instalaciones
        if (!get_option('accessitrans_aria_options')) {
            update_option('accessitrans_aria_options', [
                'captura_total' => true,
                'captura_elementor' => true,
                'procesar_templates' => true,
                'procesar_elementos' => true,
                'modo_debug' => false,
                'solo_admin' => true,
                'formato_valor_directo' => true,
                'formato_prefijo' => true,
                'formato_elemento_id' => true,
                'reintentar_traducciones' => true,
                'prioridad_traduccion' => 9999
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
            'formato_valor_directo' => true,
            'formato_prefijo' => true,
            'formato_elemento_id' => true,
            'reintentar_traducciones' => true,
            'prioridad_traduccion' => 9999
        ]);
        
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
        
        // Agregar función para forzar actualización
        add_action('wp_ajax_accessitrans_force_refresh', [$this, 'force_refresh_callback']);
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
            
            // También aplicar traducciones al contenido final con prioridad alta
            $priority = intval($this->options['prioridad_traduccion']);
            add_filter('elementor/frontend/the_content', [$this, 'translate_aria_attributes'], $priority);
            add_filter('the_content', [$this, 'translate_aria_attributes'], $priority);
            
            // Hook en wp_loaded para asegurar que WPML esté completamente cargado
            add_action('wp_loaded', function() {
                $priority = intval($this->options['prioridad_traduccion']);
                add_filter('elementor/frontend/the_content', [$this, 'translate_aria_attributes'], $priority);
                add_filter('the_content', [$this, 'translate_aria_attributes'], $priority);
            }, 20);
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
        
        // Registro de debug
        if ($this->options['modo_debug']) {
            $this->log_debug('Plugin inicializado - Versión 0.2.0');
        }
        
        // Retry para traducciones fallidas
        if ($this->options['reintentar_traducciones']) {
            add_action('shutdown', [$this, 'retry_failed_translations'], 999);
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
                'formato_valor_directo' => true,
                'formato_prefijo' => true,
                'formato_elemento_id' => true,
                'reintentar_traducciones' => true,
                'prioridad_traduccion' => 9999
            ]);
        }
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
            'accessitrans_aria_formats',
            __('Configuración de formatos de registro', 'accessitrans-aria'),
            [$this, 'section_formats_callback'],
            'accessitrans-aria'
        );
        
        add_settings_field(
            'formato_valor_directo',
            __('Formato directo', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_formats',
            ['label_for' => 'formato_valor_directo', 'descripcion' => __('Registrar el valor literal como nombre.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'formato_prefijo',
            __('Formato con prefijo', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_formats',
            ['label_for' => 'formato_prefijo', 'descripcion' => __('Registrar con formato aria-atributo_valor.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'formato_elemento_id',
            __('Formato con ID de elemento', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_formats',
            ['label_for' => 'formato_elemento_id', 'descripcion' => __('Registrar con formato incluyendo ID del elemento.', 'accessitrans-aria')]
        );
        
        add_settings_section(
            'accessitrans_aria_advanced',
            __('Configuración avanzada', 'accessitrans-aria'),
            [$this, 'section_advanced_callback'],
            'accessitrans-aria'
        );
        
        add_settings_field(
            'reintentar_traducciones',
            __('Reintentar traducciones fallidas', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_advanced',
            ['label_for' => 'reintentar_traducciones', 'descripcion' => __('Intenta nuevamente aplicar traducciones que fallaron en el primer intento.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'prioridad_traduccion',
            __('Prioridad de traducción', 'accessitrans-aria'),
            [$this, 'number_callback'],
            'accessitrans-aria',
            'accessitrans_aria_advanced',
            ['label_for' => 'prioridad_traduccion', 'descripcion' => __('Prioridad de los filtros de traducción. Valores más altos se ejecutan más tarde (9999 por defecto).', 'accessitrans-aria')]
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
    }
    
    /**
     * Callback para la sección principal de ajustes
     */
    public function section_callback() {
        echo '<p>' . esc_html__('Configura los métodos de captura de atributos ARIA. Puedes activar varios métodos simultáneamente para una detección más robusta.', 'accessitrans-aria') . '</p>';
    }
    
    /**
     * Callback para la sección de formatos
     */
    public function section_formats_callback() {
        echo '<p>' . esc_html__('Configura los formatos de registro de cadenas para WPML. Se recomienda mantener activados todos los formatos para mayor robustez.', 'accessitrans-aria') . '</p>';
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
     * Callback para campos number
     */
    public function number_callback($args) {
        $option_name = $args['label_for'];
        $descripcion = $args['descripcion'];
        $value = isset($this->options[$option_name]) ? intval($this->options[$option_name]) : 9999;
        
        echo '<input type="number" id="' . esc_attr($option_name) . '" name="accessitrans_aria_options[' . esc_attr($option_name) . ']" value="' . esc_attr($value) . '" min="1" max="100000" />';
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
                'formato_valor_directo' => isset($options['formato_valor_directo']),
                'formato_prefijo' => isset($options['formato_prefijo']),
                'formato_elemento_id' => isset($options['formato_elemento_id']),
                'reintentar_traducciones' => isset($options['reintentar_traducciones']),
                'prioridad_traduccion' => isset($options['prioridad_traduccion']) ? intval($options['prioridad_traduccion']) : 9999
            ];
            
            update_option('accessitrans_aria_options', $sanitized_options);
            $this->options = $sanitized_options;
            
            // Mensaje de éxito con atributos para lectores de pantalla
            echo '<div class="notice notice-success is-dismissible" role="alert" aria-live="polite"><p>' . esc_html__('Configuración guardada correctamente.', 'accessitrans-aria') . '</p></div>';
        }
        
        // Script para la función de forzar actualización
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#accessitrans-force-refresh').on('click', function(e) {
                e.preventDefault();
                
                $('#accessitrans-force-refresh').prop('disabled', true);
                $('#refresh-status').html('<?php echo esc_js(__('Procesando...', 'accessitrans-aria')); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'accessitrans_force_refresh',
                        nonce: '<?php echo wp_create_nonce('accessitrans-force-refresh'); ?>'
                    },
                    success: function(response) {
                        $('#refresh-status').html(response.data);
                        $('#accessitrans-force-refresh').prop('disabled', false);
                    },
                    error: function() {
                        $('#refresh-status').html('<?php echo esc_js(__('Error al procesar la solicitud.', 'accessitrans-aria')); ?>');
                        $('#accessitrans-force-refresh').prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
        
        // Contar cadenas registradas por contexto
        $strings_count = [
            'direct' => 0,
            'prefix' => 0,
            'id' => 0
        ];
        
        if (function_exists('icl_get_string_translations')) {
            global $wpdb;
            // Contar para cada contexto
            $strings_count['direct'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings WHERE context = %s", $this->direct_context));
            $strings_count['prefix'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings WHERE context = %s", $this->prefix_context));
            $strings_count['id'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings WHERE context = %s", $this->id_context));
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
                        // Mostrar conteo por tipo de registro
                        printf(
                            __('Cadenas registradas: %d directas, %d con prefijo, %d con ID.', 'accessitrans-aria'),
                            $strings_count['direct'],
                            $strings_count['prefix'],
                            $strings_count['id']
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
            
            <div class="card" style="max-width:800px;">
                <h2><?php _e('Forzar actualización de traducciones', 'accessitrans-aria'); ?></h2>
                <p><?php _e('Si encuentras problemas con las traducciones, puedes intentar forzar una actualización que limpiará las cachés internas.', 'accessitrans-aria'); ?></p>
                <p><button id="accessitrans-force-refresh" class="button button-secondary"><?php _e('Forzar actualización', 'accessitrans-aria'); ?></button> <span id="refresh-status"></span></p>
                <p><em><?php _e('Nota: Esta operación puede tardar unos segundos y puede generar carga en el servidor.', 'accessitrans-aria'); ?></em></p>
            </div>
            
            <section class="card" aria-labelledby="instrucciones-uso-titulo" style="max-width:800px;">
                <h2 id="instrucciones-uso-titulo"><?php _e('Instrucciones de uso', 'accessitrans-aria'); ?></h2>
                <p><?php _e('Para agregar atributos ARIA en Elementor:', 'accessitrans-aria'); ?></p>
                <ol>
                    <li><?php _e('Edite cualquier elemento en Elementor', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Vaya a la pestaña "Avanzado"', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Encuentre la sección "Atributos personalizados"', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Añada los atributos ARIA que desee traducir (ej: aria-label|Texto a traducir)', 'accessitrans-aria'); ?></li>
                </ol>
                <p><?php _e('Para traducir los atributos:', 'accessitrans-aria'); ?></p>
                <ol>
                    <li><?php _e('Vaya a WPML → Traducción de cadenas', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Filtre por alguno de los contextos "AccessiTrans ARIA Attributes_XXX"', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Traduzca las cadenas como lo haría normalmente en WPML', 'accessitrans-aria'); ?></li>
                </ol>
                <p><?php _e('Prácticas recomendadas:', 'accessitrans-aria'); ?></p>
                <ul>
                    <li><?php _e('Mantenga activados todos los formatos de registro para mayor robustez', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Si una traducción no aparece, pruebe a utilizar la función "Forzar actualización"', 'accessitrans-aria'); ?></li>
                    <li><?php _e('Una vez capturadas todas las cadenas, puede desactivar los métodos de captura para mejorar el rendimiento', 'accessitrans-aria'); ?></li>
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
        </div>
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
        $this->failed_translations = [];
        
        // Limpiar caché de WPML si está disponible
        if (function_exists('icl_cache_clear')) {
            icl_cache_clear();
        }
        
        if (function_exists('wpml_st_flush_caches')) {
            wpml_st_flush_caches();
        } elseif (function_exists('icl_st_update_string_actions')) {
            icl_st_update_string_actions(true);
        }
        
        // Limpiar caché de objetos de WordPress
        wp_cache_flush();
        
        // Limpiar opciones transientes
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
        
        // Registrar en el log
        if ($this->options['modo_debug']) {
            $this->log_debug("Forzada actualización manual de traducciones y caché");
        }
        
        // Responder al AJAX
        wp_send_json_success(__('Actualización completada correctamente.', 'accessitrans-aria'));
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
        
        if ($this->options['modo_debug']) {
            $this->log_debug("Procesando HTML para buscar atributos ARIA");
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
                    
                    // Buscar el ID del elemento que contiene el atributo
                    $element_id = $this->extract_element_id_from_context($html, $match[0]);
                    
                    // Registrar el valor para traducción
                    $this->register_value_for_translation($attr, $attr_value, $element_id);
                    
                    if ($this->options['modo_debug']) {
                        $this->log_debug("CAPTURA - {$attr} = \"{$attr_value}\"" . ($element_id ? " (ID: {$element_id})" : ""));
                    }
                }
            }
        }
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
        if (!empty($content) && is_string($content)) {
            $this->extract_aria_from_html($content);
        }
        return $content;
    }
    
    /**
     * Procesa cualquier elemento de Elementor
     */
    public function process_element_attributes($element) {
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
                        
                        // Registrar el valor para traducción
                        $this->register_value_for_translation($attr_name, $value, $element_id);
                        
                        if ($this->options['modo_debug']) {
                            $this->log_debug("Encontrado en settings: {$attr_name} = \"{$value}\" (ID: {$element_id})");
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
                        $this->register_value_for_translation($key, $value, $element_id);
                        
                        if ($this->options['modo_debug']) {
                            $this->log_debug("Encontrado atributo: {$key} = \"{$value}\" (ID: {$element_id})");
                        }
                    }
                }
                // Formato key|value como string
                elseif (is_string($attribute) && strpos($attribute, '|') !== false) {
                    list($key, $value) = explode('|', $attribute, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    if (in_array($key, $this->traducible_attrs)) {
                        $this->register_value_for_translation($key, $value, $element_id);
                        
                        if ($this->options['modo_debug']) {
                            $this->log_debug("Encontrado atributo pipe: {$key} = \"{$value}\" (ID: {$element_id})");
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
                        $this->register_value_for_translation($key, $value, $element_id);
                        
                        if ($this->options['modo_debug']) {
                            $this->log_debug("Encontrado atributo multilínea: {$key} = \"{$value}\" (ID: {$element_id})");
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Registra un valor para traducción con varios formatos según la configuración
     */
    private function register_value_for_translation($attr, $value, $element_id = '') {
        if (empty($value)) {
            return;
        }
        
        // Usar el cache para evitar procesar el mismo valor varias veces
        $cache_key = md5($attr . '_' . $value . '_' . $element_id);
        if (isset($this->processed_values[$cache_key])) {
            return;
        }
        $this->processed_values[$cache_key] = true;
        
        // Registrar con diferentes formatos según la configuración
        
        // 1. Valor directamente como nombre
        if ($this->options['formato_valor_directo']) {
            do_action('wpml_register_single_string', $this->direct_context, $value, $value);
            
            if ($this->options['modo_debug']) {
                $this->log_debug("Registrado en contexto directo: \"{$value}\"");
            }
        }
        
        // 2. Formato aria-atributo_valor
        if ($this->options['formato_prefijo']) {
            $prefixed_name = "{$attr}_{$value}";
            do_action('wpml_register_single_string', $this->prefix_context, $prefixed_name, $value);
            
            if ($this->options['modo_debug']) {
                $this->log_debug("Registrado en contexto prefijo: \"{$prefixed_name}\"");
            }
        }
        
        // 3. Formato con ID de elemento
        if ($this->options['formato_elemento_id'] && !empty($element_id)) {
            $id_format = "aria_{$element_id}_{$attr}";
            do_action('wpml_register_single_string', $this->id_context, $id_format, $value);
            
            if ($this->options['modo_debug']) {
                $this->log_debug("Registrado en contexto ID: \"{$id_format}\"");
            }
        }
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
        
        // Guardar referencia al HTML completo
        $full_html = $content;
        
        // Para depuración
        $debug_translations = [];
        
        // Traducir atributos
        $result = preg_replace_callback($pattern, function($matches) use ($full_html, &$debug_translations) {
            $attr_name = $matches[1];
            $quote_type = $matches[2];
            $attr_value = $matches[3];
            
            if (empty($attr_value)) {
                return $matches[0];
            }
            
            // Para depuración
            if ($this->options['modo_debug']) {
                $debug_translations[$attr_value] = [
                    'attr' => $attr_name,
                    'original' => $attr_value,
                    'methods' => []
                ];
            }
            
            // Estrategia de traducción en cascada
            $translated = $attr_value; // Valor por defecto si no se encuentra traducción
            $translation_found = false;
            
            // 1. Intentar traducir directamente con el valor como clave
            if ($this->options['formato_valor_directo']) {
                $temp = apply_filters('wpml_translate_single_string', $attr_value, $this->direct_context, $attr_value);
                
                if ($this->options['modo_debug']) {
                    $debug_translations[$attr_value]['methods']['direct'] = ($temp !== $attr_value);
                }
                
                if ($temp !== $attr_value) {
                    $translated = $temp;
                    $translation_found = true;
                }
            }
            
            // 2. Intentar con formato atributo_valor
            if (!$translation_found && $this->options['formato_prefijo']) {
                $prefixed_name = "{$attr_name}_{$attr_value}";
                $temp = apply_filters('wpml_translate_single_string', $attr_value, $this->prefix_context, $prefixed_name);
                
                if ($this->options['modo_debug']) {
                    $debug_translations[$attr_value]['methods']['prefix'] = ($temp !== $attr_value);
                }
                
                if ($temp !== $attr_value) {
                    $translated = $temp;
                    $translation_found = true;
                }
            }
            
            // 3. Intentar con formato específico de ID de elemento
            if (!$translation_found && $this->options['formato_elemento_id']) {
                $element_id = $this->extract_element_id_from_context($full_html, $matches[0]);
                if (!empty($element_id)) {
                    $id_format = "aria_{$element_id}_{$attr_name}";
                    $temp = apply_filters('wpml_translate_single_string', $attr_value, $this->id_context, $id_format);
                    
                    if ($this->options['modo_debug']) {
                        $debug_translations[$attr_value]['methods']['id'] = ($temp !== $attr_value);
                        $debug_translations[$attr_value]['element_id'] = $element_id;
                    }
                    
                    if ($temp !== $attr_value) {
                        $translated = $temp;
                        $translation_found = true;
                    }
                }
            }
            
            // Si no se encontró traducción y está habilitado el reintento, agregar a la lista de fallidos
            if (!$translation_found && $this->options['reintentar_traducciones']) {
                $this->failed_translations[] = [
                    'attr_name' => $attr_name,
                    'attr_value' => $attr_value,
                    'element_id' => $this->extract_element_id_from_context($full_html, $matches[0])
                ];
            }
            
            // Registrar resultados en el log
            if ($this->options['modo_debug'] && isset($debug_translations[$attr_value])) {
                $debug_info = $debug_translations[$attr_value];
                $result_text = $translation_found ? "TRADUCIDO: {$attr_value} → {$translated}" : "NO TRADUCIDO: {$attr_value}";
                $method_text = '';
                
                foreach ($debug_info['methods'] as $method => $success) {
                    if ($success) {
                        $method_text = "Usando método: {$method}";
                        break;
                    }
                }
                
                $this->log_debug($result_text . ($method_text ? " ({$method_text})" : ''));
            }
            
            return " {$attr_name}={$quote_type}{$translated}{$quote_type}";
        }, $content);
        
        return $result !== null ? $result : $content;
    }
    
    /**
     * Reintenta las traducciones que fallaron en el primer intento
     */
    public function retry_failed_translations() {
        if (empty($this->failed_translations)) {
            return;
        }
        
        if ($this->options['modo_debug']) {
            $this->log_debug("Reintentando " . count($this->failed_translations) . " traducciones fallidas");
        }
        
        // Intentar nuevamente las traducciones fallidas
        foreach ($this->failed_translations as $item) {
            $attr_name = $item['attr_name'];
            $attr_value = $item['attr_value'];
            $element_id = $item['element_id'];
            
            // Intentar todos los métodos nuevamente
            $success = false;
            
            // 1. Directo
            if ($this->options['formato_valor_directo']) {
                $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->direct_context, $attr_value);
                if ($translated !== $attr_value) {
                    $success = true;
                    if ($this->options['modo_debug']) {
                        $this->log_debug("Reintento exitoso (directo): {$attr_value} → {$translated}");
                    }
                }
            }
            
            // 2. Prefijo
            if (!$success && $this->options['formato_prefijo']) {
                $prefixed_name = "{$attr_name}_{$attr_value}";
                $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->prefix_context, $prefixed_name);
                if ($translated !== $attr_value) {
                    $success = true;
                    if ($this->options['modo_debug']) {
                        $this->log_debug("Reintento exitoso (prefijo): {$attr_value} → {$translated}");
                    }
                }
            }
            
            // 3. ID
            if (!$success && $this->options['formato_elemento_id'] && !empty($element_id)) {
                $id_format = "aria_{$element_id}_{$attr_name}";
                $translated = apply_filters('wpml_translate_single_string', $attr_value, $this->id_context, $id_format);
                if ($translated !== $attr_value) {
                    $success = true;
                    if ($this->options['modo_debug']) {
                        $this->log_debug("Reintento exitoso (ID): {$attr_value} → {$translated}");
                    }
                }
            }
            
            if (!$success && $this->options['modo_debug']) {
                $this->log_debug("Reintento fallido para: {$attr_value}");
            }
        }
        
        // Limpiar la lista de traducciones fallidas
        $this->failed_translations = [];
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
     * Logger para debug
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