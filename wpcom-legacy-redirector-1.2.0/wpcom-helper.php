<?php

// Do not allow inserts to be enabled on the frontend on wpcom
add_filter( 'wpcom_legacy_redirector_allow_insert', '__return_false', 9999 );
