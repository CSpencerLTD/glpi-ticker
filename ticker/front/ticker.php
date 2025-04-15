<?php

include ('../../../inc/includes.php');

Html::header(__('Ticker', 'ticker'), $_SERVER["PHP_SELF"], "plugins", "ticker");

echo "<div class='center'>";
echo "<h2>Live Ticker</h2>";
include_once(GLPI_ROOT . "/plugins/ticker/inc/ticker_content.php");
echo "</div>";

Html::footer();

