<?php

/**
 * @file
 * Drush commands for Security Review module.
 */

use Drupal\security_review\CheckResult;

/**
 * Implements hook_drush_command().
 */
function security_review_drush_command() {
  $items = [];

  $items['security-review'] = [
    'aliases' => ['secrev'],
    'callback' => 'security_review_drush',
    'description' => "Run the Security Review checklist",
    'options' => [
      'store' => 'Write results to the database',
      'log' => 'Log results of each check to watchdog, defaults to off',
      'lastrun' => 'Do not run the checklist, just print last results',
      'check' => 'Comma-separated list of specified checks to run. See README.txt for list of options',
      'skip' => 'Comma-separated list of specified checks not to run. This takes precedence over --check.',
      'short' => "Short result messages instead of full description (e.g. 'Text formats')",
      'results' => 'Show the incorrect settings for failed checks',
    ],
    'examples' => [
      'secrev' => 'Run the checklist and output the results',
      'secrev --store' => 'Run the checklist, store, and output the results',
      'secrev --lastrun' => 'Output the stored results from the last run of the checklist',
    ],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function security_review_drush_help($section) {
  switch ($section) {
    case 'drush:security-review':
      return dt("Run configuration security checks on your Drupal site.");
  }
}

/**
 * Runs a checklist and displays results.
 */
function security_review_drush() {
  /** @var \Drupal\security_review\SecurityReview $security_review */
  $security_review = Drupal::service('security_review');

  /** @var \Drupal\security_review\Checklist $checklist */
  $checklist = Drupal::service('security_review.checklist');

  $store = drush_get_option('store');
  $log = drush_get_option('log');
  $last_run = drush_get_option('lastrun');
  $specific_checks = drush_get_option_list('check');
  $skip_checks = drush_get_option_list('skip');
  $short_titles = drush_get_option('short');
  $show_findings = drush_get_option('results');

  if (!Drupal::moduleHandler()->moduleExists('security_review')) {
    // Checklist is being executed when module is disabled. Deny these features.
    $store = FALSE;
    $log = FALSE;
    $last_run = FALSE;
  }

  // Set temporary logging.
  if ($log) {
    $security_review->setLogging(TRUE, TRUE);
  }

  if (!empty($short_titles)) {
    $short_titles = TRUE;
  }
  else {
    $short_titles = FALSE;
  }

  $results = [];
  if (!$last_run) {
    // Initialize the arrays.
    $checks = [];
    $skip = [];

    // Fill the $checks array.
    if (!empty($specific_checks)) {
      // Get explicitly specified checks.
      foreach ($specific_checks as $specific_check) {
        $checks[] = _security_review_drush_get_check($specific_check);
      }
    }
    else {
      // Get the whole checklist.
      $checks = $checklist->getChecks();
    }

    // Remove checks listed after --skip.
    if (!empty($skip_checks)) {
      foreach ($skip_checks as $skip_check) {
        $skip[] = _security_review_drush_get_check($skip_check);
      }
    }

    // Remove checks that are being skipped if storing.
    if ($store) {
      foreach ($checks as $check) {
        if ($check->isSkipped()) {
          $skip[] = $check;
        }
      }
    }

    // Remove the skipped checks.
    foreach ($skip as $skip_check) {
      foreach ($checks as $key => $check) {
        if ($check->id() == $skip_check->id()) {
          unset($checks[$key]);
        }
      }
    }

    if (empty($checks)) {
      return drush_set_error('EMPTY_CHECKLIST', dt("No checks to run. Run 'drush help secrev' for option use or consult the drush section of API.txt for further help."));
    }

    // Run the checks.
    $results = $checklist->runChecks($checks, TRUE);

    // Store the results.
    if ($store) {
      $checklist->storeResults($results);
    }
  }
  else {
    foreach ($checklist->getChecks() as $check) {
      $last_result = $check->lastResult($show_findings);
      if ($last_result instanceof CheckResult) {
        $results[] = $last_result;
      }
    }
  }

  _security_review_drush_print_results($results, $short_titles, $show_findings);
}

/**
 * Helper function for parsing input check name strings.
 *
 * @param string $check_name
 *   The check to get.
 *
 * @return \Drupal\security_review\Check|null
 *   The found Check.
 */
function _security_review_drush_get_check($check_name) {
  /** @var \Drupal\security_review\Checklist $checklist */
  $checklist = Drupal::service('security_review.checklist');

  $namespace = 'security_review';
  $title = $check_name;
  if (strpos($check_name, ':') !== FALSE) {
    list($namespace, $title) = explode(':', $check_name);
  }
  return $checklist->getCheck($namespace, $title);
}

/**
 * Helper function to print Security Review results using drush_log().
 *
 * @param \Drupal\security_review\CheckResult[] $results
 *   An array of CheckResults.
 * @param bool $short_titles
 *   Whether to use short message (check title) or full check success or failure
 *   message.
 * @param bool $show_findings
 *   Whether to print failed check results.
 */
function _security_review_drush_print_results(array $results, $short_titles = FALSE, $show_findings = FALSE) {
  foreach ($results as $result) {
    if ($result instanceof CheckResult) {
      $check = $result->check();
      $message = $short_titles ? $check->getTitle() : $result->resultMessage();
      $status = 'notice';

      switch ($result->result()) {
        case CheckResult::SUCCESS:
          $status = 'success';
          break;

        case CheckResult::FAIL:
          $status = 'failed';
          break;

        case CheckResult::WARN:
          $status = 'warning';
          break;

        case CheckResult::INFO:
          $status = 'info';
          break;

        case CheckResult::HIDE:
          continue 2;
      }

      if ($show_findings) {
        $findings = trim($result->check()->evaluatePlain($result));
        if ($findings != '') {
          $message .= "\n" . $findings;
        }
      }

      drush_log($message, $status);
    }
  }
}
