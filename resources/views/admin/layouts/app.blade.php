<!DOCTYPE html>
<html ng-app="{{ config('app.name') }}" lang="en" class="light-style layout-menu-fixed layout-compact" dir="ltr" data-theme="theme-default"
    data-assets-path="../assets/" data-template="vertical-menu-template-free">
    <head>
        <meta charset="utf-8" />
        <title>{{ config('app.name') }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="ws_url" content="{{ env('WS_URL') }}">
        <meta name="user_id" content="{{ Auth::id() }}">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet"/>
        <link rel="stylesheet" href="{{asset('assets/admin/vendor/fonts/boxicons.css')}}" />
        <link rel="stylesheet" href="{{asset('assets/admin/vendor/css/core.css')}}" class="template-customizer-core-css" />
        <link rel="stylesheet" href="{{asset('assets/admin/vendor/css/theme-default.css')}}" class="template-customizer-theme-css" />
        <link rel="stylesheet" href="{{asset('assets/admin/css/demo.css')}}" />
        <link rel="stylesheet" href="{{asset('assets/admin/css/custom.css')}}" />
        <link rel="stylesheet" href="{{asset('assets/admin/css/bootstrapDataTable.css')}}" />
        <link rel="stylesheet" href="{{asset('assets/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.css')}}" />
        <link rel="stylesheet" href="{{asset('assets/admin/vendor/libs/apex-charts/apex-charts.css')}}" />
        <script src="{{asset('assets/admin/vendor/js/helpers.js')}}"></script>
        <script src="{{asset('assets/admin/js/config.js')}}"></script>
        <link rel="stylesheet" href="{{asset('assets/admin/css/sweet-alert.css')}}" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
        <style>
            /* Sneat Admin Select2 Custom Styling */
            .select2-container--default .select2-selection--single {
                height: 38px;
                border: 1px solid #d9dee3;
                border-radius: 0.375rem;
                background-color: #fff;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 36px;
                color: #697a8d;
                padding-left: 0.875rem;
                padding-right: 2rem;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 36px;
                right: 6px;
            }
            .select2-dropdown {
                border: 1px solid #d9dee3;
                border-radius: 0.375rem;
                box-shadow: 0 0.25rem 1rem rgba(161, 172, 184, 0.45);
                z-index: 9999;
            }
            .select2-search__field {
                border: 1px solid #d9dee3 !important;
                border-radius: 0.375rem !important;
                padding: 0.375rem 0.75rem !important;
                margin: 8px !important;
                width: calc(100% - 16px) !important;
                outline: none !important;
            }
            .select2-results__option {
                padding: 8px 14px;
                font-size: 0.9rem;
            }
            .select2-results__option--highlighted[aria-selected] {
                background-color: rgba(105, 108, 255, 0.08) !important;
                color: #696cff !important;
            }
            .select2-container--default .select2-selection--multiple {
                border: 1px solid #d9dee3;
                border-radius: 0.375rem;
                min-height: 38px;
            }
            .is-invalid + .select2-container .select2-selection--single {
                border-color: #ff3e1d !important;
            }
        </style>
        @yield('style')
        
    </head>
    <body>
       <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">
                @include('admin.layouts.elements.left_sidebar')
                <div class="layout-page">
                    @include('admin.layouts.elements.header')
                    <div class="content-wrapper">
                        @yield('content')
                        @include('admin.layouts.elements.footer')
                        <div class="content-backdrop fade"></div>
                    </div>
                    @include('admin.layouts.elements.right_sidebar')
                </div>
        
                <script src="{{asset('assets/admin/vendor/libs/jquery/jquery.js')}}"></script>
                <script src="{{asset('assets/admin/vendor/libs/popper/popper.js')}}"></script>
                <script src="{{asset('assets/admin/vendor/js/bootstrap.js')}}"></script>
                <script src="{{asset('assets/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')}}"></script>
                <script src="{{asset('assets/admin/vendor/js/menu.js')}}"></script>
                <script src="{{asset('assets/admin/vendor/libs/apex-charts/apexcharts.js')}}"></script>
                <script src="{{asset('assets/admin/js/main.js')}}"></script>
                <script src="{{asset('assets/admin/js/dataTable.js')}}"></script>
                <script src="{{asset('assets/admin/js/bootstrapDataTable.js')}}"></script>
                <script src="{{asset('assets/admin/js/dashboards-analytics.js')}}"></script>
                <script src="{{asset('assets/admin/js/moment.min.js')}}"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
                <script async defer src="https://buttons.github.io/buttons.js"></script>
                <script>
                    $(document).ready(function() {
                        function initGlobalSelect2(context) {
                            if (typeof $.fn.select2 === 'undefined') return;
                            var $target = context ? $(context).find('.select2, select.select2-search') : $('.select2, select.select2-search');
                            $target.each(function() {
                                var $this = $(this);
                                if ($this.data('select2')) return;
                                var firstEmpty = $this.find('option[value=""]');
                                var placeholderText = $this.attr('placeholder') || (firstEmpty.length ? firstEmpty.text() : ($this.find('option:first-child').text() || 'Select an option'));
                                var allowClear = !$this.prop('required');
                                var $modal = $this.closest('.modal');
                                var selectConfig = {
                                    placeholder: {
                                        id: '',
                                        text: placeholderText
                                    },
                                    allowClear: allowClear,
                                    width: '100%'
                                };
                                if ($modal.length) {
                                    selectConfig.dropdownParent = $modal;
                                }
                                $this.select2(selectConfig);
                            });
                        }
                        window.initGlobalSelect2 = initGlobalSelect2;
                        initGlobalSelect2();

                        $(document).on('shown.bs.modal', '.modal', function() {
                            initGlobalSelect2(this);
                        });

                        $(document).on('submit', 'form', function() {
                            $(this).find('.select2').each(function() {
                                var val = $(this).val();
                                if (val !== null && val !== undefined) {
                                    $(this).val(val);
                                }
                            });
                        });
                    });
                </script>
                @yield('script')
                @include('admin.layouts.elements.sweet_alerts')
            </div>
            <div class="layout-overlay mobile-menu-overlay"></div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var menu = document.getElementById('layout-menu');
                var overlay = document.querySelector('.mobile-menu-overlay');
                var closeBtn = document.querySelector('.menu-close-btn');

                if (!menu) return;

                function openMobileMenu() {
                    if (!menu || window.innerWidth > 992) return;
                    menu.classList.add('show-menu');
                    if (overlay) overlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }

                function closeMobileMenu() {
                    if (!menu) return;
                    menu.classList.remove('show-menu');
                    if (overlay) overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }

                document.querySelectorAll('.layout-menu-toggle a').forEach(function(toggle) {
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (menu.classList.contains('show-menu')) {
                            closeMobileMenu();
                        } else {
                            openMobileMenu();
                        }
                    });
                });

                if (closeBtn) {
                    closeBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        closeMobileMenu();
                    });
                }

                if (overlay) {
                    overlay.addEventListener('click', function() {
                        closeMobileMenu();
                    });
                }

                var menuLinks = menu.querySelectorAll('.menu-link');
                menuLinks.forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 992) {
                            setTimeout(closeMobileMenu, 200);
                        }
                    });
                });

                var resizeTimer;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(function() {
                        if (window.innerWidth > 992) {
                            closeMobileMenu();
                        }
                    }, 100);
                });

                window.closeMobileMenu = closeMobileMenu;
            });
        </script>
    </body>
</html>