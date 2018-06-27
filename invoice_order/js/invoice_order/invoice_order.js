(function($) {

    var options = {
        btn: {},
        getInvoiceAction: 'generateInvoiceAction',
        checkAction: 'checkOrderForRulesAction',

        //Order data
        orderId: '',
        deliveryType: '',

        //URL Endpoints
        baseUrl: '/admin/generate_invoice_endpoint.php',

        //Elements
        buttonId: '#get-order-invoice',
        iframeId: '#order-invoice-iframe'
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

        options.btn =  $(options.buttonId);
        options.orderId = options.btn.data('orders-id');
        options.deliveryType = options.btn.data('delivery-type');

    };

    var __sendData = function () {

        var data = {'action': options.checkAction, 'orderId': parseInt(options.orderId)};

        $.ajax({
            type: "POST",
            url: options.baseUrl,
            data: data,
            success: function (response) {

                if(!response){
                    return;
                }

                response = response.trim();

                if(! isValidJson(response) ){
                    getModalForm('error', 'Товарная накладная - что-то пошло не так во время получения результатов об этой операции. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                data = JSON.parse(response);

                if(!data ){
                    getModalForm('error', 'Товарная накладная - пришедшие данные не соответсвуют формату. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                if(data.result == 'error'){
                    getModalForm('error', data.message);
                    return false;
                }

                if(data.result == 'success'){
                    $(options.iframeId).attr("src",options.baseUrl + '?action=' + options.getInvoiceAction + '&orderId=' + options.orderId + '&deliveryType=' + options.deliveryType);

                }

            }
        });

    };

    init();

})(jQuery);