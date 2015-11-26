<?php

/**
 * @file
 * Contains \Drupal\ultimate_cron\CronJobListBuilder.
 */

namespace Drupal\ultimate_cron;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of cron jobs.
 *
 * @see \Drupal\ultimate_cron\Entity\CronJob
 */
class CronJobListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array();
    $header['module'] = array('data' => t('Module'));
    $header['title'] = array('data' => t('Title'));
    $header['scheduled'] = array('data' => t('Scheduled'));
    $header['started'] = array('data' => t('Last Run'));
    $header['duration'] = array('data' => t('Duration in ms'));
    $header['status'] = array('data' => t('Status'));
    return $header + parent::buildHeader();
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ultimate_cron\Entity\CronJob $entity */
    $icon = drupal_get_path('module', 'ultimate_cron') . '/icons/hourglass.png';
    $job_status = SafeMarkup::format('<img src="@s" title="@l"><span></span></img>', array("@s" => file_create_url($icon), "@l" => "Job is behind schedule!"));

    $entry = $entity->loadLatestLogEntry();
    $row['module'] = array(
      'data' => SafeMarkup::checkPlain($entity->getModuleName()),
      'class' => array('ctools-export-ui-module'),
      'title' => strip_tags($entity->getModuleDescription()),
    );
    $row['title'] = $this->getLabel($entity);
    if ($entity->isScheduled()) {
      $row['scheduled'] = SafeMarkup::format('@label @icon', array('@label' => $entity->getPlugin('scheduler')->formatLabel($entity), '@icon' => $job_status));
    }
    else {
      $row['scheduled'] = $entity->getPlugin('scheduler')->formatLabel($entity);
    }
    // If the start time is 0, the jobs have never been run.
    $row['started'] = $entry->start_time ? \Drupal::service('date.formatter')->format($entry->start_time, "short") : $this->t('Never');
    // In milliseconds.
    $row['duration'] = round(($entry->end_time - $entry->start_time) * 1000, 0);
    if (!$entity->isValid()) {
      $row['status'] = $this->t('Missing');
    }
    elseif (!$entity->status()) {
      $row['status'] = $this->t('Disabled');
    }
    else {
      $row['status'] = $this->t('Enabled');
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * Returns the default operations for the entity.
   *
   * @param EntityInterface $entity
   *   The entity of this row.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   parent::getOperations().
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->status() && $entity->isValid()) {
      $operations += ['run' => [
        'title' => t('Run'),
        'weight' => 9,
        'url' => $entity->urlInfo('run'),
      ]];
    }

    // Invalid jobs can not be enabled nor disabled.
    if (!$entity->isValid()) {
      unset($operations['disable']);
      unset($operations['enable']);
    }

    return $operations;
  }

  /**
   * Build a row based on the item.
   *
   * By default all of the rows are placed into a table by the render
   * method, so this is building up a row suitable for theme('table').
   * This doesn't have to be true if you override both.
   */
  public function list_build_row($item, &$form_state, $operations) {
    $schema = ctools_export_get_schema($this->plugin['schema']);

    // Started and duration.
    $item->lock_id = isset($item->lock_id) ? $item->lock_id : $item->isLocked();
    $item->log_entry = isset($item->log_entry) ? $item->log_entry : $item->loadLatestLogEntry();
    $item->progress = isset($item->progress) ? $item->progress : $item->getProgress();
    if ($item->log_entry->lid && $item->lock_id && $item->log_entry->lid !== $item->lock_id) {
      $item->log_entry = $item->loadLogEntry($item->lock_id);
    }

    // Setup row.
    $this->rows[$name]['id'] = $name;
    $this->rows[$name]['data'] = array();

    // Enabled/disabled.
    $this->rows[$name]['class'] = !empty($item->disabled) ? array('ctools-export-ui-disabled') : array('ctools-export-ui-enabled');

    // Module.
    $this->rows[$name]['data'][] = array(
      'data' => SafeMarkup::checkPlain($item->getModuleName()),
      'class' => array('ctools-export-ui-module'),
      'title' => strip_tags($item->getModuleDescription()),
    );

    // If we have an admin title, make it the first row.
    if (!empty($this->plugin['export']['admin_title'])) {
      $this->rows[$name]['data'][] = array(
        'data' => SafeMarkup::checkPlain($item->{$this->plugin['export']['admin_title']}),
        'class' => array('ctools-export-ui-title'),
        'title' => strip_tags($item->name),
      );
    }

    // Schedule settings.
    $label = $item->getPlugin('scheduler')->formatLabel($item);
    $label = str_replace("\n", '<br/>', $label);
    if ($behind = $item->isBehindSchedule()) {
      $this->jobs_behind++;
      $label = "<em>$label</em><br/>" . format_interval($behind) . ' ' . t('behind schedule');
    }
    $this->rows[$name]['data'][] = array(
      'data' => $label,
      'class' => array('ctools-export-ui-scheduled'),
      'title' => strip_tags($item->getPlugin('scheduler')->formatLabelVerbose($item)),
    );

    $this->rows[$name]['data'][] = array(
      'data' => $item->log_entry->formatStartTime(),
      'class' => array('ctools-export-ui-start-time'),
      'title' => strip_tags($item->log_entry->formatInitMessage()),
    );

    $progress = $item->lock_id ? $item->formatProgress() : '';
    $this->rows[$name]['data'][] = array(
      'data' => '<span class="duration-time" data-src="' . $item->log_entry->getDuration() . '">' . $item->log_entry->formatDuration() . '</span> <span class="duration-progress">' . $progress . '</span>',
      'class' => array('ctools-export-ui-duration'),
      'title' => strip_tags($item->log_entry->formatEndTime()),
    );

    // Status.
    if ($item->lock_id && $item->log_entry->lid == $item->lock_id) {
      list($status, $title) = $item->getPlugin('launcher')->formatRunning($item);
    }
    elseif ($item->log_entry->start_time && !$item->log_entry->end_time) {
      list($status, $title) = $item->getPlugin('launcher')->formatUnfinished($item);
    }
    else {
      list($status, $title) = $item->log_entry->formatSeverity();
      $title = $item->log_entry->message ? $item->log_entry->message : $title;
    }
    $this->rows[$name]['data'][] = array(
      'data' => $status,
      'class' => array('ctools-export-ui-status'),
      'title' => strip_tags($title),
    );


    // Storage.
    $this->rows[$name]['data'][] = array('data' => SafeMarkup::checkPlain($item->{$schema['export']['export type string']}), 'class' => array('ctools-export-ui-storage'));

  }

}
