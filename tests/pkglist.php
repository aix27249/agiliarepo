<html>
<head>
	<meta charset="UTF-8" />
	<title>AgiliaLinux repository index test</title>
</head>
<body>
<?php
require_once '../core/mongo.class.php';

$db = MongoConnection::c()->agiliarepo;


if (isset($_GET['md5'])) {
	$pkg = $db->packages->findOne(['md5' => $_GET['md5']]);
	$pkgfiles = $db->package_files->findOne(['md5' => $_GET['md5']]);
	echo '<h1>' . $pkg['name'] . '</h1>';
	echo '<div class="description">' . ($pkg['description']!=='' ? $pkg['description'] : $pkg['short_description']) . '</div>';

	//echo '<div id="pkgdesc"><pre>' . print_r($pkg, true) . '</pre></div>';
	echo '<div id="filelist"><ol>';
	foreach($pkgfiles['files'] as $file) {
		echo '<li>' . $file . '</li>';
	}
	echo '</ol></div>';

}
else {
	echo '<h1>Пакеты в репозитории</h1>
	<ol>';

	foreach($db->packages->find() as $pkg) {
		echo '<li><a href="?md5=' . $pkg['md5'] . '&amp;action=filelist">' . $pkg['name'] . '-' . $pkg['version'] . '-' . $pkg['build'] . ' (' . $pkg['arch'] . ')</a></li>';
	}
}

?>
</ol>
</body>
</html>

