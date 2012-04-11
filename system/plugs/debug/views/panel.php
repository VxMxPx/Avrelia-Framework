<div id="cdebugPanel">
<div class="navigation">
	<a href="#" class="cnt_log selected">Log</a>
	<a href="#" class="cnt_input">Input</a>
	<a href="#" class="cnt_config">Config</a>
</div>

<div class="content log">
	<?php echo Log::Get(2); ?>
</div>

<div class="content input">
	<div class="spacer">
		$_POST: <?php dumpVar($_POST, false); ?>
	</div>
	<div class="spacer">
		Input::GetRequestUri: <?php dumpVar(Input::GetRequestUri(), false); ?>
	</div>
</div>

<div class="content config">
	<div class="spacer">
		<?php dumpVar(Cfg::Debug(), false); ?>
	</div>
</div>

</div>
