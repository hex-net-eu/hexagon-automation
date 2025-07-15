/**
 * Hexagon Automation - Nonces & AJAX Helper
 * Bezpieczne wywołania AJAX z proper nonces
 */

window.hexagonAjax = {
    
    // Test czy AJAX działa
    testConnection: function() {
        return jQuery.post(hexagon_ajax.ajax_url, {
            action: 'hexagon_debug_ajax',
            timestamp: Date.now()
        });
    },
    
    // AI Generation z proper nonce
    generateAI: function(provider, content_type, prompt, language = 'pl') {
        return jQuery.post(hexagon_ajax.ajax_url, {
            action: 'hexagon_ai_generate',
            nonce: hexagon_ajax.nonces.ai,
            provider: provider,
            content_type: content_type,
            prompt: prompt,
            language: language
        });
    },
    
    // Test AI Connection
    testAI: function(provider) {
        return jQuery.post(hexagon_ajax.ajax_url, {
            action: 'hexagon_ai_test_connection',
            nonce: hexagon_ajax.nonces.ai,
            provider: provider
        });
    },
    
    // Email Test
    testEmail: function(settings) {
        return jQuery.post(hexagon_ajax.ajax_url, {
            action: 'hexagon_test_email',
            nonce: hexagon_ajax.nonces.email,
            settings: settings
        });
    },
    
    // Social Media Post
    socialPost: function(platform, content) {
        return jQuery.post(hexagon_ajax.ajax_url, {
            action: 'hexagon_social_post',
            nonce: hexagon_ajax.nonces.social,
            platform: platform,
            content: content
        });
    },
    
    // Debug Export
    exportLogs: function() {
        return jQuery.post(hexagon_ajax.ajax_url, {
            action: 'hexagon_export_logs',
            nonce: hexagon_ajax.nonces.debug
        });
    },
    
    // System Tests
    runTests: function() {
        return jQuery.post(hexagon_ajax.ajax_url, {
            action: 'hexagon_run_tests',
            nonce: hexagon_ajax.nonces.test
        });
    }
};

// Test connection przy ładowaniu
jQuery(document).ready(function($) {
    
    if (typeof hexagon_ajax === 'undefined') {
        console.error('Hexagon: AJAX configuration not loaded');
        return;
    }
    
    console.log('Hexagon AJAX initialized:', hexagon_ajax);
    
    // Auto-test połączenia
    if (hexagon_ajax.debug_mode) {
        hexagonAjax.testConnection()
            .done(function(response) {
                console.log('✅ Hexagon AJAX Test Success:', response);
            })
            .fail(function(xhr, status, error) {
                console.error('❌ Hexagon AJAX Test Failed:', error, xhr.responseText);
            });
    }
    
    // Dodaj test button na dashboard
    if ($('#hexagon-dashboard').length > 0) {
        const testButton = $('<button class="button" id="hexagon-ajax-test">🧪 Test AJAX</button>');
        testButton.click(function() {
            $(this).prop('disabled', true).text('Testing...');
            
            hexagonAjax.testConnection()
                .done(function(response) {
                    alert('✅ AJAX działa poprawnie!\n\nOdpowiedź: ' + JSON.stringify(response.data, null, 2));
                })
                .fail(function(xhr) {
                    alert('❌ AJAX Error!\n\nStatus: ' + xhr.status + '\nResponse: ' + xhr.responseText);
                })
                .always(function() {
                    testButton.prop('disabled', false).text('🧪 Test AJAX');
                });
        });
        
        $('.hexagon-page-header, .wrap h1').first().after(testButton);
    }
});