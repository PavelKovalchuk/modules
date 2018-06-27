(function($) {

    var options = {
        btn: {},
        getTtnAction: 'generateTtnAction',
        checkAction: 'checkOrderForTtnRulesAction',

        //Order data
        orderId: '',

        //URL Endpoints
        baseUrl: '/admin/generate_ttn_dropshipping_action.php',

        //Elements
        buttonTtnId: '#get-ttn-dropshipping',
        iframeTtnId: '#dropshipping_iframe'
    };

    var init = function () {

        initData();
        initButtonsEvents();

    };

    var initButtonsEvents = function () {

        options.btn.on('click', function(e){

            __sendData();

        });

    };

    var initData = function () {

        options.btn =  $(options.buttonTtnId);
        options.orderId = options.btn.data('orders-id');

    };

    var __sendData = function () {

        var data = {'action': options.checkAction, 'orderId': parseInt(options.orderId)};

        $.ajax({
            type: "POST",
            url: options.baseUrl ,
            data: data,
            success: function (response) {

                if(!response){
                    return;
                }

                response = response.trim();

                if(! isValidJson(response) ){
                    getModalForm('error', 'ТТН по дропшиппингу - что-то пошло не так во время получения результатов об этой операции. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                data = JSON.parse(response);

                if(!data ){
                    getModalForm('error', 'ТТН по дропшиппингу - пришедшие данные не соответсвуют формату. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                if(data.result == 'error'){
                    getModalForm('error', data.message);
                    return false;
                }

                if(data.result == 'success'){

                    $(options.iframeTtnId).attr("src",options.baseUrl + '?action=' + options.getTtnAction + '&orderId=' + options.orderId);

                }

            }
        });

    };

    init();

})(jQuery);