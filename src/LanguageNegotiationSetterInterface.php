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
  public function enableNegotiationMethods(array $methods): void;

  /**
   * Set interface language negotiation settings.
   *
   * Usage:
   *
   * \Drupal::service('oe_multilingual.language_negotiation_setter')
   *    ->setInterfaceSettings([
   *      LanguageNegotiationUrl::METHOD_ID => -19,
   *      LanguageNegotiationSelected::METHOD_ID => 20,
   *    ]);
   *
   * @param array $settings
   *   Array of language negotiation method names with their weights.
   */
  public function setInterfaceSettings(array $settings): void;

  /**
   * Add given settings to current interface language negotiation.
   *
   * Usage:
   *
   * \Drupal::service('oe_multilingual.language_negotiation_setter')
   *    ->addInterfaceSettings([
   *      LanguageNegotiationUrl::METHOD_ID => -19,
   *    ]);
   *
   * @param array $settings
   *   Array of language negotiation method names with their weights.
   */
  public function addInterfaceSettings(array $settings): void;

  /**
   * Set content language negotiation settings.
   *
   * Usage:
   *
   * \Drupal::service('oe_multilingual.language_negotiation_setter')
   *    ->setContentSettings([
   *      LanguageNegotiationUrl::METHOD_ID => -19,
   *      LanguageNegotiationSelected::METHOD_ID => 20,
   *    ]);
   *
   * @param array $settings
   *   Array of language negotiation method names with their weights.
   */
  public function setContentSettings(array $settings): void;

  /**
   * Add given settings to current content language negotiation.
   *
   * Usage:
   *
   * \Drupal::service('oe_multilingual.language_negotiation_setter')
   *    ->addContentSettings([
   *      LanguageNegotiationUrl::METHOD_ID => -19,
   *    ]);
   *
   * @param array $settings
   *   Array of language negotiation method names with their weights.
   */
  public function addContentSettings(array $settings): void;

}
