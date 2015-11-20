<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\PluginCleanupInterface.
 */

namespace Drupal\ultimate_cron;

/**
 * Plugins can implement this to be invoked for regular cleanup tasks.
 *
 * @ingroup plugin_translatable
 */
interface PluginCleanupInterface {

  /**
   * Cleans and purges data stored by this plugin.
   *
   * Note that the result of this hook is cached.
   *
   * @return array
   *   Array of cron jobs, keyed by name.
   */
  function cleanup();

}