<?php
$stmt = $db->prepare("SELECT DISTINCT Tags.Name FROM ((Feeds INNER JOIN Display USING (FeedID)) INNER JOIN Tags2Feeds USING (FeedID)) INNER JOIN Tags USING (TagID, UserID) WHERE UserID=:UserID");
$stmt->bindValue(":UserID", $user_id, SQLITE3_INTEGER);
$res = $stmt->execute();
$tags_disp = array();
for ($res_it = $res->fetcharray(); $res_it; $res_it = $res->fetcharray()) {
    $tags_disp[] = $res_it['Name'];
}

$tags = array();
for ($i = 0; $i < count($tags_disp); $i++) {
    if (isset($_GET["tag_" . $i])) {
        $tags[] = $_GET["tag_" . $i];
    }
}

if (!empty($tags)) {
    $stmt_tag = "Name=:Tag_0";
    for ($i = 1; $i < count($tags); $i++) {
        $stmt_tag .= " OR Name=:Tag_" . $i;
    }
}
?>
