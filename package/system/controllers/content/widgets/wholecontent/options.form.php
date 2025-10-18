<?php
class formWidgetContentWholecontentOptions extends cmsForm {

    const IMAGE_PRESETS = [
        'micro' => 'micro',
        'small' => 'small',
        'normal' => 'normal',
        'big' => 'big',
        'content_list_small' => 'content_list_small',
        'content_list' => 'content_list',
        'content_item' => 'content_item',
        'original' => 'original'
    ];

    public function init($options = false) {
        $model = cmsCore::getModel('content');
        $setup = [
            ['type' => 'fieldset', 'title' => LANG_OPTIONS, 'childs' => []],
			['type' => 'fieldset', 'title' => LANG_WD_CONTENT_WHOLECONTENT_LAYOUT, 'childs' => []],
            ['type' => 'fieldset', 'title' => LANG_WD_CONTENT_WHOLECONTENT_IMAGE_PRESET, 'childs' => []]
        ];
        
        $field = &$setup[0]['childs'];
		$style_config_fields = &$setup[1]['childs'];
        $image_fields = &$setup[2]['childs'];
        $id_set = [];

        foreach ($model->getContentTypes() as $ctype) {
            $this->processContentType($model, $ctype, $field, $id_set, is_array($options) ? $options : []);
        }

        $this->addCommonFields($field, $style_config_fields, $image_fields);

        if (!empty($_SERVER['REQUEST_URI'] ?? '') && strpos($_SERVER['REQUEST_URI'], 'widgets/edit') !== false) {
            $this->outputStylesAndScripts($id_set);
        }

        return $setup;
    }

    protected function processContentType($model, $ctype, &$field, &$id_set, $options) {
        $ctype_id = (int)($ctype['id'] ?? 0);
        $id_set[] = $ctype_id;

        $cats_list = $this->getCategoriesList($model, $ctype, $options, $ctype_id);
        $datasets_list = $this->getDatasetsList($model, $options, $ctype_id);
        $fields_list = $this->getFieldsList($model, $ctype, $options, $ctype_id);

        $this->addContentTypeFields($field, $ctype, $cats_list, $datasets_list, $fields_list);
    }

protected function getCategoriesList($model, $ctype, $options, $ctype_id) {
    static $cached_cats = [];
    if (empty($options['ctypeid_'.$ctype_id])) return [];
    
    $cache_key = ($ctype['name'] ?? '').'_'.$ctype_id;
    if (!isset($cached_cats[$cache_key])) {
        $cats = $model->getCategoriesTree($ctype['name'] ?? '');
        $cached_cats[$cache_key] = [];
        foreach ($cats as $cat) {
            $title = ($cat['ns_level'] ?? 0) > 1 
                ? str_repeat('-', $cat['ns_level']) . ' ' . $cat['title'] 
                : $cat['title'];
            $cached_cats[$cache_key][$cat['id']] = $title;
        }
    }
    return $cached_cats[$cache_key];
}

protected function getDatasetsList($model, $options, $ctype_id) {
    $datasets_list = ['0' => ''];
    $datasets = $model->getContentDatasets($ctype_id);
    
    if ($datasets) {
        foreach ($datasets as $dataset) {
            if ($dataset && !empty($dataset['id']) && !empty($dataset['title'])) {
                $datasets_list[$dataset['id']] = $dataset['title'];
            }
        }
    }
    
    return $datasets_list;
}

    protected function getFieldsList($model, $ctype, $options, $ctype_id) {
        $fields_list = ['' => ''];
        if (empty($options['ctypeid_'.$ctype_id])) return $fields_list;

        $ctype = $model->getContentType($options['ctypeid_'.$ctype_id]) ?: $ctype;
        $fields = $model->getContentFields($ctype['name'] ?? '');
        return $fields ? ['' => ''] + array_collection_to_list($fields, 'name', 'title') : $fields_list;
    }

