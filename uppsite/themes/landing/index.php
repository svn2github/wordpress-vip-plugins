<?php
$native_url = uppsite_get_native_link();
$base_dir = get_template_directory_uri();
?><html>
<head>
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0"/>
    <link type="text/css" rel="stylesheet" href="<?php echo $base_dir ?>/style.css"/>
    <script type="text/javascript">
        var is_permanent = "";
        function remember_func(elem) {
            if (elem.checked) {
                is_permanent = "&msa_theme_save_forever=1";
            } else {
                is_permanent = "";
            }
        }
        function btn_selected(elem) {
            window.location = elem.href + is_permanent;
            return false;
        }
    </script>
</head>
<body>
<div class="main-wrapper">
    <div id="top_container">
        <h1><?php bloginfo('name');?> </h1>
        <?php if (!is_null($native_url)): ?>
        <div class="button_image_wrapper">
            <a href='<?php echo esc_url( $native_url ); ?>'><img src='<?php echo esc_url( $base_dir . '/images/button1.png' ) ?>'/></a>
        </div>
        <?php endif; ?>
        <?php if (mysiteapp_should_show_webapp()): ?>
        <div class="button_image_wrapper">
            <a href='<?php echo esc_url( add_query_arg( 'msa_theme_select', 'webapp' ) ) ?>' onclick='return btn_selected(this);'><img src='<?php echo esc_url( $base_dir . '/images/button2.png' ); ?>'/></a>
        </div>
        <?php endif; ?>
        <div class="button_image_wrapper">
            <a href='<?php echo esc_url( add_query_arg( 'msa_theme_select', 'normal' ) ); ?>' onclick='return btn_selected(this);'><img src='<?php echo esc_url( $base_dir . '/images/button3.png' ) ?>'/></a>
        </div>
        <div class="save_text_wrapper">
            <h5>Don't show this screen again</h5>
            <input id="save_box" class="input_save" type="checkbox" name="save" value="" checked="checked" onchange="remember_func(this);"/>
        </div>
    </div>
    <div id="bottom_container">
        <img src="<?php echo esc_url( $base_dir . '/images/uppsite_logo.png' ); ?>"/>
    </div>
</div>
<script type="text/javascript">
    remember_func(document.getElementById('save_box'));
    window.onload = function() {
        window.scrollTo(0, 0);
    };
</script>
</body>
</html>