<?php

declare(strict_types=1);

namespace Drupal\oe_multilingual_url_suffix;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\LanguageInterface;
use Drupal\oe_multilingual_url_suffix\Event\UrlSuffixesAlterEvent;
use Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix;

/**
 * Helper methods for dealing with the suffixes.
 */
trait UrlSuffixTrait {

  /**
   * Get the list of url suffixes from config.
   *
   * @param string $path
   *   The path.
   *
   * @return array
   *   The array of language suffixes.
   */
  public function getUrlSuffixes(string $path = ''): array {
    $url_suffixes = $this->config->get('oe_multilingual_url_suffix.settings')->get('url_suffixes') ?? [];

    // Allow other modules to alter the list of suffixes available to the
    // negotiator.
    $event = new UrlSuffixesAlterEvent($url_suffixes);

    // Set path value into context.
    $context = [];
    $context['path'] = $path;
    $event->setContext($context);
    $this->eventDispatcher->dispatch($event, UrlSuffixesAlterEvent::EVENT);

    return $event->getUrlSuffixes();
  }

  /**
   * Extracts the suffix from a path.
   *
   * @param string $path
   *   The path.
   *
   * @return string
   *   The suffix.
   */
  public function getSuffixFromPath(string $path): string {
    $path = urldecode(trim($path, '/'));
    $parts = explode(LanguageNegotiationUrlSuffix::SUFFIX_DELIMITER, $path);
    return $parts ? array_pop($parts) : '';
  }

  /**
   * Returns the language object based on a suffix.
   *
   * @param string $suffix
   *   The suffix.
   *
   * @return \Drupal\Core\Language\LanguageInterface|null
   *   The language if found.
   */
  public function getLanguageBySuffix(string $suffix): ?LanguageInterface {
    $found_language = NULL;
    $url_suffixes = $this->getUrlSuffixes();
    foreach ($this->languageManager->getLanguages() as $language) {
      if (isset($url_suffixes[$language->getId()]) && $url_suffixes[$language->getId()] === $suffix) {
        $found_language = $language;
        break;
      }
    }

    return $found_language;
  }

}
