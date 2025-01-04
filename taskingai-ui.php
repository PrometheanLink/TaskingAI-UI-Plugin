<?php
/*
Plugin Name: TaskingAI UI Plugin
Description: An enhanced plugin that provides a TaskingAI conversational UI interface via a [taskingai_ui] shortcode.
Version: 1.7
Author: PrometheanLink LLC - Walter C. Hieber
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enqueue the JavaScript and CSS files
function taskingai_ui_enqueue_scripts() {
    // Enqueue CSS (v1.6)
    wp_enqueue_style('taskingai-ui-style', plugin_dir_url(__FILE__) . 'taskingai-ui.css', array(), '1.6');

    // (Optional) DOMPurify for sanitizing if you want it
    wp_enqueue_script(
        'dompurify',
        'https://cdn.jsdelivr.net/npm/dompurify@2/dist/purify.min.js',
        array(),
        null,
        true
    );

    // Enqueue main JS w/ dependencies, also v1.6
    wp_enqueue_script(
        'taskingai-ui-script',
        plugin_dir_url(__FILE__) . 'taskingai-ui.js',
        array('jquery', 'dompurify'),
        '1.6',
        true
    );

    // Retrieve settings
    $typing_speed = get_option('taskingai_ui_typing_speed', 30);
    $time_format  = get_option('taskingai_ui_time_format', '12');

    // Pass data to JS
    wp_localize_script('taskingai-ui-script', 'taskingai_ui_params', array(
        'ajax_url'     => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('taskingai_ui_nonce'),
        'typing_speed' => intval($typing_speed),
        'time_format'  => sanitize_text_field($time_format),
    ));
}
add_action('wp_enqueue_scripts', 'taskingai_ui_enqueue_scripts');

// Register the shortcode
function taskingai_ui_shortcode() {
    ob_start(); ?>
    <div id="taskingai-ui" role="region" aria-labelledby="taskingai-title">
        <h2 id="taskingai-title" class="screen-reader-text">TaskingAI Chat Interface</h2>
        <div id="taskingai-output" aria-live="polite"></div>
        <div id="taskingai-input-area">
            <label for="taskingai-input" class="screen-reader-text">Your Message</label>
            <input type="text" id="taskingai-input" placeholder="Ask a question..." autocomplete="off" aria-label="Your Message">
            <button id="taskingai-submit" aria-label="Send Message">Send</button>
            <button id="taskingai-save-chat" aria-label="Save Chat History">Save Chat</button>
        </div>
        <div id="taskingai-typing-indicator" style="display: none;" aria-live="polite">
            <span class="spinner"></span> TaskingAI is typing...
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('taskingai_ui', 'taskingai_ui_shortcode');

// Handle the AJAX request
function taskingai_ui_handle_ajax() {
    // Security check
    check_ajax_referer('taskingai_ui_nonce', 'nonce');

    // Sanitize user message
    $message = sanitize_text_field($_POST['message']);

    // Get API key / Model ID
    $api_key  = get_option('taskingai_ui_api_key');
    $model_id = get_option('taskingai_model_id');

    if (!$api_key || !$model_id) {
        wp_send_json_error('API key or Model ID is missing. Please set it in the plugin settings.');
    }

    // Request to TaskingAI
    $response = wp_remote_post('https://oapi.tasking.ai/v1/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
        'body' => json_encode(array(
            'model'    => $model_id,
            'messages' => array(
                array('role' => 'user', 'content' => $message),
            ),
        )),
        'timeout' => 60,
    ));

    // Check for errors
    if (is_wp_error($response)) {
        wp_send_json_error('Error communicating with TaskingAI: ' . $response->get_error_message());
    }

    // Parse body
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Return AI text
    if (isset($data['choices'][0]['message']['content'])) {
        wp_send_json_success($data['choices'][0]['message']['content']);
    } else {
        wp_send_json_error('No response from TaskingAI.');
    }
}
add_action('wp_ajax_taskingai_ui_send_message', 'taskingai_ui_handle_ajax');
add_action('wp_ajax_nopriv_taskingai_ui_send_message', 'taskingai_ui_handle_ajax');

// Admin Settings
function taskingai_ui_add_admin_menu() {
    add_options_page('TaskingAI UI Settings', 'TaskingAI UI', 'manage_options', 'taskingai-ui', 'taskingai_ui_options_page');
}
add_action('admin_menu', 'taskingai_ui_add_admin_menu');

function taskingai_ui_options_page() { ?>
    <div class="wrap">
        <h1>TaskingAI UI Settings (v1.7)</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('taskingai_ui_options_group');
            do_settings_sections('taskingai-ui');
            submit_button();
            ?>
        </form>
    </div>
<?php }

function taskingai_ui_settings_init() {
    register_setting('taskingai_ui_options_group', 'taskingai_ui_api_key');
    register_setting('taskingai_ui_options_group', 'taskingai_model_id');
    register_setting('taskingai_ui_options_group', 'taskingai_ui_typing_speed');
    register_setting('taskingai_ui_options_group', 'taskingai_ui_time_format');

    // Section
    add_settings_section(
        'taskingai_ui_settings_section',
        'API Key Settings',
        function() { echo '<p>Enter your TaskingAI API key and Model ID:</p>'; },
        'taskingai-ui'
    );

    add_settings_field(
        'taskingai_ui_api_key_field',
        'API Key',
        function() {
            $api_key = get_option('taskingai_ui_api_key');
            echo '<input type="text" name="taskingai_ui_api_key" value="' . esc_attr($api_key) . '" class="taskingai-input" required>';
        },
        'taskingai-ui',
        'taskingai_ui_settings_section'
    );

    add_settings_field(
        'taskingai_model_id_field',
        'Model ID',
        function() {
            $model_id = get_option('taskingai_model_id');
            echo '<input type="text" name="taskingai_model_id" value="' . esc_attr($model_id) . '" class="taskingai-input" required>';
        },
        'taskingai-ui',
        'taskingai_ui_settings_section'
    );

    // Typing Speed
    add_settings_section(
        'taskingai_ui_typing_section',
        'Typing Speed Settings',
        function() { echo '<p>Configure how quickly text appears (ms per character).</p>'; },
        'taskingai-ui'
    );

    add_settings_field(
        'taskingai_ui_typing_speed_field',
        'Typing Speed (ms)',
        function() {
            $speed = get_option('taskingai_ui_typing_speed', 30);
            echo '<input type="number" name="taskingai_ui_typing_speed" value="' . esc_attr($speed) . '" min="10" max="200" step="5" class="taskingai-input"> ms';
        },
        'taskingai-ui',
        'taskingai_ui_typing_section'
    );

    // Time Format
    add_settings_section(
        'taskingai_ui_time_section',
        'Timestamp Settings',
        function() { echo '<p>Select the time format for message timestamps:</p>'; },
        'taskingai-ui'
    );

    add_settings_field(
        'taskingai_ui_time_format_field',
        'Time Format',
        function() {
            $time_format = get_option('taskingai_ui_time_format', '12');
            ?>
            <label>
                <input type="radio" name="taskingai_ui_time_format" value="12" <?php checked($time_format, '12'); ?>>
                12-Hour (AM/PM)
            </label><br>
            <label>
                <input type="radio" name="taskingai_ui_time_format" value="24" <?php checked($time_format, '24'); ?>>
                24-Hour
            </label>
            <?php
        },
        'taskingai-ui',
        'taskingai_ui_time_section'
    );
}
add_action('admin_init', 'taskingai_ui_settings_init');
