<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Url;

/**
 * Helper service around multilingual functionalities.
 */
class LanguageProvider {

  /**
   * The multilingual helper service.
   *
   * @var \Drupal\oe_multilingual\MultilingualHelperInterface
   */
  protected $multilingualHelper;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs an ContentLanguageBlock object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\oe_multilingual\MultilingualHelperInterface $multilingual_helper
   *   The multilingual helper service.
   */
  public function __construct(LanguageManagerInterface $language_manager, PathMatcherInterface $path_matcher, MultilingualHelperInterface $multilingual_helper) {
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
    $this->multilingualHelper = $multilingual_helper;
  }

  /**
   * Returns a list of available translation links for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The language manager.
   *
   * @return array
   *   Array of available translation links.
   */
  public function getEntityAvailableLanguages(EntityInterface $entity) {
    $available_languages = [];

    $translation = $this->multilingualHelper->getCurrentLanguageEntityTranslation($entity);
    $route_name = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';
    $links = $this->languageManager->getLanguageSwitchLinks(LanguageInterface::TYPE_CONTENT, Url::fromRoute($route_name));

    if (isset($links->links)) {
      // Only show links to the available translation languages except the
      // current one.
      $available_languages = array_intersect_key($links->links, $entity->getTranslationLanguages());
      unset($available_languages[$translation->language()->getId()]);
    }

    return $available_languages;
  }

}