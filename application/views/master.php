<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AvreliaFramework v2</title>
    <style>
        *       { padding: 0; margin: 0; line-height: 1.5em; }
        ::selection      { background-color: #47c; color: #eee; }
        ::-moz-selection { background-color: #47c; color: #eee; }
        body    { background-color: #fafaf2; font-size: 16px; font-family: "Sans", sans-serif; }
        h1, h2  { font-family: "Serif", serif; font-weight: normal; }
        h2      { padding-top: 30px; padding-bottom: 4px; margin-bottom: 4px; border-bottom: 1px dotted #ddd; }
        a       { color: #47c; padding: 2px; }
        a:hover { background-color: #47c; color: #fff; text-decoration: none; border-radius: 4px; }
        code    { font-family: "Monospace", monospace; background-color: #f2f2f2; color: #224; }
        .fade   { color: #666; font-style: italic; }
        #page   { width: 800px; margin: 20px auto; padding: 20px; }
        #page p { }
        #log    { padding-top: 10px; margin-top: 5px; }
        #log > div { box-shadow: 0 0 4px #000; border-radius: 4px; }
        #log > div > div:first-child { border-radius: 4px 4px 0 0; }
        #log > div > div:last-child  { border-radius: 0 0 4px 4px; }
    </style>
    <?php HTML::get_headers(); ?>
</head>
<body>
    <div id="page">
        <?php View::region('main'); ?>
    </div> <!-- end: page -->
    <?php HTML::get_footers(); ?>
    <script>
        $('toggleLog').on('click', Avrelia.Plug.Debug.show);
    </script>
</body>
</html>
