<?php
function generate_ai_content_from_rss($title, $content) {
    $api_key = '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';

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

    if (is_wp_error($response)) {
        return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $aicontent = $data['choices'][0]['message']['content'] ?? '';

    $title_match = '';
    $content_match = '';

    if (preg_match('/^Title:\s*(.*?)\n\n/s', $aicontent, $matches)) {
        $title_match = trim($matches[1]);
    }

    if (preg_match('/Content:\s*(.+)$/s', $aicontent, $matches)) {
        $content_match = trim($matches[1]);
    }

    return [
        'title' => $title_match ?: $title,
        'content' => $content_match ?: $description,
    ];
}