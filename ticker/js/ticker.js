jQuery(function($) {
  let refreshInterval,
      inited = false;
  const storageKey = 'tickerPersonalOnly';

  // 1) Fetch & swap the wrapper, then re-attach controls
  function refreshTables() {
    const $wrapper = $('#tickerFullScreenWrapper');
    const ajaxUrl  = $wrapper.data('ajax-url');
    if (!$wrapper.length || !ajaxUrl) {
      console.warn('ticker: missing wrapper or ajax-url');
      return;
    }
    console.log('ticker: fetching data…');
    $.get(ajaxUrl)
      .done(html => {
        const $new = $('<div>').html(html)
                               .find('#tickerFullScreenWrapper');
        if ($new.length) {
          $wrapper.html($new.html());
          attachControls();
        }
      })
      .fail((xhr, status, err) => {
        console.error('ticker: AJAX error', status, err);
      });
  }

  // 2) Wire up Pause & My-Tasks-Only, then do an initial filter
  function attachControls() {
    // Pause toggle: start/stop the 30s interval
    $('#pauseRefreshToggle')
      .off('change')
      .on('change', function() {
        clearInterval(refreshInterval);
        if (!this.checked) {
          refreshTables();
          refreshInterval = setInterval(refreshTables, 30000);
        }
      });

    // My-Tasks-Only toggle: persist & trigger an immediate refresh
    $('#personalViewToggle')
      .off('change')
      .on('change', function() {
        localStorage.setItem(storageKey, this.checked);
        refreshTables();
      })
      .prop('checked', localStorage.getItem(storageKey) === 'true');

    // Run the filter on whatever is currently there
    filterPersonalView();
  }

  // 3) Keep only the rows/tables we want (and inject messages if empty)
  function filterPersonalView() {
    const onlyMine = $('#personalViewToggle').prop('checked');
    const myID = parseInt(
      $('#tickerFullScreenWrapper').data('current-user-id'),
      10
    );

    // Remove any old injected messages
    $('p.no-tasks-message').remove();

    ['todayZone','futureZone'].forEach(zoneId => {
      const $table   = $(`#${zoneId}`);
      const $wrapper = $table.parent();          // <div style="overflow-x:auto;">
      const $title   = $wrapper.prev('h3');
      const msg      = zoneId === 'todayZone'
                     ? 'No tasks for today!'
                     : 'Nothing planned for the future!';

      // If filter is off, just show the wrapper and skip
      if (!onlyMine) {
        return $wrapper.show();
      }

      // If there's no table at all (PHP printed its own <p>), leave it
      if (!$table.length) {
        return;
      }

      // Filter the rows
      let any = false;
      $table.find('tbody tr').each(function() {
        const ids = ('' + $(this).data('userIds'))
                      .split(',')
                      .map(i => parseInt(i, 10));
        const keep = ids.includes(myID);
        $(this).toggle(keep);
        if (keep) any = true;
      });

      if (any) {
        // we have rows → show the wrapper
        $wrapper.show();
      } else {
        // no rows → hide the wrapper, inject message under the title
        $wrapper.hide();
        $title.after(
          `<p class="no-tasks-message"><i>${msg}</i></p>`
        );
      }
    });
  }

  // 4) Full-screen toggle (delegated)
  $(document).on('click', '#fullScreenToggle', function() {
    const wrapper = document.getElementById('tickerFullScreenWrapper');
    if (!wrapper) {
      return console.error('ticker: no wrapper for fullscreen');
    }
    if (!document.fullscreenElement) {
      wrapper.requestFullscreen().catch(e => {
        alert('Could not enter full-screen: ' + e.message);
      });
    } else {
      document.exitFullscreen();
    }
  });

  // 5) Init: poll until the wrapper exists, then start the loop
  (function initTicker() {
    const $w = $('#tickerFullScreenWrapper');
    if ($w.length && !inited) {
      console.log('ticker: wrapper found - starting');
      inited = true;
      attachControls();
      refreshTables();
      refreshInterval = setInterval(refreshTables, 30000);
    } else if (!inited) {
      setTimeout(initTicker, 500);
    }
  })();
});

