jQuery(document).ready(function($) {
  $("a#ultimate-cron-show-all").click(function() {
    $(".ultimate-cron-admin-status").parent().show();
  });
  $("a#ultimate-cron-show-error").click(function() {
    $("tr .ultimate-cron-admin-status:not(.ultimate-cron-admin-status-error)").parent().hide();
    $("tr .ultimate-cron-admin-status-error").parent().show();
  });
  $("a#ultimate-cron-show-warning").click(function() {
    $("tr .ultimate-cron-admin-status:not(.ultimate-cron-admin-status-warning)").parent().hide();
    $("tr .ultimate-cron-admin-status-warning").parent().show();
  });
  $("a#ultimate-cron-show-info").click(function() {
    $("tr .ultimate-cron-admin-status:not(.ultimate-cron-admin-status-info)").parent().hide();
    $("tr .ultimate-cron-admin-status-info").parent().show();
  });
  $("a#ultimate-cron-show-success").click(function() {
    $("tr .ultimate-cron-admin-status:not(.ultimate-cron-admin-status-success)").parent().hide();
    $("tr .ultimate-cron-admin-status-success").parent().show();
  });
  $("a#ultimate-cron-show-running").click(function() {
    $("tr .ultimate-cron-admin-status:not(.ultimate-cron-admin-status-running)").parent().hide();
    $("tr .ultimate-cron-admin-status-running").parent().show();
  });

  // $("#ultimate-cron-view").tablesorter();

  var sel = location.hash.substring(1);
  $('a#ultimate-cron-' + sel).trigger('click');
});