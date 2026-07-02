<?php
/**
 * AI Helper for Claude API Integration
 * AI-Powered Smart Complaint & Escalation System
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Classify a complaint using Claude AI API.
 * Falls back to a local keyword-matching algorithm if the API call fails or is unconfigured.
 * 
 * @param string $text The complaint text
 * @return array Array containing keys: 'category', 'priority', 'summary'
 */
function ai_classify_complaint($text) {
    // Basic validation
    if (empty(trim($text))) {
        return [
            'category' => 'Infrastructure',
            'priority' => 'Low',
            'summary' => 'Empty complaint text submitted.'
        ];
    }

    $apiKey = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : '';

    // If the key is not set or is still the default placeholder, use local heuristics immediately
    if (empty($apiKey) || $apiKey === 'YOUR_CLAUDE_API_KEY_HERE') {
        error_log("Claude API Key not configured. Using local fallback classification.");
        return ai_classify_fallback($text);
    }

    $url = 'https://api.anthropic.com/v1/messages';
    
    $prompt = "Analyze the following college student complaint and classify it.
    
Complaint: \"$text\"

Return a raw JSON object with the exact keys:
1. 'category' (must be exactly one of: Academic, Infrastructure, Hostel, Transport, Harassment)
2. 'priority' (must be exactly one of: High, Medium, Low)
3. 'summary' (exactly 1-2 sentences summarizing the core issue).

Do NOT wrap the response in markdown code blocks like ```json ... ```. Do NOT write any introduction or conclusion. Return ONLY the raw JSON string.";

    $data = [
        'model'      => 'claude-3-5-sonnet-20241022',
        'max_tokens' => 1000,
        'messages'   => [
            [
                'role'    => 'user',
                'content' => $prompt
            ]
        ]
    ];

    $headers = [
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
        'content-type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // Timeout parameters
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    // Disable SSL verification issues if running in local windows environment with bad cert configs
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log("Claude API cURL Error: " . $curlError . ". Falling back to local classification.");
        return ai_classify_fallback($text);
    }

    if ($httpCode !== 200) {
        error_log("Claude API HTTP Error code: " . $httpCode . ". Response: " . $response . ". Falling back to local classification.");
        return ai_classify_fallback($text);
    }

    $responseDecoded = json_decode($response, true);
    if (!isset($responseDecoded['content'][0]['text'])) {
        error_log("Claude API Response format mismatch. Response: " . $response . ". Falling back.");
        return ai_classify_fallback($text);
    }

    $rawText = trim($responseDecoded['content'][0]['text']);
    
    // Clean potential markdown wrap
    if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $rawText, $matches)) {
        $rawText = trim($matches[1]);
    }

    $result = json_decode($rawText, true);

    if (!$result || !isset($result['category']) || !isset($result['priority']) || !isset($result['summary'])) {
        error_log("Failed to parse JSON content from Claude response. Raw text: " . $rawText . ". Falling back.");
        return ai_classify_fallback($text);
    }

    // Sanitize category & priority to match strict constraints
    $validCategories = ['Academic', 'Infrastructure', 'Hostel', 'Transport', 'Harassment'];
    $validPriorities = ['High', 'Medium', 'Low'];

    $category = ucwords(strtolower(trim($result['category'])));
    if (!in_array($category, $validCategories)) {
        $category = 'Infrastructure'; // Fallback category
    }

    $priority = ucwords(strtolower(trim($result['priority'])));
    if (!in_array($priority, $validPriorities)) {
        $priority = 'Medium';
    }

    return [
        'category' => $category,
        'priority' => $priority,
        'summary'  => htmlspecialchars($result['summary'])
    ];
}

/**
 * Fallback classification using simple keyword matching rules.
 * 
 * @param string $text
 * @return array
 */
function ai_classify_fallback($text) {
    $textLower = strtolower($text);
    
    // Default values
    $category = 'Infrastructure';
    $priority = 'Low';
    $summary = strlen($text) > 80 ? substr($text, 0, 77) . '...' : $text;

    // 1. Category Detection Heuristic
    if (preg_match('/(professor|teacher|exam|grading|grade|marks|class|lecture|syllabus|course|academic|assignment)/i', $textLower)) {
        $category = 'Academic';
    } elseif (preg_match('/(hostel|mess|warden|room|dorm|roommate)/i', $textLower)) {
        $category = 'Hostel';
    } elseif (preg_match('/(bus|transport|parking|shuttle|route|driver|vehicle)/i', $textLower)) {
        $category = 'Transport';
    } elseif (preg_match('/(harass|threat|bully|abuse|ragging|discrimination|fight|assault)/i', $textLower)) {
        $category = 'Harassment';
    } elseif (preg_match('/(water|electricity|power|wifi|internet|fan|bench|classroom|leak|canteen|toilet|building|maintenance)/i', $textLower)) {
        $category = 'Infrastructure';
    }

    // 2. Priority Detection Heuristic
    if (preg_match('/(harass|threat|bully|abuse|ragging|fight|assault|danger|emergency|fire|leakage|injury)/i', $textLower)) {
        $priority = 'High';
    } elseif (preg_match('/(exam|marks|wifi|water|electricity|mess)/i', $textLower)) {
        $priority = 'Medium';
    }

    // 3. Simple Summary Generation
    $summary = "Summary: " . $summary;

    return [
        'category' => $category,
        'priority' => $priority,
        'summary'  => htmlspecialchars($summary)
    ];
}
