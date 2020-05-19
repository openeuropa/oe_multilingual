<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\oe_multilingual_url_suffix\Event\UrlSuffixesAlterEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Identifies the language via a URL suffix.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix::METHOD_ID,
 *   types = {
 *     \Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *     \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *     \Drupal\Core\Language\LanguageInterface::TYPE_URL
 *   },
 *   weight = -10,
 *   name = @Translation("URL suffix"),
 *   description = @Translation("Language from the URL (Path suffix)."),
 *   config_route_name = "oe_multilingual_url_suffix.negotiation_url_suffix"
 * )
 */
class LanguageNegotiationUrlSuffix extends LanguageNegotiationUrl implements ContainerFactoryPluginInterface {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'oe-multilingual-url-suffix-negotiation-method';

  /**
   * The suffix delimiter.
   */
  const SUFFIX_DELIMITER = '_';

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new LanguageNegotiationUrlSuffix instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    $url_suffixes = $this->getUrlSuffixes();
    if ($request && $this->languageManager && $url_suffixes) {
      $request_path = urldecode(trim($request->getPathInfo(), '/'));
      $parts = explode(static::SUFFIX_DELIMITER, $request_path);
      $suffix = array_pop($parts);

      // Search suffix within added languages.
      $negotiated_language = FALSE;
      foreach ($this->languageManager->getLanguages() as $language) {
        if (isset($url_suffixes[$language->getId()]) && $url_suffixes[$language->getId()] === $suffix) {
          $negotiated_language = $language;
          break;
        }
      }

      if ($negotiated_language) {
        $langcode = $negotiated_language->getId();
      }
    }

    return $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $url_suffixes = $this->getUrlSuffixes();
    if (!empty($url_suffixes) && is_array($url_suffixes)) {

      // Split the path by the defined delimiter.
      $parts = explode(static::SUFFIX_DELIMITER, trim($path, '/'));

      // Suffix should be the last part on the path.
      $suffix = array_pop($parts);

      // If the suffix is one of the configured language suffix, rebuild the
      // path to remove it.
      if (array_search($suffix, $url_suffixes)) {
        $path = '/' . implode(static::SUFFIX_DELIMITER, $parts);
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $languages = array_flip(array_keys($this->languageManager->getLanguages()));
    // Language can be passed as an option, or we go for current URL language.
    if (!isset($options['language'])) {
      $language_url = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL);
      $options['language'] = $language_url;
    }
    // We allow only added languages here.
    elseif (!is_object($options['language']) || !isset($languages[$options['language']->getId()])) {
      return $path;
    }

    // Append suffix to path.
    $url_suffixes = $this->getUrlSuffixes();
    if (isset($url_suffixes[$options['language']->getId()])) {
      $path .= static::SUFFIX_DELIMITER . $url_suffixes[$options['language']->getId()];
      if ($bubbleable_metadata) {
        $bubbleable_metadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
      }
    }

    return $path;
  }

  /**
   * Get the list of url suffixes from config.
   *
   * @return array
   *   The array of language suffixes.
   */
  public function getUrlSuffixes(): array {
    $url_suffixes = $this->config->get('oe_multilingual_url_suffix.settings')->get('url_suffixes') ?? [];

    // Allow other modules to alter the list of suffixes available to the
    // negotiator.
    $event = new UrlSuffixesAlterEvent($url_suffixes);
    $this->eventDispatcher->dispatch(UrlSuffixesAlterEvent::EVENT, $event);

    return $event->getUrlSuffixes();
  }

}
