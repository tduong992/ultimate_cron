<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\PluginCleanupInterface.
 */

namespace Drupal\ultimate_cron;

interface PluginCleanupInterface {

  /**
   * Inform Ultimate Cron about cron jobs.
   *
   * Note that the result of this hook is cached.
   *
   * @return array
   *   Array of cron jobs, keyed by name.
   */
  function cleanup();

}