<?php

namespace Drupal\admin_toolbar_content\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdminToolbarContentSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a AdminToolbarContentSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ModuleHandlerInterface $module_handler) {
    parent::__construct($configFactory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_toolbar_content';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'admin_toolbar_content.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('admin_toolbar_content.settings');

    $form['#title'] = $this->t('Admin Toolbar Content');
    $form['#tree'] = true;

    $form['recent_items'] = [
      '#type' => 'textfield',
      '#title' => 'Show recent content',
      '#description' => 'Show recent content items. Leave empty or 0 to show none.',
      '#default_value' => $config->get('recent_items') ?? 10
    ];

    $recent_items_link_options = ['default' => $this->t('Edit form')];
    // Add Layout Builder option.
    if ($this->moduleHandler->moduleExists('layout_builder')) {
      $recent_items_link_options = $recent_items_link_options + ['layout_builder' => $this->t('Layout Builder')];
    }

    if (count($recent_items_link_options) > 1) {
      $form['recent_items_link'] = [
        '#type' => 'radios',
        '#title' => 'Recent items link',
        '#description' => $this->t('Choose the destination the recent items link should go to.'),
        '#options' => $recent_items_link_options,
        '#default_value' => $config->get('recent_items_link') ?? 'default',
      ];
    }

    $form['hide_non_content_items'] = [
      '#type' => 'checkbox',
      '#title' => 'Hide non content items',
      '#description' => 'Hides items under "content" not directly related to content types.',
      '#default_value' => $config->get('hide_non_content_items') ?? 0
    ];

    $form['show_account_link'] = [
      '#type' => 'radios',
      '#title' => 'User account link',
      '#description' => 'Links to user account pages.',
      '#options' => [
        '' => t('Show no link'),
        'user' => t('Link to user page'),
        'edit' => t('Link to account edit form'),
        'both' => t('Link to both'),
      ],
      '#default_value' => $config->get('show_account_link') ?? ''
    ];

    $form['enhance_content_item'] = [
      '#type' => 'checkbox',
      '#title' => 'Enhance content menu',
      '#description' => 'Enhances menu items for content types and collections.',
      '#default_value' => $config->get('enhance_content_item') ?? 0
    ];

    $form['show_categories_item'] = [
      '#type' => 'checkbox',
      '#title' => 'Show categories menu',
      '#description' => 'Shows a separate main menu item for categories (vocabularies).',
      '#default_value' => $config->get('show_categories_item') ?? 0
    ];

    $form['show_media_item'] = [
      '#type' => 'checkbox',
      '#title' => 'Show media menu',
      '#description' => 'Shows a separate main menu item for media.',
      '#default_value' => $config->get('show_media_item') ?? 0
    ];

    $form['show_webforms_item'] = [
      '#type' => 'checkbox',
      '#title' => 'Show webform menu',
      '#description' => 'Shows a separate main menu item for forms.',
      '#default_value' => $config->get('show_webforms_item') ?? 0
    ];

    $form['show_menus_item'] = [
      '#type' => 'checkbox',
      '#title' => 'Show menus menu',
      '#description' => 'Shows a separate main menu item for menus.',
      '#default_value' => $config->get('show_menus_item') ?? 0
    ];

    $form['group_collections'] = [
      '#type' => 'radios',
      '#title' => $this->t("Group collections"),
      '#description' => $this->t("Group the collections to a specific place."),
      '#options' => [
        '' => $this->t("Don't Group"),
        'bottom' => $this->t("At bottom"),
        'top' => $this->t("At top"),
      ],
      '#default_value' => $config->get('group_collections') ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('admin_toolbar_content.settings');
    $config->set('recent_items',  $form_state->getValue('recent_items', ''));
    $config->set('recent_items_link',  $form_state->getValue('recent_items_link', 'default'));
    $config->set('hide_non_content_items',  $form_state->getValue('hide_non_content_items', 0));
    $config->set('show_account_link',  $form_state->getValue('show_account_link', ''));
    $config->set('enhance_content_item',  $form_state->getValue('enhance_content_item', 0));
    $config->set('show_categories_item',  $form_state->getValue('show_categories_item', 0));
    $config->set('show_media_item',  $form_state->getValue('show_media_item', 0));
    $config->set('show_webforms_item',  $form_state->getValue('show_webforms_item', 0));
    $config->set('show_menus_item',  $form_state->getValue('show_menus_item', 0));
    $config->set('group_collections',  $form_state->getValue('group_collections', ''));
    $config->save();

    // Clear cache so admin menu can rebuild.
    \Drupal::service('plugin.manager.menu.link')->rebuild();

    parent::submitForm($form, $form_state);
  }

}
