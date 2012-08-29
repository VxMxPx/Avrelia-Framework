<div id="cdebugPanel">
<div class="navigation">
    <a href="#" class="cnt_log selected">Log</a>
    <a href="#" class="cnt_input">Input</a>
    <a href="#" class="cnt_config">Config</a>
</div>

<div class="content log">
    <?php echo Log::as_html(); ?>
</div>

<div class="content input">
    <div class="spacer">
        $_POST: <?php dump($_POST, false); ?>
    </div>
    <div class="spacer">
        Input::get_request_uri: <?php dump(Input::get_request_uri(), false); ?>
    </div>
</div>

<div class="content config">
    <div class="spacer">
        <?php dump(Cfg::debug(), false); ?>
    </div>
</div>

</div>
