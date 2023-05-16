<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual_url_suffix\Event;

use Symfony\Contracts\EventDispatcher\Event;

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
   * The path.
   *
   * @var string
   */
  protected string $path;

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

  /**
   * Sets the path of target URL.
   *
   * @param string $path
   *   The path.
   */
  public function setPath(string|null $path): void {
    $this->path = $path;
  }

  /**
   * Gets the path of target URL.
   *
   * @return string|null
   *   The path.
   */
  public function getPath(): ?string {
    return $this->path ?? NULL;
  }

}
