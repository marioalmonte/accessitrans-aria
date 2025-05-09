<?php
/**
 * Clase para la interfaz de administración
 *
 * @package AccessiTrans
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

/**
 * Clase para gestionar la interfaz de administración
 */
class AccessiTrans_Admin {
    
    /**
     * Referencia a la clase principal
     */
    private $core;
    
    /**
     * Constructor
     */
    public function __construct($core) {
        $this->core = $core;
        
        // Registrar ajustes
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Añadir enlaces en la página de plugins
        add_filter('plugin_action_links_' . plugin_basename(ACCESSITRANS_PATH . 'accessitrans-aria.php'), [$this, 'add_action_links']);
        
        // Encolar scripts y estilos para la interfaz de administración
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Registrar funciones AJAX
        add_action('wp_ajax_accessitrans_toggle_scan', [$this, 'toggle_scan_callback']);
        add_action('wp_ajax_accessitrans_diagnostics', [$this, 'diagnostics_callback']);
        add_action('wp_ajax_accessitrans_force_refresh', [$this, 'force_refresh_callback']);
        add_action('wp_ajax_accessitrans_check_health', [$this, 'check_health_callback']);
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
     * Encola scripts y estilos para la interfaz de administración
     *
     * @param string $hook_suffix La página de administración actual
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Solo cargar en la página de configuración del plugin
        if ('settings_page_accessitrans-aria' !== $hook_suffix) {
            return;
        }
        
        // Encolar estilos CSS
        wp_enqueue_style(
            'accessitrans-admin-styles',
            ACCESSITRANS_URL . 'assets/css/admin-styles.css',
            [],
            ACCESSITRANS_VERSION
        );
        
        // Encolar scripts JS
        wp_enqueue_script(
            'accessitrans-admin-scripts',
            ACCESSITRANS_URL . 'assets/js/admin-scripts.js',
            ['jquery'],
            ACCESSITRANS_VERSION,
            true // Cargar en el footer para mejor rendimiento
        );
        
        // Pasar variables a JavaScript
        wp_localize_script(
            'accessitrans-admin-scripts',
            'accessitransAdmin',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonces' => [
                    'toggle_scan' => wp_create_nonce('accessitrans-toggle-scan'),
                    'diagnostics' => wp_create_nonce('accessitrans-diagnostics'),
                    'force_refresh' => wp_create_nonce('accessitrans-force-refresh'),
                    'check_health' => wp_create_nonce('accessitrans-check-health')
                ],
                'strings' => [
                    'saving' => __('Guardando...', 'accessitrans-aria'),
                    'saveError' => __('Error al guardar la configuración.', 'accessitrans-aria'),
                    'processing' => __('Procesando...', 'accessitrans-aria'),
                    'requestError' => __('Error al procesar la solicitud.', 'accessitrans-aria'),
                    'noString' => __('Por favor, ingresa una cadena para verificar.', 'accessitrans-aria'),
                    'analyzing' => __('Analizando...', 'accessitrans-aria'),
                    'analyzingMessage' => __('Analizando cadena...', 'accessitrans-aria'),
                    'errorNoString' => __('Error: No se ha ingresado una cadena para verificar.', 'accessitrans-aria'),
                    'scanEnabledMessage' => __('Escaneo activado. Los métodos de captura están disponibles.', 'accessitrans-aria'),
                    'scanDisabledMessage' => __('Escaneo desactivado. Los métodos de captura están deshabilitados.', 'accessitrans-aria'),
                    'diagnosticResults' => __('Resultados del diagnóstico:', 'accessitrans-aria'),
                    'originalText' => __('Texto original:', 'accessitrans-aria'),
                    'language' => __('Idioma:', 'accessitrans-aria'),
                    'languageCorrect' => __('✓ Idioma correcto', 'accessitrans-aria'),
                    'languageNotPrimary' => __('✗ No es idioma principal', 'accessitrans-aria'),
                    
                    // Añadir todas las cadenas que faltan
                    'foundInWPML' => __('✓ Encontrada en WPML', 'accessitrans-aria'),
                    'notFoundInWPML' => __('✗ No encontrada en WPML', 'accessitrans-aria'),
                    'registeredFormats' => __('Formatos registrados:', 'accessitrans-aria'),
                    'hasTranslations' => __('✓ Tiene traducciones', 'accessitrans-aria'),
                    'noTranslations' => __('✗ No tiene traducciones', 'accessitrans-aria'),
                    'availableTranslations' => __('Traducciones disponibles:', 'accessitrans-aria'),
                    'noCurrentLanguageTranslation' => __('✗ No hay traducción para el idioma actual', 'accessitrans-aria'),
                    'recommendedActionTranslate' => __('Acción recomendada: Traduce esta cadena al idioma actual en WPML → String Translation.', 'accessitrans-aria'),
                    'translateAction' => __('Acción recomendada: Traduce esta cadena en WPML → String Translation.', 'accessitrans-aria'),
                    'notPrimaryLanguageWarning' => __('⚠️ Estás navegando en un idioma que no es el principal. Cambia al idioma principal para registrar cadenas.', 'accessitrans-aria'),
                    'recommendedActionNavigate' => __('Acción recomendada: Navega por tu sitio con los métodos de captura activados o edita el elemento en Elementor para registrar esta cadena.', 'accessitrans-aria'),
                    'troubleshootingTips' => __('Consejos para solucionar problemas:', 'accessitrans-aria'),
                    'tip1' => __('Asegúrate de navegar en el idioma principal del sitio al capturar cadenas.', 'accessitrans-aria'),
                    'tip2' => __('Prueba a utilizar "Forzar actualización" para limpiar todas las cachés.', 'accessitrans-aria'),
                    'tip3' => __('Si has cambiado el texto en el idioma original, necesitarás traducirlo nuevamente en WPML.', 'accessitrans-aria'),
                    'technicalInfo' => __('Información técnica avanzada', 'accessitrans-aria'),
                    'analysisComplete' => __('Análisis completado. Se encontraron resultados para la cadena', 'accessitrans-aria'),
                    'analysisError' => __('Error en el análisis:', 'accessitrans-aria'),
                    'connectionError' => __('Error al realizar el análisis. No se pudo contactar con el servidor.', 'accessitrans-aria'),
                    
                    // Cadenas para verificación de salud
                    'verifying' => __('Verificando...', 'accessitrans-aria'),
                    'verifyingStatus' => __('Verificando estado del sistema...', 'accessitrans-aria'),
                    'systemStatus' => __('Estado del sistema:', 'accessitrans-aria'),
                    'active' => __('Activo', 'accessitrans-aria'),
                    'inactive' => __('Inactivo', 'accessitrans-aria'),
                    'wpml' => __('WPML:', 'accessitrans-aria'),
                    'elementor' => __('Elementor:', 'accessitrans-aria'),
                    'registeredStrings' => __('Cadenas registradas:', 'accessitrans-aria'),
                    'primaryLanguage' => __('Idioma principal:', 'accessitrans-aria'),
                    'currentLanguage' => __('Idioma actual:', 'accessitrans-aria'),
                    'availableLanguages' => __('Idiomas disponibles:', 'accessitrans-aria'),
                    'currentConfiguration' => __('Configuración actual:', 'accessitrans-aria'),
                    'recommendations' => __('Recomendaciones:', 'accessitrans-aria'),
                    'noStringsRegistered' => __('• No hay cadenas registradas. Navega por tu sitio con los métodos de captura activados.', 'accessitrans-aria'),
                    'stringsNoTranslations' => __('• Hay cadenas registradas pero sin traducciones. Visita WPML → String Translation para traducirlas.', 'accessitrans-aria'),
                    'navigatingNonPrimary' => __('• Estás navegando en un idioma que no es el principal. Si quieres registrar nuevas cadenas, cambia al idioma principal.', 'accessitrans-aria'),
                    'serverDate' => __('Fecha del servidor:', 'accessitrans-aria'),
                    'pluginVersion' => __('Versión del plugin:', 'accessitrans-aria'),
                    'verificationComplete' => __('Verificación completada. Se encontraron', 'accessitrans-aria'),
                    'registeredStringsText' => __('cadenas registradas y', 'accessitrans-aria'),
                    'translationsText' => __('traducciones.', 'accessitrans-aria'),
                    'verificationError' => __('Error en la verificación:', 'accessitrans-aria'),
                    'connectionVerificationError' => __('Error al realizar la verificación. No se pudo contactar con el servidor.', 'accessitrans-aria')
                ]
            ]
        );
    }
    
    /**
     * Sanitiza las opciones del plugin
     * 
     * @param array $input Las opciones a sanitizar
     * @return array Las opciones sanitizadas
     */
    public function sanitize_plugin_options($input) {
        if (!is_array($input)) {
            return array();
        }
        
        // Crear array para las opciones sanitizadas
        $sanitized_options = array();
        
        // Lista de opciones esperadas tipo checkbox
        $checkbox_options = array(
            'captura_total',
            'captura_elementor',
            'procesar_templates',
            'procesar_elementos',
            'modo_debug',
            'solo_admin',
            'captura_en_idioma_principal',
            'permitir_escaneo'
        );
        
        // Sanitizar checkbox options (true/false)
        foreach ($checkbox_options as $option) {
            $sanitized_options[$option] = isset($input[$option]) && filter_var($input[$option], FILTER_VALIDATE_BOOLEAN);
        }
        
        return $sanitized_options;
    }
    
    /**
     * Registra los ajustes del plugin
     */
    public function register_settings() {
        // Registrar la opción principal del plugin
        register_setting(
            'accessitrans_aria',          // Option group
            'accessitrans_aria_options',  // Option name
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize_plugin_options'),
            )
        );
       
        // Establecer valores por defecto si la opción no existe
        if (!get_option('accessitrans_aria_options')) {
            update_option('accessitrans_aria_options', array(
                'captura_total' => true,
                'captura_elementor' => true,
                'procesar_templates' => true,
                'procesar_elementos' => true,
                'modo_debug' => false,
                'solo_admin' => true,
                'captura_en_idioma_principal' => true,
                'permitir_escaneo' => true
            ));
        }
        
        // Esta sección no se usa directamente en el render, está configurada 
        // solo para mantener compatibilidad con la API de WordPress
        
        // Nueva sección general para el interruptor principal
        add_settings_section(
            'accessitrans_aria_general',
            __('Configuración general', 'accessitrans-aria'),
            array($this, 'section_general_callback'),
            'accessitrans-aria'
        );
        
        // Nuevo campo para el interruptor principal de escaneo
        add_settings_field(
            'permitir_escaneo',
            __('Permitir escaneo de nuevas cadenas', 'accessitrans-aria'),
            [$this, 'toggle_callback'],
            'accessitrans-aria',
            'accessitrans_aria_general',
            [
                'label_for' => 'permitir_escaneo', 
                'descripcion' => __('Activa la captura de nuevas cadenas ARIA. Desactívalo después de capturar todas las cadenas para mejorar el rendimiento.', 'accessitrans-aria')
            ]
        );
        
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
            ['label_for' => 'modo_debug', 'descripcion' => __('Activa el registro detallado de eventos. Se almacena en uploads/accessitrans-logs.', 'accessitrans-aria')]
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
     * Callback para la acción AJAX de activar/desactivar escaneo
     */
    public function toggle_scan_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-toggle-scan', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'accessitrans-aria'));
            return;
        }
        
        $enabled = isset($_POST['enabled']) ? absint($_POST['enabled']) === 1 : false;
        
        // Obtener opciones actuales
        $options = get_option('accessitrans_aria_options', []);
        
        // Actualizar la opción de escaneo
        $options['permitir_escaneo'] = $enabled;
        
        // Guardar opciones
        update_option('accessitrans_aria_options', $options);
        
        // Actualizar opciones en el objeto core
        $this->core->options = $options;
        
        // Mensaje para el usuario
        if ($enabled) {
            wp_send_json_success(__('Escaneo de cadenas activado. Los cambios se han guardado.', 'accessitrans-aria'));
        } else {
            wp_send_json_success(__('Escaneo de cadenas desactivado. Los cambios se han guardado.', 'accessitrans-aria'));
        }
    }
    
    /**
     * Callback para diagnóstico de cadenas
     */
    public function diagnostics_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-diagnostics', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('No tienes permisos para realizar esta acción.', 'accessitrans-aria'));
            return;
        }
        
        // Obtener la cadena a verificar - sanitizar y deseslashear
        $string = isset($_POST['string']) ? sanitize_text_field(wp_unslash($_POST['string'])) : '';
        if (empty($string)) {
            wp_send_json_error(esc_html__('No se proporcionó ninguna cadena para verificar.', 'accessitrans-aria'));
            return;
        }
        
        // IMPORTANTE: Establecer temporalmente permitir_escaneo a false para evitar registros
        $original_scan_state = isset($this->core->options['permitir_escaneo']) ? 
                             $this->core->options['permitir_escaneo'] : false;
        $this->core->options['permitir_escaneo'] = false;
        
        // Obtener resultados de diagnóstico sin registrar nuevas cadenas
        $results = $this->core->diagnostics->diagnose_translation_improved($string);
        
        // Restaurar estado original
        $this->core->options['permitir_escaneo'] = $original_scan_state;
        
        wp_send_json_success($results);
    }
    
    /**
     * Callback para forzar actualización de traducciones
     */
    public function force_refresh_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-force-refresh', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('No tienes permisos para realizar esta acción.', 'accessitrans-aria'));
            return;
        }
        
        // IMPORTANTE: Establecer temporalmente permitir_escaneo a false
        $original_scan_state = isset($this->core->options['permitir_escaneo']) ? 
                             $this->core->options['permitir_escaneo'] : false;
        $this->core->options['permitir_escaneo'] = false;
        
        // Limpiar caché de traducciones
        delete_option('accessitrans_translation_cache');
        if ($this->core->translator) {
            $this->core->translator->clear_cache();
        }
        
        // Restaurar estado original
        $this->core->options['permitir_escaneo'] = $original_scan_state;
        
        wp_send_json_success(esc_html__('Caché de traducciones actualizada correctamente.', 'accessitrans-aria'));
    }
    
    /**
     * Callback para verificación de salud del sistema
     */
    public function check_health_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-check-health', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('No tienes permisos para realizar esta acción.', 'accessitrans-aria'));
            return;
        }
        
        // IMPORTANTE: Establecer temporalmente permitir_escaneo a false
        $original_scan_state = isset($this->core->options['permitir_escaneo']) ? 
                             $this->core->options['permitir_escaneo'] : false;
        $this->core->options['permitir_escaneo'] = false;
        
        // Obtener estado del sistema
        $health_data = $this->core->diagnostics->check_health();
        
        // Restaurar estado original
        $this->core->options['permitir_escaneo'] = $original_scan_state;
        
        wp_send_json_success($health_data);
    }
    
    /**
     * Callback para la sección general
     */
    public function section_general_callback() {
        echo '<p>' . esc_html__('Configuración general del plugin.', 'accessitrans-aria') . '</p>';
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
        $checked = isset($this->core->options[$option_name]) && $this->core->options[$option_name] ? 'checked' : '';
        $disabled = '';
        $disabled_attr = '';
        
        // Desactivar los controles dependientes si el escaneo está desactivado
        if (in_array($option_name, ['captura_total', 'captura_elementor', 'procesar_templates', 'procesar_elementos']) && 
            isset($this->core->options['permitir_escaneo']) && 
            !$this->core->options['permitir_escaneo']) {
            $disabled = 'disabled';
            $disabled_attr = 'disabled aria-disabled="true"';
        }
        
        echo '<div class="accessitrans-field ' . esc_attr($disabled) . '">';
        echo '<input type="checkbox" id="' . esc_attr($option_name) . '" name="accessitrans_aria_options[' . esc_attr($option_name) . ']" value="1" ' . esc_attr($checked) . ' ' . esc_attr($disabled_attr) . ' />';
        echo '<label for="' . esc_attr($option_name) . '">' . esc_html($descripcion) . '</label>';
        echo '</div>';
    }
    
    /**
     * Callback para campos toggle (interruptor on/off)
     */
    public function toggle_callback($args) {
        $option_name = $args['label_for'];
        $descripcion = $args['descripcion'];
        $checked = isset($this->core->options[$option_name]) && $this->core->options[$option_name] ? 'checked' : '';
        
        echo '<div class="accessitrans-field">';
        echo '<label class="accessitrans-switch" for="' . esc_attr($option_name) . '">';
        echo '<input type="checkbox" id="' . esc_attr($option_name) . '" name="accessitrans_aria_options[' . esc_attr($option_name) . ']" value="1" ' . esc_attr($checked) . ' aria-describedby="desc-' . esc_attr($option_name) . '" role="switch" />';
        echo '<span class="accessitrans-slider round"></span>';
        echo '</label>';
        echo '<p class="description" id="desc-' . esc_attr($option_name) . '">' . esc_html($descripcion) . '</p>';
        
        // Mensaje informativo destacado sobre la desactivación del escaneo
        echo '<div class="accessitrans-notice">';
        echo '<strong>' . esc_html__('Consejo de rendimiento:', 'accessitrans-aria') . '</strong>';
        echo esc_html__('Una vez hayas capturado todas las cadenas ARIA de tu sitio, puedes desactivar el escaneo para mejorar el rendimiento. Las traducciones seguirán funcionando.', 'accessitrans-aria');
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Renderiza la página de ajustes usando estructura semántica con fieldset/legend
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Guardar opciones si se ha enviado el formulario
        if (isset($_POST['submit'])) {
            // Verificar nonce de seguridad
            check_admin_referer('accessitrans_aria_settings');
            
            // Deseslashear y sanitizar las opciones del formulario
            $raw_options = isset($_POST['accessitrans_aria_options']) ? map_deep(wp_unslash($_POST['accessitrans_aria_options']), 'sanitize_text_field') : [];
            
            // Verificar el campo oculto que refleja el estado del toggle AJAX
            $permitir_escaneo = false;
            if (isset($_POST['hidden_permitir_escaneo'])) {
                $permitir_escaneo = (sanitize_text_field(wp_unslash($_POST['hidden_permitir_escaneo'])) === '1');
            }
            
            // Lista de opciones esperadas tipo checkbox
            $checkbox_options = [
                'captura_total',
                'captura_elementor',
                'procesar_templates',
                'procesar_elementos',
                'modo_debug',
                'solo_admin',
                'captura_en_idioma_principal'
            ];
            
            // Construir opciones sanitizadas
            $sanitized_options = [];
            
            // Sanitizar opciones de checkbox (convertir a booleanos explícitos)
            foreach ($checkbox_options as $option) {
                // Sanitizar cada opción individualmente como booleano
                $sanitized_options[$option] = isset($raw_options[$option]) && filter_var($raw_options[$option], FILTER_VALIDATE_BOOLEAN);
            }
            
            // Si el escaneo está desactivado, preservar los valores actuales de los métodos de captura
            if (!$permitir_escaneo) {
                // Recuperar los valores actuales de los métodos de captura
                $sanitized_options['captura_total'] = $this->core->options['captura_total'] ?? false;
                $sanitized_options['captura_elementor'] = $this->core->options['captura_elementor'] ?? false;
                $sanitized_options['procesar_templates'] = $this->core->options['procesar_templates'] ?? false;
                $sanitized_options['procesar_elementos'] = $this->core->options['procesar_elementos'] ?? false;
            }
            
            // Asignar el valor correcto a permitir_escaneo
            $sanitized_options['permitir_escaneo'] = $permitir_escaneo;
            
            // Guardar opciones sanitizadas
            update_option('accessitrans_aria_options', $sanitized_options);
            $this->core->options = $sanitized_options;
            
            // Mensaje de éxito con atributos para lectores de pantalla
            echo '<div class="notice notice-success is-dismissible" role="alert" aria-live="polite"><p>' . esc_html__('Configuración guardada correctamente.', 'accessitrans-aria') . '</p></div>';
        }
        
        // Contar cadenas registradas
        $strings_count = 0;
        if (function_exists('icl_get_string_translations')) {
            global $wpdb;
            $strings_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings WHERE context = %s",
                $this->core->get_context()
            ));
        }
        
        // Área para anuncios ARIA live
        echo '<div id="accessitrans-aria-live" class="screen-reader-text" aria-live="polite"></div>';
        
        // Mensaje para cuando el escaneo está desactivado
        echo '<div id="scan-disabled-message" class="screen-reader-text">' . esc_html__('El escaneo global está desactivado. Estos controles no están disponibles.', 'accessitrans-aria') . '</div>';
        
        // Mostrar formulario de ajustes con estructura semántica mejorada
        ?>
        <div class="wrap accessitrans-admin-container">
            <h1><?php echo esc_html(__('Configuración de', 'accessitrans-aria') . ' AccessiTrans'); ?></h1>
            
            <p class="plugin-description"><?php esc_html_e('Este plugin permite traducir atributos ARIA en sitios desarrollados con Elementor y WPML.', 'accessitrans-aria'); ?></p>
            
            <div class="notice notice-info" role="region" aria-label="<?php esc_attr_e('Estado actual', 'accessitrans-aria'); ?>">
                <p>
                    <strong><?php esc_html_e('Estado actual:', 'accessitrans-aria'); ?></strong>
                    <?php 
                    printf(
                    /* translators: %d: número de cadenas registradas */
                        esc_html__('Cadenas registradas: %d', 'accessitrans-aria'),
                        esc_html($strings_count)
                    ); 
                    ?>
                </p>
            </div>
            
            <!-- Activación general (independiente) con AJAX -->
            <section class="card" aria-labelledby="activacion-general-titulo">
                <h2 id="activacion-general-titulo"><?php esc_html_e('Activación general', 'accessitrans-aria'); ?></h2>
                
                <div class="accessitrans-field">
                    <label class="accessitrans-switch" for="permitir_escaneo_ajax">
                        <input type="checkbox" id="permitir_escaneo_ajax" <?php echo isset($this->core->options['permitir_escaneo']) && $this->core->options['permitir_escaneo'] ? 'checked' : ''; ?> aria-describedby="desc-permitir_escaneo_ajax" role="switch" />
                        <span class="accessitrans-slider round"></span>
                    </label>
                    <span style="display: inline-block; margin-left: 10px; vertical-align: middle;"><?php esc_html_e('Permitir escaneo de nuevas cadenas', 'accessitrans-aria'); ?></span>
                    <p class="description" id="desc-permitir_escaneo_ajax"><?php esc_html_e('Activa la captura de nuevas cadenas ARIA. Desactívalo después de capturar todas las cadenas para mejorar el rendimiento.', 'accessitrans-aria'); ?></p>
                    
                    <div class="accessitrans-notice">
                        <strong><?php esc_html_e('Consejo de rendimiento:', 'accessitrans-aria'); ?></strong>
                        <?php esc_html_e('Una vez hayas capturado todas las cadenas ARIA de tu sitio, puedes desactivar el escaneo para mejorar el rendimiento. Las traducciones seguirán funcionando.', 'accessitrans-aria'); ?>
                    </div>
                    
                    <div id="switch-status" aria-live="polite" style="margin-top: 10px;"></div>
                </div>
            </section>
            
            <!-- Formulario principal -->
            <section class="card" aria-labelledby="configuracion-detallada-titulo">
                <h2 id="configuracion-detallada-titulo"><?php esc_html_e('Configuración detallada', 'accessitrans-aria'); ?></h2>
                <form method="post" action="" id="accessitrans-settings-form">
                    <?php wp_nonce_field('accessitrans_aria_settings'); ?>
                    
                    <!-- Campo oculto para mantener el valor actual del permitir_escaneo -->
                    <input type="hidden" id="hidden_permitir_escaneo" name="hidden_permitir_escaneo" value="<?php echo isset($this->core->options['permitir_escaneo']) && $this->core->options['permitir_escaneo'] ? '1' : '0'; ?>" />
                    
                    <fieldset class="accessitrans-methods-fieldset">
                        <legend><?php esc_html_e('Métodos de captura', 'accessitrans-aria'); ?></legend>
                        
                        <p class="description"><?php esc_html_e('Configura los métodos de captura de atributos ARIA. Puedes activar varios métodos simultáneamente para una detección más robusta.', 'accessitrans-aria'); ?></p>
                        
                        <?php 
                        // Renderizar los campos de métodos de captura
                        $capture_fields = [
                            'captura_total' => esc_html__('Captura todo el HTML de la página. Altamente efectivo pero puede afectar al rendimiento.', 'accessitrans-aria'),
                            'captura_elementor' => esc_html__('Procesa el contenido generado por Elementor.', 'accessitrans-aria'),
                            'procesar_templates' => esc_html__('Procesa los datos de templates de Elementor.', 'accessitrans-aria'),
                            'procesar_elementos' => esc_html__('Procesa cada widget y elemento de Elementor individualmente.', 'accessitrans-aria')
                        ];
                        
                        foreach ($capture_fields as $field => $desc) {
                            $args = [
                                'label_for' => $field,
                                'descripcion' => $desc
                            ];
                            $this->checkbox_callback($args);
                        }
                        ?>
                    </fieldset>
                    
                    <fieldset>
                        <legend><?php esc_html_e('Configuración avanzada', 'accessitrans-aria'); ?></legend>
                        
                        <p class="description"><?php esc_html_e('Configuración avanzada para rendimiento y depuración.', 'accessitrans-aria'); ?></p>
                        
                        <?php 
                        // Renderizar los campos avanzados
                        $advanced_fields = [
                            'modo_debug' => esc_html__('Activa el registro detallado de eventos. Se almacena en uploads/accessitrans-logs.', 'accessitrans-aria'),
                            'solo_admin' => esc_html__('Solo procesa la captura total cuando un administrador está conectado.', 'accessitrans-aria'),
                            'captura_en_idioma_principal' => esc_html__('Solo captura cadenas cuando se navega en el idioma principal. Previene duplicados.', 'accessitrans-aria')
                        ];
                        
                        foreach ($advanced_fields as $field => $desc) {
                            $args = [
                                'label_for' => $field,
                                'descripcion' => $desc
                            ];
                            $this->checkbox_callback($args);
                        }
                        ?>
                    </fieldset>
                    
                    <?php submit_button(); ?>
                </form>
            </section>
            
            <section class="card" aria-labelledby="herramientas-titulo">
                <h2 id="herramientas-titulo"><?php esc_html_e('Herramientas de mantenimiento', 'accessitrans-aria'); ?></h2>
                
                <div class="tool-section">
                    <h3 id="actualizacion-titulo"><?php esc_html_e('Forzar actualización de traducciones', 'accessitrans-aria'); ?></h3>
                    <p><?php esc_html_e('Si encuentras problemas con las traducciones, puedes intentar forzar una actualización que limpiará las cachés internas y renovará el estado del plugin.', 'accessitrans-aria'); ?></p>
                    <button id="accessitrans-force-refresh" class="button button-secondary" aria-describedby="actualizacion-descripcion">
                        <?php esc_html_e('Forzar actualización', 'accessitrans-aria'); ?>
                    </button>
                    <span id="refresh-status" aria-live="polite"></span>
                    <p id="actualizacion-descripcion" class="description">
                        <?php esc_html_e('Esta operación limpia todas las cachés de WordPress y WPML y puede resolver problemas donde las traducciones están registradas pero no se muestran correctamente.', 'accessitrans-aria'); ?>
                    </p>
                </div>
                
                <div class="tool-section">
                    <h3 id="diagnostico-titulo"><?php esc_html_e('Diagnóstico de traducciones', 'accessitrans-aria'); ?></h3>
                    <p><?php esc_html_e('Si una cadena específica no se traduce correctamente, puedes diagnosticar el problema aquí:', 'accessitrans-aria'); ?></p>
                    
                    <form class="diagnostics-form" onsubmit="runDiagnostic(event)">
                        <label for="string-to-check"><?php esc_html_e('Cadena a verificar:', 'accessitrans-aria'); ?></label>
                        <input type="text" id="string-to-check" class="regular-text" placeholder="<?php esc_attr_e('Ejemplo: Next', 'accessitrans-aria'); ?>" />
                        <button id="accessitrans-diagnostic" type="submit" class="button button-secondary" aria-describedby="diagnostico-descripcion">
                            <?php esc_html_e('Ejecutar diagnóstico', 'accessitrans-aria'); ?>
                        </button>
                        <div role="status" id="diagnostico-proceso" class="screen-reader-text" aria-live="polite"></div>
                    </form>
                    
                    <div id="diagnostic-results" class="diagnostic-results" aria-live="polite"></div>
                    
                    <p id="diagnostico-descripcion" class="description">
                        <?php esc_html_e('Esta herramienta verifica si una cadena está correctamente registrada para traducción y si tiene traducciones asignadas.', 'accessitrans-aria'); ?>
                    </p>
                </div>
                
                <div class="tool-section">
                    <h3 id="salud-titulo"><?php esc_html_e('Verificar salud del sistema', 'accessitrans-aria'); ?></h3>
                    <p><?php esc_html_e('Comprueba el estado general de las traducciones y la configuración del plugin:', 'accessitrans-aria'); ?></p>
                    
                    <button id="accessitrans-check-health" class="button button-secondary" aria-describedby="salud-descripcion">
                        <?php esc_html_e('Verificar salud', 'accessitrans-aria'); ?>
                    </button>
                    <span class="screen-reader-text" id="salud-proceso" aria-live="polite"></span>
                    
                    <div id="health-results" class="health-results" aria-live="polite"></div>
                    
                    <p id="salud-descripcion" class="description">
                        <?php esc_html_e('Esta herramienta verifica la configuración general del sistema y muestra estadísticas sobre las traducciones registradas.', 'accessitrans-aria'); ?>
                    </p>
                </div>
            </section>
            
            <section class="card" aria-labelledby="instrucciones-uso-titulo">
                <h2 id="instrucciones-uso-titulo"><?php esc_html_e('Instrucciones de uso', 'accessitrans-aria'); ?></h2>
                <p><?php esc_html_e('Para agregar atributos ARIA en Elementor:', 'accessitrans-aria'); ?></p>
                <ol>
                    <li><?php esc_html_e('Edita cualquier elemento en Elementor', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Ve a la pestaña "Avanzado"', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Encuentra la sección "Atributos personalizados"', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Añade los atributos ARIA que desees traducir (ej: aria-label|Texto a traducir)', 'accessitrans-aria'); ?></li>
                </ol>
                <p><?php esc_html_e('Para traducir los atributos:', 'accessitrans-aria'); ?></p>
                <ol>
                    <li><?php esc_html_e('Ve a WPML → Traducción de cadenas', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Filtra por el contexto "AccessiTrans ARIA Attributes"', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Traduce las cadenas como lo harías normalmente en WPML', 'accessitrans-aria'); ?></li>
                </ol>
                <p><?php esc_html_e('Prácticas recomendadas:', 'accessitrans-aria'); ?></p>
                <ul>
                    <li><?php esc_html_e('Navega por el sitio en el idioma principal mientras capturas cadenas para evitar duplicados', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Si una traducción no aparece, utiliza la herramienta "Forzar actualización" o el diagnóstico', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Una vez capturadas todas las cadenas, puedes desactivar el escaneo para mejorar el rendimiento', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Si cambias un texto en el idioma original, deberás traducirlo nuevamente en WPML', 'accessitrans-aria'); ?></li>
                </ul>
            </section>
            
            <section class="card" aria-labelledby="acerca-autor-titulo">
                <h2 id="acerca-autor-titulo"><?php esc_html_e('Acerca del autor', 'accessitrans-aria'); ?></h2>
                <p><?php esc_html_e('Desarrollado por', 'accessitrans-aria'); ?> Mario Germán Almonte Moreno:</p>
                <ul>
                    <li><?php esc_html_e('Miembro de IAAP (International Association of Accessibility Professionals)', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Certificado CPWA (CPACC y WAS)', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Profesor en el Curso de especialización en Accesibilidad Digital (Universidad de Lleida)', 'accessitrans-aria'); ?></li>
                </ul>
                <h3><?php esc_html_e('Servicios Profesionales:', 'accessitrans-aria'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Formación y consultoría en Accesibilidad Web y eLearning', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Auditorías de accesibilidad web según EN 301 549 (WCAG 2.2, ATAG 2.0)', 'accessitrans-aria'); ?></li>
                </ul>
                <p><a href="https://www.linkedin.com/in/marioalmonte/" target="_blank"><?php esc_html_e('Visita mi perfil de LinkedIn', 'accessitrans-aria'); ?></a></p>
                <p><a href="https://aprendizajeenred.es" target="_blank"><?php esc_html_e('Sitio web y blog', 'accessitrans-aria'); ?></a></p>
            </section>
        </div>
        <?php
    }
    
    /**
     * Añade enlaces a la página de configuración en la lista de plugins
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=accessitrans-aria')) . '">' . esc_html__('Settings', 'accessitrans-aria') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}