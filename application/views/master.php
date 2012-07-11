<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>AvreliaFramework: <?php echo strip_tags($greeting); ?></title>
	<style>
		*       { padding: 0; margin: 0; line-height: 1.5em; }
		::selection 	 { background-color: #acf; color: #006; }
		::-moz-selection { background-color: #acf; color: #006; }
		body    { background-color: #123; font-size: 14px; font-family: sans-serif; }
		h1, h2  { font-family: serif; font-weight: bold; }
		h2      { padding-top: 20px; }
		a       { color: #4477cc; padding: 2px; }
		a:hover { background-color: #4477cc; color: #fff; text-decoration: none; border-radius: 4px; }
		code    { font-family: monospace; background-color: #eee; outline: 1px solid #e0e0e0; color: #444; }
		.fade   { color: #666; font-style: italic; }
		#page   { width: 600px; margin: 20px auto; padding: 20px; background-color: #fff; border: 2px solid #ddd; }
		#page   { box-shadow: 0 0 6px 0 #000; border-radius: 4px; }
		#page p { }
		#log    { padding-top: 10px; margin-top: 5px; }
		#log > div { box-shadow: 0 0 4px #000; border-radius: 4px; }
		#log > div > div:first-child { border-radius: 4px 4px 0 0; }
		#log > div > div:last-child { border-radius: 0 0 4px 4px; }
	</style>
	<?php cHTML::GetHeaders(); ?>
</head>
<body>
	<div id="page">
		<?php View::Region('main'); ?>
	</div> <!-- end: page -->
	<?php cHTML::GetFooters(); ?>
	<script>
		$(document).ready(function() {
			var h1 = $('h1'),
				intro = '<span class="fade">Avrelia Framework:</span> ',
				headTitle = $('head title');

			$("#newGreeting").click(function(e) {
				$.get('<?php echo url('/greeting'); ?>', function(data) {
					h1.fadeOut('normal', function() {
						h1.html(intro + data);
						headTitle.html("AvreliaFramework: " + data);
						h1.fadeIn('normal');
					});
				});
				e.preventDefault();
			});

			$("#toggleLog").on('click', cDebug.panelShow);
		});
	</script>
</body>
</html>
