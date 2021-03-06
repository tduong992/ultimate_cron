<?php
/**
 * @file
 * Cache logger for Ultimate Cron.
 */

namespace Drupal\ultimate_cron\Plugin\ultimate_cron\Logger;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ultimate_cron\Logger\LoggerBase;

/**
 * Cache Logger.
 *
 * @LoggerPlugin(
 *   id = "cache",
 *   title = @Translation("Cache"),
 *   description = @Translation("Stores the last log entry (and only the last) in the cache."),
 * )
 */
class CacheLogger extends LoggerBase {

  public $logEntryClass = '\Drupal\ultimate_cron\Logger\CacheLogEntry';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'bin' => 'cache_ultimate_cron',
      'timeout' => 0,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load($name, $lock_id = NULL, array $log_types = [ULTIMATE_CRON_LOG_TYPE_NORMAL]) {
    $log_entry = new $this->logEntryClass($name, $this);

    $job = ultimate_cron_job_load($name);
    $settings = $job->getSettings('logger');

    if (!$lock_id) {
      $cache = cache_get('uc-name:' . $name, $settings['bin']);
      if (empty($cache) || empty($cache->data)) {
        return $log_entry;
      }
      $lock_id = $cache->data;
    }
    $cache = cache_get('uc-lid:' . $lock_id, $settings['bin']);

    if (!empty($cache->data)) {
      $log_entry->setData((array) $cache->data);
      $log_entry->finished = TRUE;
    }
    return $log_entry;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogEntries($name, array $log_types, $limit = 10) {
    $log_entry = $this->load($name);
    return $log_entry->lid ? array($log_entry) : array();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['bin'] = array(
      '#type' => 'textfield',
      '#title' => t('Cache bin'),
      '#description' => t('Select which cache bin to use for storing logs.'),
      '#default_value' => $this->configuration['bin'],
      '#fallback' => TRUE,
      '#required' => TRUE,
    );

    $form['timeout'] = array(
      '#type' => 'textfield',
      '#title' => t('Cache timeout'),
      '#description' => t('Seconds before cache entry expires (0 = never, -1 = on next general cache wipe).'),
      '#default_value' => $this->configuration['timeout'],
      '#fallback' => TRUE,
      '#required' => TRUE,
    );

    return $form;
  }
}
