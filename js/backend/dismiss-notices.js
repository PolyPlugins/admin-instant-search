jQuery(document).ready(function($) {

  let ajax_url = admin_instant_search_object.ajax_url;
  let nonce    = admin_instant_search_object.nonce;

  $('body').on('click', '.admin-instant-search .notice-dismiss', function() {
    $.post(ajax_url, {
      action: 'admin_instant_search_dismiss_notice_nonce',
      nonce: nonce
    });
  });

});
