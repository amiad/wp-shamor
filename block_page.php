<?php
require_once("../../../wp-load.php");?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); shamor_site_get_headers_503();?>
	<script>
		shouldShammor = false;
	</script>
</head>
<body <?php body_class(); ?>>
<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		if(empty(get_option('display_template'))) {
	        echo '<div style="text-align: center; padding: 100px;"><h1>';
	        echo get_option('display_text'); 
	        echo '</h1><div>';
	    } else {
	        echo do_shortcode('[elementor-template id="' . get_option('display_template') . '"]');
	    }
		?>

	</main><!-- .site-main -->

</div><!-- .content-area -->
<div style="display: none;">
	<?php get_footer(); ?>
</div>
</body>
</html>