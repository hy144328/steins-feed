<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/steins-feed/php_include/steins_db.php";
$db = steins_db(SQLITE3_OPEN_READONLY);
$db->exec("BEGIN");

include_once $_SERVER['DOCUMENT_ROOT'] . "/steins-feed/php_include/clf.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/steins-feed/php_include/feed.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/steins-feed/php_include/langs.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/steins-feed/php_include/page.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/steins-feed/php_include/tags.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/steins-feed/php_include/timeunit.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/steins-feed/php_include/user.php";

// Last updated.
$stmt = $db->prepare("SELECT MIN(Updated) From Feeds");
$res = $stmt->execute()->fetcharray();
$last_updated = $res[0];

// Languages.
$word_tables = array();
foreach (glob("$user_id/$clf/*_words.json") as $f_it) {
    $lang_it = basename($f_it, "_words.json");
    $table_it = json_decode(file_get_contents($f_it), true);
    $word_tables[$lang_it] = $table_it;
}
$feed_tables = array();
foreach (glob("$user_id/$clf/*_feeds.json") as $f_it) {
    $lang_it = basename($f_it, "_feeds.json");
    $table_it = json_decode(file_get_contents($f_it), true);
    $feed_tables[$lang_it] = $table_it;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="/steins-feed/css/index.css" rel="stylesheet" type="text/css">
<link href="/steins-feed/css/topnav.css" rel="stylesheet" type="text/css">
<link href="/steins-feed/css/sidenav.css" rel="stylesheet" type="text/css">
<link href="/steins-feed/favicon.ico" rel="shortcut icon" type="image/png">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<title>Stein's Feed</title>
<script>
var user="<?php echo $user;?>";
var clf="<?php echo $clf;?>";
</script>
</head>
<body>
<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/steins-feed/php_include/topnav.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/steins-feed/php_include/sidenav.php";
?>
<main>
<header>
<h1><?php echo $user;?></h1>
<p>
Last updated: <?php echo $last_updated, " GMT";?>.
</p>
</header>
<hr>
<h2>Most favorite words</h2>
<table>
<tr>
<?php foreach ($word_tables as $lang_it => $table_it):?>
<th><?php echo $lang_it;?></th>
<?php endforeach;?>
</tr>
<td colspan=<?php echo count($word_tables);?>><hr></td>
<?php for ($i = 0; $i < 10; $i++):?>
<tr>
<?php foreach ($word_tables as $table_it):?>
<td><?php
        $idx = key(array_slice($table_it, -$i - 1, 1, true));
        echo $idx;
?> (<?php printf("%.2f", 2. * $table_it[$idx] - 1.);?>)</td>
<?php endforeach;?>
</tr>
<?php endfor;?>
</table>
<h2>Least favorite words</h2>
<table>
<tr>
<?php foreach ($word_tables as $lang_it => $table_it):?>
<th><?php echo $lang_it;?></th>
<?php endforeach;?>
</tr>
<td colspan=<?php echo count($word_tables);?>><hr></td>
<?php for ($i = 0; $i < 10; $i++):?>
<tr>
<?php foreach ($word_tables as $table_it):?>
<td><?php
        $idx = key(array_slice($table_it, $i, 1, true));
        echo $idx;
?> (<?php printf("%.2f", 2. * $table_it[$idx] - 1.);?>)</td>
<?php endforeach;?>
</tr>
<?php endfor;?>
</table>
<hr>
<h2>Most recommended feeds</h2>
<table>
<tr>
<?php foreach ($feed_tables as $lang_it => $table_it):?>
<th><?php echo $lang_it;?></th>
<?php endforeach;?>
</tr>
<td colspan=<?php echo count($feed_tables);?>><hr></td>
<?php for ($i = 0; $i < 10; $i++):?>
<tr>
<?php foreach ($feed_tables as $table_it):?>
<td><?php
        $idx = key(array_slice($table_it, -$i - 1, 1, true));
        $stmt = $db->prepare("SELECT Title FROM Feeds WHERE FeedID=:FeedID");
        $stmt->bindValue(":FeedID", $idx);
        echo $stmt->execute()->fetcharray()['Title'];
?> (<?php printf("%.2f", 2. * $table_it[$idx] - 1.);?>)</td>
<?php endforeach;?>
</tr>
<?php endfor;?>
</table>
<h2>Least recommended feeds</h2>
<table>
<tr>
<?php foreach ($feed_tables as $lang_it => $table_it):?>
<th><?php echo $lang_it;?></th>
<?php endforeach;?>
</tr>
<td colspan=<?php echo count($feed_tables);?>><hr></td>
<?php for ($i = 0; $i < 10; $i++):?>
<tr>
<?php foreach ($feed_tables as &$table_it):?>
<td><?php
        $idx = key(array_slice($table_it, $i, 1, true));
        $stmt = $db->prepare("SELECT Title FROM Feeds WHERE FeedID=:FeedID");
        $stmt->bindValue(":FeedID", $idx);
        echo $stmt->execute()->fetcharray()['Title'];
?> (<?php printf("%.2f", 2. * $table_it[$idx] - 1.);?>)</td>
<?php endforeach;?>
</tr>
<?php endfor;?>
</table>
<hr>
</main>
</body>
</html>
<?php
$db->exec("END");
$db->close();
?>
