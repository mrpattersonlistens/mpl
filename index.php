<?php
require_once(dirname(__FILE__) . '/lib/setup.php');

$PAGE->set_url(new url($CFG->wwwroot.'/'));
$PAGE->set_title('Mr. Patterson Listens');

echo $OUTPUT->header();

$html = '<div class="enterbutton">
    <img class="fadeIn" src="/img/mpl_logo.png" height="183" width="183" alt="Enter" />
    <div class="enterlinks">
        <div><a href="'.$CFG->firstpost.'">First</a></div>
        <div><a href="'.$CFG->lastpost.'">Last</a></div>
    </div>
</div>
';

echo $html;
echo $OUTPUT->footer(true);