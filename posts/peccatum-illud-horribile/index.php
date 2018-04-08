<?php
require_once('../../lib/setup.php');

$PAGE->set_url(new url($CFG->postroot.'/peccatum-illud-horribile/'));
$PAGE->set_title('Mr. Patterson Listens');
$PAGE->add_head_resource(array('rel' => 'stylesheet', 'href' => 'https://fonts.googleapis.com/css?family=Sue+Ellen+Francisco'));
$PAGE->add_head_resource(array('rel' => 'stylesheet', 'href' => 'https://fonts.googleapis.com/css?family=East+Sea+Dokdo'));
$PAGE->add_head_resource(array('rel' => 'stylesheet', 'href' => 'https://fonts.googleapis.com/css?family=Marck+Script'));
$PAGE->add_head_resource(array('rel' => 'stylesheet', 'href' => 'https://fonts.googleapis.com/css?family=Aladin'));

$render = new post_renderer($PAGE);
echo $render->header();
$prevlink = $CFG->postroot.'/begin/';
$nextlink = null;
echo $render->sidebar($prevlink, $nextlink);

$post = <<<EOT
<h1><a href="http://www.duhaime.org/LegalDictionary/P/PeccatumilludhorribileinterChristianosnonnominandum.aspx" target="_blank">Peccatum illud horribile</a></h1>
<p>When I was young&mdash;in high school and college&mdash;I didn't consider myself "in the closet," but I was. Looking back, it's obvious to me that I was deeply unhappy. No, not unhappy&mdash;traumatized. I spent so much energy pushing away the things I felt for other guys. I called them "my desires." But no amount of pushing away kept them at bay. They always returned; it was out of my control.</p>
<p>Still, it wasn't until I came out, until I gave up the endless and exhausting battle against these feelings, that I realized how unhappy I had been. It's like how you don't sense the weight of the atmosphere until you climb the hill and feel your ears pop.</p>
<p>At that time, being gay was just not an option. It was an impossibility. It did not compute. Division by zero. Solution undefined. I knew that I liked guys: I thought about them all the time. I fantasized. But embracing "gay" as an identity? That was not a path that was open to me. I was in my mid-twenties before the idea even seemed like a hypothetical possibility. And even then, only after the pressure became too much and all other paths forward closed even harder. But that's another story.</p>
<p>Before that, though, in high school, aware of intense conflict and confusion within but without the experience to recognize its significance, I found relief in stories. I began to collect quotes that resonated with me from books or poems or even advertisements. I wrote them down in a Japanese-stab-bound book I created myself in a bookmaking workshop at school, using cheap calligraphic pens I had received for Christmas one year. I filled the very first page with a selection from Ayn Rand's <span class="cite">Anthem</span>, long before I even understood what it meant to be "in the closet":</p>
<div class="w3-card quotecard" style="font-family:'Sue Ellen Francisco', cursive;font-size:40px;line-height:54px">
<p style="text-align:justify;">&ldquo;We were born with a curse. It was always driven us to thoughts which are forbidden. It has always given us wishes which men may not wish. We know that</p>
<p style="margin-left:10%;">we are evil, but there is no will</p>
<p style="margin-left:10%;">in us and no power to resist it.</p>
<p>This is our wonder and our secret fear, that we know and do not resist.&rdquo;</p>
</div>
<p>There were many that connected with the discomfort and confusion and shame I felt because I liked boys.</p>
<div class="w3-card quotecard" style="font-family:'East Sea Dokdo', cursive;font-size:40px;">
<div style="max-width:15em;margin:auto;">
<p>&ldquo;Commit not that which is forbidden you...</p>
<p style="margin-left:10%;">and be not of those who</p>
<p style="margin-left:10%;">rove distractedly</p>
<p style="margin-left:20%;">in the wilderness</p>
<p style="margin-left:30%;">of their desires.&rdquo;</p>
<p style="text-align:right;margin-top:18px;">&mdash;Baha'u'llah, <span class="cite">The Kitab&#65279;-&#65279;i&#65279;-&#65279;Aqdas</span></p>
</div>
</div>
<div class="w3-card quotecard" style="font-family:'Marck Script', cursive;font-size:40px;line-height:50px;">
<div style="max-width:15em;margin:auto;">
<p>&ldquo;What would he do if he had the</p>
<p style="margin-left:10%;">motive and cue</p>
<p style="text-align:center;">for passion</p>
<p style="margin-left:10%;">that I have?</p>
<p style="text-align:right;margin-top:18px;">&mdash;<span class="cite">Hamlet</span></p>
</div>
</div>
<div class="w3-card quotecard" style="font-family:Aladin, cursive;font-size:30px;line-height:50px;">
<div style="max-width:20em;margin:auto;">
<p style="text-align:justify;">&ldquo;I wish I could go all the way to with you Rivendell, Mr.&nbsp;Frodo, and see Mr.&nbsp;Bilbo,&rdquo; said Sam. &ldquo;And yet the only place I really want to be is here.</p>
<p style="margin-left:10%;">I am that torn in two.&rdquo;</p>
<p style="text-align:justify;">&ldquo;Poor Sam! It will feel like that, I am afraid,&rdquo; said Frodo. &ldquo;But you will be healed. You were meant to be solid and whole, and</p>
<p style="text-align:center;">you will be.&rdquo;</p>
<p style="text-align:right;margin-top:18px;">&mdash;<span class="cite">The Lord of the Rings</span>, J.R.R.&nbsp;Tolkien</p>
</div>
</div>
<p>As I look back on this seemingly erratic collection, I feel like I'm meeting that boy again, a boy as removed from me now as any stranger, yet somehow my very self. What mystery is this?</p>

EOT;

echo $render->post($post);
echo $render->footer();