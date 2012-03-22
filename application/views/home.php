<h1><?php echo $greeting; ?></h1><small>(<a href="#" id="newGreeting">New Greeting!</a>)</small>
<p>Looks like your page is working!</p>
<style>
	form label { float: left; clear: both; width: 100%; }
	form input, form textarea { float: left; clear: both; width: 100%; }
</style>
<p>
	<a href="<?php echo Avrelia::WEBSITE; ?>"><small><?php echo Avrelia::WEBSITE; ?></small></a> <small class="fade">|</small>
	<a href="#" id="toggleLog"><small>Toggle Log</small></a>
</p>
<div class="fade" style="display: none;" id="log">
<?php echo Log::Get(2, false); ?></div>
