<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual_url_suffix\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Dispatched by LanguageNegotiationUrlSuffix::getUrlSuffixes().
 *
 * Allows to alter the list of URL suffixes available to the
 * LanguageNegotiationUrlSuffix negotiator.
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
   * The UrlSuffixesAlterEvent constructor.
   *
   * @param array $url_suffixes
   *   Array of url suffixes.
   */
  public function __construct(array $url_suffixes) {
    $this->urlSuffixes = $url_suffixes;
  }

  /**
   * Sets the url suffixes array.
   *
   * @param array $url_suffixes
   *   The array containing the url suffixes.
   */
  public function setUrlSuffixes(array $url_suffixes): void {
    $this->urlSuffixes = $url_suffixes;
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
