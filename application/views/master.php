<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>AvreliaFramework: <?php echo $greeting; ?></title>
	<style>
		*     { padding: 0; margin: 0; font-family: sans-serif; }
		body  { background-color: #333; }
		.fade { color: #333; }
		#page { width: 600px; margin: 20px auto; padding: 20px 20px 10px 20px; background-color: #eee; }
		#page { box-shadow: 0 0 6px 0 #000; border-radius: 4px; }
		#page p { margin: 10px 0; }
		#log  { border-top: 1px solid #999; padding-top: 10px; }
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
			$("#newGreeting").click(function() {
				$.get('<?php echo url('/greeting'); ?>', function(data) {
					var h1 = $("h1");
					h1.fadeOut('fast', function() {
						h1.html(data);
						$("head title").html("AvreliaFramework: " + data);
						h1.fadeIn('fast');
					});
				});
				return false;
			});
			$("#toggleLog").click(function() {
				$("#log").slideToggle('fast');
			});
		});
	</script>
</body>
</html>
