<?php

declare(strict_types=1);

namespace Drupal\oe_multilingual_url_suffix\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrlFallback;
use Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix;

/**
 * Hook implementations for OE Multilingual URL Suffix module.
 */
class OeMultilingualUrlSuffixHooks {

  /**
   * Implements hook_language_types_info_alter().
   *
   * Fixes the suffix-based language negotiation plugin for the URL type of
   * language. This basically changes the default URL-based language negotiation
   * plugin set by core (LanguageNegotiationUrl). This is needed because we
   * cannot configure the language negotiation for the URL type via the UI (only
   * content and interface). And there are subsystems in core (such as the
   * language manager) that rely exclusively on the URL type.
   *
   * @phpstan-ignore-next-line
   */
  #[Hook('language_types_info_alter')]
  public function languageTypesInfoAlter(array &$language_types): void {
    $language_types[LanguageInterface::TYPE_URL]['fixed'] = [
      LanguageNegotiationUrlSuffix::METHOD_ID,
      LanguageNegotiationUrlFallback::METHOD_ID,
    ];
  }

}
