<?php

/**
 * @file
 * Documentation for admin_toolbar_content API.
 */

/**
 * Provide an array describing content type collections.
 *
 * Collections can group content types together in the admin ui under the
 * content menu.
 *
 * @return array
 *   [
 *     collection_machinename => [
 *       'label' => 'English label',
 *       'content_types' => [
 *           'bundle name content type',
 *           ...
 *           'bundle name content type'
 *       ]
 *     ],
 *     ...
 *   ]
 *
 */
function hook_content_type_collections() {
  return [
    'content' => [
      'label' => 'Content',
      'content_types' => [
        'page',
        'article'
      ]
    ]
  ];
}

/**
 * Provide an array describing vocabulary collections.
 *
 * Collections can group vocabularies together in the admin ui under the
 * categories menu.
 *
 * @return array
 *   [
 *     collection_machinename => [
 *       'label' => 'English label',
 *       'vocabularies' => [
 *           'bundle name vocabulary',
 *           ...
 *           'bundle name vocabulary'
 *       ]
 *     ],
 *     ...
 *   ]
 *
 */
function hook_vocabularies_collections() {
  return [
    'categories' => [
      'label' => 'Categories',
      'vocabularies' => [
        'tags',
      ],
    ],
  ];
}

/**
 * Provide an array describing menu collections.
 *
 * Collections can group menus together in the admin ui under the menus menu.
 *
 * @return array
 *   [
 *     collection_machinename => [
 *       'label' => 'English label',
 *       'menus' => [
 *           'bundle name menu',
 *           ...
 *           'bundle name menu'
 *       ]
 *     ],
 *     ...
 *   ]
 *
 */
function hook_menus_collections() {
  return [
    'menus' => [
      'label' => 'Menus',
      'menus' => [
        'main',
      ],
    ],
  ];
}
