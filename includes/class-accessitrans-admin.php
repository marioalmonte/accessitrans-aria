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
            __('Settings for', 'accessitrans-aria') . ' AccessiTrans',
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
                    'saving' => __('Saving...', 'accessitrans-aria'),
                    'saveError' => __('Error saving settings.', 'accessitrans-aria'),
                    'processing' => __('Processing...', 'accessitrans-aria'),
                    'requestError' => __('Error processing request.', 'accessitrans-aria'),
                    'noString' => __('Please enter a string to verify.', 'accessitrans-aria'),
                    'analyzing' => __('Analyzing...', 'accessitrans-aria'),
                    'analyzingMessage' => __('Analyzing string...', 'accessitrans-aria'),
                    'errorNoString' => __('Error: No string has been entered to verify.', 'accessitrans-aria'),
                    'scanEnabledMessage' => __('Scanning enabled. Capture methods are available.', 'accessitrans-aria'),
                    'scanDisabledMessage' => __('Scanning disabled. Capture methods are disabled.', 'accessitrans-aria'),
                    'diagnosticResults' => __('Diagnostic results:', 'accessitrans-aria'),
                    'originalText' => __('Original text:', 'accessitrans-aria'),
                    'language' => __('Language:', 'accessitrans-aria'),
                    'languageCorrect' => __('✓ Correct language', 'accessitrans-aria'),
                    'languageNotPrimary' => __('✗ Not primary language', 'accessitrans-aria'),
                    
                    // Añadir todas las cadenas que faltan
                    'foundInWPML' => __('✓ Found in WPML', 'accessitrans-aria'),
                    'notFoundInWPML' => __('✗ Not found in WPML', 'accessitrans-aria'),
                    'registeredFormats' => __('Registered formats:', 'accessitrans-aria'),
                    'hasTranslations' => __('✓ Has translations', 'accessitrans-aria'),
                    'noTranslations' => __('✗ No translations', 'accessitrans-aria'),
                    'availableTranslations' => __('Available translations:', 'accessitrans-aria'),
                    'noCurrentLanguageTranslation' => __('✗ No translation for current language', 'accessitrans-aria'),
                    'recommendedActionTranslate' => __('Recommended action: Translate this string to the current language in WPML → String Translation.', 'accessitrans-aria'),
                    'translateAction' => __('Recommended action: Translate this string in WPML → String Translation.', 'accessitrans-aria'),
                    'notPrimaryLanguageWarning' => __('⚠️ You are browsing in a language that is not the primary one. Switch to the primary language to register strings.', 'accessitrans-aria'),
                    'recommendedActionNavigate' => __('Recommended action: Browse your site with capture methods enabled or edit the element in Elementor to register this string.', 'accessitrans-aria'),
                    'troubleshootingTips' => __('Troubleshooting tips:', 'accessitrans-aria'),
                    'tip1' => __('Make sure to browse in the site\'s primary language when capturing strings.', 'accessitrans-aria'),
                    'tip2' => __('Try using "Force Update" to clear all caches.', 'accessitrans-aria'),
                    'tip3' => __('If you\'ve changed the text in the original language, you\'ll need to translate it again in WPML.', 'accessitrans-aria'),
                    'technicalInfo' => __('Advanced technical information', 'accessitrans-aria'),
                    'analysisComplete' => __('Analysis completed. Results were found for the string', 'accessitrans-aria'),
                    'analysisError' => __('Error in analysis:', 'accessitrans-aria'),
                    'connectionError' => __('Error performing the analysis. Could not contact the server.', 'accessitrans-aria'),
                    
                    // Cadenas para verificación de salud
                    'verifying' => __('Verifying...', 'accessitrans-aria'),
                    'verifyingStatus' => __('Verifying system status...', 'accessitrans-aria'),
                    'systemStatus' => __('System status:', 'accessitrans-aria'),
                    'active' => __('Active', 'accessitrans-aria'),
                    'inactive' => __('Inactive', 'accessitrans-aria'),
                    'wpml' => __('WPML:', 'accessitrans-aria'),
                    'elementor' => __('Elementor:', 'accessitrans-aria'),
                    'registeredStrings' => __('Registered strings:', 'accessitrans-aria'),
                    'primaryLanguage' => __('Primary language:', 'accessitrans-aria'),
                    'currentLanguage' => __('Current language:', 'accessitrans-aria'),
                    'availableLanguages' => __('Available languages:', 'accessitrans-aria'),
                    'currentConfiguration' => __('Current configuration:', 'accessitrans-aria'),
                    'recommendations' => __('Recommendations:', 'accessitrans-aria'),
                    'noStringsRegistered' => __('• No registered strings. Browse your site with capture methods enabled.', 'accessitrans-aria'),
                    'stringsNoTranslations' => __('• There are registered strings but no translations. Visit WPML → String Translation to translate them.', 'accessitrans-aria'),
                    'navigatingNonPrimary' => __('• You are browsing in a language that is not the primary one. If you want to register new strings, switch to the primary language.', 'accessitrans-aria'),
                    'serverDate' => __('Server date:', 'accessitrans-aria'),
                    'pluginVersion' => __('Plugin version:', 'accessitrans-aria'),
                    'verificationComplete' => __('Verification completed. Found', 'accessitrans-aria'),
                    'registeredStringsText' => __('registered strings and', 'accessitrans-aria'),
                    'translationsText' => __('translations.', 'accessitrans-aria'),
                    'verificationError' => __('Error in verification:', 'accessitrans-aria'),
                    'connectionVerificationError' => __('Error performing the verification. Could not contact the server.', 'accessitrans-aria')
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
            __('General settings', 'accessitrans-aria'),
            array($this, 'section_general_callback'),
            'accessitrans-aria'
        );
        
        // Nuevo campo para el interruptor principal de escaneo
        add_settings_field(
            'permitir_escaneo',
            __('Allow scanning for new strings', 'accessitrans-aria'),
            [$this, 'toggle_callback'],
            'accessitrans-aria',
            'accessitrans_aria_general',
            [
                'label_for' => 'permitir_escaneo', 
                'descripcion' => __('Activates the capture of new ARIA strings. Disable it after capturing all strings to improve performance.', 'accessitrans-aria')
            ]
        );
        
        add_settings_section(
            'accessitrans_aria_main',
            __('Capture methods settings', 'accessitrans-aria'),
            [$this, 'section_callback'],
            'accessitrans-aria'
        );
        
        add_settings_field(
            'captura_total',
            __('Full HTML capture', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_main',
            ['label_for' => 'captura_total', 'descripcion' => __('Captures all HTML of the page. Highly effective but may affect performance.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'captura_elementor',
            __('Elementor content filter', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_main',
            ['label_for' => 'captura_elementor', 'descripcion' => __('Processes content generated by Elementor.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'procesar_templates',
            __('Process Elementor templates', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_main',
            ['label_for' => 'procesar_templates', 'descripcion' => __('Processes Elementor template data.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'procesar_elementos',
            __('Process individual elements', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_main',
            ['label_for' => 'procesar_elementos', 'descripcion' => __('Processes each Elementor widget and element individually.', 'accessitrans-aria')]
        );
        
        add_settings_section(
            'accessitrans_aria_advanced',
            __('Advanced settings', 'accessitrans-aria'),
            [$this, 'section_advanced_callback'],
            'accessitrans-aria'
        );
        
        add_settings_field(
            'modo_debug',
            __('Debug mode', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_advanced',
            ['label_for' => 'modo_debug', 'descripcion' => __('Enables detailed event logging. Stored in uploads/accessitrans-logs.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'solo_admin',
            __('Capture for admins only', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_advanced',
            ['label_for' => 'solo_admin', 'descripcion' => __('Only processes full capture when an admin is logged in.', 'accessitrans-aria')]
        );
        
        add_settings_field(
            'captura_en_idioma_principal',
            __('Capture in main language only', 'accessitrans-aria'),
            [$this, 'checkbox_callback'],
            'accessitrans-aria',
            'accessitrans_aria_advanced',
            ['label_for' => 'captura_en_idioma_principal', 'descripcion' => __('Only captures strings when browsing in the default language. Prevents duplicates.', 'accessitrans-aria')]
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
            wp_send_json_error(__('You don\'t have permission to perform this action.', 'accessitrans-aria'));
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
            wp_send_json_success(__('String scanning enabled. Changes have been saved.', 'accessitrans-aria'));
        } else {
            wp_send_json_success(__('String scanning disabled. Changes have been saved.', 'accessitrans-aria'));
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
            wp_send_json_error(esc_html__('You don\'t have permission to perform this action.', 'accessitrans-aria'));
            return;
        }
        
        // Obtener la cadena a verificar - sanitizar y deseslashear
        $string = isset($_POST['string']) ? sanitize_text_field(wp_unslash($_POST['string'])) : '';
        if (empty($string)) {
            wp_send_json_error(esc_html__('No string was provided for verification.', 'accessitrans-aria'));
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
            wp_send_json_error(esc_html__('You don\'t have permission to perform this action.', 'accessitrans-aria'));
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
        
        wp_send_json_success(esc_html__('Translation cache successfully updated.', 'accessitrans-aria'));
    }
    
    /**
     * Callback para verificación de salud del sistema
     */
    public function check_health_callback() {
        // Verificar nonce
        check_ajax_referer('accessitrans-check-health', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('You don\'t have permission to perform this action.', 'accessitrans-aria'));
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
        echo '<p>' . esc_html__('General plugin settings.', 'accessitrans-aria') . '</p>';
    }
    
    /**
     * Callback para la sección principal de ajustes
     */
    public function section_callback() {
        echo '<p>' . esc_html__('Configure ARIA attribute capture methods. You can activate multiple methods simultaneously for more robust detection.', 'accessitrans-aria') . '</p>';
    }
    
    /**
     * Callback para la sección avanzada
     */
    public function section_advanced_callback() {
        echo '<p>' . esc_html__('Advanced settings for performance and debugging.', 'accessitrans-aria') . '</p>';
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
        echo '<strong>' . esc_html__('Performance tip:', 'accessitrans-aria') . '</strong>';
        echo esc_html__('Once you\'ve captured all ARIA strings from your site, you can disable scanning to improve performance. Translations will continue to work.', 'accessitrans-aria');
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
            echo '<div class="notice notice-success is-dismissible" role="alert" aria-live="polite"><p>' . esc_html__('Settings saved successfully.', 'accessitrans-aria') . '</p></div>';
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
        echo '<div id="scan-disabled-message" class="screen-reader-text">' . esc_html__('Global scanning is disabled. These controls are not available.', 'accessitrans-aria') . '</div>';
        
        // Mostrar formulario de ajustes con estructura semántica mejorada
        ?>
        <div class="wrap accessitrans-admin-container">
            <h1><?php echo esc_html(__('Settings for', 'accessitrans-aria') . ' AccessiTrans'); ?></h1>
            
            <p class="plugin-description"><?php esc_html_e('This plugin allows you to translate ARIA attributes in sites developed with Elementor and WPML.', 'accessitrans-aria'); ?></p>
            
            <div class="notice notice-info" role="region" aria-label="<?php esc_attr_e('Current status', 'accessitrans-aria'); ?>">
                <p>
                    <strong><?php esc_html_e('Current status:', 'accessitrans-aria'); ?></strong>
                    <?php 
                    printf(
                    /* translators: %d: number of registered strings */
                        esc_html__('Registered strings: %d', 'accessitrans-aria'),
                        esc_html($strings_count)
                    ); 
                    ?>
                </p>
            </div>
            
            <!-- Activación general (independiente) con AJAX -->
            <section class="card" aria-labelledby="activacion-general-titulo">
                <h2 id="activacion-general-titulo"><?php esc_html_e('General activation', 'accessitrans-aria'); ?></h2>
                
                <div class="accessitrans-field">
                    <label class="accessitrans-switch" for="permitir_escaneo_ajax">
                        <input type="checkbox" id="permitir_escaneo_ajax" <?php echo isset($this->core->options['permitir_escaneo']) && $this->core->options['permitir_escaneo'] ? 'checked' : ''; ?> aria-describedby="desc-permitir_escaneo_ajax" role="switch" />
                        <span class="accessitrans-slider round"></span>
                    </label>
                    <span style="display: inline-block; margin-left: 10px; vertical-align: middle;"><?php esc_html_e('Allow scanning for new strings', 'accessitrans-aria'); ?></span>
                    <p class="description" id="desc-permitir_escaneo_ajax"><?php esc_html_e('Activates the capture of new ARIA strings. Disable it after capturing all strings to improve performance.', 'accessitrans-aria'); ?></p>
                    
                    <div class="accessitrans-notice">
                        <strong><?php esc_html_e('Performance tip:', 'accessitrans-aria'); ?></strong>
                        <?php esc_html_e('Once you\'ve captured all ARIA strings from your site, you can disable scanning to improve performance. Translations will continue to work.', 'accessitrans-aria'); ?>
                    </div>
                    
                    <div id="switch-status" aria-live="polite" style="margin-top: 10px;"></div>
                </div>
            </section>
            
            <!-- Formulario principal -->
            <section class="card" aria-labelledby="configuracion-detallada-titulo">
                <h2 id="configuracion-detallada-titulo"><?php esc_html_e('Detailed configuration', 'accessitrans-aria'); ?></h2>
                <form method="post" action="" id="accessitrans-settings-form">
                    <?php wp_nonce_field('accessitrans_aria_settings'); ?>
                    
                    <!-- Campo oculto para mantener el valor actual del permitir_escaneo -->
                    <input type="hidden" id="hidden_permitir_escaneo" name="hidden_permitir_escaneo" value="<?php echo isset($this->core->options['permitir_escaneo']) && $this->core->options['permitir_escaneo'] ? '1' : '0'; ?>" />
                    
                    <fieldset class="accessitrans-methods-fieldset">
                        <legend><?php esc_html_e('Capture methods', 'accessitrans-aria'); ?></legend>
                        
                        <p class="description"><?php esc_html_e('Configure ARIA attribute capture methods. You can activate multiple methods simultaneously for more robust detection.', 'accessitrans-aria'); ?></p>
                        
                        <?php 
                        // Renderizar los campos de métodos de captura
                        $capture_fields = [
                            'captura_total' => esc_html__('Captures all HTML of the page. Highly effective but may affect performance.', 'accessitrans-aria'),
                            'captura_elementor' => esc_html__('Processes content generated by Elementor.', 'accessitrans-aria'),
                            'procesar_templates' => esc_html__('Processes Elementor template data.', 'accessitrans-aria'),
                            'procesar_elementos' => esc_html__('Processes each Elementor widget and element individually.', 'accessitrans-aria')
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
                        <legend><?php esc_html_e('Advanced settings', 'accessitrans-aria'); ?></legend>
                        
                        <p class="description"><?php esc_html_e('Advanced settings for performance and debugging.', 'accessitrans-aria'); ?></p>
                        
                        <?php 
                        // Renderizar los campos avanzados
                        $advanced_fields = [
                            'modo_debug' => esc_html__('Enables detailed event logging. Stored in uploads/accessitrans-logs.', 'accessitrans-aria'),
                            'solo_admin' => esc_html__('Only processes full capture when an admin is logged in.', 'accessitrans-aria'),
                            'captura_en_idioma_principal' => esc_html__('Only captures strings when browsing in the default language. Prevents duplicates.', 'accessitrans-aria')
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
                <h2 id="herramientas-titulo"><?php esc_html_e('Maintenance tools', 'accessitrans-aria'); ?></h2>
                
                <div class="tool-section">
                    <h3 id="actualizacion-titulo"><?php esc_html_e('Force translation update', 'accessitrans-aria'); ?></h3>
                    <p><?php esc_html_e('If you encounter issues with translations, you can try forcing an update that will clear internal caches and renew the plugin state.', 'accessitrans-aria'); ?></p>
                    <button id="accessitrans-force-refresh" class="button button-secondary" aria-describedby="actualizacion-descripcion">
                        <?php esc_html_e('Force update', 'accessitrans-aria'); ?>
                    </button>
                    <span id="refresh-status" aria-live="polite"></span>
                    <p id="actualizacion-descripcion" class="description">
                        <?php esc_html_e('This operation clears all WordPress and WPML caches and can resolve issues where translations are registered but not displaying correctly.', 'accessitrans-aria'); ?>
                    </p>
                </div>
                
                <div class="tool-section">
                    <h3 id="diagnostico-titulo"><?php esc_html_e('Translation diagnostics', 'accessitrans-aria'); ?></h3>
                    <p><?php esc_html_e('If a specific string is not translating correctly, you can diagnose the problem here:', 'accessitrans-aria'); ?></p>
                    
                    <form class="diagnostics-form" onsubmit="runDiagnostic(event)">
                        <label for="string-to-check"><?php esc_html_e('String to check:', 'accessitrans-aria'); ?></label>
                        <input type="text" id="string-to-check" class="regular-text" placeholder="<?php esc_attr_e('Example: Next', 'accessitrans-aria'); ?>" />
                        <button id="accessitrans-diagnostic" type="submit" class="button button-secondary" aria-describedby="diagnostico-descripcion">
                            <?php esc_html_e('Run diagnostic', 'accessitrans-aria'); ?>
                        </button>
                        <div role="status" id="diagnostico-proceso" class="screen-reader-text" aria-live="polite"></div>
                    </form>
                    
                    <div id="diagnostic-results" class="diagnostic-results" aria-live="polite"></div>
                    
                    <p id="diagnostico-descripcion" class="description">
                        <?php esc_html_e('This tool verifies if a string is correctly registered for translation and if it has assigned translations.', 'accessitrans-aria'); ?>
                    </p>
                </div>
                
                <div class="tool-section">
                    <h3 id="salud-titulo"><?php esc_html_e('Check system health', 'accessitrans-aria'); ?></h3>
                    <p><?php esc_html_e('Check the general status of translations and plugin configuration:', 'accessitrans-aria'); ?></p>
                    
                    <button id="accessitrans-check-health" class="button button-secondary" aria-describedby="salud-descripcion">
                        <?php esc_html_e('Check health', 'accessitrans-aria'); ?>
                    </button>
                    <span class="screen-reader-text" id="salud-proceso" aria-live="polite"></span>
                    
                    <div id="health-results" class="health-results" aria-live="polite"></div>
                    
                    <p id="salud-descripcion" class="description">
                        <?php esc_html_e('This tool verifies the general system configuration and displays statistics about registered translations.', 'accessitrans-aria'); ?>
                    </p>
                </div>
            </section>
            
            <section class="card" aria-labelledby="instrucciones-uso-titulo">
                <h2 id="instrucciones-uso-titulo"><?php esc_html_e('Usage instructions', 'accessitrans-aria'); ?></h2>
                <p><?php esc_html_e('To add ARIA attributes in Elementor:', 'accessitrans-aria'); ?></p>
                <ol>
                    <li><?php esc_html_e('Edit any element in Elementor', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Go to the "Advanced" tab', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Find the "Custom Attributes" section', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Add the ARIA attributes you want to translate (e.g., aria-label|Text to translate)', 'accessitrans-aria'); ?></li>
                </ol>
                <p><?php esc_html_e('To translate the attributes:', 'accessitrans-aria'); ?></p>
                <ol>
                    <li><?php esc_html_e('Go to WPML → String Translation', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Filter by the "AccessiTrans ARIA Attributes" context', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Translate the strings as you would normally in WPML', 'accessitrans-aria'); ?></li>
                </ol>
                <p><?php esc_html_e('Best practices:', 'accessitrans-aria'); ?></p>
                <ul>
                    <li><?php esc_html_e('Browse the site in the primary language while capturing strings to avoid duplicates', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('If a translation doesn\'t appear, use the "Force Update" tool or the diagnostic', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Once all strings are captured, you can disable scanning to improve performance', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('If you change text in the original language, you\'ll need to translate it again in WPML', 'accessitrans-aria'); ?></li>
                </ul>
            </section>
            
            <section class="card" aria-labelledby="acerca-autor-titulo">
                <h2 id="acerca-autor-titulo"><?php esc_html_e('About the author', 'accessitrans-aria'); ?></h2>
                <p><?php esc_html_e('Developed by', 'accessitrans-aria'); ?> Mario Germán Almonte Moreno:</p>
                <ul>
                    <li><?php esc_html_e('Member of IAAP (International Association of Accessibility Professionals)', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('CPWA Certified (CPACC and WAS)', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Professor in the Digital Accessibility Specialization Course (University of Lleida)', 'accessitrans-aria'); ?></li>
                </ul>
                <h3><?php esc_html_e('Professional services:', 'accessitrans-aria'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Web Accessibility and eLearning Training and Consulting', 'accessitrans-aria'); ?></li>
                    <li><?php esc_html_e('Web accessibility audits according to EN 301 549 (WCAG 2.2, ATAG 2.0)', 'accessitrans-aria'); ?></li>
                </ul>
                <p><a href="https://www.linkedin.com/in/marioalmonte/" target="_blank"><?php esc_html_e('Visit my LinkedIn profile', 'accessitrans-aria'); ?></a></p>
                <p><a href="https://aprendizajeenred.es" target="_blank"><?php esc_html_e('Website and blog', 'accessitrans-aria'); ?></a></p>
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