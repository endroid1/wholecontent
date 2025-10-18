<?php
class widgetContentWholecontent extends cmsWidget {
    private static $cache = [];
    public function run(): array {
        $params = [
            'title_len' => (int)$this->getOption('title_len', 0),
            'teaser_len' => (int)$this->getOption('teaser_len', 0),
        'show_author' => $this->getOption('show_author', false),
        'show_date' => $this->getOption('show_date', false),
		'show_group' => $this->getOption('show_group', false),
        'show_comments' => $this->getOption('show_comments', false),
        'show_views' => $this->getOption('show_views', false),
            'is_show_no_photo' => $this->getOption('is_show_no_photo', false),
            'is_show_item_parent' => $this->getOption('show_item_parent', false),
            'is_show_item_cat' => $this->getOption('show_item_cat', false),
            'sorting'          => $this->getOption('sorting', 'sort_by_date'),
            'image_preset'     => $this->getOption('image_preset', 'content_list'),
            'default_image'    => $this->getOption('default_image'),
            'lazy_loading'     => $this->getOption('lazy_loading', true),
			'show_special' => $this->getOption('show_special', false),
			'special_positions' => $this->getOption('special_positions', 'none'),
			'custom_positions' => $this->getOption('custom_positions', ''),
            'css_class'        => $this->getOption('css_class', '')
        ];

    $params['show_ads'] = $this->getOption('show_ads', false);
    $params['ads_positions'] = $this->getOption('ads_positions', '');
    $params['ads_html'] = $this->getOption('ads_html', '');

        $articles = [];
        $model = cmsCore::getModel('content');
        
        foreach ((array)$this->options as $key => $id) {
            if ($id && strpos($key, 'ctypeid') === 0) {
                $cache_key = 'ctype_'.(int)$id;
                if (!isset(self::$cache[$cache_key])) {
                    self::$cache[$cache_key] = $this->processContentType($model, (int)$id, $params);
                }
                if (self::$cache[$cache_key]) {
                    $articles[] = self::$cache[$cache_key];
                }
            }
        }

        return array_merge($params, [
        'articles' => $articles,
        'lazy_loading' => $params['lazy_loading'],
        'widget_id' => $this->id
    ]);
    }

public function isSpecialPosition($index, $special_positions, $custom_positions) {
    if ($special_positions === 'none' || $special_positions === '') {
        return false;
    }
    
    $position = $index + 1;
    $rules = [
        'all' => fn($p) => true,
        'every_2' => fn($p) => ($p % 2 === 0),
        'every_3' => fn($p) => ($p % 3 === 0),
        'every_4' => fn($p) => ($p % 4 === 0),
        'every_5' => fn($p) => ($p % 5 === 0),
        'every_7' => fn($p) => ($p % 7 === 0),
        'first_2' => fn($p) => ($p === 1 || $p % 2 === 0),
        'first_3' => fn($p) => ($p === 1 || $p % 3 === 0),
        'first_4' => fn($p) => ($p === 1 || $p % 4 === 0),
        'first_5' => fn($p) => ($p === 1 || $p % 5 === 0),
        'first_6' => fn($p) => ($p === 1 || $p % 6 === 0),
        'first_7' => fn($p) => ($p === 1 || $p % 7 === 0),
        'custom' => function($p) use ($custom_positions) {
            $positions = array_map('intval', explode(',', $custom_positions));
            return in_array($p, $positions, true);
        }
    ];

    return isset($rules[$special_positions]) ? $rules[$special_positions]($position) : false;
}

public function isAdPosition($index, $ads_positions) {
    if (empty($ads_positions)) {
        return false;
    }
    
    $positions = array_map('intval', explode(',', $ads_positions));
    $position = $index + 1;
    
    return in_array($position, $positions, true);
}

public function getAdColumnClass($layout, $column_count, $mobile_columns) {
    switch ($layout) {
        case 'basic':
            return 'col-12';
            
        case 'column':
            $columns = max(1, min($column_count, 6));
            $class = 'col-12';
            if ($mobile_columns > 1) {
                $class .= ' col-sm-'.(12/$mobile_columns);
            }
            $class .= ' col-md-6 col-lg-'.(12/$columns);
            return $class;
            
        case 'dynamic':
            $columns_config = $this->getOption('dynamic_layout_columns', '1,3,2');
            $columns_per_row = array_map('intval', explode(',', $columns_config));
            $avg_items = array_sum($columns_per_row) / count($columns_per_row);
            $col_size = $avg_items > 0 ? floor(12 / $avg_items) : 12;
            
            $class = 'col-md-'.max(1, $col_size);
            if ($mobile_columns > 1) {
                $class .= ' col-'.(12/$mobile_columns);
            }
            return $class;
            
        default:
            return 'col-12';
    }
}
	
