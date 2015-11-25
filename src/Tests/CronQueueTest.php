<?php

/**
 * @file
 * Contains \Drupal\ultimate_cron\Tests\CronQueueTest.
 *
 * Test that queues are processed on cron using the aggregator module.
 */

namespace Drupal\ultimate_cron\Tests;

use Drupal\aggregator\Tests\AggregatorTestBase;

/**
 * Update feeds on cron.
 *
 * @group ultimate_cron
 */
class CronQueueTest extends AggregatorTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('ultimate_cron');

  /**
   * Adds feeds and updates them via cron process.
   */
  public function testCron() {
    // Create feed and test basic updating on cron.
    $this->createSampleNodes();
    $feed = $this->createFeed();
    $this->cronRun();
    $this->assertEqual(5, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->id()))->fetchField());
    $this->deleteFeedItems($feed);
  }
}
