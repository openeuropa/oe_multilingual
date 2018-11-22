<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Override multilingual related services.
 */
class OeMultilingualServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('language_manager')
      ->setClass('Drupal\oe_multilingual\ConfigurableLanguageManagerOverride');
  }

}
