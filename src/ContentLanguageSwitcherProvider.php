<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Service that provides the content language switcher links for a given entity.
 */
class ContentLanguageSwitcherProvider {

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
   * The active route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs an ContentLanguageBlock object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\oe_multilingual\MultilingualHelperInterface $multilingual_helper
   *   The multilingual helper service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(LanguageManagerInterface $language_manager, MultilingualHelperInterface $multilingual_helper, RouteMatchInterface $route_match) {
    $this->languageManager = $language_manager;
    $this->multilingualHelper = $multilingual_helper;
    $this->routeMatch = $route_match;
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
  public function getAvailableEntityLanguages(EntityInterface $entity) {
    $links = $this->languageManager->getLanguageSwitchLinks(LanguageInterface::TYPE_CONTENT, Url::fromRouteMatch($this->routeMatch));

    $available_languages = [];
    if (isset($links->links)) {
      // Only show links to the available translation languages except the
      // current one.
      $available_languages = array_intersect_key($links->links, $entity->getTranslationLanguages());
      $translation = $this->multilingualHelper->getCurrentLanguageEntityTranslation($entity);
      unset($available_languages[$translation->language()->getId()]);
    }

    return $available_languages;
  }

}
