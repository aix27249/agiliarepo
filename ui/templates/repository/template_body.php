<div id="header_wrapper" class="wrapper clearfix">
	<div id="header" class="area">
		<?php $page->printBlocks('header');?>
	</div>
</div>
<div id="content_wrapper" class="wrapper clearfix">
	<div class="column_wrapper area clearfix">
		<div id="content" class="subarea">
			<?php $page->printBlocks('content');?>
		</div>
		<div id="sidebar" class="subarea">
			<?php $page->printBLocks('sidebar');?>
		</div>
	</div>
</div>
<div id="footer_wrapper" class="wrapper clearfix">
	<div id="footer" class="area">
		<?php $page->printBlocks('footer');?>
	</div>
</div>
