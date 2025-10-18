(function($) {
    "use strict";

    $.fn.wholecontentInfiniteScroll = function(options) {
        
        const settings = $.extend({
            widgetId: 0,
            loadingText: 'Загрузка...',
            errorText: 'Ошибка загрузки',
            noMoreText: 'Загружено все',
            path: '/content/wholecontent_load_more'
        }, options);

        const $container = this;
        let currentPage = 2;
        let isLoading = false;
        let hasMore = true;

        function init() {
            if (!settings.widgetId) {
                console.error('WholeContent: Widget ID is required');
                return;
            }

            createLoader();
            bindScroll();
        }

        function createLoader() {
            $container.after(
                '<div id="wholecontent-loader-' + settings.widgetId + '" class="wholecontent-loader text-center py-4" style="display: none;">' +
                '   <div class="spinner-border text-primary" role="status">' +
                '       <span class="visually-hidden">' + settings.loadingText + '</span>' +
                '   </div>' +
                '   <div class="mt-2">' + settings.loadingText + '</div>' +
                '</div>' +
                '<div id="wholecontent-no-more-' + settings.widgetId + '" class="wholecontent-no-more text-center py-4 text-muted" style="display: none;">' +
                '   ' + settings.noMoreText +
                '</div>'
            );
        }

        function bindScroll() {
            $(window).on('scroll.wholecontent-' + settings.widgetId, function() {
                if (isLoading || !hasMore) return;

                const loader = $('#wholecontent-loader-' + settings.widgetId);
                const windowBottom = $(window).scrollTop() + $(window).height();
                const containerBottom = $container.offset().top + $container.outerHeight();

                // Загружаем когда дошли до 300px до конца контейнера
                if (windowBottom >= (containerBottom - 300)) {
                    loadMore();
                }
            });
        }

        function loadMore() {
            isLoading = true;
            
            const loader = $('#wholecontent-loader-' + settings.widgetId);
            loader.show();

            $.ajax({
                url: settings.path,
                type: 'POST',
                dataType: 'json',
                data: {
                    widget_id: settings.widgetId,
                    page: currentPage,
                    options: getWidgetOptions()
                },
                success: function(response) {
                    if (response.error) {
                        if (response.message === 'No more content') {
                            showNoMore();
                        } else {
                            showError(response.message);
                        }
                    } else {
                        $container.append(response.html);
                        currentPage++;
                        
                        if (!response.has_more) {
                            showNoMore();
                        }
                        
                        // Инициализируем ленивую загрузку для новых изображений
                        initLazyLoading();
                    }
                },
                error: function(xhr, status, error) {
                    showError(settings.errorText + ': ' + error);
                },
                complete: function() {
                    isLoading = false;
                    loader.hide();
                }
            });
        }

        function getWidgetOptions() {
            // Получаем опции виджета из data-атрибутов
            return {
                layout: $container.data('layout') || 'basic',
                sorting: $container.data('sorting') || 'sort_by_date',
                show_ads: $container.data('show-ads') || false,
                // ... другие опции
            };
        }

        function initLazyLoading() {
            // Инициализация ленивой загрузки для новых изображений
            if (typeof initLazyImages === 'function') {
                initLazyImages();
            }
        }

        function showNoMore() {
            hasMore = false;
            $('#wholecontent-no-more-' + settings.widgetId).show();
            $(window).off('scroll.wholecontent-' + settings.widgetId);
        }

        function showError(message) {
            $container.after(
                '<div class="wholecontent-error alert alert-warning text-center">' + 
                message + 
                '</div>'
            );
        }

        // Публичные методы
        this.destroy = function() {
            $(window).off('scroll.wholecontent-' + settings.widgetId);
            $('#wholecontent-loader-' + settings.widgetId).remove();
            $('#wholecontent-no-more-' + settings.widgetId).remove();
        };

        init();
        return this;
    };

})(jQuery);