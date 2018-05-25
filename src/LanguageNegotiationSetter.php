<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Helper service for language negotiation method configuration.
 */
class LanguageNegotiationSetter implements LanguageNegotiationSetterInterface {

  /**
     * Configuration name.
     */
  const CONFIG_NAME = 'language.types';

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new LanguageNegotiationSetter object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Enable given language negotiation methods.
   *
   * @param array $methods
   *   Array of language negotiation method names.
   */
  public function enableNegotiationMethods(array $methods):void {
    $this->configFactory
      ->getEditable(self::CONFIG_NAME)
      ->set('configurable', $methods)
      ->save();
  }

  /**
   * Set interface language negotiation settings.
   *
   * @param array $settings
   *   Array of language negotiation method names with their weights.
   */
  public function setInterfaceSettings(array $settings):void {
    $this->configFactory
      ->getEditable(self::CONFIG_NAME)
      ->set('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.enabled', $settings)
      ->set('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.method_weights', $settings)
      ->save();
  }

  /**
   * Set content language negotiation settings.
   *
   * @param array $settings
   *   Array of language negotiation method names with their weights.
   */
  public function setContentSettings(array $settings):void {
    $this->configFactory
      ->getEditable(self::CONFIG_NAME)
      ->set('negotiation.' . LanguageInterface::TYPE_CONTENT . '.enabled', $settings)
      ->set('negotiation.' . LanguageInterface::TYPE_CONTENT . '.method_weights', $settings)
      ->save();
  }

}
