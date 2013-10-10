<?php
	require_once(dirname(__FILE__) . '/../functions.php');
	require_once(dirname(__FILE__) . '/header.php');

	if (isset($_REQUEST['mac'])) {
		include(dirname(__FILE__) . '/dosearch.php');
		echo '<br><br>';
	}

	include(dirname(__FILE__) . '/searchform.php');

	require_once(dirname(__FILE__) . '/footer.php');
?>
