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
 * Disable English translation.
 *
 * Disable English translations to prevent configuration changes to be
 * stored as locale translations.
 */
function oe_multilingual_post_update_00002_disable_english_translation(): void {
  $locale_configuration = \Drupal::configFactory()->getEditable('locale.settings');
  $locale_configuration->set('translate_english', FALSE);
  $locale_configuration->save();
}
