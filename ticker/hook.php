<?php

/**
 * -------------------------------------------------------------------------
 * Ticker plugin for GLPI
 * Copyright (C) 2025 by the Ticker Development Team.
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 */

use CommonGLPI;
use Central;

/**
 * Display the ticker content on the central dashboard
 */
function plugin_ticker_display_central() {
    echo "<div class='center'>";
    include_once(GLPI_ROOT . "/plugins/ticker/front/ticker.php");
    echo "</div>";
}

/**
 * Define the direct menu link for GLPI menu systems (left bar)
 */
function plugin_ticker_getMenuContent() {
    return [
        'title' => __('Ticker', 'ticker'),
        'page'  => '/plugins/ticker/front/ticker.php'
    ];
}

/**
 * Tab integration class for the Central page
 */
class PluginTickerCentralTab extends CommonGLPI {

    /**
     * Returns the name of the tab
     *
     * @param CommonGLPI $item     Parent item (unused)
     * @param int        $withtemplate Template flag (unused)
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        return self::createTabEntry(__('Ticker', 'ticker'));
    }

    /**
     * Renders the tab content when clicked
     *
     * @param CommonGLPI $item     Parent item (unused)
     * @param int        $tabnum   Tab number (unused)
     * @param int        $withtemplate Template flag (unused)
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        echo "<div class='center'>";
        include_once(GLPI_ROOT . "/plugins/ticker/front/ticker.php");
        echo "</div>";
        return true;
    }
}

/**
 * Called when the plugin is installed (activated)
 *
 * @return boolean true on success
 */
function plugin_ticker_install() {
   // TODO: add database tables or default config here if needed
   return true;
}

/**
 * Called when the plugin is uninstalled (deactivated)
 *
 * @return boolean true on success
 */
function plugin_ticker_uninstall() {
   // TODO: remove database tables or cleanup here if needed
   return true;
}
