<?php

// configuration
define('PAGE_TITLE', 'Nanochan BBS');

function getmongo() {
	return new Mongo('mongodb://dep5ezna7wf:Rz32A5PxYgMgsFs@127.0.0.1/Rz32A5PxYgMgsFs');
}
$m = getmongo();
$db_posts = $m->posts;

$adminPasses = array();
$modPasses = array();

define('TIME_BETWEEN_TOPICS', 120);
define('TIME_BETWEEN_POSTS', 30);
// end configuration

define('START_TIME', _microtime());
define('SELF', $_SERVER['SCRIPT_NAME']);
define('IS_ADMIN', (isset($_COOKIE['admin'])) ?  in_array($_COOKIE['admin'], $adminPasses) : FALSE);
define('IS_MOD', (isset($_COOKIE['mod'])) ? in_array($_COOKIE['mod'], $modPasses) : FALSE);

define('JS_VERSION', 1);
define('CSS_VERSION', 1);

if(isset($_COOKIE['sid']))
	define('SID', $_COOKIE['sid']);
else {
	$sid = hash('sha256', rand() . uniqid() . mt_rand());
	define('SID', $sid);
	setcookie('sid', $sid, time() + 315360000, dirname(SELF)); // 315360000 = 3600 * 24 * 365 * 10 = 10 years
}
	
// stripslashes
if(get_magic_quotes_gpc())
	$_POST = array_map('stripslashes', $_POST);

// misc functions
function redirect($where) {
	header('HTTP/1.0 302 Moved Temporarily');
	header('Location: ' . $where);
	exit();
}

function error($message) {
	template_header();
	
	template_error_header();
	template_error_content($message);
	template_error_footer();
	
	template_footer();
	exit();
}

function check_topic_flood($ip, $topics) {
	$startTime = time();
	ksort($topics, SORT_NUMERIC);
	foreach(array_reverse($topics) as $topic) {
		if($topic[0]['time'] + TIME_BETWEEN_TOPICS < $startTime)
			return 0;
		if($topic[0]['ip'] == $ip)
			return $topic[0]['time'];
	}
	return 0;
}

function check_post_flood($ip, $topics) {
	$startTime = time();
	
	foreach(array_reverse($topics) as $topic) {
		ksort($topic, SORT_NUMERIC);
		foreach(array_reverse($topic) as $id => $post) {
			if($post['time'] + TIME_BETWEEN_POSTS < $startTime) {
				if($id == 0)
					return 0;
				break;
			}
			if($post['ip'] == $ip)
				return $post['time'];
		}
	}
	return 0;
}

function check_poster_number($topic) {
	ksort($topic, SORT_NUMERIC);
	
	$sids = array();
	
	foreach($topic as $id => $post) {
		if($post['sid'] == SID)
			break;
		if(!in_array($post['sid'], $sids))
			$sids[] = $post['sid'];
	}
			
	return count($sids);
}

function _microtime() {
	list($sec, $usec) = explode(' ', microtime());
	return (float) $sec + (float) $usec;
}


// pick action
$action = (isset($_GET['action'])) ? $_GET['action'] : 'list';

