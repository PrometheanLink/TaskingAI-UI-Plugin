// taskingai-ui.js (v1.6) - "Recursive DOM typing" approach
jQuery(document).ready(function($) {

    //--- Event handlers ---
    $('#taskingai-submit').on('click', function() {
        sendMessage();
    });
    $('#taskingai-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });
    $('#taskingai-save-chat').on('click', function() {
        saveChatHistory();
    });

    //--- Send message ---
    function sendMessage() {
        var message = $('#taskingai-input').val().trim();
        if (!message) {
            alert('Please enter a message.');
            return;
        }
        $('#taskingai-input').val('');

        // Show user's message
        var timestamp = getCurrentTime();
        $('#taskingai-output').append(
            '<p class="user-message"><strong>You:</strong> '
            + escapeHtml(message)
            + ' <span class="timestamp">' + timestamp + '</span></p>'
        );

        // Indicate typing
        $('#taskingai-typing-indicator').show();
        $('#taskingai-submit').prop('disabled', true);

        // AJAX to server
        $.ajax({
            url: taskingai_ui_params.ajax_url,
            type: 'POST',
            data: {
                action: 'taskingai_ui_send_message',
                message: message,
                nonce: taskingai_ui_params.nonce
            },
            success: function(response) {
                var replyTime = getCurrentTime();
                if (response.success) {
                    // 1) Parse raw AI text -> HTML string
                    var html = parseMarkdown(response.data);

                    // 2) Convert HTML string -> DOM structure in a hidden container
                    var hiddenDiv = $('<div></div>').html(html);

                    // 3) Create final container for AI message
                    var $aiMessage = $('<div class="ai-message"></div>');
                    var $label = $('<strong>TaskingAI:</strong> ');
                    $aiMessage.append($label);

                    // Append AI container now
                    $('#taskingai-output').append($aiMessage);
                    scrollToBottom();

                    // 4) Recursively walk hiddenDiv’s child nodes, typing them into $aiMessage
                    var typingSpeed = parseInt(taskingai_ui_params.typing_speed, 10) || 30;
                    recursivelyTypeNodes(hiddenDiv[0], $aiMessage[0], typingSpeed, function() {
                        // 5) After done typing, show timestamp
                        $aiMessage.append('<span class="timestamp">' + replyTime + '</span>');
                        scrollToBottom();
                    });
                } else {
                    // Show error
                    $('#taskingai-output').append(
                        '<p class="error-message"><strong>Error:</strong> '
                        + escapeHtml(response.data)
                        + ' <span class="timestamp">' + replyTime + '</span></p>'
                    );
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                var errorTime = getCurrentTime();
                $('#taskingai-output').append(
                    '<p class="error-message"><strong>Error:</strong> '
                    + escapeHtml(textStatus + ': ' + errorThrown)
                    + ' <span class="timestamp">' + errorTime + '</span></p>'
                );
            },
            complete: function() {
                $('#taskingai-typing-indicator').hide();
                $('#taskingai-submit').prop('disabled', false);
                $('#taskingai-input').focus();
            }
        });
    }

    /**
     * Recursively walk all child nodes of `sourceNode`, cloning them into `destNode`.
     * If it’s a text node, type out its characters at `typingSpeed`.
     * If it’s an element, create it immediately & recurse into its children.
     * `onComplete` is a callback that runs once all nodes are typed.
     */
    function recursivelyTypeNodes(sourceNode, destNode, typingSpeed, onComplete) {
        var childNodes = sourceNode.childNodes;
        var currentIndex = 0;

        function processNextNode() {
            if (currentIndex >= childNodes.length) {
                // Done all children of this level
                if (onComplete) onComplete();
                return;
            }

            var node = childNodes[currentIndex];
            currentIndex++;

            if (node.nodeType === Node.TEXT_NODE) {
                // Type out text node
                typeTextNode(node, destNode, typingSpeed, processNextNode);
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                // Create the same element in dest
                var newEl = document.createElement(node.tagName);
                // Copy attributes
                for (var i = 0; i < node.attributes.length; i++) {
                    var attr = node.attributes[i];
                    newEl.setAttribute(attr.name, attr.value);
                }
                // Append to dest
                destNode.appendChild(newEl);

                // Now recursively type its children
                recursivelyTypeNodes(node, newEl, typingSpeed, processNextNode);

            } else {
                // skip e.g. comment nodes, etc.
                processNextNode();
            }
        }

        processNextNode();
    }

    /**
     * Type out a TEXT_NODE's content into `destNode`, one character at a time.
     * Then call `callback` when done.
     */
    function typeTextNode(textNode, destNode, speed, callback) {
        var text = textNode.nodeValue;
        var idx = 0;

        var typingInterval = setInterval(function() {
            if (idx < text.length) {
                // Append next character
                destNode.appendChild(document.createTextNode(text.charAt(idx)));
                idx++;
                scrollToBottom();
            } else {
                clearInterval(typingInterval);
                if (callback) callback();
            }
        }, speed);
    }

    //--- parseMarkdown -> minimal approach ---
    function parseMarkdown(text) {
        // Replace ###... -> <h3><strong>...</strong></h3>
        text = text.replace(/###\s*(.*?)$/gm, '<h3><strong>$1</strong></h3>');
        // Replace ##...## -> <h2><strong>...</strong></h2>
        text = text.replace(/##\s*(.*?)\s*##/g, '<h2><strong>$1</strong></h2>');
        // Replace **...** -> <strong>...</strong>
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Replace *...* -> <em>...</em>
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Replace newlines -> <br>
        text = text.replace(/\n/g, '<br>');
        return text;
    }

    //--- Utility: scroll output to bottom ---
    function scrollToBottom() {
        var out = $('#taskingai-output')[0];
        if (out) out.scrollTop = out.scrollHeight;
    }

    //--- Save chat ---
    function saveChatHistory() {
        var content = '';
        $('#taskingai-output p, #taskingai-output div.ai-message, #taskingai-output div.error-message')
            .each(function() {
                content += $(this).text() + '\n';
            });
        if (!content) {
            alert('No chat history to save!');
            return;
        }
        var blob = new Blob([content], { type: 'text/plain' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'chat_history.txt';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    //--- Escape user text to avoid XSS ---
    function escapeHtml(str) {
        return $('<div>').text(str).html();
    }

    //--- Time formatting from plugin settings ---
    function getCurrentTime() {
        var now = new Date();
        var hours = now.getHours();
        var minutes = now.getMinutes();
        var fmt = taskingai_ui_params.time_format;
        if (fmt === '12') {
            var ampm = (hours >= 12) ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            if (minutes < 10) minutes = '0' + minutes;
            return hours + ':' + minutes + ' ' + ampm;
        } else {
            if (hours < 10) hours = '0' + hours;
            if (minutes < 10) minutes = '0' + minutes;
            return hours + ':' + minutes;
        }
    }
});
