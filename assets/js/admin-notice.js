/**
 * Handle admin notice dismissal.
 */
(
	function ( $ ) {
		'use strict'
		$( document ).ready( function () {
			$( document ).on( 'click', '.nsrwc-admin-notice .notice-dismiss', function () {
				var $notice = $( this ).closest( '.nsrwc-admin-notice' )
				var noticeType = $notice.data( 'notice' )
				var noticeTypeName = noticeType ? noticeType.replace( 'nsrwc-', '' ) : ''

				$.ajax( {
					url: nsrwcAdmin.ajax_url,
					type: 'POST',
					data: {
						action: 'nsrwc_dismiss_notice',
						nonce: nsrwcAdmin.nonce,
						notice_type: noticeTypeName
					}
				} )
			} )
		} )
	}
)( jQuery )
