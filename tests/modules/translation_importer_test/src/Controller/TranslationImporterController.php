<?php

declare(strict_types = 1);

namespace Drupal\translation_importer_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\oe_multilingual\LocalTranslationsBatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for batch tests.
 */
class TranslationImporterController extends ControllerBase {

  /**
   * The local translation batcher service.
   *
   * @var \Drupal\oe_multilingual\LocalTranslationsBatcher
   */
  protected $localTranslationsBatcher;

  /**
   * Constructs an instance of TranslationImporterController.
   *
   * @param \Drupal\oe_multilingual\LocalTranslationsBatcher $localTranslationsBatcher
   *   The local translation batcher service.
   */
  public function __construct(LocalTranslationsBatcher $localTranslationsBatcher) {
    $this->localTranslationsBatcher = $localTranslationsBatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oe_multilingual.local_translations_batcher')
    );
  }

  /**
   * Fires a batch process without a form submission.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value
   *   otherwise.
   */
  public function importBatchPage(): ?RedirectResponse {
    $this->localTranslationsBatcher->createBatch();
    return batch_process('import-translations/done');
  }

  /**
   * Redirect callback for when the batch finishes.
   *
   * @return array
   *   The page.
   */
  public function redirectPage(): array {
    return [
      '#markup' => $this->t('The batch has completed.'),
    ];
  }

}
