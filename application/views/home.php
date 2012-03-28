<h1><?php echo $greeting; ?></h1><small>(<a href="#" id="newGreeting">Ajax Greeting!</a>)</small>

<h2>So, what to do next?</h2>
<p>
	Start editing files in <code>application</code> folder. If you want to change
	configurations see <code>config/main.php</code>.<br /><br />
	The controllers, models and views are all in standard folders with same names.
	Additional to that there's also <code>util</code> folder, for all your costume classes and functions.<br /><br />
	All your public files are stored in <code>public</code> folder, if something doesn't
	show up, then check <code>.htaccess</code> file.<br /><br />
	For more information and latest version,
	visit <a href="https://github.com/VxMxPx/Avrelia-Framework">Avrelia&nbsp;Framework&nbsp;@&nbsp;GitHub</a>.<br />
</p>

<h2>Need CLI access?</h2>
<p>No problem. Just cd to application folder and run <code>./dot help</code> and follow instructions.</p>

<h2>What just happened?</h2>
<p>
	If you're curious what's going on under the hood, <a href="#log" id="not_toggleLog">see the log</a>.
</p>

<div id="log">
	<a name="log"></a>
	<?php echo Log::Get(2, false); ?>
</div>
