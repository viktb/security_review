<?php

/**
 * @file
 * Drush commands for Security Review module.
 */

use Drupal\security_review\Checklist;
use Drupal\security_review\CheckResult;
use Drupal\security_review\Checks\FilePermissions;
use Drupal\security_review\SecurityReview;

/**
 * Implementation of hook_drush_command().
 */
function security_review_drush_command() {
  $items = array();

  $items['security-review'] = array(
    'aliases' => array('secrev'),
    'callback' => 'security_review_drush',
    'description' => "Run the Security Review checklist",
    'options' => array(
      'store' => 'Write results to the database',
      'log' => 'Log results of each check to watchdog, defaults to off',
      'lastrun' => 'Do not run the checklist, just print last results',
      'check' => 'Comma-separated list of specified checks to run. See README.txt for list of options',
      'skip' => 'Invert behavior of --check. Run all checks except specified checks',
      'short' => "Short result messages instead of full description (e.g. 'Text formats')",
      'results' => 'Show the incorrect settings for failed checks',
    ),
    'examples' => array(
      'secrev' => 'Run the checklist and output the results',
      'secrev --store' => 'Run the checklist, store, and output the results',
      'secrev --lastrun' => 'Output the stored results from the last run of the checklist'
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL
  );

  return $items;
}

/**
 * Implementation of hook_drush_help().
 */
function security_review_drush_help($section) {
  switch ($section) {
    case 'drush:security-review':
      return dt("Run configuration security checks on your Drupal site.");
  }
}

/**
 * Run checklist and display results command.
 */
function security_review_drush() {
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
    SecurityReview::setLogging(TRUE, TRUE);
  }

  if (!empty($short_titles)) {
    $short_titles = TRUE;
  }
  else {
    $short_titles = FALSE;
  }

  $results = array();
  if (!$last_run) {
    // Initialize the arrays.
    $checks = array();
    $skip = array();

    // Fill the $checks array.
    if (!empty($specific_checks)) {
      // Get explicitly specified checks.
      foreach ($specific_checks as $specific_check) {
        $checks[] = _security_review_drush_get_check($specific_check);
      }
    }
    else {
      // Get the whole checklist.
      $checks = Checklist::getChecks();

      // Unset file_perms of security_review because drush is running as a
      // different user.
      $skip_checks[] = 'security_review:file_perms';
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
    $results = Checklist::runChecks($checks, TRUE);

    // Store the results.
    if ($store) {
      Checklist::storeResults($results);
    }
  }
  else {
    foreach (Checklist::getChecks() as $check) {
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
 *   The Check.
 */
function _security_review_drush_get_check($check_name) {
  $namespace = 'security_review';
  $title = $check_name;
  if (strpos($check_name, ':') !== FALSE) {
    list($namespace, $title) = explode(':', $check_name);
  }
  return Checklist::getCheck($namespace, $title);
}

/**
 * Helper function to print Security Review results using drush_log().
 *
 * @param \Drupal\security_review\CheckResult[] $results
 *   An array of CheckResults.
 * @param boolean $short_titles
 *   Whether to use short message (check title) or full check success or failure
 *   message.
 * @param boolean $show_findings
 *   Whether to print failed check results.
 *
 * @return NULL
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
