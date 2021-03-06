<aside id="sidenav" class="sidenav">
<nav class="topnav">
<span class="right">
<?php
$link = "/steins-feed/index.php";
$link .= "?user=" . $user;
foreach ($langs as $lang_ct => $lang_it) {
    $link .= "&lang_" . $lang_ct . "=" . $lang_it;
}
foreach ($tags as $tag_ct => $tag_it) {
    $link .= "&tag_" . $tag_ct . "=" . $tag_it;
}
$link .= "&page=0";
$link .= "&feed=" . $feed;
$link .= "&clf=" . $clf;
$link .= "&timeunit=" . $timeunit;
?>
<a href="<?php echo $link;?>"><i class="material-icons">home</i></a>
<a href="/steins-feed/statistics.php?user=<?php echo $user;?>" target="_blank"><i class="material-icons">insert_chart_outlined</i></a>
<a href="/steins-feed/settings.php?user=<?php echo $user;?>" target="_blank"><i class="material-icons">settings</i></a>
<i onclick="close_menu()" class="material-icons">
close
</i>
</span>
</nav>
<script src="/steins-feed/js/toggle_checkbox.js" defer></script>
<section>
<form action="/steins-feed/index.php" method="get">
<?php if (!empty($tags_disp)):?>
<p>
<fieldset id="input_tag">
<legend>Tags</legend>
<label>
<?php if (count($tags) >= count($tags_disp)):?>
<input type="checkbox" onclick="toggle_checkbox('input_tag')" checked>
<?php else:?>
<input type="checkbox" onclick="toggle_checkbox('input_tag')">
<?php endif;?>
ALL TAGS
<br>
</label>
<?php foreach ($tags_disp as $tag_ct => $tag_it):?>
<label>
<?php   if (in_array($tag_it, $tags)):?>
<input name="tag_<?php echo $tag_ct;?>" onclick="prove_checkbox('input_tag')" value="<?php echo $tag_it;?>" type="checkbox" checked>
<?php   else:?>
<input name="tag_<?php echo $tag_ct;?>" onclick="prove_checkbox('input_tag')" value="<?php echo $tag_it;?>" type="checkbox">
<?php
        endif;
    echo $tag_it . PHP_EOL;
?>
</label>
<br>
<?php endforeach;?>
</fieldset>
</p>
<?php endif;?>
<?php if (!empty($langs_disp)):?>
<p>
<fieldset id="input_lang">
<legend>Languages</legend>
<label>
<?php if (count($langs) >= count($langs_disp)):?>
<input type="checkbox" onclick="toggle_checkbox('input_lang')" checked>
<?php else:?>
<input type="checkbox" onclick="toggle_checkbox('input_lang')">
<?php endif?>
ALL LANGUAGES
<br>
</label>
<?php foreach ($langs_disp as $lang_ct => $lang_it):?>
<label>
<?php   if (in_array($lang_it, $langs)):?>
<input name="lang_<?php echo $lang_ct;?>" onclick="prove_checkbox('input_lang')" value="<?php echo $lang_it;?>" type="checkbox" checked>
<?php   else:?>
<input name="lang_<?php echo $lang_ct;?>" onclick="prove_checkbox('input_lang')" value="<?php echo $lang_it;?>" type="checkbox">
<?php
        endif;
        echo $lang_it;
?>
</label>
<br>
<?php endforeach;?>
</fieldset>
</p>
<?php endif;?>
<p>
<fieldset>
<legend>Feed</legend>
<?php
$feed_dict = array("Full", "Magic", "Surprise");
$clf_dict = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/steins-feed/json/steins_magic.json"), true);
$clf_key = key($clf_dict);
$magic_exists = file_exists($_SERVER['DOCUMENT_ROOT'] . "/steins-feed/$user_id/$clf_key/clfs.pickle");
foreach($feed_dict as $key):
?>
<label>
<?php
    $feed_input = '<input name="feed" value="' . $key . '" type="radio"';
    if ($key == $feed) {
        $feed_input .= ' checked';
    }
    if (!$magic_exists) {
        $feed_input .= ' disabled';
    }
    $feed_input .= '>';
    echo $feed_input . PHP_EOL;
    echo $key . PHP_EOL;
?>
</label>
<br>
<?php endforeach;?>
</fieldset>
</p>
<p>
<fieldset>
<input name="user" value="<?php echo $user;?>" type="hidden">
<input name="page" value="<?php echo $page;?>" type="hidden">
<input name="timeunit" value="<?php echo $timeunit;?>" type="hidden">
<input name="new_timeunit" value="Day" type="submit">
<input name="new_timeunit" value="Week" type="submit">
<input name="new_timeunit" value="Month" type="submit">
</fieldset>
</p>
</form>
</section>
<hr>
<form action="/steins-feed/feed.php" method="get">
<p>
<fieldset>
<legend>Feed</legend>
<select name="feed">
<?php
$stmt = $db->prepare("SELECT * FROM Feeds Order BY Title COLLATE NOCASE");
$res = $stmt->execute();
$feeds_all = array();
for ($row_it = $res->fetcharray(); $row_it; $row_it = $res->fetcharray()) {
    $feeds_all[$row_it['FeedID']] = $row_it['Title'];
}

foreach ($feeds_all as $feed_ct => $feed_it):
?>
<option value="<?php echo $feed_ct;?>"><?php echo $feed_it;?></option>
<?php endforeach;?>
</select>
<input name="user" value="<?php echo $user;?>" type="hidden">
<input type="submit" value="Edit">
</fieldset>
</p>
</form>
<form action="/steins-feed/tag.php" method="get">
<p>
<fieldset>
<legend>Tag</legend>
<select name="tag">
<?php
$stmt = $db->prepare("SELECT * FROM Tags WHERE UserID=:UserID ORDER BY Name COLLATE NOCASE");
$stmt->bindValue(":UserID", $user_id, SQLITE3_INTEGER);
$res = $stmt->execute();
$tags_all = array();
for ($row_it = $res->fetcharray(); $row_it; $row_it = $res->fetcharray()) {
    $tags_all[$row_it['TagID']] = $row_it['Name'];
}

foreach ($tags_all as $tag_ct => $tag_it):?>
<option value="<?php echo $tag_ct;?>"><?php echo $tag_it;?></option>
<?php endforeach;?>
</select>
<input name="user" value="<?php echo $user;?>" type="hidden">
<input type="submit" value="Edit">
</fieldset>
</p>
</form>
</aside>
