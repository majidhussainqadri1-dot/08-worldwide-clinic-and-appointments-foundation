<?php
/** File 08 canonical route template. */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div id="primary" class="content-area wca-route-wrapper">
	<?php echo WCA_Frontend::render_current_route(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes fields. ?>
</div>
<?php get_footer(); ?>
