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
            <div class="anysearch-card">
                <div class="anysearch-section-header">
                    <div class="anysearch-section-header__label">
                        <span><?php esc_html_e( 'Settings', 'anysearch' ); ?></span>
                    </div>
                </div>

                <div class="inside">
                    <form action="<?php echo esc_url( Anysearch_Admin::get_page_url() ); ?>" method="POST">
                        <table cellspacing="0" class="anysearch-settings">
                            <tbody>
                            <tr>
                                <th class="anysearch-api-key" width="10%" align="left"
                                    scope="row"><?php esc_html_e( 'API Key', 'anysearch' ); ?></th>
                                <td width="5%"/>
                                <td align="left">
                                    <span class="api-key"><input id="key" name="key" type="text" size="15"
                                                                 value="<?php echo esc_attr( get_option( 'anysearch_api_key' ) ); ?>"
                                                                 class="<?php echo esc_attr( 'regular-text code ' . $anysearch_user->status ); ?>"></span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="anysearch-card-actions">
                            <div id="delete-action">
                                <a class="submitdelete deletion"
                                   href="<?php echo esc_url( Anysearch_Admin::get_page_url( 'delete_key' ) ); ?>"><?php esc_html_e( 'Disconnect this account', 'anysearch' ); ?></a>
                            </div>
							<?php wp_nonce_field( Anysearch_Admin::NONCE ) ?>
                            <div id="publishing-action">
                                <input type="hidden" name="action" value="enter-key">
                                <input type="submit" name="submit" id="submit"
                                       class="anysearch-button anysearch-could-be-primary"
                                       value="<?php esc_html_e( 'Save Changes', 'anysearch' ); ?>">
                            </div>
                            <div class="clear"></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="anysearch-card">
                <div class="anysearch-section-header">
                    <div class="anysearch-section-header__label">
                        <span><?php esc_html_e( 'Export settings', 'anysearch' ); ?></span>
                    </div>
                </div>
                <div class="inside">
                    <form action="<?php echo esc_url( Anysearch_Admin::get_page_url() ); ?>" method="POST"
                          id="sync_settings">
	                    <?php wp_nonce_field( Anysearch_Admin::NONCE ) ?>
                        <table cellspacing="0" border="0" class="anysearch-settings">
                            <tbody>
                            <tr>
                                <th scope="row"
                                    align="left"><?php esc_html_e( 'Exporting status', 'anysearch' ); ?></th>
                                <td width="5%"/>
                                <td align="left">
                                    <p id="sync_status"><?php
										echo Anysearch_Admin::get_sync_status();
										?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row" align="left"><?php esc_html_e( 'Last upload', 'anysearch' ); ?></th>
                                <td width="5%"/>
                                <td align="left">
                                    <p id="last_upload"><?php echo get_option( 'anysearch_last_sync' ) ?: 'Never';
										?></p>
                                </td>
                            </tr>
							<?php if ( get_option( 'anysearch_sync_settings' ) ) : $sync_options = get_option( 'anysearch_sync_settings' ) ?>
                                <tr>
                                    <th scope="row"
                                        align="left"><?php esc_html_e( 'Partial sync', 'anysearch' ); ?></th>
                                    <td width="5%"/>
                                    <td align="left">
                                        <p>
                                            <label for="anysearch_partial_sync" title="Turn on partial sync">
                                                <input
                                                        name="anysearch_partial_sync"
                                                        id="anysearch_partial_sync"
                                                        value="true"
                                                        type="checkbox"
													<?php
													checked( true, ( in_array( $sync_options['partial_sync'], array(
														true,
														'1',
														'true'
													), true ) ) );
													?>
                                                />
												<?php esc_html_e( "Turn on sync when product have been modified or created", 'anysearch' ) ?>
                                            </label>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"
                                        align="left"><?php esc_html_e( 'Remove if out of stock', 'anysearch' ); ?></th>
                                    <td width="5%"/>
                                    <td align="left">
                                        <p>
                                            <label for="anysearch_out_of_stock_remove_sync"
                                                   title="Turn on excluding out of stock items">
                                                <input
                                                        name="anysearch_out_of_stock_remove_sync"
                                                        id="anysearch_out_of_stock_remove_sync"
                                                        value="true"
                                                        type="checkbox"
													<?php
													checked( true, ( in_array( $sync_options['out_of_stock_remove_sync'], array(
														true,
														'1',
														'true'
													), true ) ) );
													?>
                                                />
												<?php esc_html_e( "Items which are out of stock will be removed from search results", 'anysearch' ) ?>
                                            </label>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"
                                        align="left"><?php esc_html_e( 'Full sync period', 'anysearch' ); ?></th>
                                    <td width="5%"/>
                                    <td align="left">
                                        <p>
                                            <label for="anysearch_priod_full_sync"
                                                   title="Turn on excluding out of stock items">
                                                <select name="anysearch_priod_full_sync" id="anysearch_priod_full_sync"
                                                        form="sync_settings">
                                                    <option value="never" <?php selected( $sync_options['priod_full_sync'], 'never' ) ?> >
														<?php esc_html_e( "Never", 'anysearch' ) ?>
                                                    </option>
                                                    <option value="daily" <?php selected( $sync_options['priod_full_sync'], 'daily' ) ?> >
														<?php esc_html_e( "Every day", 'anysearch' ) ?>
                                                    </option>
                                                    <option value="3daily" <?php selected( $sync_options['priod_full_sync'], '3daily' ) ?> >
														<?php esc_html_e( "Every 3d day", 'anysearch' ) ?>
                                                    </option>
                                                    <option value="weekly" <?php selected( $sync_options['priod_full_sync'], 'weekly' ) ?> >
														<?php esc_html_e( "Every week", 'anysearch' ) ?>
                                                    </option>
                                                </select>
                                            </label>
                                        </p>
                                    </td>
                                </tr>
							<?php endif; ?>
                            </tbody>
                        </table>
                        <div class="anysearch-card-actions">
                            <div id="publishing-action">
                                <input type="hidden" name="action" value="save-sync-options">
                                <input type="submit" name="sync-options-submit" id="sync-options-submit"
                                       class="anysearch-button anysearch-could-be-primary"
                                       value="<?php esc_html_e( 'Save', 'anysearch' ); ?>">
                            </div>
                            <div id="publishing-action">
                                <input type="hidden" name="action" value="sync">
                                <input type="button" name="sync-start" id="sync-start"
                                       class="anysearch-button anysearch-could-be-primary"
                                       value="<?php esc_html_e( 'Manual full sync', 'anysearch' ); ?>">
                            </div>
                            <div class="clear"></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="anysearch-card">
                <div class="anysearch-section-header">
                    <div class="anysearch-section-header__label">
                        <span><?php esc_html_e( 'Widget settings', 'anysearch' ); ?></span>
                    </div>
                </div>
                <div class="inside">
                    <div style="text-align: center">
                        <a class="anysearch-button anysearch-could-be-primary"
                           href="<?php echo $anysearch_user->settings_url ?>"
                           target="_blank"><?php esc_html_e( "Settings page", 'anysearch' ) ?></a>
                    </div>
                </div>
            </div>
		<?php endif; ?>
    </div>
</div>
