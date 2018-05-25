<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

/**
 * Helper service for language negotiation method configuration.
 */
interface LanguageNegotiationSetterInterface {

  /**
   * Enable given language negotiation methods.
   *
   * @param array $methods
   *   Array of language negotiation method names.
   */
  public function enableNegotiationMethods(array $methods):void;

  /**
   * Set interface language negotiation settings.
   *
   * @param array $settings
   *   Array of language negotiation method names with their weights.
   */
  public function setInterfaceSettings(array $settings):void;

  /**
   * Set content language negotiation settings.
   *
   * @param array $settings
   *   Array of language negotiation method names with their weights.
   */
  public function setContentSettings(array $settings):void;

}
