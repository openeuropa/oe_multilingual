<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs an ContentLanguageBlock object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path matcher.
   */
  public function __construct(AliasManagerInterface $alias_manager) {
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('path.alias_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    $config = $this->config->get('oe_multilingual_url_suffix.settings')->get('url_suffixes');
    if ($request && $this->languageManager && $config) {
      $request_path = urldecode(trim($request->getPathInfo(), '/'));
      $parts = explode(static::SUFFIX_DELIMITER, $request_path);
      $suffix = array_pop($parts);

      // Search suffix within added languages.
      $negotiated_language = FALSE;
      foreach ($this->languageManager->getLanguages() as $language) {
        if (isset($config[$language->getId()]) && $config[$language->getId()] === $suffix) {
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
    $url_suffixes = $this->config->get('oe_multilingual_url_suffix.settings')->get('url_suffixes');
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
    $config = $this->config->get('oe_multilingual_url_suffix.settings')->get('url_suffixes');
    if (isset($config[$options['language']->getId()])) {

      // Ensure front-page path has the configured alias of the front-page
      // in order to avoid links pointing to "/_[language_suffix]".
      if ($path === '/') {
        $front_uri = $this->config->get('system.site')->get('page.front');
        $front_alias = $this->aliasManager->getAliasByPath($front_uri);
        $path = $front_alias;
      }

      $path .= static::SUFFIX_DELIMITER . $config[$options['language']->getId()];
      if ($bubbleable_metadata) {
        $bubbleable_metadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
      }
    }

    return $path;
  }

}
