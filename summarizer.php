<?php
require 'vendor/autoload.php'; // Include the Composer autoload

use GuzzleHttp\Client;

function summarizeText($text) {
 
    $apiKey = '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';
  $url = 'https://api.openai.com/v1/chat/completions'; 

    // Prepare the data for OpenAI API request
    $data = [
        'model' => 'gpt-3.5-turbo', // Use the GPT model
        'messages' => [
            [
                'role' => 'user',
                'content' => "Summarize the following news article: " . $text
            ]
        ],
        'temperature' => 0.5,
        'max_tokens' => 150,
    ];

    // Use Guzzle HTTP client to make the request
    $client = new Client();
    try {
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        // Decode the response from OpenAI
        $responseBody = json_decode($response->getBody(), true);
        return trim($responseBody['choices'][0]['message']['content']); // Return the summary
    } catch (GuzzleHttp\Exception\RequestException $e) {
        // Handle quota error
        if ($e->hasResponse()) {
            $responseBody = json_decode($e->getResponse()->getBody(), true);
            if (isset($responseBody['error']['code']) && $responseBody['error']['code'] === 'insufficient_quota') {
                return "You have exceeded your quota. Please check your plan and billing details.";
            }
        }
        return "HTTP Request Error: " . $e->getMessage();
    } catch (Exception $e) {
        return "General Error: " . $e->getMessage();
    }
}

// Example article content to summarize
$article = "Your news article content goes here. It can be multiple paragraphs.";

// Debugging output
var_dump($article);

// Call the summarizeText function and display the result
$summary = summarizeText($article);
echo "AI Summary: " . $summary;
?>
