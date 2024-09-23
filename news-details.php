<?php
session_start();
include('includes/config.php'); // Database connection settings

// Include the Composer autoloader for Guzzle
require 'vendor/autoload.php'; // Ensure Guzzle is installed via Composer

// Function to call OpenAI API and summarize text
function summarizeText($text) {
    $apiKey = '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';
    $url = 'https://api.openai.com/v1/chat/completions';

    // Prepare the data for the API request
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'user',
                'content' => "Summarize the following article in 2 sentences: " . $text
            ]
        ],
        'temperature' => 0.5,  // Controls randomness
        'max_tokens' => 150,   // Adjust the token limit for concise summary
    ];

    // Create HTTP request using Guzzle
    try {
        $client = new \GuzzleHttp\Client();
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json',
            ],
            'json' => $data
        ]);

        // Decode the response from the API
        $responseBody = json_decode($response->getBody(), true);

        // Extract the summary from the response
        if (isset($responseBody['choices'][0]['message']['content'])) {
            return trim($responseBody['choices'][0]['message']['content']);
        } else {
            return "Summary could not be generated.";
        }
    } catch (GuzzleHttp\Exception\ClientException $e) {
        // Handle Guzzle-specific client errors (4xx)
        $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);

        if (isset($errorResponse['error']['code'])) {
            switch ($errorResponse['error']['code']) {
                case 'insufficient_quota':
                    return "You have exceeded your OpenAI quota. Please upgrade your plan.";
                case 'invalid_api_key':
                    return "Invalid API key. Please check your OpenAI API key.";
                default:
                    return "Error: " . $errorResponse['error']['message'];
            }
        }
        return "Error in summarizing the text.";
    } catch (Exception $e) {
        // Handle other errors
        return "Error in summarizing the text: " . $e->getMessage();
    }
}

// Check if news ID is provided in the URL
if (isset($_GET['nid'])) {
    $postId = intval($_GET['nid']);

    // Fetch the full post details from the database
    $query = mysqli_query($con, "SELECT PostTitle, PostDetails, PostingDate FROM tblposts WHERE id='$postId' AND Is_Active=1");
    $row = mysqli_fetch_array($query);

    if ($row) {
        $postTitle = htmlentities($row['PostTitle']);
        $postDetails = trim(html_entity_decode($row['PostDetails'])); // Clean text
        $postingDate = htmlentities($row['PostingDate']);

        // Call the summarizer function to summarize the article
        $summary = summarizeText($postDetails);
    } else {
        echo "No article found!";
        exit();
    }
} else {
    echo "Invalid article!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>News Portal | <?php echo $postTitle; ?></title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="css/modern-business.css" rel="stylesheet">
</head>
<body>

    <!-- Navigation -->
    <?php include('includes/header.php'); ?>

    <!-- Page Content -->
    <div class="container">

        <div class="row" style="margin-top: 4%">

            <!-- Blog Entries Column -->
            <div class="col-md-8">
                
                <!-- Blog Post -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $postTitle; ?></h2>

                        <!-- Display the AI-generated summary -->
                        <h4>Summary:</h4>
                        <p><?php echo $summary; ?></p>

                        <!-- Display the full content -->
                        <h4>Full Article:</h4>
                        <p><?php echo $postDetails; ?></p>
                    </div>
                    <div class="card-footer text-muted">
                        Posted on <?php echo $postingDate; ?>
                    </div>
                </div>

            </div>

            <!-- Sidebar Widgets Column -->
            <?php include('includes/sidebar.php'); ?>

        </div>
        <!-- /.row -->

    </div>
    <!-- /.container -->

    <!-- Footer -->
    <?php include('includes/footer.php'); ?>

    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
