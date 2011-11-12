<?php

namespace jolt;

/**
 * Jolt Exception
 */
$type = get_class($exception);
$body = $exception->getMessage();
$body = "<p>$body</p>";
$a = ucfirst(english_article($type));
$main = "$a $type has occurred";
$_line = $exception->getLine();
$_file = $exception->getFile();

if(strtolower($_file) === strtolower(script))
	$location = '';
else
	$location = "<h3><span class='label'>File</span> `$_file` <span class='label'>Line</span> $_line</h3>";

if(defined('jolt\\root')) {
	$root = root;
	$hostinfo = "<h3><span class='label'>Host</span> `$_SERVER[HTTP_HOST]`</h3><h3><span class='label'>Document Root</span> `$root`</h3>";
}
else
	$hostinfo = "<h3>No matching host</h3>";

while($exception = $exception->getPrevious()) {
	if(is_null($exception))
		break;
	$type = get_class($exception);
	$a = english_article($type);
	$sub = "Which started out as $a $type";
	$line = $exception->getLine();
	$file = $exception->getFile();
	$location = "<h3><span class='label'>File</span> `$file` <span class='label'>Line</span> $line</h3>";
	$body .= '<div class="prev">'."<h2>$sub</h2>$location<p>" . $exception->getMessage() . '</p></div>';
}

$body = preg_replace('/\\s\\[\\<a.*>]/', '', $hostinfo.$location.$body);
$body = preg_replace('/`(.*?)`/', '<code>$1</code>', $body);

?>
<!doctype html>
<html>
	<head>
		<title>Jolt &bull; <?php echo $type ?></title>
		<style>
			body {
				cursor: default; font: 14px sans-serif; background: #200; 
				color: #fff; padding: 30px;
			}
			a {color: #f88;}
			h1 {font-size: 22px; margin-top: 0;}
			h2 {font-size: 18px; margin-top: 0; color: #c00; text-shadow: 1px 1px 3px #000;}
			h3 {font-size: 14px; margin-top: 0; color: #dc0; font-weight: normal;}
			.prev {
				margin: 30px 0; background: #200; padding: 30px 30px;
				box-shadow: inset 0 0 6px #422;
				border-radius: 2px;
			}
			.prev h2 {margin-top: 0;}
			.prev p:last-child {margin-bottom: 0;}
			.label {
				border-radius: 4px; background: #dc0; color: #100; font-size: 80%;
				font-weight: bold; margin: 0 4px; padding: 3px 7px 2px;
				box-shadow: inset 0 10px 20px -10px #fec, inset 0 -10px 20px -10px #ca0;
			}
			.label:first-child {margin-left: 0;}
			p, h2 {margin-top: 24px;}
			p code {
				background: #a99; color: #200; padding: 3px 6px; margin: 0 4px;
				box-shadow: inset 0 0 5px #100; font-size: 90%; text-shadow: 1px 1px 1px #baa;
			}
		</style>
	</head>
	<body>
		<h1>Jolt &bull; System Notice</h1>
		<h2><?php echo $main ?></h2>
		<?php echo $body ?>
	</body>
</html>