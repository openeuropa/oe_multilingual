<?php

declare(strict_types=1);

namespace Drupal\oe_multilingual;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for multilingual helper service.
 */
interface MultilingualHelperInterface {

  /**
   * Extracts an entity from the current route.
   *
   * @return null|\Drupal\Core\Entity\EntityInterface
   *   Returns the entity or null if no entity was found.
   */
  public function getEntityFromCurrentRoute(): ?EntityInterface;

  /**
   * Returns the entity translation for the current language.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose translation will be returned.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity translation.
   */
  public function getCurrentLanguageEntityTranslation(EntityInterface $entity): EntityInterface;

  /**
   * Returns the entity from the route.
   *
   * It tries to get the relevant entity from the canonical route if possible.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity if found.
   */
  public function getRequestCanonicalEntity(Request $request): ?ContentEntityInterface;

}
