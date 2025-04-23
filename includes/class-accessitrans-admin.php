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
        add_action('after_plugin_row', [$this, 'after_plugin_row'], 10, 3);
        
        // Agregar estilos para la interfaz de administración
        add_action('admin_head-settings_page_accessitrans-aria', [$this, 'add_admin_styles']);
        
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
     * Sanitiza las opciones del plugin
     * 
     * @param array $input Las opciones a sanitizar
     * @return array Las opciones sanitizadas
     */
    public function sanitize_plugin_options($input) {
        // Crear array para las opciones sanitizadas
        $sanitized_options = [];
        
        // Lista de opciones esperadas tipo checkbox
        $checkbox_options = [
            'captura_total',
            'captura_elementor',
            'procesar_templates',
            'procesar_elementos',
            'modo_debug',
            'solo_admin',
            'captura_en_idioma_principal',
            'permitir_escaneo'
        ];
        
        // Sanitizar checkbox options (true/false)
        foreach ($checkbox_options as $option) {
            $sanitized_options[$option] = isset($input[$option]) ? (bool)$input[$option] : false;
        }
        
        return $sanitized_options;
    }
    
    /**
     * Registra los ajustes del plugin
     */
    public function register_settings() {
        register_setting(
            'accessitrans_aria',
            'accessitrans_aria_options',
            [
                'sanitize_callback' => [$this, 'sanitize_plugin_options'],
                'default' => [
                    'captura_total' => true,
                    'captura_elementor' => true,
                    'procesar_templates' => true,
                    'procesar_elementos' => true,
                    'modo_debug' => false,
                    'solo_admin' => true,
                    'captura_en_idioma_principal' => true,
                    'permitir_escaneo' => true
                ]
            ]
        );
        
        // Esta sección no se usa directamente en el render, está configurada 
        // solo para mantener compatibilidad con la API de WordPress
        
        // Nueva sección general para el interruptor principal
        add_settings_section(
            'accessitrans_aria_general',
            __('Configuración general', 'accessitrans-aria'),
            [$this, 'section_general_callback'],
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
        
        $enabled = isset($_POST['enabled']) && (int)$_POST['enabled'] === 1;
        
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
     * Agrega estilos CSS para la interfaz de administración
     */
    public function add_admin_styles() {
        ?>
        <style>
            /* Estilos generales */
            .accessitrans-admin-container {
                max-width: 800px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            
            .accessitrans-admin-container .card {
                background-color: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                width: 100% !important;
                box-sizing: border-box !important;
            }
            
            /* Estilos para fieldsets y leyendas */
            .accessitrans-admin-container fieldset {
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
                background-color: #fff;
                width: 100% !important;
                box-sizing: border-box !important;
                min-width: 0 !important; /* Evitar que se desborde */
            }
            
            .accessitrans-admin-container legend {
                background-color: #fff;
                padding: 0 10px;
                font-weight: 600;
                font-size: 14px;
            }
            
            /* Estilo para campos de formulario */
            .accessitrans-field {
                margin-bottom: 12px;
                padding: 8px 0;
                width: 100%;
            }
            
            .accessitrans-field.indent {
                margin-left: 20px;
                position: relative;
            }
            
            .accessitrans-field.indent::before {
                content: "";
                position: absolute;
                left: -12px;
                top: 0;
                height: 100%;
                border-left: 2px solid #ddd;
            }
            
            .accessitrans-field label {
                display: inline-block;
                margin-left: 8px;
                vertical-align: middle;
            }
            
            /* Estilos para campos desactivados */
            .accessitrans-field.disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            .accessitrans-field.disabled input,
            .accessitrans-field.disabled select,
            .accessitrans-field.disabled textarea {
                pointer-events: none;
            }
            
            /* Estilos para el interruptor tipo toggle */
            .accessitrans-switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
                vertical-align: middle;
            }
            
            .accessitrans-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .accessitrans-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
            }
            
            .accessitrans-slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
            }
            
            input:checked + .accessitrans-slider {
                background-color: #2196F3;
            }
            
            input:focus + .accessitrans-slider {
                box-shadow: 0 0 3px #2196F3;
                outline: 2px solid #2196F3;
            }
            
            input:checked + .accessitrans-slider:before {
                transform: translateX(26px);
            }
            
            .accessitrans-slider.round {
                border-radius: 34px;
            }
            
            .accessitrans-slider.round:before {
                border-radius: 50%;
            }
            
            /* Estilos para status en tools */
            .tool-section {
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #eee;
                width: 100%;
            }
            
            .tool-section:last-child {
                border-bottom: none;
            }
            
            /* Estilo específico para el diagnóstico para mantener el tamaño limitado del campo */
            .diagnostics-form {
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                flex-wrap: nowrap;
                gap: 10px;
                width: 80%;
            }
            
            .diagnostics-form label {
                flex: 0 0 auto;
                white-space: nowrap;
            }
            
            .diagnostics-form input[type="text"] {
                flex: 1 1 auto;
                min-width: 200px;
            }
            
            .diagnostics-form button {
                flex: 0 0 auto;
            }
            
            /* En pantallas pequeñas, permitir el wrapping */
            @media (max-width: 782px) {
                .diagnostics-form {
                    flex-wrap: wrap;
                }
                
                .diagnostics-form input[type="text"] {
                    flex: 1 1 100%;
                }
            }
            
            .diagnostic-results, 
            .health-results {
                margin-top: 15px;
                padding: 10px;
                background: #f8f8f8;
                border: 1px solid #ddd;
                border-radius: 4px;
                max-height: 300px;
                overflow-y: auto;
                display: none;
                width: 100%;
                box-sizing: border-box;
            }
            
            .diagnostic-results.active, 
            .health-results.active {
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
            
            /* Estilos para avisos informativos */
            .accessitrans-notice {
                background-color: #f0f6fc;
                border-left: 4px solid #72aee6;
                padding: 10px 12px;
                margin: 10px 0;
                border-radius: 2px;
                width: 100%;
                box-sizing: border-box;
            }
            
            .accessitrans-notice strong {
                display: block;
                margin-bottom: 5px;
            }
            
            /* Estilos específicos para WordPress admin */
            .wrap.accessitrans-admin-container {
                margin-right: 0 !important;
                margin-left: 0 !important;
            }
            
            /* Forzar ancho completo para todos los elementos internos */
            .accessitrans-admin-container * {
                max-width: 100% !important;
            }
        </style>
        <?php
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
        
        // Agregar JavaScript para controlar los campos dependientes
        echo '<script>
            jQuery(document).ready(function($) {
                // Función para actualizar estados de campos dependientes
                function updateDependentFields() {
                    var enabled = $("#' . esc_attr($option_name) . '").is(":checked");
                    
                    // Obtener todos los campos de métodos de captura
                    $(".accessitrans-methods-fieldset input[type=checkbox]").prop("disabled", !enabled);
                    $(".accessitrans-methods-fieldset .accessitrans-field").toggleClass("disabled", !enabled);
                    
                    // Actualizar atributos ARIA
                    if (!enabled) {
                        $(".accessitrans-methods-fieldset input[type=checkbox]").attr("aria-disabled", "true");
                        $(".accessitrans-methods-fieldset").attr("aria-describedby", "scan-disabled-message");
                    } else {
                        $(".accessitrans-methods-fieldset input[type=checkbox]").removeAttr("aria-disabled");
                        $(".accessitrans-methods-fieldset").removeAttr("aria-describedby");
                    }
                    
                    // Anunciar cambio para lectores de pantalla
                    if (window.accessitrans_announce) {
                        clearTimeout(window.accessitrans_announce);
                    }
                    
                    window.accessitrans_announce = setTimeout(function() {
                        var message = enabled ? 
                            "' . esc_js(__('Escaneo activado. Los métodos de captura están disponibles.', 'accessitrans-aria')) . '" : 
                            "' . esc_js(__('Escaneo desactivado. Los métodos de captura están deshabilitados.', 'accessitrans-aria')) . '";
                            
                        $("#accessitrans-aria-live").text(message);
                    }, 100);
                }
                
                // Inicializar
                updateDependentFields();
                
                // Actualizar cuando cambie
                $("#' . esc_attr($option_name) . '").on("change", updateDependentFields);
            });
        </script>';
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
            check_admin_referer('accessitrans_aria_settings');
            
            // Deseslashear y sanitizar las opciones del formulario
            $raw_options = isset($_POST['accessitrans_aria_options']) ? wp_unslash($_POST['accessitrans_aria_options']) : [];
            
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
        
        // Incluir scripts para las herramientas interactivas
        $this->enqueue_admin_scripts();
        
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
                            'modo_debug' => esc_html__('Activa el registro detallado de eventos. Se almacena en wp-content/debug-aria-wpml.log', 'accessitrans-aria'),
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
     * Registra e incluye scripts para la página de administración
     */
    private function enqueue_admin_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Switch AJAX para activar/desactivar escaneo
            $('#permitir_escaneo_ajax').on('change', function() {
                const $switch = $(this);
                const $status = $('#switch-status');
                const enabled = $switch.is(':checked');
                
                $switch.prop('disabled', true);
                $status.html('<?php echo esc_js(__('Guardando...', 'accessitrans-aria')); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'accessitrans_toggle_scan',
                        nonce: '<?php echo esc_attr(wp_create_nonce('accessitrans-toggle-scan')); ?>',
                        enabled: enabled ? 1 : 0
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<div class="notice notice-success inline"><p>' + response.data + '</p></div>');
                            
                            // Actualizar los campos dependientes
                            updateDependentFields(enabled);
                            
                            // Actualizar campo oculto en el formulario
                            $('#hidden_permitir_escaneo').val(enabled ? '1' : '0');
                            
                            // Anuncio para lectores de pantalla
                            $('#accessitrans-aria-live').text(response.data);
                        } else {
                            $status.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>');
                            $switch.prop('checked', !enabled); // Revertir estado
                        }
                        
                        $switch.prop('disabled', false);
                        
                        // Ocultar mensaje después de 3 segundos
                        setTimeout(function() {
                            $status.empty();
                        }, 3000);
                    },
                    error: function() {
                        $status.html('<div class="notice notice-error inline"><p><?php echo esc_js(__('Error al guardar la configuración.', 'accessitrans-aria')); ?></p></div>');
                        $switch.prop('checked', !enabled); // Revertir estado
                        $switch.prop('disabled', false);
                    }
                });
            });
            
            // Función para actualizar estados de campos dependientes
            function updateDependentFields(enabled) {
                // Obtener todos los campos de métodos de captura
                $(".accessitrans-methods-fieldset input[type=checkbox]").prop("disabled", !enabled);
                $(".accessitrans-methods-fieldset .accessitrans-field").toggleClass("disabled", !enabled);
                
                // Actualizar atributos ARIA
                if (!enabled) {
                    $(".accessitrans-methods-fieldset input[type=checkbox]").attr("aria-disabled", "true");
                    $(".accessitrans-methods-fieldset").attr("aria-describedby", "scan-disabled-message");
                } else {
                    $(".accessitrans-methods-fieldset input[type=checkbox]").removeAttr("aria-disabled");
                    $(".accessitrans-methods-fieldset").removeAttr("aria-describedby");
                }
            }
            
            // Actualizar la interfaz cuando se carga la página
            $(window).on('load', function() {
                // Asegurarse de que los controles AJAX y hidden estén sincronizados
                var enabled = $('#hidden_permitir_escaneo').val() === '1';
                $('#permitir_escaneo_ajax').prop('checked', enabled);
                
                // Actualizar la UI dependiente
                updateDependentFields(enabled);
            });
            
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
                        nonce: '<?php echo esc_attr(wp_create_nonce('accessitrans-force-refresh')); ?>'
                    },
                    success: function(response) {
                        $status.html(response.data);
                        $button.prop('disabled', false);
                        
                        // Anuncio para lectores de pantalla
                        $('#accessitrans-aria-live').text(response.data);
                    },
                    error: function() {
                        $status.html('<?php echo esc_js(__('Error al procesar la solicitud.', 'accessitrans-aria')); ?>');
                        $button.prop('disabled', false);
                        
                        // Anuncio para lectores de pantalla
                        $('#accessitrans-aria-live').text('<?php echo esc_js(__('Error al procesar la solicitud.', 'accessitrans-aria')); ?>');
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
                    
                    // Anuncio para lectores de pantalla
                    $proceso.text('<?php echo esc_js(__('Error: No se ha ingresado una cadena para verificar.', 'accessitrans-aria')); ?>');
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
                        nonce: '<?php echo esc_attr(wp_create_nonce('accessitrans-diagnostics')); ?>',
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
                            
                            // Anuncio para lectores de pantalla
                            $proceso.text('<?php echo esc_js(__('Análisis completado. Se encontraron resultados para la cadena', 'accessitrans-aria')); ?> ' + data.string);
                        } else {
                            $results.html('<div class="diagnostic-error">' + response.data + '</div>');
                            $proceso.text('<?php echo esc_js(__('Error en el análisis:', 'accessitrans-aria')); ?> ' + response.data);
                        }
                        
                        $button.prop('disabled', false);
                    },
                    error: function() {
                        $results.html('<div class="diagnostic-error"><?php echo esc_js(__('Error al procesar la solicitud.', 'accessitrans-aria')); ?></div>');
                        $button.prop('disabled', false);
                        $proceso.text('<?php echo esc_js(__('Error al realizar el análisis. No se pudo contactar con el servidor.', 'accessitrans-aria')); ?>');
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
                        nonce: '<?php echo esc_attr(wp_create_nonce('accessitrans-check-health')); ?>'
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
                            
                            // Anuncio para lectores de pantalla
                            $proceso.text('<?php echo esc_js(__('Verificación completada. Se encontraron', 'accessitrans-aria')); ?> ' + 
                                data.string_count + ' <?php echo esc_js(__('cadenas registradas y', 'accessitrans-aria')); ?> ' + 
                                data.translation_count + ' <?php echo esc_js(__('traducciones.', 'accessitrans-aria')); ?>');
                        } else {
                            $results.html('<div class="diagnostic-error">' + response.data + '</div>');
                            $proceso.text('<?php echo esc_js(__('Error en la verificación:', 'accessitrans-aria')); ?> ' + response.data);
                        }
                        
                        $button.prop('disabled', false);
                    },
                    error: function() {
                        $results.html('<div class="diagnostic-error"><?php echo esc_js(__('Error al procesar la solicitud.', 'accessitrans-aria')); ?></div>');
                        $button.prop('disabled', false);
                        $proceso.text('<?php echo esc_js(__('Error al realizar la verificación. No se pudo contactar con el servidor.', 'accessitrans-aria')); ?>');
                    }
                });
            });
        });
        </script>
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
    
    /**
     * Añade información en la lista de plugins
     */
    public function after_plugin_row($plugin_file, $plugin_data, $status) {
        if (plugin_basename(ACCESSITRANS_PATH . 'accessitrans-aria.php') == $plugin_file) {
            echo '<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange"><div class="notice inline notice-info" style="margin:0; padding:5px;">';
            echo '<strong>' . esc_html__('Compatibilidad verificada:', 'accessitrans-aria') . '</strong> WordPress 6.7-6.8, Elementor 3.28.4, WPML Multilingual CMS 4.7.3, WPML String Translation 3.3.2.';
            echo '</div></td></tr>';
        }
    }
}