switch($action) {
	
	case 'list':
		// header
		template_header();
		
		template_index_header();
		
		$i = 0;
		foreach( $db_posts->find() AS $id => $topic ) {
			template_index_topic($id, $topic, $i % 2);
			$i++;
		}
		
		template_index_footer();
		
		template_footer();
	break;
	
	case 'new':
		if(isset($_POST['topicname']) && isset($_POST['name']) && isset($_POST['content'])) {
			$time = check_topic_flood($_SERVER['REMOTE_ADDR'], $db_posts);
			if($time > 0) {
				$time_to_wait = TIME_BETWEEN_TOPICS + $time - time();
				error('Please wait another ' . $time_to_wait . ' seconds before creating another topic.');
			}
			
			$_POST['topicname'] = trim($_POST['topicname']);
			if(empty($_POST['topicname']))
				error('The topic name can not be blank.');
			if(empty($_POST['content']))
				error('The post body can not be blank.');
				
			$_POST['name'] = htmlentities($_POST['name']);
			if(empty($_POST['name']))
				$_POST['name'] = '</span>Anonymous <span class="author">A';

			$new_id = $db_posts->insert(	array(	'name' => $_POST['name'],
						'topicname' => $_POST['topicname'],
						'content' => nl2br(htmlentities($_POST['content'])),
						'time' => time(),
						'ip' => $_SERVER['REMOTE_ADDR'],
						'sid' => SID ) );
			
			redirect(SELF . '?action=topic&id=' . $new_id);
		}
		
		// header
		template_header();
		
		template_new_header();
		template_new_form();
		template_new_footer();
		
		template_footer();
	break;
	
	case 'topic':
		if(!isset($_GET['id'])) {
			redirect(SELF);
		}
		
		$topic = $db_posts->find( array('_id' => $_GET['id']) );

		if(isset($_POST['name']) && isset($_POST['content'])) {
			$time = check_post_flood($_SERVER['REMOTE_ADDR'], $db_posts);
			if($time > 0) {
				$time_to_wait = TIME_BETWEEN_POSTS + $time - time();
				error('Please wait another ' . $time_to_wait . ' seconds before adding another reply.');
			}
			
			$_POST['content'] = trim($_POST['content']);
			if(empty($_POST['content']))
				error('The post body can not be blank.');
	
			$_POST['name'] = htmlentities($_POST['name']);
			if(empty($_POST['name']) || strpos(trim($_POST['name']), 'Anonymous') === 0) {
				if(($number = check_poster_number($topic)) == -1)
					$number = count($topic);

				if($number > 26)
					$_POST['name'] = '</span>Anonymous <span class="author">Z-' . ($number - 26);
				else
					$_POST['name'] = '</span>Anonymous <span class="author">' . chr(65 + $number);
			}
			
			$topic = $db_posts->insert(array(	'name' => $_POST['name'],
							'content' => nl2br(htmlentities($_POST['content'])),
							'time' => time(),
							'ip' => $_SERVER['REMOTE_ADDR'],
							'sid' => SID ));
		}
		
		template_header();
		
		template_topic_header($topic[0]['topicname']);
		if(!IS_ADMIN && !IS_MOD)
			foreach($topic as $id => $post)
				template_topic_post($id, $post);
		else
			foreach($topic as $id => $post)
				template_topic_post_mod($id, $post, $_GET['id']);
		
		template_topic_reply_form($_GET['id']);
		template_topic_footer();
		
		template_footer();
	break;
	
	case 'css':
		header('Content-Type: text/css');
		header('Cache-Control: public; max-age=' . time());
		header('Expires: ' . date('r', time() + time()));
	
		template_css();
	break;
	
	case 'js':
		header('Content-Type: text/javascript');
		header('Cache-Control: public; max-age=' . time());
		header('Expires: ' . date('r', time() + time()));

		template_js();
	break;
	
	case 'login':
		if(isset($_POST['pwd']) && in_array($_POST['pwd'], $adminPasses))
			setcookie('admin', $_POST['pwd'], 0, dirname(SELF));
		elseif(isset($_POST['pwd']) && in_array($_POST['pwd'], $modPasses))
			setcookie('mod', $_POST['pwd'], 0, dirname(SELF));
		
		redirect(SELF);
	break;
	
	case 'logout':
		if(IS_ADMIN)
			setcookie('admin', 0, time() - 1, dirname(SELF));
		if(IS_MOD)
			setcookie('mod', 0, time() - 1, dirname(SELF));
		
		redirect(SELF);
	break;
	
	case 'delete':
		if(IS_ADMIN && isset($_GET['id'])) {
			$db_posts->remove( array('_id' => $_GET['ID']) );		
		}
	
		redirect(SELF);
	break;
	
	case 'deletepost':
		if((IS_ADMIN || IS_MOD) && isset($_GET['topic']) && isset($_GET['post'])) {
			$db_posts->remove( array('_id' => $_GET['post']) );
		}
	
		redirect(SELF . '?action=topic&id=' . @$_GET['topic']);
	break;
	
	default:
		redirect(SELF);
	break;
}

// template functions
function template_header() {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

	<head>
		<title><?php echo PAGE_TITLE; ?></title>
		
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		
		<link rel="stylesheet" type="text/css" href="<?php echo SELF; ?>?action=css&v=<?php echo CSS_VERSION; ?>" />
		
	</head>
	
	<body>
		<div id="inner">
			<div id="header">
				<h1><a class="logo" href="<?php echo SELF; ?>"><?php echo PAGE_TITLE; ?></a></h1>
<?php if(!IS_ADMIN): ?>
				<form id="login_form" action="<?php echo SELF; ?>?action=login" method="post">
					<input type="password" name="pwd" value="" /><input type="submit" value="Login" />
				</form>
<?php else: ?>
				<form id="login_form" action="<?php echo SELF; ?>?action=logout" method="post">
					<input type="submit" value="Logout" />
				</form>
<?php endif; ?>
			</div>
			
			<div id="content">
<?php
}

