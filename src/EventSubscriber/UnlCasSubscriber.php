<?php

namespace Drupal\unl_cas\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UnlCasSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new UnlCasSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Check if the unl_sso cookie is set as an indicator the user may be
   * logged into CAS and try to log in if so.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onRequest(RequestEvent $event) {
    $path = $event->getRequest()->getPathInfo();
    // Bail if a cas path is being requested.
    if (str_starts_with($path, '/cas')) {
      return;
    }

    if ($this->currentUser->isAnonymous() && $event->getRequest()->cookies->get('unl_sso')) {
      $destination = $event->getRequest()->getRequestUri();
      $response = new RedirectResponse(
        Url::fromRoute('cas.login', [], ['query' => ['destination' => $destination]])->toString(),
        302,
        ['Cache-Control' => 'no-cache'],
      );
      $event->setResponse($response);
    }
  }

  /**
    * {@inheritdoc}
    */
  public static function getSubscribedEvents(): array {
    // Set a high priority so it is executed before routing.
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    return $events;
  }

}
