<?php

//phpcs:disable VariableAnalysis
// There are "undefined" variables here because they're defined in the code that includes this file as a template.

?>
<?php if ( $type == 'plugin' ) : ?>
    <div class="updated" id="anysearch_setup_prompt">
        <form name="anysearch_activate" action="<?php echo esc_url( Anysearch_Admin::get_page_url() ); ?>"
              method="POST">
            <div class="anysearch_activate">
                <div class="aa_a">A</div>
                <div class="aa_button_container">
                    <div class="aa_button_border">
                        <input type="submit" class="aa_button"
                               value="<?php esc_attr_e( 'Enter your Anysearch key', 'anysearch' ); ?>"/>
                    </div>
                </div>
                <div class="aa_description"><?php esc_attr_e( '<strong>Almost done</strong> - configure Anysearch and use quick search', 'anysearch' ); ?></div>
            </div>
        </form>
    </div>
<?php elseif ( $type == 'notice' ) : ?>
    <div class="anysearch-alert anysearch-critical">
        <h3 class="anysearch-key-status failed"><?php echo $notice_header; ?></h3>
        <p class="anysearch-description">
			<?php echo $notice_text; ?>
        </p>
    </div>
<?php elseif ( $type == 'servers-be-down' ) : ?>
    <div class="anysearch-alert anysearch-critical">
        <h3 class="anysearch-key-status failed"><?php esc_html_e( "Your site can&#8217;t connect to the Anysearch servers.", 'anysearch' ); ?></h3>
        <p class="anysearch-description"><?php esc_html_e( 'Your firewall may be blocking Anysearch from connecting to its API. Please contact your host to resolve this', 'anysearch' ); ?></p>
    </div>
<?php elseif ( $type == 'new-key-valid' ) : ?>
    <div class="anysearch-alert anysearch-active">
        <h3 class="anysearch-key-status"><?php esc_html_e( 'Your WP site is now connected to search API.', 'anysearch' ); ?></h3>
    </div>
<?php elseif ( $type == 'sync-started' ) : ?>
    <div class="anysearch-alert anysearch-active">
        <h3 class="anysearch-key-status"><?php esc_html_e( 'Sync started', 'anysearch' ); ?></h3>
    </div>
<?php elseif ( $type == 'sync-settings-save' ) : ?>
    <div class="anysearch-alert anysearch-active">
        <h3 class="anysearch-key-status"><?php esc_html_e( 'Settings saved', 'anysearch' ); ?></h3>
    </div>
<?php elseif ( $type == 'sync-finished' ) : ?>
    <div class="anysearch-alert anysearch-active">
        <h3 class="anysearch-key-status"><?php esc_html_e( 'Sync finished', 'anysearch' ); ?></h3>
    </div>
<?php elseif ( $type == 'new-key-invalid' ) : ?>
    <div class="anysearch-alert anysearch-critical">
        <h3 class="anysearch-key-status"><?php esc_html_e( 'The key you entered is invalid. Please double-check it.', 'anysearch' ); ?></h3>
    </div>
<?php elseif ( $type == 'new-key-empty' ) : ?>
    <div class="anysearch-alert anysearch-critical">
        <h3 class="anysearch-key-status"><?php esc_html_e( 'Please enter the key', 'anysearch' ); ?></h3>
    </div>
<?php elseif ( $type == 'existing-key-invalid' ) : ?>
    <div class="anysearch-alert anysearch-critical">
        <h3 class="anysearch-key-status"><?php esc_html_e( 'Your API key is no longer valid. Please enter a new key.', 'anysearch' ); ?></h3>
    </div>
<?php elseif ( $type == 'license-expired' ) : ?>
    <div class="anysearch-alert anysearch-critical">
        <h3 class="anysearch-key-status"><?php esc_html_e( 'Your license has expired', 'anysearch' ); ?></h3>
    </div>
<?php endif; ?>