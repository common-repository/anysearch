jQuery( function ( $ ) {

	/**
	 * Shows the Enter API key form
	 */
	$( '.anysearch-enter-api-key-box a' ).on( 'click', function ( e ) {
		e.preventDefault();

		var div = $( '.enter-api-key' );
		div.show( 500 );
		div.find( 'input[name=key]' ).focus();

		$( this ).hide();
	} );


	/**
	 * Start sync handler
	 */
	$( '#sync-start' ).on( 'click', function() {
		var data = {
			action: 'manual_sync',
		};

		var url = $( "#sync_settings" ).attr( "action" );
		$.post( url, data)

		$( '#sync_status' ).text( "Started exchange" );

		function check_statuses() {
			check_notices();
			check_sync_status();
		}

		check_statuses();
	});

	/**
	 * Checks sync status
	 */
	function check_sync_status() {
		var data = {
			action: 'get_sync_status',
		};
		function get_sync_status() {
			$.post( ajaxurl, data, function(response) {
				response = JSON.parse(response)
				if($('#sync_status').text() === 'Started exchange' && response.sync_status === 'finished parse'){
					return false;
				}else{
					$( '#sync_status' ).text(response.sync_status);
					$( '#last_upload' ).text(response.last_upload);
				}
			}).then(
				function(){
					if( $( '#sync_status' ).text() !== 'finished parse' ){
						setTimeout(get_sync_status(), 100)
					}
				}
			)
		}
		get_sync_status();
	}


	/**
	 * Checks notices
	 */
	function check_notices() {
		var data = {
			action: 'get_notices',
		};

		function get_notices() {
			async function show_notice(div){
				var $notice_block = $(div)
				$( '.anysearch-notifications' ).append($notice_block.fadeIn( 'slow' )).last()
				setTimeout(function(){
					$notice_block.fadeOut( 'slow' )
				}, 5000);
			}
			$.post( ajaxurl, data, function(response) {
						show_notice(response);
					}
				).then(
				function(){
					if( $( '#sync_status' ).text() !== 'finished parse' ){
						setTimeout(get_notices(), 1000)
					}
				}
			)
		}
		get_notices();
	}

});
