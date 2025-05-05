/**
 * AccessiTrans Admin Scripts
 * 
 * Scripts para la interfaz de administración del plugin AccessiTrans.
 * 
 * @package AccessiTrans
 */

jQuery(document).ready(function($) {
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
        
        // Anunciar cambio para lectores de pantalla
        if (window.accessitrans_announce) {
            clearTimeout(window.accessitrans_announce);
        }
        
        window.accessitrans_announce = setTimeout(function() {
            var message = enabled ? 
                accessitransAdmin.strings.scanEnabledMessage : 
                accessitransAdmin.strings.scanDisabledMessage;
                
            $("#accessitrans-aria-live").text(message);
        }, 100);
    }
    
    // Inicializar
    updateDependentFields($("#permitir_escaneo_ajax").is(":checked"));
    
    // Actualizar cuando cambie
    $("#permitir_escaneo_ajax").on("change", function() {
        const $switch = $(this);
        const $status = $('#switch-status');
        const enabled = $switch.is(':checked');
        
        $switch.prop('disabled', true);
        $status.html(accessitransAdmin.strings.saving);
        
        $.ajax({
            url: accessitransAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'accessitrans_toggle_scan',
                nonce: accessitransAdmin.nonces.toggle_scan,
                enabled: enabled ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<div class="notice notice-success inline"><p>' + response.data + '</p></div>');
                    
                    // Actualizar campos dependientes
                    updateDependentFields(enabled);
                    
                    // Actualizar campo oculto en el formulario
                    $('#hidden_permitir_escaneo').val(enabled ? '1' : '0');
                    
                    // Anunciar para lectores de pantalla
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
                $status.html('<div class="notice notice-error inline"><p>' + accessitransAdmin.strings.saveError + '</p></div>');
                $switch.prop('checked', !enabled); // Revertir estado
                $switch.prop('disabled', false);
            }
        });
    });
    
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
        $status.html(accessitransAdmin.strings.processing);
        
        $.ajax({
            url: accessitransAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'accessitrans_force_refresh',
                nonce: accessitransAdmin.nonces.force_refresh
            },
            success: function(response) {
                $status.html(response.data);
                $button.prop('disabled', false);
                
                // Anunciar para lectores de pantalla
                $('#accessitrans-aria-live').text(response.data);
            },
            error: function() {
                $status.html(accessitransAdmin.strings.requestError);
                $button.prop('disabled', false);
                
                // Anunciar para lectores de pantalla
                $('#accessitrans-aria-live').text(accessitransAdmin.strings.requestError);
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
            $results.html('<div class="diagnostic-error">' + accessitransAdmin.strings.noString + '</div>');
            $results.addClass('active');
            
            // Anunciar para lectores de pantalla
            $proceso.text(accessitransAdmin.strings.errorNoString);
            return;
        }
        
        $button.prop('disabled', true);
        $results.html(accessitransAdmin.strings.analyzing);
        $results.addClass('active');
        $proceso.text(accessitransAdmin.strings.analyzingMessage);
        
        $.ajax({
            url: accessitransAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'accessitrans_diagnostics',
                nonce: accessitransAdmin.nonces.diagnostics,
                string: stringToCheck
            },
            success: function(response) {
                $results.empty();
                
                if (response.success) {
                    const data = response.data;
                    
                    $results.append('<h4>' + accessitransAdmin.strings.diagnosticResults + '</h4>');
                    
                    $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.originalText + '</strong> ' + data.string + '</div>');
                    
                    // Verificación de idioma
                    let languageStatus = data.is_default_language ? 
                        '<span class="diagnostic-success">' + accessitransAdmin.strings.languageCorrect + '</span>' : 
                        '<span class="diagnostic-error">' + accessitransAdmin.strings.languageNotPrimary + '</span>';
                    
                    $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.language + '</strong> ' + 
                        data.current_language + ' (' + languageStatus + ')</div>');
                    
                    // Información sobre esta cadena en WPML
                    if (data.found_in_wpml) {
                        $results.append('<div class="diagnostic-item diagnostic-success"><strong>' + accessitransAdmin.strings.foundInWPML + '</strong></div>');
                        
                        if (data.string_forms && data.string_forms.length > 0) {
                            $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.registeredFormats + '</strong></div>');
                            
                            $.each(data.string_forms, function(index, form) {
                                $results.append('<div class="diagnostic-item" style="margin-left: 15px;">' + 
                                    '<strong>' + form.name + '</strong> (ID: ' + form.id + ')</div>');
                            });
                        }
                        
                        if (data.has_translation) {
                            $results.append('<div class="diagnostic-item diagnostic-success"><strong>' + accessitransAdmin.strings.hasTranslations + '</strong></div>');
                            
                            $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.availableTranslations + '</strong></div>');
                            
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
                                $results.append('<div class="diagnostic-item diagnostic-error"><strong>' + accessitransAdmin.strings.noCurrentLanguageTranslation + '</strong></div>');
                                $results.append('<div class="diagnostic-item">' + accessitransAdmin.strings.recommendedActionTranslate + '</div>');
                            }
                        } else {
                            $results.append('<div class="diagnostic-item diagnostic-error"><strong>' + accessitransAdmin.strings.noTranslations + '</strong></div>');
                            $results.append('<div class="diagnostic-item">' + accessitransAdmin.strings.translateAction + '</div>');
                        }
                    } else {
                        $results.append('<div class="diagnostic-item diagnostic-error"><strong>' + accessitransAdmin.strings.notFoundInWPML + '</strong></div>');
                        
                        if (!data.is_default_language) {
                            $results.append('<div class="diagnostic-item">' + accessitransAdmin.strings.notPrimaryLanguageWarning + '</div>');
                        } else {
                            $results.append('<div class="diagnostic-item">' + accessitransAdmin.strings.recommendedActionNavigate + '</div>');
                        }
                    }
                    
                    // Consejos adicionales
                    $results.append('<h4>' + accessitransAdmin.strings.troubleshootingTips + '</h4>');
                    $results.append('<ul>' +
                        '<li>' + accessitransAdmin.strings.tip1 + '</li>' +
                        '<li>' + accessitransAdmin.strings.tip2 + '</li>' +
                        '<li>' + accessitransAdmin.strings.tip3 + '</li>' +
                        '</ul>');
                    
                    // Información técnica para depuración
                    if (data.debug_info) {
                        $results.append('<details><summary>' + accessitransAdmin.strings.technicalInfo + '</summary>' +
                            '<pre style="font-size: 11px; overflow: auto; max-height: 150px;">' + JSON.stringify(data.debug_info, null, 2) + '</pre>' +
                            '</details>');
                    }
                    
                    // Anunciar para lectores de pantalla
                    $proceso.text(accessitransAdmin.strings.analysisComplete + ' ' + data.string);
                } else {
                    $results.html('<div class="diagnostic-error">' + response.data + '</div>');
                    $proceso.text(accessitransAdmin.strings.analysisError + ' ' + response.data);
                }
                
                $button.prop('disabled', false);
            },
            error: function() {
                $results.html('<div class="diagnostic-error">' + accessitransAdmin.strings.requestError + '</div>');
                $button.prop('disabled', false);
                $proceso.text(accessitransAdmin.strings.connectionError);
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
        $results.html(accessitransAdmin.strings.verifying);
        $results.addClass('active');
        $proceso.text(accessitransAdmin.strings.verifyingStatus);
        
        $.ajax({
            url: accessitransAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'accessitrans_check_health',
                nonce: accessitransAdmin.nonces.check_health
            },
            success: function(response) {
                $results.empty();
                
                if (response.success) {
                    const data = response.data;
                    
                    $results.append('<h4>' + accessitransAdmin.strings.systemStatus + '</h4>');
                    
                    // WPML y Elementor
                    let wpmlStatus = data.wpml_active ? 
                        '<span class="diagnostic-success">✓ ' + accessitransAdmin.strings.active + '</span>' : 
                        '<span class="diagnostic-error">✗ ' + accessitransAdmin.strings.inactive + '</span>';
                    
                    let elementorStatus = data.elementor_active ? 
                        '<span class="diagnostic-success">✓ ' + accessitransAdmin.strings.active + '</span>' : 
                        '<span class="diagnostic-error">✗ ' + accessitransAdmin.strings.inactive + '</span>';
                    
                    $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.wpml + '</strong> ' + wpmlStatus + '</div>');
                    $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.elementor + '</strong> ' + elementorStatus + '</div>');
                    
                    // Estadísticas
                    $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.registeredStrings + '</strong> ' + data.string_count + '</div>');
                    $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.availableTranslations + '</strong> ' + data.translation_count + '</div>');
                    
                    // Idiomas
                    if (data.languages) {
                        $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.primaryLanguage + '</strong> ' + data.languages.default + '</div>');
                        $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.currentLanguage + '</strong> ' + data.languages.current + '</div>');
                        
                        let langList = '<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.availableLanguages + '</strong> ';
                        $.each(data.languages.available, function(code, name) {
                            langList += code + ' (' + name + '), ';
                        });
                        langList = langList.slice(0, -2); // Eliminar última coma
                        langList += '</div>';
                        $results.append(langList);
                    }
                    
                    // Configuración del plugin
                    $results.append('<h4>' + accessitransAdmin.strings.currentConfiguration + '</h4>');
                    
                    $.each(data.options, function(option, value) {
                        let formattedOption = option.replace(/_/g, ' ');
                        formattedOption = formattedOption.charAt(0).toUpperCase() + formattedOption.slice(1);
                        
                        let statusIcon = value ? '✓' : '✗';
                        let statusClass = value ? 'diagnostic-success' : '';
                        
                        $results.append('<div class="diagnostic-item"><strong>' + formattedOption + ':</strong> <span class="' + statusClass + '">' + statusIcon + '</span></div>');
                    });
                    
                    // Recomendaciones
                    $results.append('<h4>' + accessitransAdmin.strings.recommendations + '</h4>');
                    
                    if (data.string_count === 0) {
                        $results.append('<div class="diagnostic-item diagnostic-error">' + accessitransAdmin.strings.noStringsRegistered + '</div>');
                    }
                    
                    if (data.translation_count === 0 && data.string_count > 0) {
                        $results.append('<div class="diagnostic-item diagnostic-error">' + accessitransAdmin.strings.stringsNoTranslations + '</div>');
                    }
                    
                    if (data.languages && data.languages.current !== data.languages.default) {
                        $results.append('<div class="diagnostic-item diagnostic-error">' + accessitransAdmin.strings.navigatingNonPrimary + '</div>');
                    }
                    
                    // Información del sistema
                    $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.serverDate + '</strong> ' + data.system_time + '</div>');
                    $results.append('<div class="diagnostic-item"><strong>' + accessitransAdmin.strings.pluginVersion + '</strong> ' + data.plugin_version + '</div>');
                    
                    // Anunciar para lectores de pantalla
                    $proceso.text(accessitransAdmin.strings.verificationComplete + ' ' + 
                        data.string_count + ' ' + accessitransAdmin.strings.registeredStringsText + ' ' + 
                        data.translation_count + ' ' + accessitransAdmin.strings.translationsText);
                } else {
                    $results.html('<div class="diagnostic-error">' + response.data + '</div>');
                    $proceso.text(accessitransAdmin.strings.verificationError + ' ' + response.data);
                }
                
                $button.prop('disabled', false);
            },
            error: function() {
                $results.html('<div class="diagnostic-error">' + accessitransAdmin.strings.requestError + '</div>');
                $button.prop('disabled', false);
                $proceso.text(accessitransAdmin.strings.connectionVerificationError);
            }
        });
    });
});