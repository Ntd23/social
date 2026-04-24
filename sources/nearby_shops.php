<?php
$_explore_nearby_url = Wo_SeoLink('index.php?link1=explore-nearby');
header('HTTP/1.1 301 Moved Permanently');
header("Location: " . $_explore_nearby_url);
exit();
