<?php
/**
 * Plugin Name: Elementor ARIA Translator for WPML
 * Plugin URI: https://github.com/marioalmonte/elementor-aria-translator
 * Description: Traduce atributos ARIA en Elementor utilizando WPML, mejorando la accesibilidad de tu sitio web multilingüe. Desarrollado por un profesional certificado en Accesibilidad Web (CPWA).
 * Version: 2.0.2
 * Author: Mario Germán Almonte Moreno
 * Author URI: https://www.linkedin.com/in/marioalmonte/
 * Text Domain: elementor-aria-translator
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

/**
 * Carga el dominio de texto para la internacionalización
 */
function elementor_aria_translator_load_textdomain() {
    load_plugin_textdomain(
        'elementor-aria-translator',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'elementor_aria_translator_load_textdomain', 10);

class Elementor_ARIA_Translator {
    
    /**
     * Instancia singleton
     */
    private static $instance = null;
    
    /**
     * Contexto único para todas las traducciones ARIA
     */
    private $context = 'Elementor ARIA Attributes';
    
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
     * Constructor privado
     */
    private function __construct() {
        // Inicializar valores por defecto para nuevas instalaciones
        if (!get_option('elementor_aria_translator_options')) {
            update_option('elementor_aria_translator_options', [
                'captura_total' => true,
                'captura_elementor' => true,
                'procesar_templates' => true,
                'procesar_elementos' => true,
                'modo_debug' => false,
                'solo_admin' => true,
                'formato_valor_directo' => false,
                'formato_prefijo' => true,
                'formato_elemento_id' => false
            ]);
        }
        
        // Cargar opciones
        $this->options = get_option('elementor_aria_translator_options', [
            'captura_total' => true,
            'captura_elementor' => true,
            'procesar_templates' => true,
            'procesar_elementos' => true,
            'modo_debug' => false,
            'solo_admin' => true,
            'formato_valor_directo' => false,
            'formato_prefijo' => true,
            'formato_elemento_id' => false
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
        
        // Registro de debug
        if ($this->options['modo_debug']) {
            $this->log_debug('Plugin inicializado - Versión 2.0.0');
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
        if (!get_option('elementor_aria_translator_options')) {
            update_option('elementor_aria_translator_options', [
                'captura_total' => true,
                'captura_elementor' => true,
                'procesar_templates' => true,
                'procesar_elementos' => true,
                'modo_debug' => false,
                'solo_admin' => true,
                'formato_valor_directo' => false,
                'formato_prefijo' => true,
                'formato_elemento_id' => false
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
        $message = __('Elementor ARIA Translator requiere que Elementor y WPML estén instalados y activados.', 'elementor-aria-translator');
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }
    
    /**
     * Añade página de ajustes al menú de administración
     */
    public function add_settings_page() {
        add_submenu_page(
            'options-general.php',
            __('Elementor ARIA Translator', 'elementor-aria-translator'),
            __('Elementor ARIA Translator', 'elementor-aria-translator'),
            'manage_options',
            'elementor-aria-translator',
            [$this, 'settings_page']
        );
    }
    
    /**
     * Registra los ajustes del plugin
     */
    public function register_settings() {
        register_setting('elementor_aria_translator', 'elementor_aria_translator_options');
        
        add_settings_section(
            'elementor_aria_translator_main',
            __('Configuración de métodos de captura', 'elementor-aria-translator'),
            [$this, 'section_callback'],
            'elementor-aria-translator'
        );
        
        add_settings_field(
            'captura_total',
            __('Captura total de HTML', 'elementor-aria-translator'),
            [$this, 'checkbox_callback'],
            'elementor-aria-translator',
            'elementor_aria_translator_main',
            ['label_for' => 'captura_total', 'descripcion' => __('Captura todo el HTML de la página. Altamente efectivo pero puede afectar al rendimiento.', 'elementor-aria-translator')]
        );
        
        add_settings_field(
            'captura_elementor',
            __('Filtro de contenido de Elementor', 'elementor-aria-translator'),
            [$this, 'checkbox_callback'],
            'elementor-aria-translator',
            'elementor_aria_translator_main',
            ['label_for' => 'captura_elementor', 'descripcion' => __('Procesa el contenido generado por Elementor.', 'elementor-aria-translator')]
        );
        
        add_settings_field(
            'procesar_templates',
            __('Procesar templates de Elementor', 'elementor-aria-translator'),
            [$this, 'checkbox_callback'],
            'elementor-aria-translator',
            'elementor_aria_translator_main',
            ['label_for' => 'procesar_templates', 'descripcion' => __('Procesa los datos de templates de Elementor.', 'elementor-aria-translator')]
        );
        
        add_settings_field(
            'procesar_elementos',
            __('Procesar elementos individuales', 'elementor-aria-translator'),
            [$this, 'checkbox_callback'],
            'elementor-aria-translator',
            'elementor_aria_translator_main',
            ['label_for' => 'procesar_elementos', 'descripcion' => __('Procesa cada widget y elemento de Elementor individualmente.', 'elementor-aria-translator')]
        );
        
        add_settings_section(
            'elementor_aria_translator_formats',
            __('Configuración de formatos de registro', 'elementor-aria-translator'),
            [$this, 'section_formats_callback'],
            'elementor-aria-translator'
        );
        
        add_settings_field(
            'formato_valor_directo',
            __('Formato directo', 'elementor-aria-translator'),
            [$this, 'checkbox_callback'],
            'elementor-aria-translator',
            'elementor_aria_translator_formats',
            ['label_for' => 'formato_valor_directo', 'descripcion' => __('Registrar el valor literal como nombre.', 'elementor-aria-translator')]
        );
        
        add_settings_field(
            'formato_prefijo',
            __('Formato con prefijo', 'elementor-aria-translator'),
            [$this, 'checkbox_callback'],
            'elementor-aria-translator',
            'elementor_aria_translator_formats',
            ['label_for' => 'formato_prefijo', 'descripcion' => __('Registrar con formato aria-atributo_valor.', 'elementor-aria-translator')]
        );
        
        add_settings_field(
            'formato_elemento_id',
            __('Formato con ID de elemento', 'elementor-aria-translator'),
            [$this, 'checkbox_callback'],
            'elementor-aria-translator',
            'elementor_aria_translator_formats',
            ['label_for' => 'formato_elemento_id', 'descripcion' => __('Registrar con formato incluyendo ID del elemento.', 'elementor-aria-translator')]
        );
        
        add_settings_section(
            'elementor_aria_translator_advanced',
            __('Configuración avanzada', 'elementor-aria-translator'),
            [$this, 'section_advanced_callback'],
            'elementor-aria-translator'
        );
        
        add_settings_field(
            'modo_debug',
            __('Modo de depuración', 'elementor-aria-translator'),
            [$this, 'checkbox_callback'],
            'elementor-aria-translator',
            'elementor_aria_translator_advanced',
            ['label_for' => 'modo_debug', 'descripcion' => __('Activa el registro detallado de eventos. Se almacena en wp-content/debug-aria-wpml.log', 'elementor-aria-translator')]
        );
        
        add_settings_field(
            'solo_admin',
            __('Captura solo para administradores', 'elementor-aria-translator'),
            [$this, 'checkbox_callback'],
            'elementor-aria-translator',
            'elementor_aria_translator_advanced',
            ['label_for' => 'solo_admin', 'descripcion' => __('Solo procesa la captura total cuando un administrador está conectado.', 'elementor-aria-translator')]
        );
    }
    
    /**
     * Callback para la sección principal de ajustes
     */
    public function section_callback() {
        echo '<p>' . esc_html__('Configura los métodos de captura de atributos ARIA. Puedes activar varios métodos simultáneamente para una detección más robusta.', 'elementor-aria-translator') . '</p>';
    }
    
    /**
     * Callback para la sección de formatos
     */
    public function section_formats_callback() {
        echo '<p>' . esc_html__('Configura los formatos de registro de cadenas para WPML. Elegir más de un formato duplicará las cadenas encontradas en WPML aunque puede aumentar la robustez.', 'elementor-aria-translator') . '</p>';
    }
    
    /**
     * Callback para la sección avanzada
     */
    public function section_advanced_callback() {
        echo '<p>' . esc_html__('Configuración avanzada para rendimiento y depuración.', 'elementor-aria-translator') . '</p>';
    }
    
    /**
     * Callback para campos checkbox
     */
    public function checkbox_callback($args) {
        $option_name = $args['label_for'];
        $descripcion = $args['descripcion'];
        $checked = isset($this->options[$option_name]) && $this->options[$option_name] ? 'checked' : '';
        
        echo '<input type="checkbox" id="' . esc_attr($option_name) . '" name="elementor_aria_translator_options[' . esc_attr($option_name) . ']" value="1" ' . $checked . ' />';
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
            check_admin_referer('elementor_aria_translator_settings');
            
            $options = isset($_POST['elementor_aria_translator_options']) ? $_POST['elementor_aria_translator_options'] : [];
            $sanitized_options = [
                'captura_total' => isset($options['captura_total']),
                'captura_elementor' => isset($options['captura_elementor']),
                'procesar_templates' => isset($options['procesar_templates']),
                'procesar_elementos' => isset($options['procesar_elementos']),
                'modo_debug' => isset($options['modo_debug']),
                'solo_admin' => isset($options['solo_admin']),
                'formato_valor_directo' => isset($options['formato_valor_directo']),
                'formato_prefijo' => isset($options['formato_prefijo']),
                'formato_elemento_id' => isset($options['formato_elemento_id'])
            ];
            
            update_option('elementor_aria_translator_options', $sanitized_options);
            $this->options = $sanitized_options;
            
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configuración guardada correctamente.', 'elementor-aria-translator') . '</p></div>';
        }
        
        // Contar cadenas registradas
        $strings_count = 0;
        if (function_exists('icl_get_string_translations')) {
            global $wpdb;
            $strings_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings WHERE context = '{$this->context}'");
        }
        
        // Mostrar formulario de ajustes
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('Este plugin permite traducir atributos ARIA en sitios desarrollados con Elementor y WPML.', 'elementor-aria-translator'); ?></p>
                <p><?php printf(__('Actualmente hay %d cadenas registradas en el contexto "Elementor ARIA Attributes".', 'elementor-aria-translator'), $strings_count); ?></p>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('elementor_aria_translator');
                do_settings_sections('elementor-aria-translator');
                wp_nonce_field('elementor_aria_translator_settings');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2><?php _e('Instrucciones de uso', 'elementor-aria-translator'); ?></h2>
                <p><?php _e('Para agregar atributos ARIA en Elementor:', 'elementor-aria-translator'); ?></p>
                <ol>
                    <li><?php _e('Edite cualquier elemento en Elementor', 'elementor-aria-translator'); ?></li>
                    <li><?php _e('Vaya a la pestaña "Avanzado"', 'elementor-aria-translator'); ?></li>
                    <li><?php _e('Encuentre la sección "Atributos personalizados"', 'elementor-aria-translator'); ?></li>
                    <li><?php _e('Añada los atributos ARIA que desee traducir (ej: aria-label|Texto a traducir)', 'elementor-aria-translator'); ?></li>
                </ol>
                <p><?php _e('Para traducir los atributos:', 'elementor-aria-translator'); ?></p>
                <ol>
                    <li><?php _e('Vaya a WPML → Traducción de cadenas', 'elementor-aria-translator'); ?></li>
                    <li><?php _e('Filtre por el contexto "Elementor ARIA Attributes"', 'elementor-aria-translator'); ?></li>
                    <li><?php _e('Traduzca las cadenas como lo haría normalmente en WPML', 'elementor-aria-translator'); ?></li>
                </ol>
            </div>
            
            <div class="card">
                <h2><?php _e('Acerca del autor', 'elementor-aria-translator'); ?></h2>
                <p><?php _e('Desarrollado por', 'elementor-aria-translator'); ?> Mario Germán Almonte Moreno:</p>
                <ul>
                    <li><?php _e('Miembro de IAAP (International Association of Accessibility Professionals)', 'elementor-aria-translator'); ?></li>
                    <li><?php _e('Certificado CPWA (CPACC y WAS)', 'elementor-aria-translator'); ?></li>
                    <li><?php _e('Profesor en el Curso de especialización en Accesibilidad Digital (Universidad de Lleida)', 'elementor-aria-translator'); ?></li>
                </ul>
                <p><strong><?php _e('Servicios Profesionales:', 'elementor-aria-translator'); ?></strong></p>
                <ul>
                    <li><?php _e('Formación y consultoría en Accesibilidad Web y eLearning', 'elementor-aria-translator'); ?></li>
                    <li><?php _e('Auditorías de accesibilidad web según EN 301 549 (WCAG 2.2, ATAG 2.0)', 'elementor-aria-translator'); ?></li>
                </ul>
                <p><a href="https://www.linkedin.com/in/marioalmonte/" target="_blank"><?php _e('Visita mi perfil de LinkedIn', 'elementor-aria-translator'); ?></a></p>
                <p><a href="https://aprendizajeenred.es" target="_blank"><?php _e('Sitio web y blog', 'elementor-aria-translator'); ?></a></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Añade enlaces a la página de configuración en la lista de plugins
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=elementor-aria-translator') . '">' . __('Settings', 'elementor-aria-translator') . '</a>';
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
            do_action('wpml_register_single_string', $this->context, $value, $value);
        }
        
        // 2. Formato aria-atributo_valor
        if ($this->options['formato_prefijo']) {
            $prefixed_name = "{$attr}_{$value}";
            do_action('wpml_register_single_string', $this->context, $prefixed_name, $value);
        }
        
        // 3. Formato con ID de elemento
        if ($this->options['formato_elemento_id'] && !empty($element_id)) {
            $id_format = "aria_{$element_id}_{$attr}";
            do_action('wpml_register_single_string', $this->context, $id_format, $value);
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
        
        // Traducir atributos
        $result = preg_replace_callback($pattern, function($matches) use ($full_html) {
            $attr_name = $matches[1];
            $quote_type = $matches[2];
            $attr_value = $matches[3];
            
            if (empty($attr_value)) {
                return $matches[0];
            }
            
            // Estrategia de traducción en cascada
            $translated = $attr_value; // Valor por defecto si no se encuentra traducción
            
            // 1. Intentar traducir directamente con el valor como clave
            if ($this->options['formato_valor_directo']) {
                $temp = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $attr_value);
                if ($temp !== $attr_value) {
                    $translated = $temp;
                }
            }
            
            // 2. Intentar con formato atributo_valor
            if ($translated === $attr_value && $this->options['formato_prefijo']) {
                $prefixed_name = "{$attr_name}_{$attr_value}";
                $temp = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $prefixed_name);
                if ($temp !== $attr_value) {
                    $translated = $temp;
                }
            }
            
            // 3. Intentar con formato específico de ID de elemento
            if ($translated === $attr_value && $this->options['formato_elemento_id']) {
                $element_id = $this->extract_element_id_from_context($full_html, $matches[0]);
                if (!empty($element_id)) {
                    $id_format = "aria_{$element_id}_{$attr_name}";
                    $temp = apply_filters('wpml_translate_single_string', $attr_value, $this->context, $id_format);
                    if ($temp !== $attr_value) {
                        $translated = $temp;
                    }
                }
            }
            
            return " {$attr_name}={$quote_type}{$translated}{$quote_type}";
        }, $content);
        
        return $result !== null ? $result : $content;
    }
    
    /**
     * Añade información en la lista de plugins
     */
    public function after_plugin_row($plugin_file, $plugin_data, $status) {
        if (plugin_basename(__FILE__) == $plugin_file) {
            echo '<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange"><div class="notice inline notice-info" style="margin:0; padding:5px;">';
            echo '<strong>' . __('Compatibilidad verificada:', 'elementor-aria-translator') . '</strong> WordPress 6.7, Elementor 3.28.3, WPML Multilingual CMS 4.7.3 y WPML String Translation 3.3.2.';
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
    Elementor_ARIA_Translator::get_instance();
}, 20); // Prioridad 20 para asegurarnos que WPML y Elementor estén cargados