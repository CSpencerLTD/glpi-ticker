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
use Plugin;

/**
 * Add a direct menu entry in the left‑hand navigation
 */
function plugin_ticker_getMenuContent() {
    return [
        'title' => __('Ticker', 'ticker'),
        'page'  => '/plugins/ticker/front/ticker.php'
    ];
}

/**
 * Central page tab for Ticker
 */
class PluginTickerCentralTab extends CommonGLPI {
    /**
     * Tab label
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        return self::createTabEntry(__('Ticker', 'ticker'));
    }

    /**
     * Content shown when the Ticker tab is active
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        echo "<div class='center'>";
        include_once(GLPI_ROOT . "/plugins/ticker/front/ticker.php");
        echo "</div>";
        return true;
    }
}

/**
 * Plugin activation tasks
 */
function plugin_ticker_install() {
    // Add schema or default settings here if needed
    return true;
}

/**
 * Plugin deactivation cleanup
 */
function plugin_ticker_uninstall() {
    // Clean up database or settings here if needed
    return true;
}

// Hook registrations
global $PLUGIN_HOOKS;

// Left‑bar menu entry
$PLUGIN_HOOKS['menu_entry']['ticker']     = 'plugin_ticker_getMenuContent';

// Register Central tab
Plugin::registerClass(
    PluginTickerCentralTab::class,
    ['addtabon' => Central::class]
);

// Reorder tabs among plugin‑added entries (Ticker will appear first among plugins)
$PLUGIN_HOOKS['redefine_menus']['ticker']  = 'plugin_ticker_redefineMenus';

/**
 * Move Ticker plugin tab to front of plugin entries
 */
function plugin_ticker_redefineMenus(array $menus, $type = null) {
    if (isset($menus['PluginTickerCentralTab'])) {
        $tab = $menus['PluginTickerCentralTab'];
        unset($menus['PluginTickerCentralTab']);
        $menus = ['PluginTickerCentralTab' => $tab] + $menus;
    }
    return $menus;
}