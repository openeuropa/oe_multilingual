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