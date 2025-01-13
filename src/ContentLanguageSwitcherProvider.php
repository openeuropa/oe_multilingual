<?php

declare(strict_types=1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
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
   *   The path matcher (deprecated).
   * @param \Drupal\oe_multilingual\MultilingualHelperInterface $multilingual_helper
   *   The multilingual helper service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(LanguageManagerInterface $language_manager, PathMatcherInterface $path_matcher, MultilingualHelperInterface $multilingual_helper, ?RouteMatchInterface $route_match = NULL) {
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
    $this->multilingualHelper = $multilingual_helper;
    // @codingStandardsIgnoreStart
    if (!$route_match) {
      @trigger_error('Calling ContentLanguageSwitcherProvider::__construct() without the $route_match argument is deprecated in 1.14.0 and will be required from 2.0.0.', E_USER_DEPRECATED);
      $route_match = \Drupal::service('current_route_match');
    }
    // @codingStandardsIgnoreEnd
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
