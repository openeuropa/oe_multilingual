<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Helper service around multilingual functionalities.
 */
class MultilingualHelper implements MultilingualHelperInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Instantiates a new MultilingualHelper service.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, RouteMatchInterface $current_route_match, LanguageManagerInterface $language_manager) {
    $this->entityRepository = $entity_repository;
    $this->currentRouteMatch = $current_route_match;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromCurrentRoute(): ?EntityInterface {
    if (($route = $this->currentRouteMatch->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
      // Determine if the current route represents an entity.
      foreach ($parameters as $name => $options) {
        if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
          $entity = $this->currentRouteMatch->getParameter($name);
          if ($entity instanceof ContentEntityInterface && $entity->hasLinkTemplate('canonical')) {
            return $entity;
          }
        }
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentLanguageEntityTranslation(EntityInterface $entity): EntityInterface {
    return $this->entityRepository->getTranslationFromContext($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageNameList(): array {
    $language_list = $this->languageManager->getStandardLanguageList();
    $language_names = array_combine(array_keys($language_list), array_column($language_list, 1));
    // Manage 2 special cases.
    $language_names['pt'] = 'Português';
    $language_names['mt'] = 'Malti';
    return $language_names;
  }

}
