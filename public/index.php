<?php
$config = require __DIR__ . '/../bootstrap.php';
$nexmo = new \Nexmo\Client(new \Nexmo\Client\Credentials\Basic($config['nexmo']['key'], $config['nexmo']['secret']));
$mongo = new \MongoDB\Client($config['mongo']['uri']);
$db = $mongo->selectDatabase($config['mongo']['database']);

session_start();

if (isset($_GET['logout'])) {
    $_SESSION['user'] = null;
    $_SESSION['verification'] = null;
}

if (isset($_SESSION['user'])) {
    //find groups user belongs to
    $groups = $db->selectCollection('users')->find([
        'user' => $_SESSION['user']
    ])->toArray();
    
    if (!$groups) {
        $error = 'User has no groups.';
        return;
    }

    //select a group
    if (!isset($_GET['group'])) {
        $selected = reset($groups);
    } else {
        foreach ($groups as $group) {
            if ($group['group'] == $_GET['group']) {
                $selected = $group;
                break;
            }
        }
    }
        
    //find all messages the user's been sent
    $query = [
        'group' => $selected['group'],
        '$or' => [
            ['user' => $_SESSION['user']],
            ['sends.user' => $_SESSION['user']]
        ]
    ];

    $options = [
        'sort' => ['date' => -1]
    ];

    $messages = $db->selectCollection('logs')->find($query, $options);

    //simple template rendering
    ob_start();
    include __DIR__ . '/messages.phtml';
    $content = ob_get_clean();
} elseif (isset($_SESSION['verification']) and isset($_POST['code'])) {
    try {
        $verificaton = unserialize($_SESSION['verification']);
        $nexmo->verify()->check($verificaton, $_POST['code']);
        $_SESSION['user'] = $verificaton->getNumber();
        header('Location: /');
        return;
    } catch (\Nexmo\Client\Exception\Request $e) {
        $error = $e->getMessage();
        $content = file_get_contents(__DIR__ . '/code.html');
    }
} elseif (isset($_SESSION['verification'])) {
    $content = file_get_contents(__DIR__ . '/code.html');
} elseif (isset($_POST['number'])) {
    //start the verification
    try {
        $verificaton = $nexmo->verify()->start([
            'number' => $_POST['number'],
            'brand'  => 'GroupChat'
        ]);
        $_SESSION['verification'] = serialize($verificaton);
        header('Location: /');
        return;
    } catch (\Nexmo\Client\Exception\Request $e) {
        $error = $e->getMessage();
        $content = file_get_contents(__DIR__ . '/login.html');
    }
} else {
    $content = file_get_contents(__DIR__ . '/login.html');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Group Chat Tutorial</title>

    <!-- Bootstrap core CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="container">
    <div class="row">
        <h1>Group Chat Tutorial</h1>
        <p class="lead">
            This is a simple tutorial of Nexmo and the PHP Client Library<br>
            Here you can find a log of your group messages.
        </p>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $error ?>
    </div>
    <?php endif; ?>

    <?php echo $content ?>
</div><!-- /.container -->



<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</body>
</html>

