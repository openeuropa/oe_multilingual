<?php

declare(strict_types=1);

namespace Drupal\oe_multilingual_url_suffix\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrlFallback;
use Drupal\oe_multilingual_url_suffix\Plugin\LanguageNegotiation\LanguageNegotiationUrlSuffix;
use Drupal\redirect\Entity\Redirect;

/**
 * Hook implementations for OE Multilingual URL Suffix module.
 */
class OeMultilingualUrlSuffixHooks {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs an OeMultilingualUrlSuffixHooks object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
  }

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

  /**
   * Implements hook_redirect_response_alter().
   *
   * Ensures that redirects resolve to the correct language when using URL
   * suffix language negotiation. Handles two cases:
   * - When the redirect destination path contains an explicit language suffix
   *   (e.g., /test-page_fr), the suffix is detected and used.
   * - When the redirect entity has a specific language set, that language is
   *   used instead of the current request's URL language.
   *
   * @param \Drupal\Core\Routing\TrustedRedirectResponse $response
   *   The redirect response.
   * @param \Drupal\redirect\Entity\Redirect $redirect
   *   The redirect entity.
   *
   * @phpstan-ignore-next-line
   */
  #[Hook('redirect_response_alter')]
  public function redirectResponseAlter(TrustedRedirectResponse $response, Redirect $redirect): void {
    // First, check if the redirect destination path contains a language suffix.
    $language = $this->detectDestinationLanguage($redirect);

    // If no suffix language found, check the redirect entity's language field.
    if (!$language) {
      $redirect_langcode = $redirect->language()->getId();
      if ($redirect_langcode === Language::LANGCODE_NOT_SPECIFIED) {
        return;
      }
      $language = $this->languageManager->getLanguage($redirect_langcode);
    }

    if (!$language) {
      return;
    }

    // If the detected language matches the current URL language, no change
    // needed.
    $current_language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL);
    if ($current_language && $current_language->getId() === $language->getId()) {
      return;
    }

    // Regenerate the URL with the correct language.
    $url = $redirect->getRedirectUrl();
    if ($url) {
      $url->setOption('language', $language);
      $response->setTrustedTargetUrl($url->setAbsolute()->toString());
    }
  }

  /**
   * Detects a language suffix in the redirect destination path.
   *
   * @param \Drupal\redirect\Entity\Redirect $redirect
   *   The redirect entity.
   *
   * @return \Drupal\Core\Language\LanguageInterface|null
   *   The language if a valid suffix was found, NULL otherwise.
   */
  protected function detectDestinationLanguage(Redirect $redirect): ?LanguageInterface {
    $uri = $redirect->get('redirect_redirect')->uri;
    if (!$uri) {
      return NULL;
    }

    $path = parse_url($uri, PHP_URL_PATH);
    if (!$path) {
      return NULL;
    }

    $url_suffixes = $this->configFactory->get('oe_multilingual_url_suffix.settings')->get('url_suffixes');
    if (empty($url_suffixes)) {
      return NULL;
    }

    $parts = explode(LanguageNegotiationUrlSuffix::SUFFIX_DELIMITER, trim($path, '/'));
    if (count($parts) < 2) {
      return NULL;
    }

    $suffix = array_pop($parts);
    $langcode = array_search($suffix, $url_suffixes);
    if (!$langcode) {
      return NULL;
    }

    return $this->languageManager->getLanguage($langcode);
  }

}