function template_footer() {
	define('END_TIME', _microtime());
?>
			</div>
			
			<div id="footer">
				<sub>Powered by <a href="http://github.com/Purgox/NanoBBS/">NanoBBS</a> - loaded in <?php echo END_TIME - START_TIME; ?> seconds.</sub>
			</div>
			
			<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
			<script type="text/javascript" src="<?php echo SELF; ?>?action=js&v=<?php echo JS_VERSION; ?>"></script>
		</div>
	</body>

</html>
<?php
}

function template_index_header() {
?>
				<h2>Topics</h2>
				
				<table class="topic_list">
					<thead>
						<tr>
							<th class="topic_head topic_name">Name</th>
							<th class="topic_head">Number of replies</th>
							<th class="topic_head">Last bump</th>
<?php if(IS_ADMIN): ?>
							<th class="topic_head">Delete</th>
<?php endif; ?>
						</tr>
					</thead>
					<tbody class="topic_list">
<?php
}

function template_index_topic($id, $topic, $odd) {
?>
						<tr class="<?php if($odd): ?>odd<?php else: ?>even<?php endif; ?>">
							<td><a class="topic_link topic_title" href="<?php echo SELF; ?>?action=topic&id=<?php echo $id; ?>"><?php echo htmlentities($topic[0]['topicname']); ?></a></td>
							<td><?php echo count($topic) - 1; ?></td>
							<td><?php echo date('d-m-Y H:i:s', $topic[end(array_keys($topic))]['time']); ?></td>
<?php if(IS_ADMIN): ?>
							<td><a href="<?php echo SELF; ?>?action=delete&id=<?php echo $id; ?>">X</a></td>
<?php endif; ?>
						</tr>
<?php
}

function template_index_footer() {
?>
					</tbody>
					<tfoot>
						<tr>
						</tr>
					</tfoot>
				</table>
				<div>
					<a class="new_topic_link" href="<?php echo SELF; ?>?action=new">Create new topic</a>
				</div>
<?php
}

function template_new_header() {
?>
				<h2>New topic</h2>
<?php
}

function template_new_form() {
?>
				<form action="<?php echo SELF; ?>?action=new" method="post">
					Title<br />
					<input type="text" name="topicname" maxlength="100" /><br />
					Name<br />
					<sub>(Tripcodes are not supported.)</sub><br />
					<input type="text" name="name" maxlength="100" /><br />
					Contents<br />
					<textarea name="content" rows="6" cols="50"></textarea><br />
					<input type="submit" value="Add" />
				</form>
<?php
}

function template_new_footer() {
?>
				<sub>Please don't post anything illegal, kthxbye.</sub>
<?php
}

function template_topic_header($title) {
?>
				<h2>Topic: <?php echo htmlentities($title); ?></h2>
<?php
}

function template_topic_post($id, $post) {
?>
				<div class="post" id="post_<?php echo $id; ?>">
					<div class="author"><span class="author"><?php echo $post['name']; ?></span> said at <span class="date"><?php echo date('d-m-Y H:i:s', $post['time']); ?></span>: <a href="#reply" class="quote_link right" onclick="add_cite(<?php echo $id; ?>);">#<strong><?php echo $id; ?></strong></a></div>
					<div class="content">
						<?php echo $post['content']; ?>
					</div>
				</div>
<?php
}

function template_topic_post_mod($id, $post, $topicid) {
?>
				<div class="post" id="post_<?php echo $id; ?>">
					<div class="author"><span class="author"><?php echo $post['name']; ?></span> said at <span class="date"><?php echo date('d-m-Y H:i:s', $post['time']); ?></span>: (<span class="ip"><?php echo $post['ip']; ?></span>) <a href="<?php echo SELF; ?>?action=deletepost&topic=<?php echo $topicid; ?>&post=<?php echo $id; ?>" class="delete_link right">&nbsp;delete</a> <a href="#reply" class="quote_link right" onclick="add_cite(<?php echo $id; ?>);">#<strong><?php echo $id; ?></strong></a></div>
					<div class="content">
						<?php echo $post['content']; ?>
					</div>
				</div>
<?php
}

function template_topic_reply_form($id) {
?>
				<h3>Reply</h3>
				<form id="reply_form" action="<?php echo SELF; ?>?action=topic&id=<?php echo $id; ?>" method="post">
					<a name="reply"></a>
					Name<br />
					<sub>(Tripcodes are not supported.)</sub><br />
					<input type="text" name="name" maxlength="100" /><br />
					Contents<br />
					<textarea id="reply" name="content" rows="6" cols="50"></textarea><br />
					<input type="submit" value="Reply" />
				</form>
<?php
}

