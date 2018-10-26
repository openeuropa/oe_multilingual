<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual\Plugin\LanguageNegotiation;

use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin;
use Symfony\Component\HttpFoundation\Request;

/**
 * Identifies admin language from the site default language.
 *
 * @LanguageNegotiation(
 *   id = Drupal\oe_multilingual\Plugin\LanguageNegotiation\LanguageNegotiationAdmin::METHOD_ID,
 *   types = {Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE},
 *   weight = -6,
 *   name = @Translation("Administration pages"),
 *   description = @Translation("Administration pages language setting.")
 * )
 */
class LanguageNegotiationAdmin extends LanguageNegotiationUserAdmin {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-admin';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL): ?string {
    $langcode = NULL;

    if ($this->currentUser->hasPermission('access administration pages') && $this->isAdminPath($request)) {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }

    // If not on an admin path this will be NULL we defer to other plugins.
    return $langcode;
  }

}
