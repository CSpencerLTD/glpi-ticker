jQuery(function($) {
  var refreshInterval,
      inited = false;

  function refreshTables() {
    var $w       = $('#tickerFullScreenWrapper'),
        ajaxUrl  = $w.data('ajax-url');
    if (!$w.length || !ajaxUrl) { return; }

    $.get(ajaxUrl)
      .done(function(html) {
        var $new = $('<div>').html(html)
                             .find('#tickerFullScreenWrapper');
        if ($new.length) {
          // Instead of replaceWith...
          $w.html( $new.html() );
        }
      })
      .fail(function(xhr, status, err) {
        console.error('ticker: AJAX error', status, err);
      });
  }

  $(document).ajaxComplete(function(e, xhr, settings) {
    if (settings.url.indexOf('common.tabs.php') === -1) {
      return;
    }
    if ($('#tickerFullScreenWrapper').length && !inited) {
      inited = true;
      // start immediately + every 30s
      refreshTables();
      refreshInterval = setInterval(refreshTables, 30000);

      // pause only when in fullscreen
      $(document).on('change', '#pauseRefreshToggle', function() {
        if (this.checked && document.fullscreenElement) {
          clearInterval(refreshInterval);
        } else {
          // either unpaused or not in full screen
          refreshTables();
          refreshInterval = setInterval(refreshTables, 30000);
        }
      });
    }
  });
});

// full-screen handler stays the same
jQuery(function($) {
  $(document).on('click', '#fullScreenToggle', function() {
    var wrapper = document.getElementById('tickerFullScreenWrapper');
    if (!wrapper) { return console.error('FS: missing wrapper'); }
    if (!document.fullscreenElement) {
      wrapper.requestFullscreen().catch(function(err){
        alert('Could not enter full-screen: ' + err.message);
      });
    } else {
      document.exitFullscreen();
    }
  });
});
