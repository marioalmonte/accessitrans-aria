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
        $checked = isset($this->core->options[$option_name]) && $this->core->options[$option_name] ? 'checked' : '';
        
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
     * Añade enlaces a la página de configuración en la lista de plugins
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=accessitrans-aria') . '">' . __('Settings', 'accessitrans-aria') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Añade información en la lista de plugins
     */
    public function after_plugin_row($plugin_file, $plugin_data, $status) {
        if (plugin_basename(ACCESSITRANS_PATH . 'accessitrans-aria.php') == $plugin_file) {
            echo '<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange"><div class="notice inline notice-info" style="margin:0; padding:5px;">';
            echo '<strong>' . __('Compatibilidad verificada:', 'accessitrans-aria') . '</strong> WordPress 6.7-6.8, Elementor 3.28.3, WPML Multilingual CMS 4.7.3, WPML String Translation 3.3.2.';
            echo '</div></td></tr>';
        }
    }
}