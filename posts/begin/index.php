<?php
require_once('../../lib/setup.php');

$PAGE->set_url(new url($CFG->postroot.'/begin/'));
$PAGE->set_title('Mr. Patterson Listens');

$render = new post_renderer($PAGE);
echo $render->header();
$prevlink = null;
$nextlink = $CFG->postroot.'/peccatum-illud-horribile/';
echo $render->sidebar($prevlink, $nextlink);

$post = <<<EOT
<h1>Begin</h1>
<p>I ought to begin by recognizing that just the act of writing this while expecting to be heard is a privilege I enjoy by chance. I'm a white male, about 30 years old. I was born into the lower middle class, and I've married into the upper. I work a white collar job that offers me health insurance and retirement plans, among other benefits. None of these descriptors defines me, but each of them is significant. Each results from chance or circumstance, and together they form a role that my community rewards in various ways—in large part not because of anything I have done.</p>
<p>One of these rewards is that, when I speak, I can expect to be heard. I can assume that you won't shush me, or dismiss me, or attempt to silence me for no reason. This is the privilege most evident to me now, as I write this and launch this blog.</p>
<p>Just a few days ago, David Hogg, one of the survivors of the Marjory Stoneman Douglas massacre, <a href="http://www.axios.com/stoneman-douglas-like-prison-8770ca8e-fd48-4dcc-ae75-a677ba5cc61b.html">criticized the media</a> for &ldquo;not giving black students a voice&rdquo; in its coverage of the shooting. &ldquo;My school is about 25 percent black,&rdquo; he said, &ldquo;but the way we're covered doesn't reflect that.&rdquo;</p>
<p>In this case the media reveals a pervasive cultural bias: The white and wealthy and male are worthy of being heard. We can take their word for it. We approach them with the assumption that what they say is valid—while the words, the shouts, the cries of minorities are dismissed or silenced.</p>
<p>I want to challenge that assumption, but I wonder how. I can vote, I can speak, I can march. And these are things that I do. But there's something else I can do, simple yet powerful. I can listen. I can believe that you are the only one who can speak for you. I can trust that your thoughts and feelings are valid and worthy to be heard. And, when we're talking together, I can treasure the things that you wish to share with me.</p>
<p>In the words of Mr. Rogers, &ldquo;In times of stress, the best thing we can do for each other is to listen with our ears and our hearts and to be assured that our questions are just as important as our answers.&rdquo;</p>
<p>So I won't take your attention for granted. I won't assume that you'll give me your trust. But I will hope to earn it.</p>
<p>Listening with ears and heart,<br />
Mr. Patterson</p>
EOT;

echo $render->post($post);
echo $render->footer();