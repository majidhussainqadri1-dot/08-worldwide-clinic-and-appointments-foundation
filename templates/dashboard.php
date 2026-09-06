<?php
/** File 08 canonical clinic dashboard template. */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div id="primary" class="content-area wca-route-wrapper">
	<?php echo WCA_Query_API::render_dashboard(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes all fields. ?>
</div>
<?php get_footer(); ?>
