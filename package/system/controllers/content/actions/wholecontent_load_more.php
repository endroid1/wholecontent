<?php

class actionContentWholecontent_load_more extends cmsAction {

    public function run() {
        
        if (!$this->request->isAjax()) {
            return cmsCore::error404();
        }

        $widget_id = $this->request->get('widget_id', 0);
        $page = $this->request->get('page', 2);
        $options = $this->request->get('options', []);

        if (!$widget_id) {
            return $this->cms_template->renderJSON([
                'error' => true,
                'message' => 'No widget ID'
            ]);
        }

        // Получаем виджет по ID
        $widgets_model = cmsCore::getModel('widgets');
        $widget = $widgets_model->getWidget($widget_id);
        
        if (!$widget) {
            return $this->cms_template->renderJSON([
                'error' => true,
                'message' => 'Widget not found'
            ]);
        }

        // Создаем экземпляр виджета
        $widget_object = cmsCore::getWidget($widget['name'], $widget['controller']);
        $widget_object->setOptions($widget['options']);
        $widget_object->id = $widget['id'];

        // Получаем данные для следующей страницы
        $result = $this->getNextPageData($widget_object, $page, $options);
        
        if (empty($result['items'])) {
            return $this->cms_template->renderJSON([
                'error' => true,
                'message' => 'No more content',
                'html' => ''
            ]);
        }

        // Рендерим HTML для новых элементов
        $html = $this->renderItemsHTML($result['items'], $widget_object, $options);

        return $this->cms_template->renderJSON([
            'error' => false,
            'html' => $html,
            'has_more' => $result['has_more'],
            'page' => $page
        ]);
    }

    private function getNextPageData($widget_object, $page, $options) {
        
        $model = cmsCore::getModel('content');
        $all_items = [];
        $has_more = false;

        // Получаем оригинальные данные виджета для структуры
        $original_data = $widget_object->run();
        $original_articles = $original_data['articles'] ?? [];

        foreach ($original_articles as $article_data) {
            $ctype = $article_data['ctype'];
            $ctype_id = $ctype['id'];
            
            $limit = (int)($widget_object->getOption('limit_'.$ctype_id) ?? 0);
            $skip_first = (int)($widget_object->getOption('ctype_'.$ctype_id.'_skip_first') ?? 0);
            
            if ($limit > 0) {
                $offset = $skip_first + (($page - 1) * $limit);
                
                // Применяем фильтры как в основном виджете
                $this->applyWidgetFilters($model, $ctype, $ctype_id, $widget_object->getOptions());
                
                // Получаем элементы с пагинацией
                $items = $model->limit($limit, $offset)->getContentItems($ctype['name']);
                
                if ($items) {
                    // Подготавливаем элементы как в основном виджете
                    $prepared_items = $this->prepareWidgetItems($items, $article_data, $widget_object->getOptions());
                    
                    foreach ($prepared_items as $item) {
                        $item['_article'] = $article_data;
                        $all_items[] = $item;
                    }
                    
                    // Проверяем, есть ли еще элементы
                    $total_count = $this->getTotalItemsCount($model, $ctype, $ctype_id, $widget_object->getOptions());
                    $current_total = $offset + count($items);
                    $has_more = $has_more || ($current_total < $total_count);
                }
            }
        }

        // Сортируем если нужно
        if (!empty($all_items) && !empty($options['sorting'])) {
            $all_items = $this->sortItems($all_items, $options['sorting']);
        }

        return [
            'items' => $all_items,
            'has_more' => $has_more
        ];
    }

    private function getTotalItemsCount($model, $ctype, $ctype_id, $options) {
        
        $model->resetFilters();
        $this->applyWidgetFilters($model, $ctype, $ctype_id, $options);
        
        return $model->getContentItemsCount($ctype['name']);
    }

    private function applyWidgetFilters($model, $ctype, $ctype_id, $options) {
        
        // Категория
        if (!empty($options['ctype_'.$ctype_id.'_cat_id'])) {
            $category = $model->getCategory($ctype['name'], (int)$options['ctype_'.$ctype_id.'_cat_id']);
            $model->filterCategory($ctype['name'], $category, true);
        }
        
        // Датасет
        $dataset_value = $options['ctype_'.$ctype_id.'_dataset'] ?? '';
        if (!empty($dataset_value) && is_numeric($dataset_value)) {
            $dataset_id = (int)$dataset_value;
            if ($dataset_id > 0) {
                $dataset = $model->getContentDataset($dataset_id);  
                if ($dataset && !empty($dataset['filters'])) {
                    $model->applyDatasetFilters($dataset);
                }
            }
        }
        
        $model->enableHiddenParentsFilter();
        $model->filterEqual('i.is_approved', 1);
        
        list($ctype, $model) = cmsEventsManager::hook("content_list_filter", [$ctype, $model]);
        list($ctype, $model) = cmsEventsManager::hook("content_{$ctype['name']}_list_filter", [$ctype, $model]);
    }

