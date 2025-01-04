# TaskingAI UI Plugin

**Version:** 1.7  
**Author:** [PrometheanLink LLC - Walter C. Hieber](https://github.com/PrometheanLink)

## Description

The **TaskingAI UI Plugin** provides a conversational UI interface for TaskingAI, allowing users to interact with the AI via a shortcode `[taskingai_ui]`. The plugin includes customizable typing speeds, time formats, and AJAX-based communication with the TaskingAI API.

## Features

- Fully accessible chat interface with typing indicators.
- Customizable typing speed and time format settings.
- Save chat history functionality.
- AJAX integration with TaskingAI API.
- Easy-to-use settings page for API key and model configuration.

## Installation

1. Download the plugin or clone the repository.
2. Place the plugin folder into your WordPress `wp-content/plugins` directory.
3. Activate the plugin through the WordPress admin dashboard.
4. Configure your API key and model ID in the plugin settings (`Settings > TaskingAI UI`).

## Shortcode Usage

Add the following shortcode to any page or post to display the TaskingAI chat interface:

```plaintext
[taskingai_ui]
```
## Settings

Navigate to `Settings > TaskingAI UI` in the WordPress admin dashboard and configure the following options:

- **API Key**:  
  Enter your TaskingAI API key.  
  Example: `123456789abcdef`

- **Model ID**:  
  Specify the model ID for TaskingAI interactions.  
  Example: `gpt-4`

- **Typing Speed**:  
  Adjust the typing speed (in milliseconds per character).  
  Default: `30ms`  
  Range: `10ms` to `200ms`

- **Time Format**:  
  Choose the format for message timestamps:  
  - `12-Hour (AM/PM)`  
  - `24-Hour`

---

Let me know if you’d like any further refinements! 😊
## Settings

    Navigate to Settings > TaskingAI UI in the WordPress admin dashboard.
    Configure the following options:
        API Key: Enter your TaskingAI API key.
        Model ID: Specify the model ID for TaskingAI interactions.
        Typing Speed: Adjust the typing speed (in milliseconds per character).
        Time Format: Choose between 12-hour and 24-hour time formats.

## Development Details
JavaScript and CSS

    CSS: taskingai-ui.css
    JavaScript: taskingai-ui.js
    Dependencies:
        jQuery
        DOMPurify (for message sanitization)

## AJAX Communication

Handles secure communication with TaskingAI servers via admin-ajax.php. Ensures input sanitization and nonce verification.
Admin Settings

    Fully integrated settings page for API key and model configuration.
    Supports additional customization options like typing speed and time format.

## Requirements

    WordPress 5.0 or higher
    PHP 7.4 or higher

## Contributing

Pull requests are welcome! For major changes, please open an issue to discuss what you’d like to change.
License

This plugin is licensed under the GPLv3. See the LICENSE file for details.
