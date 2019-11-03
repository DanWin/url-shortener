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
	$db=new PDO("mysql:host='.DB_HOST.';dbname=" . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING, PDO::ATTR_PERSISTENT=>true]);
}catch(PDOException $e){
}
if(empty($_GET['id'])){
	echo '<!DOCTYPE html><html><head>';
	echo '<title>URL-Shortener/Redirector</title>';
	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
	echo '<meta name="author" content="Daniel Winzen">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '</head><body>';
	echo '<h1>URL-Shortener/Redirector</h1>';
	echo '<p>Shorten a URL or strip referrers by redirecting via '.CANONICAL_URL.'?r=LINK</p>';
	exit;
	if(!isset($db)){
		echo '<p><b style="color:red">ERROR:</b> No database connection!</p></div>';
		echo '</body></html>';
		exit;
	}
	echo "<form action=\"$_SERVER[SCRIPT_NAME]\" method=\"POST\">";
	echo "<p>Link: <br><input name=\"addr\" size=\"30\" placeholder=\"$_SERVER[HTTP_HOST]\" value=\"";
	if(!empty($_POST['addr'])){
		echo htmlspecialchars($_POST['addr']);
	}
	echo '" required></p>';
	echo '<input type="submit" name="action" value="Shorten"></form><br>';
	echo "<form action=\"$_SERVER[SCRIPT_NAME]\" method=\"POST\">";
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
			echo '<p>Short link is: <a href="'.CANONICAL_URL."?id=$_REQUEST[info]\" rel=\"nofollow\">".CANONICAL_URL."?id=$_REQUEST[info]</a></p>";
			echo "<p>Redirects to: <a href=\"$url\" rel=\"nofollow\">$url</a></p>";
		}else{
			echo '<p>Sorry, this redirect doesn\'t exist.</p>';
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
			echo '<p style="color:red">ERROR: Invalid address given.</p>';
		}else{
			$id=$db->query("SELECT COUNT(*) FROM link;")->fetch(PDO::FETCH_NUM);
			$id=$id[0]+1;
			$db->prepare("INSERT INTO link (id, url) VALUES (?, ?);")->execute([$id, $_POST['addr']]);
			echo '<p>Your link is: <a href="'.CANONICAL_URL."?id=$id\" rel=\"nofollow\">".CANONICAL_URL."?id=$id</a></p>";
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

function redirect($url){
	preg_match('~^(.*)://~', $url, $match);
	$url=preg_replace('~^(.*)://~', '', $url);
	$escaped=htmlspecialchars($url);
	if(isset($match[1]) && ($match[1]==='http' || $match[1]==='https')){
		header("Refresh: 0; URL=$match[0]$url");
		echo '<!DOCTYPE html>';
		echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; url=$match[0]$escaped\">";
		echo '<meta name="robots" content="noindex, nofollow"></head><body>';
		echo "<p>Redirecting to: <a href=\"$match[0]$escaped\" rel=\"nofollow\">$match[0]$escaped</a>.</p>";
		echo '</body></html>';
	}else{
		if(!isset($match[0])){
			$match[0]='';
		}
		echo '<!DOCTYPE html>';
		echo '<html><head><meta name="robots" content="noindex, nofollow"></head><body>';
		echo "<p>Non-http link requested: <a href=\"$match[0]$escaped\" rel=\"nofollow\">$match[0]$escaped</a>.</p>";
		echo "<p>If it's not working, try this one: <a href=\"http://$escaped\" rel=\"nofollow\">http://$escaped</a>.</p>";
		echo '</body></html>';
	}
	exit;
}
