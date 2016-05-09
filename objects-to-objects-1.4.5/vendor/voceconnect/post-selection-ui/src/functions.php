<?php

function post_selection_ui($name, $args) {
	$select_box = new Post_Selection_Box($name, $args);
	return $select_box->render();
}