    protected function addContentTypeFields(&$field, $ctype, $cats_list, $datasets_list, $fields_list) {
        $ctype_id = isset($ctype['id']) ? (int)$ctype['id'] : 0;
        $ctype_title = isset($ctype['title']) ? $ctype['title'] : '';

        $field[] = new fieldCheckbox('options:ctypeid_'.$ctype_id, [
            'title' => $ctype_title
        ]);

        $field[] = new fieldList('options:ctype_'.$ctype_id.'_cat_id', [
            'title' => LANG_CATEGORY,
            'disable_array_key_rules' => true,
            'parent' => [
                'list' => 'options:ctypeid_'.$ctype_id,
                'url' => href_to('content', 'widget_cats_ajax')
            ],
            'items' => $cats_list
        ]);

$field[] = new fieldList('options:ctype_'.$ctype_id.'_dataset', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_DATASET,
    'items' => $datasets_list,
    'rules' => [
        array('number')
    ]
]);

        $field[] = new fieldList('options:ctype_'.$ctype_id.'_teaser_field', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_TEASER,
            'disable_array_key_rules' => true,
            'parent' => [
                'list' => 'options:ctypeid_'.$ctype_id,
                'url' => href_to('content', 'widget_fields_ajax')
            ],
            'items' => $fields_list
        ]);
        
        $field[] = new fieldList('options:ctype_'.$ctype_id.'_image_field', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_IMAGE,
            'disable_array_key_rules' => true,
            'parent' => [
                'list' => 'options:ctypeid_'.$ctype_id,
                'url' => href_to('content', 'widget_fields_ajax')
            ],
            'items' => $fields_list
        ]);

        $field[] = new fieldNumber('options:limit_'.$ctype_id, [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_QUANTITY,
            'default' => 2,
            'rules' => [['min', 1]]
        ]);
        $field[] = new fieldNumber('options:ctype_'.$ctype_id.'_skip_first', [
            'title' => 'Пропустить первые N записей',
            'default' => 0,
            'rules' => [['min', 0]],
            'parent' => [
                'list' => 'options:ctypeid_'.$ctype_id
            ]
        ]);
		
		$field[] = new fieldCheckbox('options:infinite_scroll', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_INFINITE_SCROLL,
    'default' => false,
    'hint' => 'Автоматически подгружать контент при прокрутке страницы'
]);

$field[] = new fieldNumber('options:items_per_page', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_ITEMS_PER_PAGE,
    'default' => 6,
    'rules' => [['min', 1], ['max', 50]],
    'visible_depend' => ['options:infinite_scroll' => ['show' => ['1']]],
    'hint' => 'Сколько элементов подгружать за один раз'
]);
		
    }

    protected function addCommonFields(&$field, &$style_config_fields, &$image_fields) {
       
        $field[] = new fieldList('options:sorting', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_SORT_OPTIONS,
            'default' => 'sort_by_date',
            'items' => [
                'sort_by_date' => LANG_WD_CONTENT_WHOLECONTENT_SORT_BY_DATE,
                'sort_by_title' => LANG_WD_CONTENT_WHOLECONTENT_SORT_BY_TITLE,
                'sort_by_types' => LANG_WD_CONTENT_WHOLECONTENT_SORT_BY_TYPES,
                'sort_by_rating' => 'По рейтингу',
                'sort_rand' => 'Случайный порядок'
            ]
        ]);
		
		$field[] = new fieldCheckbox('options:show_ads', [
    'title' => 'Вставлять рекламные блоки',
    'default' => false
]);

$field[] = new fieldString('options:ads_positions', [
    'title' => 'Позиции для рекламы',
    'default' => '1,7',
    'visible_depend' => ['options:show_ads' => ['show' => ['1']]],
    'hint' => 'Номера позиций через запятую (например: 1,5,8)'
]);

$field[] = new fieldHtml('options:ads_html', [
    'title' => 'HTML код рекламного блока',
    'visible_depend' => ['options:show_ads' => ['show' => ['1']]],
    'options' => ['editor' => 'ace', 'editor_options' => ['mode' => 'html']],
    'default' => '<div class="wholecontent-ad-block" style="width: 100%; height: 100%; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 2px dashed #dee2e6; border-radius: 0.5rem;">
    <div class="text-muted">Место для вашей рекламы</div>
</div>'
]);

	   $style_config_fields[] = new fieldList('options:layout', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_LAYOUT,
            'default' => 'basic',
            'items' => [
                'basic' => LANG_WD_CONTENT_WHOLECONTENT_LAYOUT_BASIC,
                'column' => LANG_WD_CONTENT_WHOLECONTENT_LAYOUT_COLUMN,
                'dynamic' => LANG_WD_CONTENT_WHOLECONTENT_LAYOUT_DYNAMIC,
				'masonry' => LANG_WD_CONTENT_WHOLECONTENT_LAYOUT_MASONRY,
            ]
        ]);
		
