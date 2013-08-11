<?php
require 'class-twitter.php';
$twitter = new Twitter('consumerkey', 'consumersecret', 'oauthaccesstoken', 'oauthaccesstokensecret');
$tweets = $twitter->fetch_tweets('addeliito', 2);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Twitter Fetcher</title>
</head>
<body>
	<ul id="tweets">
	<?php foreach ($tweets as $tweet) : ?>
		<li><?php echo $tweet->text; ?></li>
	<?php endforeach; ?>
	</ul>
</body>
</html>