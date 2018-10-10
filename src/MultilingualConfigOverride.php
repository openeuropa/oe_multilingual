<?php

namespace Drupal\oe_multilingual;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Override configuration values related to multilingual elements.
 */
class MultilingualConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    foreach ($names as $config_name) {
      // Force the default site's language as the default language and prevent
      // the users from changing it when creating a node.
      if (preg_match('/^language\.content_settings\.node\.(.*)$/', $config_name)) {
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
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'oe_multilingual.language_configs_override';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

}
