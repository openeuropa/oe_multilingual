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

    $definition = $container->getDefinition('language_manager');
    $definition->setClass('Drupal\oe_multilingual\ConfigurableLanguageManagerOverride');
  }

}
