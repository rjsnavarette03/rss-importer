<?php
function generate_ai_content_from_rss($title, $content, $description) {
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    if (empty($api_key)) {
        log_to_file('OpenAI API key is not defined!', 'API KEY ERROR');
        return [
            'title' => $title,
            'content' => $content,
        ];
    }

    $prompt = "You're a blogger for a local Orlando news site. Use the description below to create a brand new original 1000 word SEO wise blog for my Orlando news website called 'Daily Orlando News'. The blog post should include an introduction, main body, and conclusion. The conclusion should invite readers to leave a comment. The main body should be split into at least 4 different subsections. For the title, make it 50-60 characters only.\n\nDescription: {$description}\n\nAlso, make sure you know that it's an ORLANDO news to address how the issue affects Orlando whenever possible. Return the result in the format below and should be HTML safe, like if there is a link, wrap it in a <a> tag:\nTitle: <Your title>\n\nContent:\n<Your content>";

    $body = json_encode([
        'model' => 'gpt-4.1',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant who writes blog posts.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
        'max_tokens' => 1200,
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

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($data)) {
        log_to_file('Failed to decode OpenAI response', 'JSON ERROR');
        return [
            'title' => $title,
            'content' => $content,
        ];
    }
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
        'content' => $content_match ?: $content,
    ];
}

function log_to_file($message, $context = '') {
    $log_file = plugin_dir_path(__FILE__) . 'ai-log.txt';
    $time = date('Y-m-d H:i:s');
    $formatted = "[$time] $context: " . print_r($message, true) . "\n";
    file_put_contents($log_file, $formatted, FILE_APPEND);
}