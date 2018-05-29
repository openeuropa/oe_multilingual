<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual\Plugin\Block;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\language\Plugin\Block\LanguageBlock;
use Drupal\oe_multilingual\MultilingualHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Content Language Switcher' block.
 *
 * @Block(
 *   id = "oe_multilingual_content_language_switcher",
 *   admin_label = @Translation("OpenEuropa Content Language Switcher"),
 *   category = @Translation("OpenEuropa")
 * )
 */
class ContentLanguageBlock extends LanguageBlock implements ContainerFactoryPluginInterface {

  /**
   * The multilingual helper service.
   *
   * @var \Drupal\oe_multilingual\MultilingualHelperInterface
   */
  protected $multilingualHelper;

  /**
   * Constructs an ContentLanguageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\oe_multilingual\MultilingualHelperInterface $multilingual_helper
   *   The multilingual helper service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, PathMatcherInterface $path_matcher, MultilingualHelperInterface $multilingual_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $language_manager, $path_matcher);
    $this->multilingualHelper = $multilingual_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('path.matcher'),
      $container->get('oe_multilingual.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $entity = $this->multilingualHelper->getEntityFromCurrentRoute();
    // Bail out if there is no entity or if it's not a content entity.
    if (!$entity || !$entity instanceof ContentEntityInterface) {
      return $build;
    }

    // Render the links only if the current entity translation language is not
    // the same as the current site language.
    $translation = $this->multilingualHelper->getCurrentLanguageEntityTranslation($entity);
    if ($translation->language()->getId() === $this->languageManager->getCurrentLanguage()->getId()) {
      return $build;
    }

    $route_name = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';
    $links = $this->languageManager->getLanguageSwitchLinks(LanguageInterface::TYPE_CONTENT, Url::fromRoute($route_name));

    if (isset($links->links)) {
      // Only show links to the available translation languages except the
      // current one.
      $available_languages = array_intersect_key($links->links, $entity->getTranslationLanguages());
      unset($available_languages[$translation->language()->getId()]);

      $build = [
        '#theme' => 'links__oe_multilingual_content_language_block',
        '#links' => $available_languages,
        '#attributes' => [
          'class' => [
            "language-switcher-{$links->method_id}",
          ],
        ],
        '#set_active_class' => TRUE,
      ];
    }

    return $build;
  }

}
