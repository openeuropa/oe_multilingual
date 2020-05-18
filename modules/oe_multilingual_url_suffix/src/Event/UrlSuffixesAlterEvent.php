<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual_url_suffix\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Dispatched by LanguageNegotiationUrlSuffix::getUrlSuffixes().
 *
 * Allows to alter the list of url suffixes retrieved from config.
 */
class UrlSuffixesAlterEvent extends Event {

  const EVENT = 'oe_multilingual_url_suffix.url_suffixes_alter';

  /**
   * The list of url suffixes.
   *
   * @var array
   */
  protected $urlSuffixes;

  /**
   * Sets the url suffixes array.
   *
   * @param array $config
   *   The array containing the url suffixes list form config.
   */
  public function setUrlSuffixes(array $config): void {
    $this->urlSuffixes = $config;
  }

  /**
   * Gets the url suffixes array.
   *
   * @return array
   *   The list of url suffixes.
   */
  public function getUrlSuffixes(): array {
    return $this->urlSuffixes;
  }

}
