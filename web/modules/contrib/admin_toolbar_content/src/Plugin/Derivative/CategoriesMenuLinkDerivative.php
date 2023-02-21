<?php
namespace Drupal\admin_toolbar_content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CategoriesMenuLinkDerivative extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
  private $languageManager;

  /**
   * Create an AdminToolbarToolsHelper object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, LanguageManagerInterface $languageManager) {
    $this->moduleHandler = $moduleHandler;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    /** @var ModuleHandlerInterface $moduleHandler */
    $moduleHandler = $container->get('module_handler');
    return new static(
      $moduleHandler,
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    $config = \Drupal::config('admin_toolbar_content.settings');
    $show_categories_item = $config->get('show_categories_item') ?? 0;

    if ($show_categories_item  && $this->moduleHandler->moduleExists('taxonomy')) {

      $links['categories'] = [
          'title' => $this->t('Categories'),
          'route_name' => 'entity.taxonomy_vocabulary.collection',
          'route_parameters' => [],
          'menu_name' => 'admin',
          'parent' => 'system.admin',
          'weight' => -9,
        ] + $base_plugin_definition;

      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
      $entityTypeManager = \Drupal::service('entity_type.manager');

      $vocabularies_collections = \Drupal::service('module_handler')
        ->invokeAll('vocabularies_collections');

      // Sort collections so grouping will use alphabetic order.
      asort($vocabularies_collections);

      $group_collections = $config->get('group_collections') ?? 0;
      if ($group_collections) {
        switch ($group_collections) {
          case 'bottom':
            $group_collections_weight = 100;
            break;

          case 'top':
            $group_collections_weight = -100;
            break;
        }
      }

      // Add a list terms for each vocabulary.
      $vocabularies = $entityTypeManager->getStorage('taxonomy_vocabulary')
        ->loadMultiple();

      foreach ($vocabularies_collections as $collection => $vocabulary_collections) {
        $links['categories.' . $collection] = [
            'title' => $this->t((string) $vocabulary_collections['label']),
            'route_name' => 'entity.taxonomy_vocabulary.collection',
            'route_parameters' => [
              'collection' => $collection,
            ],
            'menu_name' => 'admin',
            'parent' => $base_plugin_definition['id'] . ':categories',
            'options' => [
              'attributes' => [
                'class' => [
                  'admin-toolbar-collection',
                  'admin-toolbar-collection--vocabulary',
                ],
              ],
            ],
          ] + $base_plugin_definition;

        // Use group collections weight if group collections is enabled.
        if (!empty($group_collections_weight)) {
          $links['categories.' . $collection]['weight'] = $group_collections_weight;
          $group_collections_weight++;
        }

        foreach ($vocabulary_collections['vocabularies'] as $vocabulary_id) {
          if (isset($vocabularies[$vocabulary_id])) {
            $this->addVocabularyLink($vocabularies[$vocabulary_id], $collection, $links, $base_plugin_definition);
            unset($vocabularies[$vocabulary_id]);
          }
        }
      }

      $collection = 'categories';
      foreach ($vocabularies as $vocabulary) {
        $this->addVocabularyLink($vocabulary, $collection, $links, $base_plugin_definition);
      }
    }

    return $links;
  }

  protected function addVocabularyLink($vocabulary, $collection, &$links, $base_plugin_definition) {

    $link_name = $collection . '.' . $vocabulary->id();

    $label = $vocabulary->label();
    if ($this->languageManager->isMultilingual()) {
      $typeConfig = $this->languageManager->getLanguageConfigOverride('en', 'taxonomy.vocabulary.' . $vocabulary->id());
      $label = !empty($typeConfig->get('name')) ? $typeConfig->get('name') : $label;
    }

    $links['categories.' . $link_name] = [
      'title' => $this->t($label),
      'route_name' => 'entity.taxonomy_vocabulary.overview_form',
      'route_parameters' => [
        'taxonomy_vocabulary' => $vocabulary->id(),
        'collection' => $collection,
      ],
      'menu_name' => 'admin',
      'parent' => !empty($links['categories.' . $collection]) ? $base_plugin_definition['id'] . ':categories.' . $collection : $base_plugin_definition['id'] . ':categories',
      'metadata' => [
        'entity_type' => 'taxonomy_vocabulary',
        'entity_id' => $vocabulary->id()
      ]
    ] + $base_plugin_definition;

    $links['categories.' . $link_name . '.add'] = [
        'title' => $this->t('Add new'),
      'route_name' => "entity.taxonomy_term.add_form",
      'route_parameters' => [
        'taxonomy_vocabulary' => $vocabulary->id()
      ],
      'menu_name' => 'admin',
      'parent' =>  $base_plugin_definition['id'] . ':categories.' . $link_name,
      'metadata' => [
        'entity_type' => 'taxonomy_vocabulary',
        'entity_id' => $vocabulary->id()
      ]
    ] + $base_plugin_definition;

  }
}
