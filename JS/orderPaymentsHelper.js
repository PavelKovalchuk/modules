
(function (global, $) {
    "use strict";

    var orderPaymentsHelper = function () {
        return new orderPaymentsHelper.init();
    };

    orderPaymentsHelper.init = function () {

        var self = this;
        self.hasError = false;
        self.errors = new Set();

    };

    orderPaymentsHelper.prototype = {

        options: {

            orderItemClass: 'js-orders-chain-item',
            orderItemPercentageNameAttr: 'prepayment_percentage',
            orderItemAmountClass: 'js-counted-amount',
            formClass: 'payment-modal',
            orderChainIdDataAttr: 'orders-chain-id',
            orderGroupDataAttr: 'payment-group',
            orderItemPaid_parentClass: 'dataTableRowPaid',

            //тип: оплата или предоплата
            paymentTypeDataAttr: 'payment-type',

            //Input для хранения общей суммы для полной оплаты
            totalSumHolderNameAttr: 'total_sum',

            //Text data
            emptyDataParamErrorText: 'Не определен основной параметр (data) для начала операции.',

            //Input class, which holds the amount
            groupAmountHolder : {
                prepayment:{
                    letter: 'js-counted-sum',
                    sms: 'js-counted-sum',
                    liqPayInvoice: 'js-total-liqpay',
                    manualInput: 'js-manual-sum',
                },

                payment: {
                    letter: 'js-total-sum',
                    sms: 'js-total-sum',
                    liqPayInvoice: 'js-total-liqpay',
                    manualInput: 'js-manual-sum',
                }
            },



        },

        fireError: function () {
            var self = this;
            self.hasError = true;
        },

        clearError: function () {
            var self = this;
            self.hasError = false;
            self.errors.clear();
        },

        isError: function () {
            var self = this;
            return self.hasError;
        },

        addError: function (text) {
            var self = this;

            if(!text){
                return false;
            }

            self.errors.add(text);

            if(self.isError() === false){
                self.fireError(true);
            }

            return true;
        },

        getErrors: function () {
            var self = this;
            if(!self.errors.size){
                return false;
            }

            return self.errors;

        },

        getErrorsString: function () {

            var self = this;

            if(!self.getErrors()){
                return false;
            }

            var response = '';

            for(let errror of self.errors){
                response += " " + errror;
            }

            return response;

        },

        getFormData : function () {
            var self = this;
            var form = self.getForm();

            if(!form){
                return false;
            }

            return form.serialize();

        },

        getForm : function () {
            var self = this;
            var form = $('.' + self.options.formClass);

            if(!form.length > 0){
                self.addError('Нет формы для расчета.');
                return false;
            }

            return form;

        },

        getOrdersChainPercentData : function () {

            var self = this;
            var paymentType = $('.' + self.options.formClass).data(self.options.paymentTypeDataAttr);
            var fullPayment = ( paymentType ==  'payment') ? true : false;
            var ordersChainPaid = self.getOrdersChainPaid();
            var ordersChainData = {};
            var globalChainId = 0;

            $.each( $('.' + self.options.orderItemClass), function (index, data) {

                var order = $(this);
                var orderId = order.text();

                var isOrderPaid = (jQuery.inArray( orderId, ordersChainPaid )) > -1;
                if(!orderId){
                    return;
                }

                var chainId = order.data(self.options.orderChainIdDataAttr) ? order.data(self.options.orderChainIdDataAttr) : globalChainId;
                var percent = (fullPayment) ? '1': $('select[name = "' + self.options.orderItemPercentageNameAttr + '[' + orderId + ']"' + ']').val();
                var amount = $('#order_percentage_' + orderId + '.' + self.options.orderItemAmountClass).text();

                if(chainId > 0){
                    globalChainId = chainId;
                }


                if(! parseFloat(percent) > 0){
                   return;
                }

                if(!isOrderPaid){

                    ordersChainData[orderId] = {
                        'chain_id' : chainId,
                        'percent' : percent,
                        'amount' : amount,
                    };
                }

            });

            return ordersChainData;
        },

        getOrdersChainDataForIncome: function () {
            var self = this;
            var paymentType = $('.' + self.options.formClass).data(self.options.paymentTypeDataAttr);
            var fullPayment = ( paymentType ==  'payment') ? true : false;
            var ordersChainData = {};
            var globalChainId = 0;

            $.each( $('.' + self.options.orderItemClass), function (index, data) {

                var order = $(this);
                var orderId = order.text();
                var chainId = order.data(self.options.orderChainIdDataAttr) ? order.data(self.options.orderChainIdDataAttr) : globalChainId;
                if(chainId > 0){
                    globalChainId = chainId;
                }
                var percent = null;
                var amount = $('.' + self.options.orderItemAmountClass + '[data-id = ' + orderId + ']').val();

                if(! parseFloat(amount) > 0){
                    return;
                }

                ordersChainData[orderId] = {
                    'chain_id' : chainId,
                    'percent' : percent,
                    'amount' : amount,
                };


            });

            return ordersChainData;

        },

        getOrdersChainTotalSum : function () {

            var self = this;
            var totalSumHolder = $('input[name = "' + self.options.totalSumHolderNameAttr + '"]');

            if(!totalSumHolder.length > 0){
                self.addError('Не определено место хранения общей суммы.');
                return false;
            }

            var totalSum = totalSumHolder.val();

            if(!totalSum){
                self.addError('Не определена общая сумма.');
                return false;
            }

            return totalSum;

        },

        getOrdersChain : function () {

            var self = this;
            var ordersChain = [];

            $.each( $('.' + self.options.orderItemClass), function (index, data) {

                var order = $(this);
                if(order.text()){

                    ordersChain.push(order.text());
                }

            });

            return ordersChain;
        },

        getOrdersChainPaid : function () {

            var self = this;
            var ordersChainPaid = [];

            $.each( $('.' + self.options.orderItemClass), function (index, data) {

                var order = $(this);
                if(order.text() && order.parents('tr').hasClass(self.options.orderItemPaid_parentClass)){

                    ordersChainPaid.push(order.text());
                }

            });

            return ordersChainPaid;
        },

        getOrdersChainActive : function () {

            var self = this;
            var ordersChain = self.getOrdersChain();
            var ordersChainPaid = self.getOrdersChainPaid();
            var ordersChainPercentData = self.getOrdersChainPercentData();

            var notPaid = arr_diff(ordersChainPaid, ordersChain);
            if(!notPaid || !notPaid.length){
                self.addError('Возможно, все заказы оплачены?');
                return false;
            }
            var active = [];

            for( var orderId of notPaid){

                if(ordersChainPercentData[orderId]){
                    active.push(orderId);
                }
            }

            if( ! active.length > 0){
                self.addError('Не определены активные заказы.');
                return false;
            }

            return active;
        },

        getCleanRegistrationGroupData: function () {

            var self = this;
            var form = self.getForm();
            var groupData = form.data(self.options.orderGroupDataAttr);

            if( !groupData){
                self.addError('Не определен параметр для запроса groupData.');
                return false;
            }

            return groupData;
        },

        getGroupDataMap: function (receiverType ) {

            var self = this;
            var form = self.getForm();
            var groupData = self.getCleanRegistrationGroupData();
            Object.seal(groupData);

            if(!groupData){
                self.addError('Не хватает параметра groupData');
                return false;
            }

            if(!receiverType){
                self.addError('Не хватает параметра receiverType');
                return false;
            }

            var paymentType = form.data(self.options.paymentTypeDataAttr);
            if(!paymentType){
                self.addError('Не хватает параметра paymentType');
                return false;
            }
            var group_amount = self.getGroupAmountByType(receiverType, paymentType);
            if(!group_amount){
                self.addError('Не хватает параметра group_amount');
                return false;
            }

            groupData.payment_type = paymentType;
            groupData.receiver_type = receiverType;
            groupData.group_amount = group_amount;

            return groupData;
        },

        getGroupAmountByType: function (receiverType, paymentType) {
            var self = this;

            if(!receiverType || !paymentType){
                self.addError('Нет параметров для определения суммы счета.');
                return false;
            }

            var className = self.options.groupAmountHolder[paymentType][receiverType];

            if(! className){
                self.addError('Невозможно определить необходимую сумму.');
                console.error('Невозможно определить имя класса для input, в котором храниться group_amount');
                return false;
            }

            var group_amount = $('.' + className).val();

            if(!parseInt(group_amount)){
                self.addError('Невозможно определить необходимую сумму.');
                return false;
            }

            return group_amount;

        },

        // Добавляем active_orders параметр для выполнения запроса
        addActiveOrdersToData: function (data) {

            var self = this;
            if(!data){
                self.addError(self.options.emptyDataParamErrorText);
                return false;
            }

            var activeOrders = self.getOrdersChainActive();
            if(!activeOrders){
                self.addError('Не определены активные заказы.');
                return false;
            }
            data = data + '&active_orders=' + JSON.stringify(activeOrders);

            return data;

        },

        addOrdersPercentData: function (data) {
            var self = this;
            if(!data){
                self.addError(self.options.emptyDataParamErrorText);
                return false;
            }

            var ordersChainPercentData = self.getOrdersChainPercentData();
            if(!ordersChainPercentData){
                self.addError('Не определены данные для предоплаты.');
                return false;
            }
            data = data + '&ordersData=' + JSON.stringify(ordersChainPercentData);

            return data;
        },

        addTotalSumData: function (data) {
            var self = this;
            if(!data){
                self.addError(self.options.emptyDataParamErrorText);
                return false;
            }

            var totalSum = self.getOrdersChainTotalSum();
            if(!totalSum){
                self.addError(self.options.emptyDataParamErrorText);
                return data;
            }
            data = data + '&total_sum=' + totalSum;

            return data;

        },

        addGroupData: function (data, receiverType) {
            var self = this;

            if(!data){
                self.addError(self.options.emptyDataParamErrorText);
                return false;
            }

            if(!receiverType){
                self.addError('Не хватает параметра receiverType');
                return false;
            }

            var groupData = self.getGroupDataMap(receiverType);

            if(!groupData){
                self.addError('Не хватает параметра groupData');
                return false;
            }

            data = data + '&groupData=' + JSON.stringify(groupData);

            return data;

        },

    };

    orderPaymentsHelper.init.prototype = orderPaymentsHelper.prototype;

    global.orderPaymentsHelper = global.OPH$ = orderPaymentsHelper;

})(window, jQuery);