    protected function processContentType($model, $id, $params) {
        $cache_key = 'ctype_data_'.$id;
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        $article = [
            'image_field' => $this->options['ctype_'.$id.'_image_field'] ?? '',
        'teaser_field' => $this->options['ctype_'.$id.'_teaser_field'] ?? '',
        'skip_first' => (int)($this->options['ctype_'.$id.'_skip_first'] ?? 0)
    ];

        if (!$ctype = $model->getContentType($id)) return null;

        $this->applyFilters($model, $ctype, $id);
        $items = $this->getContentItems($model, $ctype, $id);
        if (!$items) return null;

    if ($article['skip_first'] > 0 && $article['skip_first'] < count($items)) {
        $items = array_slice($items, $article['skip_first']);
    } elseif ($article['skip_first'] > 0) {
        $items = [];
    }
        $article['ctype'] = $ctype;
        $article['hide_except_title'] = $this->checkPrivacy($ctype, $model);
        $article['items'] = $this->prepareItems($items, $article, $params);

        self::$cache[$cache_key] = $article;
        return $article;
    }

protected function applyFilters($model, $ctype, $id) {
    if ($this->options['ctype_'.$id.'_cat_id'] ?? false) {
        $category = $model->getCategory($ctype['name'], (int)$this->options['ctype_'.$id.'_cat_id']);
        $model->filterCategory($ctype['name'], $category, true);
    }
    $dataset_value = $this->options['ctype_'.$id.'_dataset'] ?? '';
    if (!empty($dataset_value) && is_numeric($dataset_value)) {
        $dataset_id = (int)$dataset_value;
        
        if ($dataset_id > 0) {
            $dataset = $model->getContentDataset($dataset_id);  
            if ($dataset && !empty($dataset['filters'])) {
                $model->applyDatasetFilters($dataset);
            }
        }
    }
}

    protected function getContentItems($model, $ctype, $id) {
        $model->enableHiddenParentsFilter();
        list($ctype, $model) = cmsEventsManager::hook("content_list_filter", [$ctype, $model]);
    list($ctype, $model) = cmsEventsManager::hook("content_{$ctype['name']}_list_filter", [$ctype, $model]);

    $limit = (int)($this->options['limit_'.$id] ?? 0);
    
    $skip_first = (int)($this->options['ctype_'.$id.'_skip_first'] ?? 0);
    if ($skip_first > 0 && $limit > 0) {
        $limit += $skip_first;
    }
    
    $items = $model->limit($limit)->getContentItems($ctype['name']);
    if (!$items) return null;

        list($ctype, $items) = cmsEventsManager::hook("content_before_list", [$ctype, $items]);
        return cmsEventsManager::hook("content_{$ctype['name']}_before_list", [$ctype, $items])[1];
    }

    protected function checkPrivacy($ctype, $model) {
        $hide_except_title = !empty($ctype['options']['privacy_type']) && $ctype['options']['privacy_type'] == 'show_title';

        if (!empty($ctype['options']['privacy_type']) && in_array($ctype['options']['privacy_type'], ['show_title', 'show_all'], true)) {
            $model->disablePrivacyFilter();
            if ($ctype['options']['privacy_type'] != 'show_title') {
                $hide_except_title = false;
            }
        }

        if (cmsUser::isAllowed($ctype['name'], 'view_all')) {
            $model->disablePrivacyFilter();
            $hide_except_title = false;
        }

        return $hide_except_title;
    }

    protected function prepareItems($items, $article, $params) {
        foreach ($items as $index => &$item) {
            $item['_index'] = $index;
            $this->processItem($item, $article, $params);
        }
        return $items;
    }

    protected function processItem(&$item, $article, $params) {
        $item['is_private'] = $item['is_private'] && $article['hide_except_title'] && empty($item['user']['is_friend']);
        
        if ($item['is_private']) {
            $item['image'] = $item['url'] = false;
            return;
        }
		

        $item['hits_count'] = $item['hits_count'] ?? 0;
        
        $item['image'] = !empty($article['image_field']) && !empty($item[$article['image_field']]) 
            ? $item[$article['image_field']] 
            : false;
            
        $item['url'] = href_to($article['ctype']['name'], ($item['slug'] ?? '').'.html');

        foreach (['title', $article['teaser_field'] ?? ''] as $field) {
            if (!empty($params[$field.'_len']) && isset($item[$field])) {
                $item[$field] = $this->truncate($item[$field] ?? '', $params[$field.'_len']);
            }
        }

        if ($params['is_show_item_parent'] || $params['is_show_item_cat']) {
            $this->addCategoryInfo($item, $article);
        }
    }

    protected function addCategoryInfo(&$item, $article) {
        if (empty($item['category_id'])) return;

        $res = cmsCore::getInstance()->db->getFields(
            'con_'.$article['ctype']['name'].'_cats', 
            'id='.(int)$item['category_id'], 
            'title, slug'
        );
        
        if ($res) {
            $item['cat_name'] = $res['title'] ?? '';
            $item['cat_slug'] = $res['slug'] ?? '';
        }
    }
	
    private function truncate(?string $string, int $length): string {
    if ($length <= 0 || empty($string) || mb_strlen($string) <= $length) {
        return (string)$string;
    }
            
        $string = str_replace("\n", ' ', strip_tags($string));
        if (mb_strlen($string) > $length) {
            $length = max(0, $length - mb_strlen('...'));
            $string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length + 1));
            return mb_substr($string, 0, $length).'...';
        }
        return $string;
    }
}