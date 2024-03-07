<?php
// Start the session
session_start();
// Include configuration file 
require_once 'config.php';
// Include and initialize user class 
require_once 'User.class.php';
$user = new User();
// Check if user is authenticated
if (isset($accessToken)) {
    // Get the user profile data from GitHub 
    $gitUser = $gitClient->getAuthenticatedUser($accessToken);
    if (!empty($gitUser)) {
        // Getting user profile details 
        $gitUserData = array();
        $gitUserData['oauth_uid'] = !empty($gitUser->id) ? $gitUser->id : '';
        $gitUserData['name'] = !empty($gitUser->name) ? $gitUser->name : '';
        $gitUserData['username'] = !empty($gitUser->login) ? $gitUser->login : '';
        $gitUserData['email'] = !empty($gitUser->email) ? $gitUser->email : '';
        $gitUserData['location'] = !empty($gitUser->location) ? $gitUser->location : '';
        $gitUserData['picture'] = !empty($gitUser->avatar_url) ? $gitUser->avatar_url : '';
        $gitUserData['link'] = !empty($gitUser->html_url) ? $gitUser->html_url : '';
        // Insert or update user data to the database 
        $gitUserData['oauth_provider'] = 'github';
        $userData = $user->checkUser($gitUserData);
        // Storing user data in the session 
        $_SESSION['userData'] = $userData;
    } else {
        $output = '<div class="container"><div class="alert alert-danger mt-5" role="alert">Something went wrong, please try again!</div></div>';
    }
} elseif (isset($_GET['code'])) {
    // Verify the state matches the stored state 
    if (!$_GET['state'] || $_SESSION['state'] != $_GET['state']) {
        header("Location: " . $_SERVER['PHP_SELF']);
    }
    // Exchange the auth code for a token 
    $accessToken = $gitClient->getAccessToken($_GET['state'], $_GET['code']);
    $_SESSION['access_token'] = $accessToken;
    header('Location: ./');
} else {
    // Generate a random hash and store in the session for security 
    $_SESSION['state'] = hash('sha256', microtime(TRUE) . rand() . $_SERVER['REMOTE_ADDR']);
    // Remove access token from the session 
    unset($_SESSION['access_token']);
    // Get the URL to authorize 
    $authUrl = $gitClient->getAuthorizeURL($_SESSION['state']);
    // Render GitHub login button 
    $output = '<div class="container mt-5"><div class="text-center"><a href="' . htmlspecialchars($authUrl) . '" class="btn btn-dark"><img src="images/github-login.png" alt="GitHub Login"> Login with GitHub</a></div></div>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Commits</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
</head>

<body>
    <?php if (!isset($accessToken)) { ?>
        <!-- Render GitHub login section if user is not authenticated -->
        <section class="bg-white dark:bg-gray-900 h-screen flex items-center">
            <div class="py-8 px-4 mx-auto max-w-screen-xl text-center lg:py-16">
                <img src="https://github.githubassets.com/assets/GitHub-Mark-ea2971cee799.png" class="mx-auto w-24 h-24 mb-4" alt="">
                <h1 class="mb-4 text-4xl font-extrabold tracking-normal	 leading-none text-gray-900 md:text-5xl lg:text-6xl dark:text-white">Code Smarter, Not Harder <br> Elevate Your Commits.</h1>
                <p class="mb-8 text-lg  font-normal text-gray-500 lg:text-xl sm:px-16 lg:px-48 dark:text-gray-400">"Commits AI" represents a leap forward in coding and software development, blending the precision of artificial intelligence with the creativity of human developers. It's not just a tool; it's your next-generation partner in building robust, innovative software solutions.
                </p>
                <div class="flex flex-col space-y-4 sm:flex-row sm:justify-center sm:space-y-0">
                    <a href="<?php echo $authUrl ?>" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-900">
                        Get started
                        <svg class="w-3.5 h-3.5 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>
    <?php } else {
        // Include the navigation bar
        include 'sidebar.php';
        // Initialize cURL session
        $ch = curl_init();
        // Set the URL for the API endpoint
        $eventsUrl = 'https://api.github.com/users/enisgjinii/events';
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $eventsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); // Some APIs may require a user-agent header
        // Execute cURL request
        $response = curl_exec($ch);
        // Check for errors
        if ($response === false) {
            echo 'cURL error: ' . curl_error($ch);
            exit;
        }
        // Close cURL session
        curl_close($ch);
        // Decode JSON response
        $events = json_decode($response, true);
        // Filter out only the push events
        $pushEvents = array_filter($events, function ($event) {
            return $event['type'] === 'PushEvent';
        });
        // Extract commits from push events
        $allCommits = array();
        foreach ($pushEvents as $event) {
            foreach ($event['payload']['commits'] as $commit) {
                $commitDetails = array(
                    'repository' => $event['repo']['name'],
                    'sha' => $commit['sha'],
                    'author' => $commit['author']['name'],
                    'date' => date('Y-m-d H:i:s'),
                    'message' => $commit['message']
                );
                $allCommits[] = $commitDetails;
            }
        }
    ?>
        <br><br>
        <div class="container mx-auto">
            <div class="relative overflow-x-auto">
                <table class="w-full border text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                Repository
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Commit ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Author
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Message
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($allCommits as $commit) { ?>
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    <?php echo $commit['repository']; ?>
                                </th>
                                <td class="px-6 py-4">
                                    <?php echo $commit['sha']; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo $commit['author']; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo $commit['date']; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo $commit['message']; ?>
                                </td>
                            </tr><?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php } ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
</body>

</html>