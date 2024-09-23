<?php
session_start();
require 'vendor/autoload.php'; // Include Composer autoload
include('includes/config.php'); // Database configuration

use GuzzleHttp\Client;

// AI Summarizer function using OpenAI API
function summarizeText($text) {
    $apiKey = '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';
    $url = 'https://api.openai.com/v1/chat/completions';

    // Prepare data for OpenAI API request
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'user',
                'content' => "Summarize the following news article: " . $text
            ]
        ],
        'temperature' => 0.5,
        'max_tokens' => 150,
    ];

    // Initialize Guzzle client and make the API request
    $client = new Client();
    try {
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        // Decode response from OpenAI
        $responseBody = json_decode($response->getBody(), true);
        return trim($responseBody['choices'][0]['message']['content']);
    } catch (GuzzleHttp\Exception\ClientException $e) {
        // Catch 429 error (Too Many Requests) and return a fallback message
        if ($e->getCode() == 429) {
            return "Summary service is currently unavailable due to request limits. Please try again later.";
        }
        // Handle other client exceptions
        return "Error summarizing the text: " . $e->getMessage();
    } catch (Exception $e) {
        // Generic exception handler
        return "An error occurred while summarizing the text. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>News Portal | Home Page</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
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
                <?php
                // Pagination setup
                $pageno = isset($_GET['pageno']) ? intval($_GET['pageno']) : 1;
                $no_of_records_per_page = 8;
                $offset = ($pageno - 1) * $no_of_records_per_page;

                // Total pages calculation
                $result = mysqli_query($con, "SELECT COUNT(*) FROM tblposts WHERE Is_Active=1");
                $total_rows = mysqli_fetch_array($result)[0];
                $total_pages = ceil($total_rows / $no_of_records_per_page);

                // Fetch posts from database
                $query = mysqli_query($con, "SELECT tblposts.id as pid, tblposts.PostTitle as posttitle, tblposts.PostImage, tblcategory.CategoryName as category, tblsubcategory.Subcategory as subcategory, tblposts.PostDetails as postdetails, tblposts.PostingDate as postingdate FROM tblposts LEFT JOIN tblcategory ON tblcategory.id=tblposts.CategoryId LEFT JOIN tblsubcategory ON tblsubcategory.SubCategoryId=tblposts.SubCategoryId WHERE tblposts.Is_Active=1 ORDER BY tblposts.id DESC LIMIT $offset, $no_of_records_per_page");

                while ($row = mysqli_fetch_array($query)) {
                    $postDetails = htmlentities($row['postdetails']);
                    $summary = summarizeText($postDetails);
                ?>
                <div class="card mb-4">
                    <img class="card-img-top" src="admin/postimages/<?php echo htmlentities($row['PostImage']); ?>" alt="<?php echo htmlentities($row['posttitle']); ?>">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo htmlentities($row['posttitle']); ?></h2>
                        <p>
                            <a class="badge bg-secondary text-decoration-none link-light" href="category.php?catid=<?php echo htmlentities($row['cid']); ?>" style="color:#fff"><?php echo htmlentities($row['category']); ?></a>
                        </p>
                        <p class="card-text"><?php echo $summary; ?></p>
                        <a href="news-details.php?nid=<?php echo htmlentities($row['pid']); ?>" class="btn btn-primary">Read More &rarr;</a>
                    </div>
                    <div class="card-footer text-muted">
                        Posted on <?php echo htmlentities($row['postingdate']); ?>
                    </div>
                </div>
                <?php } ?>

                <!-- Pagination -->
                <ul class="pagination justify-content-center mb-4">
                    <li class="page-item <?php if ($pageno <= 1) echo 'disabled'; ?>">
                        <a href="?pageno=1" class="page-link">First</a>
                    </li>
                    <li class="page-item <?php if ($pageno <= 1) echo 'disabled'; ?>">
                        <a href="<?php if ($pageno > 1) echo '?pageno=' . ($pageno - 1); else echo '#'; ?>" class="page-link">Prev</a>
                    </li>
                    <li class="page-item <?php if ($pageno >= $total_pages) echo 'disabled'; ?>">
                        <a href="<?php if ($pageno < $total_pages) echo '?pageno=' . ($pageno + 1); else echo '#'; ?>" class="page-link">Next</a>
                    </li>
                    <li class="page-item <?php if ($pageno >= $total_pages) echo 'disabled'; ?>">
                        <a href="?pageno=<?php echo $total_pages; ?>" class="page-link">Last</a>
                    </li>
                </ul>

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
