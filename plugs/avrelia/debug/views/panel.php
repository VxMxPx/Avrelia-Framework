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
            $_POST: <?php echo dump_r($_POST); ?>
        </div>
        <div class="spacer">
            $_GET: <?php echo dump_r($_GET); ?>
        </div>
        <div class="spacer">
            Path Info: <?php echo dump_r(Input::get_path_info()); ?>
        </div>
    </div>

    <div class="content config">
        <div class="spacer">
            <?php echo Cfg::debug(); ?>
        </div>
    </div>
</div>
