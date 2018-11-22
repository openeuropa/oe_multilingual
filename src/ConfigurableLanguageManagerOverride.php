<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Override ConfigurableLanguageManagerOverride to use third party settings.
 */
class ConfigurableLanguageManagerOverride extends ConfigurableLanguageManager {

  /**
   * {@inheritdoc}
   */
  public function getLanguages($flags = LanguageInterface::STATE_CONFIGURABLE) {
    $languages = parent::getLanguages($flags);
    $configurable_languages = [];
    foreach ($languages as $langcode => $language) {
      $configurable_languages[$langcode] = ConfigurableLanguage::load($langcode);
    }
    uasort($configurable_languages, function ($a, $b) {
      return $a->getThirdPartySetting("oe_multilingual", "weight") <=> $b->getThirdPartySetting("oe_multilingual", "weight");
    });
    return array_replace($configurable_languages, $languages);

  }

  /**
   * {@inheritdoc}
   */
  public function getNativeLanguages() {
    $languages = $this->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    $natives = [];

    $original_language = $this->getConfigOverrideLanguage();

    foreach ($languages as $langcode => $language) {
      $this->setConfigOverrideLanguage($language);
      $native_language = ConfigurableLanguage::load($langcode);
      $native_language->setName($native_language->getThirdPartySetting("oe_multilingual", "native_language"));
      $natives[$langcode] = $native_language;
    }
    $this->setConfigOverrideLanguage($original_language);

    return $natives;
  }

}
