<?php

/**
 * @file
 * Contains \OEMultilingualSubContext.
 */

declare(strict_types = 1);

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Drupal\Core\Language\LanguageInterface;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\locale\SourceString;

/**
 * Behat step definitions for testing the oe_multilingual module.
 */
class OEMultilingualSubContext extends DrupalSubContextBase {

  /**
   * The backed up translations.
   *
   * @var array
   */
  protected $translations = [];

  /**
   * Translates an interface string.
   *
   * @param string $string
   *   The string to translate.
   * @param string $language
   *   The language to translate into.
   * @param string $value
   *   What to translate to.
   *
   * @Given I translate :string in :language to :value
   */
  public function translateString(string $string, string $language, string $value): void {
    /** @var \Drupal\locale\StringStorageInterface $locale_storage */
    $locale_storage = \Drupal::service('locale.storage');
    $language = $this->getLanguageByName($language);

    $source = $locale_storage->findString(['source' => $string]);
    if (!$source instanceof SourceString) {
      throw new \Exception(sprintf('Missing string to translate: %s', $source));
    }

    // Backup existing translation.
    $translation = $locale_storage->findTranslation(['lid' => $source->getId(), 'language' => $language->getId()]);
    $this->translations[$source->getId()] = [
      'language' => $language,
      'translation' => clone $translation,
    ];

    $new_translation = $translation->isTranslation() ? $translation : $locale_storage->createTranslation($source->getValues(['lid']) + ['language' => $language->getId()]);
    $new_translation->setString($value);
    $new_translation->save();
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