    private function prepareWidgetItems($items, $article_data, $params) {
        
        $prepared = [];
        $article = [
            'ctype' => $article_data['ctype'],
            'image_field' => $article_data['image_field'] ?? '',
            'teaser_field' => $article_data['teaser_field'] ?? '',
            'hide_except_title' => $article_data['hide_except_title'] ?? false
        ];

        foreach ($items as $index => $item) {
            
            // Копируем логику обработки из основного виджета
            $item['is_private'] = $item['is_private'] && $article['hide_except_title'] && empty($item['user']['is_friend']);
            
            if ($item['is_private']) {
                $item['image'] = $item['url'] = false;
            } else {
                $item['image'] = !empty($article['image_field']) && !empty($item[$article['image_field']]) 
                    ? $item[$article['image_field']] 
                    : false;
                    
                $item['url'] = href_to($article['ctype']['name'], ($item['slug'] ?? '').'.html');

                // Обрезаем текст если нужно
                foreach (['title', $article['teaser_field'] ?? ''] as $field) {
                    if (!empty($params[$field.'_len']) && isset($item[$field])) {
                        $item[$field] = $this->truncateText($item[$field] ?? '', $params[$field.'_len']);
                    }
                }
            }

            $prepared[] = $item;
        }

        return $prepared;
    }

    private function truncateText(?string $string, int $length): string {
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

    private function sortItems($items, $sorting_type) {
        
        if (empty($sorting_type) || empty($items)) {
            return $items;
        }

        switch ($sorting_type) {
            case 'sort_by_title':
                usort($items, function($a, $b) {
                    return strcmp(mb_strtolower($a['title'] ?? ''), mb_strtolower($b['title'] ?? ''));
                });
                break;
            case 'sort_by_types':
                usort($items, function($a, $b) {
                    return strcmp($a['_article']['ctype']['title'] ?? '', $b['_article']['ctype']['title'] ?? '');
                });
                break;
            case 'sort_rand':
                shuffle($items);
                break;
            case 'sort_by_rating':
                usort($items, function($a, $b) {
                    return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
                });
                break;
            default:
                usort($items, function($a, $b) {
                    return strtotime($b['date_pub'] ?? '') <=> strtotime($a['date_pub'] ?? '');
                });
        }
        
        return $items;
    }

    private function renderItemsHTML($items, $widget_object, $options) {
        
        ob_start();

        // Подключаем helpers
        $template = cmsTemplate::getInstance();
        $template_path = $template->getTemplateFileName('controllers/content/widgets/wholecontent/conf/helpers', true);
        if ($template_path) {
            include $template_path;
        }

        $layout = $options['layout'] ?? 'basic';
        $layout_file = $template->getTemplateFileName('controllers/content/widgets/wholecontent/layout/'.$layout, true);
        
        if (!$layout_file || !file_exists($layout_file)) {
            $layout_file = $template->getTemplateFileName('controllers/content/widgets/wholecontent/layout/column', true);
        }

        $widget_options = $widget_object->getOptions();
        $index_offset = (int)($options['current_count'] ?? 0);

        foreach ($items as $index => $item) {
            $article = $item['_article'];
            $global_index = $index + $index_offset;
            
            $has_image = !empty($item['image']) || ($widget_options['is_show_no_photo'] ?? false && !empty($widget_options['default_image']));
            
            $is_special = false;
            if ($widget_options['show_special'] ?? false && ($widget_options['special_positions'] ?? 'none') !== 'none') {
                $is_special = $this->isSpecialPosition($global_index, $widget_options['special_positions'], $widget_options['custom_positions'] ?? '');
            }
            $item['_is_special'] = $is_special;

            // Проверяем позицию рекламы
            $is_ad_position = false;
            if ($widget_options['show_ads'] ?? false && !empty($widget_options['ads_positions'])) {
                $is_ad_position = $this->isAdPosition($global_index, $widget_options['ads_positions']);
            }

            if ($is_ad_position) {
                // Рендерим рекламный блок
                $ad_template = $template->getTemplateFileName('controllers/content/widgets/wholecontent/part/ads_block', true);
                if ($ad_template) {
                    $col_class = $this->getAdColumnClass($layout, $widget_options['column_count'] ?? 4, $widget_options['mobile_columns'] ?? 1);
                    include $ad_template;
                }
            }

            // Рендерим основной элемент
            if ($layout_file) {
                include $layout_file;
            }
        }
        
        return ob_get_clean();
    }

    private function isSpecialPosition($index, $special_positions, $custom_positions) {
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

    private function isAdPosition($index, $ads_positions) {
        if (empty($ads_positions)) {
            return false;
        }
        
        $positions = array_map('intval', explode(',', $ads_positions));
        $position = $index + 1;
        
        return in_array($position, $positions, true);
    }

    private function getAdColumnClass($layout, $column_count, $mobile_columns) {
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
                $columns_config = '1,3,2'; // default
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
}