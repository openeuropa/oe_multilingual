<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Creates the batches for importing the local translations.
 *
 * Local translations are interface translations that are "shipped" by an
 * extension by specifying the location of the strings inside its info file.
 */
class LocalTranslationsBatcher {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module extensions list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme extensions list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * The profile extensions list.
   *
   * @var \Drupal\Core\Extension\ProfileExtensionList
   */
  protected $profileExtensionList;

  /**
   * LocalTranslationsBatcher constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extensions list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extensions list.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profileExtensionList
   *   The profile extensions list.
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
   * Creates and sets the batch for importing local translations.
   *
   * @param array $langcodes
   *   The optional langcodes to import in.
   *
   * @see \Drupal\locale\Form\TranslationStatusForm::submitForm()
   */
  public function createBatch(array $langcodes = []): void {
    $this->moduleHandler->loadInclude('locale', 'fetch.inc');
    $this->moduleHandler->loadInclude('locale', 'bulk.inc');
    $this->moduleHandler->loadInclude('locale', 'translation.inc');
    $this->moduleHandler->loadInclude('locale', 'inc', 'locale.compare');

    if (!$langcodes) {
      $languages = $this->languageManager->getLanguages();
      $langcodes = [];
      foreach ($languages as $language) {
        if ($language->getId() === 'en') {
          continue;
        }

        $langcodes[] = $language->getId();
      }
    }

    $extensions = $this->getExtensionsToTranslate();
    if (!$extensions) {
      return;
    }

    locale_translation_flush_projects();
    $this->localeTranslationCheckProjectsLocal($extensions, $langcodes);
    $options = _locale_translation_default_update_options();
    $batch = locale_translation_batch_fetch_build($extensions, $langcodes, $options);
    batch_set($batch);
    if ($batch = locale_config_batch_update_components($options, $langcodes)) {
      batch_set($batch);
    }
  }

  /**
   * Creates an array of modules, themes and profiles to be translated.
   *
   * These are the ones which contain local translations.
   *
   * @return array
   *   The extensions.
   */
  protected function getExtensionsToTranslate(): array {
    $extensions = [];
    $all_extensions = array_merge($this->moduleExtensionList->getList(), $this->themeExtensionList->getList(), $this->profileExtensionList->getList());
    foreach ($all_extensions as $name => $module) {
      if (!isset($module->info['interface translation project'])) {
        continue;
      }

      // The module handler checks also profiles.
      if (!$this->moduleHandler->moduleExists($name) && !$this->themeHandler->themeExists($name)) {
        continue;
      }

      $extensions[] = $name;
    }

    return $extensions;
  }

  /**
   * Check and store the status and timestamp of local po files.
   *
   * @param array $projects
   *   Array of project names for which to check the state of translation files.
   *   Defaults to all translatable projects.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   *
   * @see locale_translation_check_projects_local()
   */
  protected function localeTranslationCheckProjectsLocal(array $projects, array $langcodes): void {
    $projects = locale_translation_get_projects($projects);
    // For each project and each language we check if a local po file is
    // available. When found the source object is updated with the appropriate
    // type and timestamp of the po file.
    foreach ($projects as $name => $project) {
      foreach ($langcodes as $langcode) {
        $source = locale_translation_source_build($project, $langcode);
        $file = locale_translation_source_check_file($source);
        $this->localeTranslationStatusSave($name, $langcode, $file);
      }
    }
  }

  /**
   * Saves the status of translation sources in static cache.
   *
   * @param string $project
   *   Machine readable project name.
   * @param string $langcode
   *   Language code.
   * @param object $data
   *   File object containing timestamp when the translation is last updated.
   *
   * @see locale_translation_status_save()
   */
  protected function localeTranslationStatusSave(string $project, string $langcode, $data): void {
    // Load the translation status or build it if not already available.
    module_load_include('translation.inc', 'locale');
    $status = locale_translation_get_status([$project]);
    if (empty($status)) {
      $projects = locale_translation_get_projects([$project]);
      if (isset($projects[$project])) {
        $status[$project][$langcode] = locale_translation_source_build($projects[$project], $langcode);
      }
    }

    // Merge the new status data with the existing status.
    if (isset($status[$project][$langcode])) {
      // Add the source data to the status array.
      $status[$project][$langcode]->files[LOCALE_TRANSLATION_LOCAL] = $data;

      // Check if this translation is the most recent one. Set timestamp and
      // data type of the most recent translation source.
      if (isset($data->timestamp) && $data->timestamp) {
        if ($data->timestamp > $status[$project][$langcode]->timestamp) {
          $status[$project][$langcode]->timestamp = $data->timestamp;
          $status[$project][$langcode]->last_checked = REQUEST_TIME;
          $status[$project][$langcode]->type = LOCALE_TRANSLATION_LOCAL;
        }
      }

      \Drupal::keyValue('locale.translation_status')
        ->set($project, $status[$project]);
      \Drupal::state()->set('locale.translation_last_checked', REQUEST_TIME);
    }
  }

}
