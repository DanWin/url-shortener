<?php
const DB_HOST='localhost';
const DB_NAME='links';
const DB_USER='links';
const DB_PASS='MY_PASSWORD';
const DB_CHARSET='utf8mb4';
const CANONICAL_URL='https://danwin1210.me/url.php';

header('Content-Type: text/html; charset=UTF-8');
if($_SERVER['REQUEST_METHOD']==='HEAD'){
	exit; // headers sent, no further processing needed
}
if(!empty($_REQUEST['r'])){
	redirect($_REQUEST['r']);
}
try{
	$db=new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>true]);
}catch(PDOException $e){
}
if(empty($_GET['id'])){
	$style = '.red{color:red}';
	send_headers([$style]);
	echo '<!DOCTYPE html><html lang="en"><head>';
	echo '<title>URL-Shortener/Redirector</title>';
	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
	echo '<meta name="author" content="Daniel Winzen">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '</head><body>';
	echo '<h1>URL-Shortener/Redirector</h1>';
	echo '<p>Shorten a URL or strip referrers by redirecting via '.CANONICAL_URL.'?r=LINK</p>';
	if(!isset($db)){
		echo '<p><b class="red">ERROR:</b> No database connection!</p></div>';
		echo '</body></html>';
		exit;
	}
	echo '<form action="'.htmlspecialchars($_SERVER['SCRIPT_NAME']).'" method="POST">';
	echo '<p>Link: <br><input name="addr" size="30" placeholder="'.htmlspecialchars($_SERVER['HTTP_HOST']).'" value="';
	if(!empty($_POST['addr'])){
		echo htmlspecialchars($_POST['addr']);
	}
	echo '" required></p>';
	echo '<input type="submit" name="action" value="Shorten"></form><br>';
	echo '<form action="'.htmlspecialchars($_SERVER['SCRIPT_NAME']).'" method="POST">';
	echo '<p>Show info of shortlink-ID: <br><input name="info" type="number" size="10" value="';
	if(!empty($_POST['info'])){
		echo htmlspecialchars($_POST['info']);
	}else{
		echo '1';
	}
	echo '" required></p>';
	echo '<input type="submit" name="action" value="Show"></form><br>';
	if(!empty($_REQUEST['info'])){
		$stmt=$db->prepare("SELECT url FROM link WHERE id=?;");
		$stmt->execute([$_REQUEST['info']]);
		if($url=$stmt->fetch(PDO::FETCH_ASSOC)){
			$url=$url['url'];
			echo '<p role="alert">Short link is: <a href="'.CANONICAL_URL."?id=$_REQUEST[info]\" rel=\"nofollow\">".CANONICAL_URL."?id=$_REQUEST[info]</a></p>";
			echo "<p role='alert'>Redirects to: <a href=\"$url\" rel=\"nofollow\">$url</a></p>";
		}else{
			echo '<p role="alert">Sorry, this redirect doesn\'t exist.</p>';
		}
	}elseif($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['addr'])){
		if(!(
			// 1. all explicit schemes with whatever xxx://yyyyyyy
			preg_match('~^(\w*://[^\s<>]+)$~i', $_POST['addr'])
			// 2. valid URLs without scheme:
		||	preg_match('~^((?:[^\s<>]*:[^\s<>]*@)?[a-z0-9\-]+(?:\.[a-z0-9\-]+)+(?::\d*)?/[^\s<>]*)(?![^<>]*>)$~i', $_POST['addr'])
		||	preg_match('~^((?:[^\s<>]*:[^\s<>]*@)?[a-z0-9\-]+(?:\.[a-z0-9\-]+)+:\d+)(?![^<>]*>)$~i', $_POST['addr'])
		||	preg_match('~^([^\s<>]*:[^\s<>]*@[a-z0-9\-]+(?:\.[a-z0-9\-]+)+(?::\d+)?)(?![^<>]*>)$~i', $_POST['addr'])
			// 3. likely servers without any hints but not filenames like *.rar zip exe etc.
		||	preg_match('~^((?:[a-z0-9\-]+\.)*[a-z2-7]{16}\.onion)(?![^<>]*>)$~i', $_POST['addr'])// *.onion
		)
		){
			echo '<p class="red">ERROR: Invalid address given.</p>';
		}else{
			$id=$db->query("SELECT COUNT(*) FROM link;")->fetch(PDO::FETCH_NUM);
			$id=$id[0]+1;
			$db->prepare("INSERT INTO link (id, url) VALUES (?, ?);")->execute([$id, $_POST['addr']]);
			echo '<p role="alert">Your link is: <a href="'.CANONICAL_URL."?id=$id\" rel=\"nofollow\">".CANONICAL_URL."?id=$id</a></p>";
		}
	}
	echo '</body></html>';
}else{
	if(!isset($db)){
		die('No database connection!');
	}
	settype($_GET['id'], 'int');
	$stmt=$db->prepare("SELECT url FROM link WHERE id=?;");
	$stmt->execute([$_GET['id']]);
	if($url=$stmt->fetch(PDO::FETCH_ASSOC)){
		$url=$url['url'];
	}
	redirect($url);
}

