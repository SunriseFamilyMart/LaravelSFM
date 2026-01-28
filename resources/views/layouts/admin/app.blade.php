<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title')</title>
    @php($icon = \App\Model\BusinessSetting::where(['key' => 'fav_icon'])->first()->value)
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/app/public/restaurant/' . $icon ?? '') }}">
    <link rel="shortcut icon" href="">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/vendor.min.css">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/vendor/icon-set/style.css">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/owl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/theme.minc619.css?v=1.0">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/style.css">
    @stack('css_or_js')

    <script src="{{ asset('public/assets/admin') }}/vendor/hs-navbar-vertical-aside/hs-navbar-vertical-aside-mini-cache.js">
    </script>
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/toastr.css">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/custom-helper.css">
</head>

<body class="footer-offset">

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="d-none" id="loading">
                    <div class="loader-image">
                        <img width="200" src="{{ asset('public/assets/admin/img/loader.gif') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.admin.partials._header')
    @include('layouts.admin.partials._sidebar')

    <main id="content" role="main" class="main pointer-event">

        @yield('content')

        @include('layouts.admin.partials._footer')

        <div class="modal fade" id="popup-modal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="text-center">
                                    <h2 class="order-check-colour">
                                        <i class="tio-shopping-cart-outlined"></i>
                                        {{ translate('You have new order, Check Please.') }}
                                    </h2>
                                    <hr>
                                    <button id="check-order"
                                        class="btn btn-primary">{{ translate('Ok, let me check') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="{{ asset('public/assets/admin') }}/js/custom.js"></script>

    @stack('script')

    <script src="{{ asset('public/assets/admin') }}/js/vendor.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/theme.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/sweet_alert.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/toastr.js"></script>
    <script src="{{ asset('public/assets/admin/js/owl.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/firebase.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>


    {!! Toastr::message() !!}

    @if ($errors->any())
        <script>
            @foreach ($errors->all() as $error)
                toastr.error('{{ $error }}', Error, {
                    CloseButton: true,
                    ProgressBar: true
                });
            @endforeach
        </script>
    @endif
    <script>
        $(document).on('ready', function() {
            var sidebar = $('.js-navbar-vertical-aside').hsSideNav();

            $('.js-nav-tooltip-link').tooltip({
                boundary: 'window'
            })

            $(".js-nav-tooltip-link").on("show.bs.tooltip", function(e) {
                if (!$("body").hasClass("navbar-vertical-aside-mini-mode")) {
                    return false;
                }
            });

            $('.js-hs-unfold-invoker').each(function() {
                var unfold = new HSUnfold($(this)).init();
            });

            $('.js-form-search').each(function() {
                new HSFormSearch($(this)).init()
            });

            $('.js-select2-custom').each(function() {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });

            $('.js-daterangepicker').daterangepicker();

            $('.js-daterangepicker-times').daterangepicker({
                timePicker: true,
                startDate: moment().startOf('hour'),
                endDate: moment().startOf('hour').add(32, 'hour'),
                locale: {
                    format: 'M/DD hh:mm A'
                }
            });
        });
    </script>


    @stack('script_2')
    <audio id="myAudio">
        <source src="{{ asset('public/assets/admin/sound/notification.mp3') }}" type="audio/mpeg">
    </audio>

    <script>
        var audio = document.getElementById("myAudio");

        function playAudio() {
            audio.play();
        }

        function pauseAudio() {
            audio.pause();
        }
    </script>
    <script>
        $('#check-order').on('click', function() {
            location.href = '{{ route('admin.order.list', ['status' => 'all']) }}';
        })

        function route_alert(route, message) {
            Swal.fire({
                title: '{{ translate('Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#7b541d',
                cancelButtonText: '{{ translate('No') }}',
                confirmButtonText: '{{ translate('Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = route;
                }
            })
        }

        $('.form-alert').on('click', function() {
            let id = $(this).data('id');
            let message = $(this).data('message');
            form_alert(id, message)
        });

        function form_alert(id, message) {
            Swal.fire({
                title: '{{ translate('Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#7b541d',
                cancelButtonText: '{{ translate('No') }}',
                confirmButtonText: '{{ translate('Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#' + id).submit()
                }
            })
        }

        function call_demo() {
            toastr.info('{{ translate('Disabled for demo version!') }}')
        }

        $('.call-demo').click(function() {
            if ('{{ env('APP_MODE') }}' === 'demo') {
                call_demo();
            }
        });
    </script>

    <script>
        $('.status-change-alert').on('click', function() {
            let url = $(this).data('route');
            let message = $(this).data('message');
            status_change_alert(url, message, event)
        });

        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: '{{ translate('Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#544c30',
                confirmButtonText: '{{ translate('Yes') }}',
                cancelButtonText: '{{ translate('No') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }
    </script>

    <script>
        var initialImages = [];
        $(window).on('load', function() {
            $("form").find('img').each(function(index, value) {
                initialImages.push(value.src);
            })
        })

        $(document).ready(function() {
            $('form').on('reset', function(e) {
                $("form").find('img').each(function(index, value) {
                    $(value).attr('src', initialImages[index]);
                })
            });
        });
    </script>

    <script>
        $(function() {
            var owl = $('.single-item-slider');
            owl.owlCarousel({
                autoplay: false,
                items: 1,
                onInitialized: counter,
                onTranslated: counter,
                autoHeight: true,
                dots: true,
            });

            function counter(event) {
                var element = event.target; // DOM element, in this example .owl-carousel
                var items = event.item.count; // Number of items
                var item = event.item.index + 1; // Position of the current item

                if (item > items) {
                    item = item - items
                }
                $('.slide-counter').html(+item + "/" + items)
            }
        });
    </script>

    <script>
        @php($admin_order_notification = \App\CentralLogics\Helpers::get_business_settings('admin_order_notification'))
        @php($admin_order_notification_type = \App\CentralLogics\Helpers::get_business_settings('admin_order_notification_type'))

        @if (\App\CentralLogics\Helpers::module_permission_check('order_management') && $admin_order_notification)

            @if ($admin_order_notification_type == 'manual')
                setInterval(function() {
                    $.get({
                        url: '{{ route('admin.get-restaurant-data') }}',
                        dataType: 'json',
                        success: function(response) {
                            let data = response.data;
                            new_order_type = data.type;
                            if (data.new_order > 0) {
                                playAudio();
                                $('#popup-modal').appendTo("body").modal('show');
                            }
                        },
                    });
                }, 10000);
            @endif

            @if ($admin_order_notification_type == 'firebase')
                @php($fcm_credentials = \App\CentralLogics\Helpers::get_business_settings('firebase_message_config'))
                var firebaseConfig = {
                    apiKey: "{{ isset($fcm_credentials['apiKey']) ? $fcm_credentials['apiKey'] : '' }}",
                    authDomain: "{{ isset($fcm_credentials['authDomain']) ? $fcm_credentials['authDomain'] : '' }}",
                    projectId: "{{ isset($fcm_credentials['projectId']) ? $fcm_credentials['projectId'] : '' }}",
                    storageBucket: "{{ isset($fcm_credentials['storageBucket']) ? $fcm_credentials['storageBucket'] : '' }}",
                    messagingSenderId: "{{ isset($fcm_credentials['messagingSenderId']) ? $fcm_credentials['messagingSenderId'] : '' }}",
                    appId: "{{ isset($fcm_credentials['appId']) ? $fcm_credentials['appId'] : '' }}",
                    measurementId: "{{ isset($fcm_credentials['measurementId']) ? $fcm_credentials['measurementId'] : '' }}"
                };


                firebase.initializeApp(firebaseConfig);
                const messaging = firebase.messaging();

                function startFCM() {
                    messaging
                        .requestPermission()
                        .then(function() {
                            return messaging.getToken();
                        })
                        .then(function(token) {
                            subscribeTokenToBackend(token, 'grofresh_admin_message');
                        }).catch(function(error) {
                            console.error('Error getting permission or token:', error);
                        });
                }

                function subscribeTokenToBackend(token, topic) {
                    fetch('{{ url('/') }}/subscribeToTopic', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            token: token,
                            topic: topic
                        })
                    }).then(response => {
                        if (response.status < 200 || response.status >= 400) {
                            return response.text().then(text => {
                                throw new Error(`Error subscribing to topic: ${response.status} - ${text}`);
                            });
                        }
                        console.log(`Subscribed to "${topic}"`);
                    }).catch(error => {
                        console.error('Subscription error:', error);
                    });
                }

                messaging.onMessage(function(payload) {
                    console.log(payload.data);
                    if (payload.data.order_id && payload.data.type == "order_request") {
                        playAudio();
                        $('#popup-modal').appendTo("body").modal('show');
                    }
                });

                startFCM();
            @endif
        @endif
    </script>

    <!-- IE Support -->
    <script>
        if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write(
            '<script src="{{ asset('public/assets/admin') }}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
    </script>
</body>

</html>
