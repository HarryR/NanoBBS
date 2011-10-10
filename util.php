<?php
/* TODO: fill out this function with a nice design */
function error($message) {
?>
<html>
	<h2>Error Occurred</h2>
	<p>Message: <?= $message ?></p>
</html>
<?php
	exit;	
}

/** Get an input parameter, or use the default value */
function param($name, $default = NULL) {
	if (!isset($_REQUEST[$name])) {
		return $default;
	}
	return $_REQUEST[$name];
}