<?php

/**
 * @file
 * Post update functions for OpenEuropa Multilingual module.
 */

declare(strict_types = 1);

/**
 * Invalidate service containers.
 *
 * Invalidate service containers for applying changes of
 * oe_multilingual.local_translations_batcher service.
 */
function oe_multilingual_post_update_00001_invalidate_containers_cache(): void {
  \Drupal::service('kernel')->invalidateContainer();
}

/**
 * Apply EU category to EU languages.
 */
function oe_multilingual_post_update_00002(): void {
  $eu_languages = [
    'en',
    'sv',
    'lv',
    'pl',
    'lt',
    'da',
    'fr',
    'hr',
    'sl',
    'ro',
    'es',
    'cs',
    'nl',
    'ga',
    'mt',
    'pt-pt',
    'it',
    'fi',
    'el',
    'hu',
    'et',
    'de',
    'bg',
    'sk',
  ];
  $languages = \Drupal::entityTypeManager()->getStorage('configurable_language')->loadMultiple($eu_languages);
  foreach ($languages as $language) {
    /** @var \Drupal\language\Entity\ConfigurableLanguage $language */
    $language->setThirdPartySetting('oe_multilingual', 'category', 'eu');
    $language->save();
  }
}

/**
 * Change the order of languages.
 */
function oe_multilingual_post_update_00003(): void {
  $language_weight_mapping = [
    'cs' => 2,
    'da' => 3,
    'de' => 4,
    'el' => 6,
    'es' => 1,
    'et' => 5,
    'en' => 7,
  ];
  $languages = \Drupal::entityTypeManager()->getStorage('configurable_language')->loadMultiple(array_keys($language_weight_mapping));
  foreach ($languages as $language) {
    /** @var \Drupal\language\Entity\ConfigurableLanguage $language */
    $language->setWeight($language_weight_mapping[$language->id()]);
    $language->save();
  }
}
