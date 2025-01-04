Settings

    Navigate to Settings > TaskingAI UI in the WordPress admin dashboard.
    Configure the following options:
        API Key: Enter your TaskingAI API key.
        Model ID: Specify the model ID for TaskingAI interactions.
        Typing Speed: Adjust the typing speed (in milliseconds per character).
        Time Format: Choose between 12-hour and 24-hour time formats.

Development Details
JavaScript and CSS

    CSS: taskingai-ui.css
    JavaScript: taskingai-ui.js
    Dependencies:
        jQuery
        DOMPurify (for message sanitization)

AJAX Communication

Handles secure communication with TaskingAI servers via admin-ajax.php. Ensures input sanitization and nonce verification.
Admin Settings

    Fully integrated settings page for API key and model configuration.
    Supports additional customization options like typing speed and time format.

Requirements

    WordPress 5.0 or higher
    PHP 7.4 or higher

Contributing

Pull requests are welcome! For major changes, please open an issue to discuss what you’d like to change.
License

This plugin is licensed under the GPLv3. See the LICENSE file for details.
