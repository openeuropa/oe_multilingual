<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

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
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface|null
   */
  protected $pathValidator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Instantiates a new MultilingualHelper service.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match.
   * @param \Drupal\Core\Path\PathValidatorInterface|null $path_validator
   *   The path validator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface|null $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, RouteMatchInterface $current_route_match, PathValidatorInterface $path_validator = NULL, EntityTypeManagerInterface $entity_type_manager = NULL) {
    if (!$path_validator) {
      $path_validator = \Drupal::service('path.validator');
    }
    if (!$entity_type_manager) {
      $entity_type_manager = \Drupal::entityTypeManager();
    }
    $this->entityRepository = $entity_repository;
    $this->currentRouteMatch = $current_route_match;
    $this->pathValidator = $path_validator;
    $this->entityTypeManager = $entity_type_manager;
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
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function getRequestCanonicalEntity(Request $request): ?ContentEntityInterface {
    if ($request->getMethod() !== 'GET' || $request->attributes->has('_drupal_ajax')) {
      // We are only interested in GET, non-ajax requests.
      return NULL;
    }

    $url = $this->pathValidator->getUrlIfValid($request->getPathInfo());
    if (!$url) {
      return NULL;
    }

    if (!$url->isRouted()) {
      return NULL;
    }

    // We only support the entity canonical route.
    $parts = explode('.', $url->getRouteName());
    if (count($parts) !== 3 || $parts[0] !== 'entity' || $parts['2'] !== 'canonical') {
      return NULL;
    }

    $entity_type = $parts[1];

    $parameters = $url->getRouteParameters();
    foreach ($parameters as $name => $value) {
      if ($name !== $entity_type) {
        continue;
      }

      $entity = $this->entityTypeManager->getStorage($entity_type)->load($value);
      if ($entity instanceof ContentEntityInterface && $entity->hasLinkTemplate('canonical')) {
        return $entity;
      }
    }

    return NULL;
  }

}
