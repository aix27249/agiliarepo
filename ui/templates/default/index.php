<!DOCTYPE html>
<html itemscope itemtype="http://schema.org/WebPage">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="<?php echo SiteSettings::$favicon;?>" type="image/vnd.microsoft.icon" />
		<link rel="icon" href="<?php echo SiteSettings::$favicon_png;?>" type="image/png" />
		<meta content="<?php echo $page->title;?>" about="/" property="dc:title" />
		<meta name="viewport" content="width=device-width,initial-scale=0.6" />
		<link rel="shortlink" href="<?php echo $page->canonical_url;?>" />
		<link rel="canonical" href="<?php echo $page->canonical_url;?>" />
		<title><?php echo $page->title;?></title>
		<?php $page->loadScripts(); ?>
		<style type="text/css" media="all"><?php $page->loadStyles(); ?></style>
		<?php $page->loadHeaderItems(); ?>
	</head>
	<body>
		<?php echo Template::renderBody($page);?>
	</body>
</html>

