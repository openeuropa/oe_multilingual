<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual\Commands;

use Drupal\oe_multilingual\LocalTranslationsBatcher;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for the OE Multilingual module.
 */
class MultilingualCommands extends DrushCommands {

  /**
   * The local translations import batcher service.
   *
   * @var \Drupal\oe_multilingual\LocalTranslationsBatcher
   */
  protected $localTranslationsBatcher;

  /**
   * MultilingualCommands constructor.
   *
   * @param \Drupal\oe_multilingual\LocalTranslationsBatcher $localTranslationsBatcher
   *   The service that creates the batch.
   */
  public function __construct(LocalTranslationsBatcher $localTranslationsBatcher) {
    $this->localTranslationsBatcher = $localTranslationsBatcher;
  }

  /**
   * Imports the translations from the local modules.
   *
   * It determines which modules it should import by checking inside their info
   * files for the `interface translation project` key.
   *
   * @param array $options
   *   Command options.
   *
   * @command oe-multilingual:import-local-translations
   * @option langcodes A comma-separated list of language codes to update. If omitted, all translations will be updated.
   * @validate-module-enabled locale
   */
  public function importLocalTranslations(array $options = ['langcodes' => self::OPT]): void {
    $langcodes = $options['langcodes'] ? $options['langcodes'] : [];

    $this->localTranslationsBatcher->createBatch($langcodes);
    $batch =& batch_get();
    if (!$batch) {
      return;
    }

    drush_backend_batch_process();
    // Update config translations.
    if ($batch = locale_config_batch_update_components([])) {
      $this->logger()->notice('Importing configuration translations...');
      batch_set($batch);
      drush_backend_batch_process();
      $this->logger()->notice('Done.');
    }

  }

}