function redirect(string $url){
	preg_match('~^(.*)://~', $url, $match);
	$url=preg_replace('~^(.*)://~', '', $url);
	$escaped=htmlspecialchars($url);
	send_headers();
	if(isset($match[1]) && ($match[1]==='http' || $match[1]==='https')){
		header("Refresh: 0; URL=$match[0]$url");
		echo '<!DOCTYPE html>';
		echo "<html lang='en'><head><meta http-equiv=\"Refresh\" content=\"0; url=$match[0]$escaped\">";
		echo '<meta name="robots" content="noindex, nofollow"></head><body>';
		echo "<p>Redirecting to: <a href=\"$match[0]$escaped\" rel=\"noopener nofollow\">$match[0]$escaped</a>.</p>";
		echo '</body></html>';
	}else{
		if(!isset($match[0])){
			$match[0]='';
		}
		echo '<!DOCTYPE html>';
		echo '<html lang="en"><head><meta name="robots" content="noindex, nofollow"></head><body>';
		echo "<p>Non-http link requested: <a href=\"$match[0]$escaped\" rel=\"noopener nofollow\">$match[0]$escaped</a>.</p>";
		echo "<p>If it's not working, try this one: <a href=\"http://$escaped\" rel=\"noopener nofollow\">http://$escaped</a>.</p>";
		echo '</body></html>';
	}
	exit;
}

function send_headers(array $styles = []){
	header('Content-Type: text/html; charset=UTF-8');
	header('Pragma: no-cache');
	header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
	header('Expires: 0');
	header('Referrer-Policy: no-referrer');
	header("Permissions-Policy: accelerometer 'none'; ambient-light-sensor 'none'; autoplay 'none'; battery 'none'; camera 'none'; cross-origin-isolated 'none'; display-capture 'none'; document-domain 'none'; encrypted-media 'none'; geolocation 'none'; fullscreen 'none'; execution-while-not-rendered 'none'; execution-while-out-of-viewport 'none'; gyroscope 'none'; magnetometer 'none'; microphone 'none'; midi 'none'; navigation-override 'none'; payment 'none'; picture-in-picture 'none'; publickey-credentials-get 'none'; screen-wake-lock 'none'; sync-xhr 'none'; usb 'none'; web-share 'none'; xr-spatial-tracking 'none'; clipboard-read 'none'; clipboard-write 'none'; gamepad 'none'; speaker-selection 'none'; conversion-measurement 'none'; focus-without-user-activation 'none'; hid 'none'; idle-detection 'none'; sync-script 'none'; vertical-scroll 'none'; serial 'none'; trust-token-redemption 'none';");
	$style_hashes = '';
	foreach($styles as $style) {
		$style_hashes .= " 'sha256-".base64_encode(hash('sha256', $style, true))."'";
	}
	header("Content-Security-Policy: base-uri 'self'; default-src 'none'; form-action 'self'; frame-ancestors 'none'; style-src $style_hashes");
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: deny');
	header('X-XSS-Protection: 1; mode=block');
	if($_SERVER['REQUEST_METHOD'] === 'HEAD'){
		exit; // headers sent, no further processing needed
	}
}
