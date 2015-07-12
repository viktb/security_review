<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\ExecutablePhp.
 */

namespace Drupal\security_review\Checks;

use Drupal;
use Drupal\Component\PhpStorage\FileStorage;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\security_review\Security;
use Drupal\security_review\SecurityReview;
use GuzzleHttp\Exception\RequestException;

/**
 * Check if PHP files written to the files directory can be executed.
 */
class ExecutablePhp extends Check {

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Executable PHP';
  }

  /**
   * {@inheritdoc}
   */
  public function run($CLI = FALSE) {
    $result = CheckResult::SUCCESS;
    $findings = array();

    global $base_url;
    $http_client = \Drupal::service('http_client');
    /** @var \Drupal\Core\Http\Client $http_client */

    // Test file data.
    $message = 'Security review test ' . date('Ymdhis');
    $content = "<?php\necho '" . $message . "';";
    $file_path = PublicStream::basePath() . '/security_review_test.php';

    // Create the test file.
    if ($test_file = @fopen('./' . $file_path, 'w')) {
      fwrite($test_file, $content);
      fclose($test_file);
    }

    // Try to access the test file.
    try {
      $response = $http_client->get($base_url . '/' . $file_path);
      if ($response->getStatusCode() == 200 && $response->getBody() === $message) {
        $result = CheckResult::FAIL;
        $findings[] = 'executable_php';
      }
    } catch (RequestException $e) {
      // Access was denied to the file.
    }

    // Remove the test file.
    if (file_exists('./' . $file_path)) {
      @unlink('./' . $file_path);
    }

    // Check for presence of the .htaccess file and if the contents are correct.
    $htaccess_path = PublicStream::basePath() . '/.htaccess';
    if (!file_exists($htaccess_path)) {
      $result = CheckResult::FAIL;
      $findings[] = 'missing_htaccess';
    }
    else {
      $contents = file_get_contents($htaccess_path);
      $expected = FileStorage::htaccessLines(FALSE);

      // Trim each line separately then put them back together.
      $contents = implode("\n", array_map('trim', explode("\n", trim($contents))));
      $expected = implode("\n", array_map('trim', explode("\n", trim($expected))));

      if ($contents !== $expected) {
        $result = CheckResult::FAIL;
        $findings[] = 'incorrect_htaccess';
      }
      $writable_htaccess = FALSE;
      if (!$CLI) {
        $writable_htaccess = is_writable($htaccess_path);
      }
      elseif ($CLI) {
        $writable = Security::cliFindWritableInPath($htaccess_path);
        $writable_htaccess = !empty($writable);
      }
      if ($writable_htaccess) {
        $findings[] = 'writable_htaccess';
        if ($result !== CheckResult::FAIL) {
          $result = CheckResult::WARN;
        }
      }
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function runCli() {
    return $this->run(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = array();
    $paragraphs[] = "The Drupal files directory is for user-uploaded files and by default provides some protection against a malicious user executing arbitrary PHP code against your site.";
    $paragraphs[] = t(
      'Read more about the !risks.',
      array(
        '!risks' => Drupal::l(
          'risk of PHP code execution on Drupal.org',
          Url::fromUri('https://drupal.org/node/615888')
        )
      )
    );

    return array(
      '#theme' => 'check_help',
      '#title' => 'Executable PHP in files directory',
      '#paragraphs' => $paragraphs
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $paragraphs = array();
    foreach ($result->findings() as $label) {
      switch ($label) {
        case 'executable_php':
          $paragraphs[] = t('Security Review was able to execute a PHP file written to your files directory.');
          break;
        case 'missing_htaccess':
          $directory = PublicStream::basePath();
          $paragraphs[] = t("The .htaccess file is missing from the files directory at !path", array('!path' => $directory));
          $paragraphs[] = t("Note, if you are using a webserver other than Apache you should consult your server's documentation on how to limit the execution of PHP scripts in this directory.");
          break;
        case 'incorrect_htaccess':
          $paragraphs[] = t("The .htaccess file exists but does not contain the correct content. It is possible it's been maliciously altered.");
          break;
        case 'writable_htaccess':
          $paragraphs[] = t("The .htaccess file is writable which poses a risk should a malicious user find a way to execute PHP code they could alter the .htaccess file to allow further PHP code execution.");
          break;
      }
    }

    return array(
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => array()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $paragraphs = array();
    $directory = PublicStream::basePath();
    foreach ($result->findings() as $label) {
      switch ($label) {
        case 'executable_php':
          $paragraphs[] = t('PHP file executed in !path', array('!path' => $directory));
          break;
        case 'missing_htaccess':
          $paragraphs[] = t('.htaccess is missing from !path', array('!path' => $directory));
          break;
        case 'incorrect_htaccess':
          $paragraphs[] = t('.htaccess wrong content');
          break;
        case 'writable_htaccess':
          $paragraphs[] = t('.htaccess writable');
          break;
      }
    }
    return implode("\n", $paragraphs);
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($resultConst) {
    switch ($resultConst) {
      case CheckResult::SUCCESS:
        return 'PHP files in the Drupal files directory cannot be executed.';
      case CheckResult::FAIL:
        return 'PHP files in the Drupal files directory can be executed.';
      case CheckResult::WARN:
        return 'The .htaccess file in the files directory is writable.';
      default:
        return 'Unexpected result.';
    }
  }

}
