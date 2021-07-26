<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\Plugin\Block\LanguageBlock;
use Drupal\oe_multilingual\MultilingualHelperInterface;
use Drupal\oe_multilingual\ContentLanguageSwitcherProvider;
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
   * The content language switcher provider.
   *
   * @var \Drupal\oe_multilingual\ContentLanguageSwitcherProvider
   */
  protected $contentLanguageSwitcherProvider;

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
   * @param \Drupal\oe_multilingual\ContentLanguageSwitcherProvider $content_language_switcher_provider
   *   The content language switcher provider.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, PathMatcherInterface $path_matcher, MultilingualHelperInterface $multilingual_helper, ContentLanguageSwitcherProvider $content_language_switcher_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $language_manager, $path_matcher);
    $this->multilingualHelper = $multilingual_helper;
    $this->contentLanguageSwitcherProvider = $content_language_switcher_provider;
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
      $container->get('oe_multilingual.helper'),
      $container->get('oe_multilingual.content_language_switcher_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->multilingualHelper->getEntityFromCurrentRoute();
    $available_languages = $this->contentLanguageSwitcherProvider->getAvailableEntityLanguages($entity);
    // Currently the language switcher block cannot be cached:
    // https://www.drupal.org/node/2232375
    $build = [
      '#theme' => 'links__oe_multilingual_content_language_block',
      '#links' => $available_languages,
      '#attributes' => [
        'class' => [
          "language-switcher",
        ],
      ],
      '#set_active_class' => TRUE,
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account): AccessResultInterface {
    $entity = $this->multilingualHelper->getEntityFromCurrentRoute();
    // Bail out if there is no entity or if it's not a content entity.
    if (!$entity || !$entity instanceof ContentEntityInterface) {
      return AccessResult::forbidden();
    }

    // Render the links only if the current entity translation language is not
    // the same as the current site language.
    $translation = $this->multilingualHelper->getCurrentLanguageEntityTranslation($entity);
    if ($translation->language()->getId() === $this->languageManager->getCurrentLanguage()->getId()) {
      return AccessResult::forbidden();
    }
    return parent::blockAccess($account);
  }

}
