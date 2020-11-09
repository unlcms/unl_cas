<?php

namespace Drupal\unl_cas;

use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Url;

/**
 * Provides a UNL CAS adapter.
 */
class UnlCasAdapter {

  /**
   * UNL CAS adapter.
   *
   * @var \Unl_Cas
   */
  protected $adapter;

  /**
   * Whether or not Zend framework is loaded.
   *
   * @var bool
   */
  protected $zendLoaded;

  /**
   * Provides helpers for redirect destinations.
   *
   * @var \Drupal\Core\Routing\RedirectDestination
   */
  protected $redirectDestination;

  /**
   * Constructs a UnlCasAdapter object.
   *
   * @param \Drupal\Core\Routing\RedirectDestination $redirect_destination
   *   Provides helpers for redirect destinations.
   */
  public function __construct(RedirectDestination $redirect_destination) {
    $this->redirectDestination = $redirect_destination;
    $this->zendLoaded = FALSE;
  }

  /**
   * Loads the Zend framework if it's not already loaded.
   */
  public function loadZendFramework() {
    if ($this->zendLoaded) {
      return;
    }

    set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../libraries');
    require_once 'Zend/Loader/Autoloader.php';
    $autoloader = \Zend_Loader_Autoloader::getInstance();
    $autoloader->registerNamespace('Unl_');
    $this->zendLoaded = TRUE;
  }

  /**
   * Returns a UNL CAS adapter object.
   *
   * @return \Unl_Cas
   *   A UNL CAS adapter object.
   */
  public function getAdapter() {
    $this->loadZendFramework();

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session $session
     */
    $session = \Drupal::service('session');

    // Start the session because if drupal doesn't then Zend_Session will.
    if (!$session->isStarted()) {
      /**
       * Drupal will start a 'lazy' session for anonymous users so that a cookie is not set (to help with things like varnish)
       * We can't $session->start(), because it is already lazy-started
       * Instead we need to migrate it to a stored (non-lazy) session
       */
      $session->migrate();
    }

    if (!$this->adapter) {
      if (\Drupal::request()->isSecure()) {
        $url = Url::fromRoute('unl_cas.validate', array(), array('absolute' => TRUE, 'query' => $this->redirectDestination->getAsArray(), 'https' => TRUE))->toString();
      } else {
        $url = Url::fromRoute('unl_cas.validate', array(), array('absolute' => TRUE, 'query' => $this->redirectDestination->getAsArray()))->toString();
      }
      $cas_server_url = \Drupal::config('unl_cas.settings')->get('cas_server_url');
      $this->adapter = new \Unl_Cas($url, $cas_server_url);
    }

    \Drupal::request()->query->remove('destination');

    return $this->adapter;
  }

}