function template_topic_footer() {
?>
				
<?php
}

function template_error_header() {
?>
				<h2>Fatal error</h2>
<?php
}

function template_error_content($message) {
?>
				<div id="error">
					<?php echo $message; ?>
				</div>
<?php
}

function template_error_footer() {
?>

<?php
}

function template_css() {
?>
body {
	font-family: helvetica, arial, sans-serif;
	background-color: #cae6c1;
	font-size: 85%;
}
h2 {
	color: red;
	padding-bottom: 3px;
	margin-bottom: 5px;
}
.right {
	float: right;
}
a.delete_link {
	text-decoration: none;
}
a.logo {
	color: inherit;
	text-decoration: none;
}
a.logo:hover {
	text-decoration: line-through;
}
a.new_topic_link {
	float: right;
	margin-top: 3px;
	margin-right: 8px;
	color: grey;
	text-decoration: none;
}
a.new_topic_link:hover {
	background-color: #ecfae8;
	border-bottom: 1px dashed red;
}
a.topic_link {
	text-decoration: none;
	color: black;
}
a.quote_link {
	text-decoration: none;
	color: red;
	font-size: 95%;
}
form#login_form {
	float: right;
	display: inline;
}
form#reply_form {
}
span.author {
	font-weight: bold;
}
span.date {
	font-style: italic;
}
span.greentext {
	color: #789922;
}
span.ip {
	color: red;
}
div.author {
	background-color: #97cc88;
	padding: 5px;
	padding-left: 6px;
	font-size: 93%;
}
div.content {
	padding: 2px;
	padding-left: 6px;
	padding-top: 4px;
	font-size: 90%;
}
div#error {
	background-color: #ecfae8;
	padding: 3px;
	border-left: 2px dashed red;
}
div.post {
	padding-bottom: 4px;
	margin-bottom: 8px;
	background-color: #bddeb4;
}
div.post.highlighted {
	background-color: #ecfae8;
	border-bottom: 2px dashed red;
}
table {
	border-collapse: collapse;
	margin-left: -8px;
}
table.topic_list {
	width: 100%;
}
th.topic_head {
	background-color: #97cc88;
	text-align: left;
}
th.topic_name {
	width: 65%;
}
td, th {
	padding: 6px;
}
tr:hover td {
	background-color: #ecfae8;
}
tr.even {
	background-color: #abd69e;
}
tr.odd {
	background-color: #bddeb4;
}
<?php
}

function template_js() {
?>
$(document).ready(function() {
	// parsing
	$('.content').each(function() {
						var html = new String($(this).html());
						html = $.trim(html);
						html = html.replace('@OP', '<a class="quote_link" href="#post_0" title="@ Original poster">@OP</a>');
						html = html.replace('@0', '<a class="quote_link" href="#post_0" title="@ Original poster">@OP</a>');
						html = html.replace(/@([0-9]+)/g, '<a class="quote_link" href="#post_$1" title="@ Post #$1">@$1</a>');
						html = html.replace(/(^|\n)&gt;(.*)/g, '$1<span class="greentext">&gt;$2</span>');
						$(this).html(html);
					});
	
	$('.quote_link').each(function() {
						if($(this).attr('href').indexOf('#post') != -1) {
							$(this).click(function() {
											$('.post').each(function() {
																$(this).removeClass('highlighted');
															});
											$($(this).attr('href')).addClass('highlighted');
										});
						}
					});
});

// add citation
function add_cite(id) {
	$('#reply').val($('#reply').val() + '@' + id + '\n');
	$('#reply').focus();
}
<?php
}

// 'db' functions
function save($data) {
	$handle = fopen(__FILE__, 'r');
	$code = fread($handle, __COMPILER_HALT_OFFSET__);
	fclose($handle);
	if(file_put_contents(__FILE__, $code . serialize($data)) === FALSE) {
		error('Unable to save database. Is it chmodded to 0777?');
	}
}

function load() {
	$handle = fopen(__FILE__, 'r');
	if(!$handle) {
		error('Unable to open database. Is it chmodded to 0777?');
	}

	fseek($handle, __COMPILER_HALT_OFFSET__);
	$data = unserialize(stream_get_contents($handle));
	fclose($handle);
	return $data;
}

// data goes after __halt_compiler(), serialized.
__halt_compiler();a:0:{}
