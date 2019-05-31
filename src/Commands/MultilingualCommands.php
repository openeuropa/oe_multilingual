<?php

namespace Drupal\oe_multilingual\Commands;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for the OE Multilingual module.
 */
class MultilingualCommands extends DrushCommands {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * @var \Drupal\Core\Extension\ProfileExtensionList
   */
  protected $profileExtensionList;

  /**
   * MultilingualCommands constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   * @param \Drupal\Core\Extension\ProfileExtensionList $profileExtensionList
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, ThemeHandlerInterface $themeHandler, LanguageManagerInterface $languageManager, ModuleExtensionList $moduleExtensionList, ThemeExtensionList $themeExtensionList, ProfileExtensionList $profileExtensionList) {
    $this->moduleHandler = $moduleHandler;
    $this->themeHandler = $themeHandler;
    $this->languageManager = $languageManager;
    $this->moduleExtensionList = $moduleExtensionList;
    $this->themeExtensionList = $themeExtensionList;
    $this->profileExtensionList = $profileExtensionList;
  }

  /**
   * Imports the translations from the local modules.
   *
   * It determines which modules it should import by checking inside their info
   * files for the `interface translation project` key.
   *
   * @command oe-multilingual:import-local-translations
   * @option langcodes A comma-separated list of language codes to update. If omitted, all translations will be updated.
   * @validate-module-enabled locale
   *
   * @param array $options
   *   Command options.
   */
  public function importLocalTranslations($options = ['langcodes' => self::OPT]): void {
    $this->moduleHandler->loadInclude('locale', 'fetch.inc');
    $this->moduleHandler->loadInclude('locale', 'bulk.inc');
    $this->moduleHandler->loadInclude('locale', 'translation.inc');
    $this->moduleHandler->loadInclude('locale', 'inc', 'locale.compare');

    if (!$options['langcodes']) {
      $languages = $this->languageManager->getLanguages();
      $langcodes = [];
      foreach ($languages as $language) {
        if ($language->getId() === 'en') {
          continue;
        }

        $langcodes[] = $language->getId();
      }
    }
    else {
      $langcodes = $options['langcodes'];
    }

    $extensions = $this->getExtensionsToTranslate();
    if (!$extensions) {
      return;
    }

    locale_translation_check_projects_local($extensions, $langcodes);
    $options = _locale_translation_default_update_options();
    $batch = locale_translation_batch_fetch_build($extensions, $langcodes, $options);
    batch_set($batch);
    if ($batch = locale_config_batch_update_components($options, $langcodes)) {
      batch_set($batch);
    }

    drush_backend_batch_process();
  }

  /**
   * Creates an array of modules, themes and profiles to be translated.
   *
   * These are the ones which contain local translations.
   *
   * @return array
   */
  protected function getExtensionsToTranslate(): array {
    $extensions = [];
    $all_extensions = array_merge($this->moduleExtensionList->getList(), $this->themeExtensionList->getList(), $this->profileExtensionList->getList());
    foreach ($all_extensions as $name => $module) {
      if (!isset($module->info['interface translation project'])) {
        continue;
      }

      // This will include also profiles.
      if (!$this->moduleHandler->moduleExists($name) && !$this->themeHandler->themeExists($name)) {
        continue;
      }

      $extensions[] = $name;
    }

    return $extensions;
  }

}
