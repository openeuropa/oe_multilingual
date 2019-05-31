<?php

namespace Drupal\oe_multilingual\Commands;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * MultilingualCommands constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, LanguageManagerInterface $languageManager, ModuleExtensionList $moduleExtensionList) {
    $this->moduleHandler = $moduleHandler;
    $this->languageManager = $languageManager;
    $this->moduleExtensionList = $moduleExtensionList;
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
  public function localeTest($options = ['langcodes' => self::OPT]) {
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

    $modules = [];
    foreach ($this->moduleExtensionList->getList() as $name => $module) {
      if (!isset($module->info['interface translation project'])) {
        continue;
      }

      if (!$this->moduleHandler->moduleExists($name)) {
        continue;
      }

      $modules[] = $name;
    }

    if (!$modules) {
      return;
    }

    locale_translation_check_projects_local($modules, $langcodes);
    $options = _locale_translation_default_update_options();
    $batch = locale_translation_batch_fetch_build($modules, $langcodes, $options);
    batch_set($batch);
    if ($batch = locale_config_batch_update_components($options, $langcodes)) {
      batch_set($batch);
    }

    drush_backend_batch_process();
  }

}
