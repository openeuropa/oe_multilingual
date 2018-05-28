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
   * {@inheritdoc}
   */
  public function enableNegotiationMethods(array $methods): void {
    $this->configFactory
      ->getEditable(self::CONFIG_NAME)
      ->set('configurable', $methods)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function setInterfaceSettings(array $settings): void {
    $this->configFactory
      ->getEditable(self::CONFIG_NAME)
      ->set('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.enabled', $settings)
      ->set('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.method_weights', $settings)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function addInterfaceSettings(array $settings): void {
    $current = $this->configFactory
      ->get(self::CONFIG_NAME)
      ->get('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.enabled');

    $settings = array_merge($current, $settings);
    asort($settings);

    $this->setInterfaceSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function setContentSettings(array $settings): void {
    $this->configFactory
      ->getEditable(self::CONFIG_NAME)
      ->set('negotiation.' . LanguageInterface::TYPE_CONTENT . '.enabled', $settings)
      ->set('negotiation.' . LanguageInterface::TYPE_CONTENT . '.method_weights', $settings)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function addContentSettings(array $settings): void {
    $current = $this->configFactory
      ->get(self::CONFIG_NAME)
      ->get('negotiation.' . LanguageInterface::TYPE_CONTENT . '.enabled');

    $settings = array_merge($current, $settings);
    asort($settings);

    $this->setContentSettings($settings);
  }

}
