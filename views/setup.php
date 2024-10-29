<div class="anysearch-setup-instructions">
	<p><?php esc_html_e( 'Set up your Anysearch account to enable fast search on this site.', 'anysearch' ); ?></p>
	<?php Anysearch::view( 'get', array( 'text' => esc_attr__( 'Set up your Anysearch account' , 'anysearch' ), 'classes' => array( 'anysearch-button', 'anysearch-is-primary' ) ) ); ?>
</div>