$style_config_fields[] = new fieldList('options:column_count', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_COLUMN_COUNT,
    'default' => '4',
    'visible_depend' => [
        'options:layout' => ['show' => ['column']]
    ],
    'items' => [
        '2' => LANG_WD_CONTENT_WHOLECONTENT_COLUMN_COUNT_2,
        '3' => LANG_WD_CONTENT_WHOLECONTENT_COLUMN_COUNT_3, 
        '4' => LANG_WD_CONTENT_WHOLECONTENT_COLUMN_COUNT_4
    ]
]);

$style_config_fields[] = new fieldList('options:masonry_columns', [
    'title' => 'Количество колонок для Masonry',
    'default' => '3',
    'visible_depend' => ['options:layout' => ['show' => ['masonry']]],
    'items' => [
        '2' => '2 колонки',
        '3' => '3 колонки', 
        '4' => '4 колонки',
        '5' => '5 колонок'
    ]
]);
		
$style_config_fields[] = new fieldString('options:dynamic_layout_columns', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_DYNAMIC_LAYOUT_COLUMNS,
    'default' => '1,3,2',
    'visible_depend' => [
        'options:layout' => ['show' => ['dynamic']]
    ],
    'hint' => LANG_WD_CONTENT_WHOLECONTENT_DYNAMIC_LAYOUT_COLUMNS_HINT
]);

$style_config_fields[] = new fieldCheckbox('options:show_special', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_SPECIAL,
    'default' => false
]);

$style_config_fields[] = new fieldList('options:special_positions', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_SPECIAL_POSITIONS,
    'default' => 'none',
    'visible_depend' => ['options:show_special' => ['show' => ['1']]],
    'items' => [
        'none' => LANG_WD_CONTENT_WHOLECONTENT_SP_NONE,
        'every_2' => LANG_WD_CONTENT_WHOLECONTENT_SP_EVERY_2,
        'every_3' => LANG_WD_CONTENT_WHOLECONTENT_SP_EVERY_3,
        'every_4' => LANG_WD_CONTENT_WHOLECONTENT_SP_EVERY_4,
        'every_5' => LANG_WD_CONTENT_WHOLECONTENT_SP_EVERY_5,
        'every_7' => LANG_WD_CONTENT_WHOLECONTENT_SP_EVERY_7,
        'first_2' => LANG_WD_CONTENT_WHOLECONTENT_SP_FIRST_2,
        'first_3' => LANG_WD_CONTENT_WHOLECONTENT_SP_FIRST_3,
        'first_4' => LANG_WD_CONTENT_WHOLECONTENT_SP_FIRST_4,
        'first_5' => LANG_WD_CONTENT_WHOLECONTENT_SP_FIRST_5,
        'first_6' => LANG_WD_CONTENT_WHOLECONTENT_SP_FIRST_6,
        'first_7' => LANG_WD_CONTENT_WHOLECONTENT_SP_FIRST_7,
        'custom' => LANG_WD_CONTENT_WHOLECONTENT_SP_CUSTOM
    ],
	    'hint' => 'Выберите из предустановленных или укажите "Выбор пользователя", чтобы сработало поле ниже'
]);

$style_config_fields[] = new fieldString('options:custom_positions', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_CUSTOM_POSITIONS,
    'visible_depend' => ['options:show_special' => ['show' => ['1']]],
    'hint' => 'Работает, если в поле выше выбрано "Выбор пользователя". Укажите номера позиций через запятую (например: 1,5,8)'
]);

$style_config_fields[] = new fieldList('options:mobile_columns', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_MOBILE_COLUMNS,
    'default' => '1',
    'visible_depend' => [
        'options:layout' => ['show' => ['column', 'dynamic']]
    ],
    'items' => [
        '1' => LANG_WD_CONTENT_WHOLECONTENT_MOBILE_COLUMNS_1,
        '2' => LANG_WD_CONTENT_WHOLECONTENT_MOBILE_COLUMNS_2
    ],
    'hint' => LANG_WD_CONTENT_WHOLECONTENT_MOBILE_COLUMNS_HINT
]);

        $style_config_fields[] = new fieldNumber('options:title_len', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_TITLE_LEN,
            'default' => 0,
            'rules' => [['min', 0]]
        ]);
        
        $style_config_fields[] = new fieldNumber('options:teaser_len', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_TEASER_LEN,
            'default' => 0,
            'rules' => [['min', 0]]
        ]);

