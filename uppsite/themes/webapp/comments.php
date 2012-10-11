<?php
global $this_comments;
$this_comments = array();
if ($comments) {
	foreach ($comments as $comment) {
		$this_comments[] = uppsite_get_comment();
	}
}