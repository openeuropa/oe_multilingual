<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorableConfigBase;

/**
 * Override configuration values related to multilingual elements.
 */
class MultilingualConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names): array {
    $overrides = [];
    foreach ($names as $config_name) {
      // Force the default site's language as the default language and prevent
      // the users from changing it when creating a node.
      if (strpos($config_name, 'language.content_settings.node.') !== FALSE) {
        $overrides[$config_name] = [
          'default_langcode' => 'site_default',
          'language_alterable' => FALSE,
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION): ?StorableConfigBase {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix(): string {
    return 'oe_multilingual.language_configs_override';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name): CacheableMetadata {
    return new CacheableMetadata();
  }

}
