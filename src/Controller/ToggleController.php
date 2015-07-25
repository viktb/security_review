<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\ToggleController.
 */

namespace Drupal\security_review\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\security_review\Checklist;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Responsible for handling the toggle links on the Run & Review page.
 */
class ToggleController extends ControllerBase {

  /**
   * The CSRF Token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   */
  protected $csrfToken;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request $request
   */
  protected $request;

  /**
   * Constructs a ToggleController.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF Token generator.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   */
  public function __construct(CsrfTokenGenerator $csrfToken, RequestStack $request) {
    $this->csrfToken = $csrfToken;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token'),
      $container->get('request_stack')
    );
  }

  /**
   * Handles check toggling.
   *
   * @param string $check_id
   *   The ID of the check.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public function index($check_id) {
    // Determine access type.
    $ajax = $this->request->query->get('js') == 1;

    // Validate token.
    $token = $this->request->query->get('token');
    if ($this->csrfToken->validate($token, $check_id)) {
      $check = Checklist::getCheckById($check_id);
      if ($check != NULL) {
        if ($check->isSkipped()) {
          $check->enable();
        }
        else {
          $check->skip();
        }
      }
      // Output.
      if ($ajax) {
        return new JsonResponse(array(
          'skipped' => $check->isSkipped(),
          'toggle_text' => $check->isSkipped() ? $this->t('Enable') : $this->t('Skip'),
          'toggle_href' => Url::fromRoute(
            'security_review.toggle',
            array('check_id' => $check->id()),
            array(
              'query' => array(
                'token' => $this->csrfToken->get($check->id()),
                'js' => 1,
              ),
            )
          ),
        ));
      }
      else {
        // Set message.
        if ($check->isSkipped()) {
          drupal_set_message($this->t(
            '%name check skipped.',
            array('%name' => $check->getTitle())
          ));
        }
        else {
          drupal_set_message($this->t(
            '%name check no longer skipped.',
            array('%name' => $check->getTitle())
          ));
        }

        // Redirect back to Run & Review.
        return $this->redirect('security_review');
      }
    }

    // Go back to Run & Review if the access was wrong.
    return $this->redirect('security_review');
  }

}
