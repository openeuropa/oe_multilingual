<?php

/**
 * @file
 * Data updates for oe_multilingual.
 */

declare(strict_types = 1);

/**
 * Implements hook_post_update_NAME().
 */
function oe_multilingual_post_update_import_translations(&$sandbox) {
  $languages = \Drupal::service('language_manager')->getLanguages();
  /** @var \Drupal\Core\Language\LanguageInterface $language */
  foreach ($languages as $language) {
    \Drupal::service('language.config_factory_override')
      ->installLanguageOverrides($language->getId());
  }
}
