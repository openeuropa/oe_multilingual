<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual\Tests\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\Core\Language\LanguageInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\oe_multilingual\Tests\Behat\Traits\ContentManagerTrait;

/**
 * Provides the functionality for interacting with multilingual functionality.
 */
class RawMultilingualContext extends RawDrupalContext {
  use ContentManagerTrait;

  /**
   * The backed up translations.
   *
   * @var array
   */
  protected $translations = [];

  /**
   * The config context.
   *
   * @var \Drupal\DrupalExtension\Context\ConfigContext
   */
  protected $configContext;

  /**
   * Gathers some other contexts.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The before scenario scope.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();
    $this->configContext = $environment->getContext('Drupal\DrupalExtension\Context\ConfigContext');
  }

  /**
   * Enable OpenEuropa Multilingual Selection Page module.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The Hook scope.
   *
   * @BeforeScenario @selection-page
   */
  public function setupSelectionPage(BeforeScenarioScope $scope): void {
    \Drupal::service('module_installer')->install(['oe_multilingual_selection_page']);
  }

  /**
   * Disable OpenEuropa Multilingual Selection Page module.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   The Hook scope.
   *
   * @AfterScenario @selection-page
   */
  public function revertSelectionPage(AfterScenarioScope $scope): void {
    \Drupal::service('module_installer')->uninstall([
      'oe_multilingual_selection_page',
      'language_selection_page',
    ]);
  }

  /**
   * Get language ID given its name.
   *
   * @param string $name
   *   Language name.
   *
   * @return string
   *   Language ID.
   */
  protected function getLanguageIdByName(string $name): string {
    foreach (\Drupal::languageManager()->getLanguages() as $language) {
      if ($language->getName() === $name) {
        return $language->getId();
      }
    }

    throw new \InvalidArgumentException("Language '{$name}' not found.");
  }

  /**
   * Restores backed up translations.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $afterScenarioScope
   *   The scope.
   *
   * @AfterScenario
   */
  public function restoreTranslations(AfterScenarioScope $afterScenarioScope): void {
    if (!$this->translations) {
      return;
    }

    /** @var \Drupal\locale\StringStorageInterface $locale_storage */
    $locale_storage = \Drupal::service('locale.storage');

    foreach ($this->translations as $info) {
      /** @var \Drupal\Core\Language\LanguageInterface $language */
      $language = $info['language'];
      /** @var \Drupal\locale\TranslationString $translation */
      $translation = $info['translation'];

      // If there is a translation value, we need to restore it by simply
      // giving it a save since we had cloned it.
      if ($translation->isTranslation()) {
        $translation->save();
        continue;
      }

      // Otherwise, we need to delete the translation for that source.
      $locale_storage->deleteTranslations(['lid' => $translation->getId(), 'language' => $language->getId()]);
    }
  }

  /**
   * Get language given its name.
   *
   * @param string $name
   *   Language name.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The language.
   */
  protected function getLanguageByName(string $name): LanguageInterface {
    foreach (\Drupal::languageManager()->getLanguages() as $language) {
      if ($language->getName() === $name) {
        return $language;
      }
    }

    throw new \InvalidArgumentException(sprintf('Language %s not found.', $name));
  }

}
