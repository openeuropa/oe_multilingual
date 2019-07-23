<?php

namespace Drupal\oe_multilingual\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LanguageNegotiationUrlSuffix.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\oe_multilingual\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix::METHOD_ID,
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_URL},
 *   weight = -10,
 *   name = @Translation("URL suffix"),
 *   description = @Translation("Language from the URL (Path suffix)."),
 *   config_route_name = "oe_multilingual.negotiation_url_suffix"
 * )
 */
class LanguageNegotiationUrlSuffix extends LanguageNegotiationUrl {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-url-suffix';

  /**
   * The suffix delimiter.
   */
  const SUFFIX_DELIMITER = '_';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    $config = $this->config->get('language.negotiation')->get('url_suffixes');
    if ($request && $config) {
      $request_path = urldecode(trim($request->getPathInfo(), '/'));
      $parts = explode(static::SUFFIX_DELIMITER, $request_path);
      $suffix = array_pop($parts);

      // Search suffix within configs.
      $negotiated_language = array_search($suffix, $config);
      if ($negotiated_language) {
        $langcode = $negotiated_language;
      }
    }

    return $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $config = $this->config->get('language.negotiation')->get('url_suffixes');
    if ($config) {
      $parts = explode(static::SUFFIX_DELIMITER, trim($path, '/'));
      $suffix = array_pop($parts);

      if (array_search($suffix, $config)) {
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
      $language_url = $this->languageManager->getCurrentLanguage();
      $options['language'] = $language_url;
    }
    // We allow only added languages here.
    elseif (!is_object($options['language']) || !isset($languages[$options['language']->getId()])) {
      return $path;
    }

    // Append suffix to path.
    $config = $this->config->get('language.negotiation')->get('url_suffixes');
    if (isset($config[$options['language']->getId()])) {
      $path .= static::SUFFIX_DELIMITER . $config[$options['language']->getId()];
    }

    if ($bubbleable_metadata) {
      $bubbleable_metadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
    }

    return $path;
  }

}
