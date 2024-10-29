<div id="anysearch-plugin-container">
    <div class="anysearch-lower">
        <div class="anysearch-notifications"></div>
		<?php if ( Anysearch::get_api_key() ) { ?>
			<?php Anysearch_Admin::display_status(); ?>
		<?php } ?>
		<?php if ( ! empty( $notices ) ) { ?>
			<?php foreach ( $notices as $notice ) { ?>
				<?php Anysearch::view( 'notice', $notice ); ?>
			<?php } ?>
		<?php } ?>

		<?php if ( $anysearch_user ): ?>
            <div class="anysearch-box anysearch-expired">
                <div class="centered anysearch-box-header">
                    <h2><?php esc_html_e( 'Your license has expired', 'anysearch' ); ?></h2>
                </div>
                <div class="anysearch-setup-instructions">
                    <p><?php esc_html_e( 'Contact with support to renew your subscription with a button below.', 'anysearch' ); ?></p>
                    <a class="anysearch-button" href="mailto:anysearch.help@gmail.com"><?php esc_attr_e( 'Contact with us' , 'anysearch') ; ?></a>
                </div>
            </div>


		<?php endif; ?>
    </div>
</div>
