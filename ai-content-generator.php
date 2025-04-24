<?php
function generate_ai_content_from_rss($title, $content) {
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    if (empty($api_key)) {
        log_to_file('OpenAI API key is not defined!', 'API KEY ERROR');
    }

    $prompt = "You're a blogger for a local Orlando news site. Based on the following headline and content, generate:\n\n1. A catchy and original blog post title.\n2. A short blog post based on the following headline and content. Make it informative, original, and human-like.\n\nHeadline: {$title}\n\Content: {$content}\n\nReturn the result in this format:\nTitle: <Your title>\n\nContent:\n<Your content>";

    $body = json_encode([
        'model' => 'gpt-4-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant who writes blog posts.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
        'max_tokens' => 700,
    ]);

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $body,
        'timeout' => 30,
    ]);

    log_to_file($response, 'OpenAI Response');

    if (is_wp_error($response)) {
        log_to_file($response->get_error_message(), 'OpenAI Error');
        return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $aicontent = $data['choices'][0]['message']['content'] ?? '';

    log_to_file($aicontent, 'AI Raw Output');

    $title_match = '';
    $content_match = '';

    if (preg_match('/^Title:\s*(.*?)\n\n/s', $aicontent, $matches)) {
        $title_match = trim($matches[1]);
    }

    if (preg_match('/Content:\s*(.+)$/s', $aicontent, $matches)) {
        $content_match = trim($matches[1]);
    }

    log_to_file(['title' => $title_match, 'content' => $content_match], 'Parsed Output');

    return [
        'title' => $title_match ?: $title,
        'content' => $content_match ?: $description,
    ];
}

function log_to_file($message, $context = '') {
    $log_file = plugin_dir_path(__FILE__) . 'ai-log.txt';
    $time = date('Y-m-d H:i:s');
    $formatted = "[$time] $context: " . print_r($message, true) . "\n";
    file_put_contents($log_file, $formatted, FILE_APPEND);
}