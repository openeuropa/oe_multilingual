<?php

/**
 * @file
 * Post update hooks.
 */

declare(strict_types = 1);

/**
 * Updates the language selection page to not include the private files.
 */
function oe_multilingual_selection_page_post_update_blacklist_private_files(array &$sandbox): void {
  $config = \Drupal::configFactory()
    ->getEditable('language_selection_page.negotiation');
  $blacklist = $config->get('blacklisted_paths');
  $blacklist[] = '/system/files';
  $blacklist[] = '/system/files/*';
  $config->set('blacklisted_paths', $blacklist);
  $config->save();
}
