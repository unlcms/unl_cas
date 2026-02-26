<?php

namespace Drupal\unl_cas\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class UnlCasSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['unl_cas.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_cas_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('unl_cas.settings');

    $form['enable_cookie_check'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable automatic CAS login from unl_sso cookie'),
      '#description' => $this->t('If checked, users with an unl_sso cookie but no Drupal session will be redirected to CAS login in an attempt to do an automatic gateway login.'),
      '#default_value' => $config->get('enable_cookie_check'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('unl_cas.settings')
      ->set('enable_cookie_check', $form_state->getValue('enable_cookie_check'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
