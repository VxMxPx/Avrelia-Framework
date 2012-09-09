--TEST--
system/core/str
--FILE--
<?php
include('../../../init.php');

dump(Str::tokenize(
        'a href="http://google.com" class="blue small" title="This is google!"',
        CHAR_SPACE, CHAR_QUOTE), false, false);

dump(Str::tokenize(
        '(one,two,three),(four,five,six),()',
        ',', array('(', ')')), false, false);

dump(Str::tokenize(
        'hello "world how are you?',
        ' ', CHAR_QUOTE), false, false);

dump(Str::tokenize(
        'hi\ there\ how\ \"are you\ today\"?',
        ' ', CHAR_QUOTE), false, false);

dump(Str::tokenize(
        '<?php script1(); ?>,<?php script2(); ?>',
        ',', array('<?php', '?>')), false, false);

dump(Str::tokenize(
        'hello!"this is "some quote"!"and some "!"more',
        '!"', CHAR_QUOTE), false, false);

dump(Str::tokenize(
        'home\user\Documents',
        CHAR_BACKSLASH, CHAR_QUOTE), false, false);
?>
--EXPECTF--
array: Array
(
    [0] => a
    [1] => href="http://google.com"
    [2] => class="blue small"
    [3] => title="This is google!"
)


array: Array
(
    [0] => (one,two,three)
    [1] => (four,five,six)
    [2] => ()
)


array: Array
(
    [0] => hello
    [1] => "world how are you?
)


array: Array
(
    [0] => hi\ there\ how\ \"are
    [1] => you\ today\"?
)


array: Array
(
    [0] => <?php script1(); ?>
    [1] => <?php script2(); ?>
)


array: Array
(
    [0] => hello
    [1] => this is "some quote"
    [2] => and some "!"more
)

%s
%s %d
%s

boolean: false