$style_config_fields[] = new fieldCheckbox('options:show_author', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_AUTHOR,
    'default' => false
]);

$style_config_fields[] = new fieldCheckbox('options:show_date', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_DATE,
    'default' => false
]);

$style_config_fields[] = new fieldCheckbox('options:show_group', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_GROUP,
    'default' => false,
    'hint' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_GROUP_HINT
]);

$style_config_fields[] = new fieldCheckbox('options:show_comments', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_COMMENTS,
    'default' => false
]);

$style_config_fields[] = new fieldCheckbox('options:show_views', [
    'title' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_VIEWS,
    'default' => false,
    'hint' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_VIEWS_HINT
]);
        
        $style_config_fields[] = new fieldCheckbox('options:show_item_parent', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_LINK_CTIPE,
            'default' => false
        ]);

        $style_config_fields[] = new fieldCheckbox('options:show_item_cat', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_LINK_CAT,
            'default' => false
        ]);

        $style_config_fields[] = new fieldCheckbox('options:show_tags', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_TAGS,
            'default' => false
        ]);

        $style_config_fields[] = new fieldNumber('options:max_tags_count', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_MAX_TAGS_COUNT,
            'default' => 3,
            'rules' => [['min', 1], ['max', 10]],
            'visible_depend' => ['options:show_tags' => ['show' => ['1']]]
        ]);

        $image_fields[] = new fieldList('options:image_preset', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_IMAGE_PRESET,
            'default' => 'content_list',
            'items' => self::IMAGE_PRESETS
        ]);
            
        $image_fields[] = new fieldCheckbox('options:lazy_loading', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_LAZY_LOADING,
            'default' => true
        ]);
        
        $image_fields[] = new fieldCheckbox('options:is_show_no_photo', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_SHOW_DEFAULT_IMAGE,
            'default' => false
        ]);
        
        $image_fields[] = new fieldImage('options:default_image', [
            'title' => LANG_WD_CONTENT_WHOLECONTENT_DEFAULT_IMAGE,
            'visible_depend' => ['options:is_show_no_photo' => ['show' => ['1']]],
            'options' => ['sizes' => array_keys(self::IMAGE_PRESETS)]
        ]);

        $style_config_fields[] = new fieldString('options:css_class', [
            'title' => 'Произвольный CSS-класс для контейнера',
            'hint' => 'Дополнительные классы для кастомизации внешнего вида'
        ]);
    }

    protected function outputStylesAndScripts($id_set) {
        $style = <<<CSS
<style>
    .fields-wrapper {
        margin-bottom: 1.5rem;
        padding: 1.5rem;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }
    #tab-1 .field.ft_image {
        margin-bottom: 1.5rem;
    }
    .modal_form {
        height: 530px !important;
        overflow-y: auto !important;
    }
</style>
CSS;

        $script_lines = [
            '<script>',
            '$(function() {'
        ];

        foreach ($id_set as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;
            
            $selectors = [
                "#f_options_ctype_{$id}_cat_id",
                "#f_options_ctype_{$id}_dataset",
                "#f_options_ctype_{$id}_image_field", 
                "#f_options_ctype_{$id}_teaser_field",
                "#f_options_limit_{$id}",
                "#f_options_ctype_{$id}_skip_first"
            ];
            
            $js_blocks = [
                '$("' . implode(', ', $selectors) . '").wrapAll(' .
                '\'<div class="fields-wrapper"></div>\');',
                
                'var opt' . $id . ' = $("#options_ctypeid_' . $id . '");',
                'var optSat' . $id . ' = opt' . $id . '.closest(".field").next(".fields-wrapper");',
                'opt' . $id . '.val(' . $id . ');',
                'if(!opt' . $id . '.prop("checked")) optSat' . $id . '.hide();',
                'opt' . $id . '.change(function(){',
                '  $(this).prop("checked") ?',
                '    optSat' . $id . '.slideDown() :',
                '    optSat' . $id . '.slideUp();',
                '});'
            ];
            
            $script_lines = array_merge($script_lines, $js_blocks);
        }

        $script_lines[] = '});';
        $script_lines[] = '</script>';
        $script = implode("\n", $script_lines);

        echo $style, $script;
    }
}