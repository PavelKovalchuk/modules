
(function () {

    var options = {
        delButton: '.js_delete_products',
        recoverButton: '.js_recover_products',
        updateOrderButton: '.js_update_order',
        group: '.js-separated-group-block',
        htmlContent: '.js-side-pushed-content',
        messageClass: 'separated-form-message',
        orderId: null,
        isAllDeleteAware: false,
        groupBlockInput: '.js-separated-group-block-input',

        //URL Endpoints
        baseUrl: 'separator_order_endpoints.php',
        separateUrl: 'separation_order_ajax.php',

        //Actions (names of methods)
        deleteAction: 'deleteAction',
        separateAction: 'separate_one_by_availability',
        notifyAction: 'notifyAction',
        logAction: 'logAction',

        //Send email data
        sendNotifyCompleted: false,

        //Loggong data
        logCompleted: false,

        //FROM CDATA
        proposalDataStorage: false,

        proposalDataStorageLength: 0,

        //ID of storehouses, which should stay in main order if main order may be empty
        storeHousesStrength: [1, 2],

        dataToDelete: {},
        dataToSeparate: {},
        newSeparatedOrders: [],

        //Result message
        finalMessageResult: '',
        endMessage: '<br><em> Для обновления данных на странице  - обновите окно страницы.</em>',
        finishBtnText: 'Обновить страницу',

        //CSS
        classCssBlockDelete: 'separated-group-block-deleted',
        classCssBlockSeparate: 'separated-group-block-separated',
        classCssBlockUneditable: 'uneditable-block'

    };

    var init = function () {

        if(typeof PROPOSAL_DATA == 'undefined'){
            return false;
        }

        //Глобальный флаг необходимости разделения заказа
        window.is_order_need_to_be_separated = true;

        var content = $(options.htmlContent);
        options.orderId = content.data("order-id");

        options.proposalDataStorage = PROPOSAL_DATA;
        options.proposalDataStorageLength =  Object.keys( options.proposalDataStorage).length;

        openSideContentInit();
        closeSideContentInit();

        deleteButtonsInit();
        recoverButtonInit();
        updateOrderInit();
        checkboxInputInit();

    };

    var deleteButtonsInit = function(){

        var btnsDel = $(options.delButton);

        $.each(btnsDel, function (key, value) {

            var btn = $(this);
            var groupId = btn.data("group-id");
            var block = $(btn).closest(options.group);
            var recoverBtn =  block.find(options.recoverButton);
            var checkbox = block.find(options.groupBlockInput);

            btn.on('click', function (event) {
                event.preventDefault();

                var groupData = options.proposalDataStorage[groupId];

                //Добавляем в обьект удаляемых элементов, если такого элемента еще нет
                __addToObject(groupData, options.dataToDelete);

                btn.attr( "disabled", "disabled" );

                checkbox.prop('checked', false).attr( "disabled", "disabled" );

                var message = '<div class="' + options.messageClass + '">' + 'Указанный набор товаров будет удален из заказа' + '</div>';
                block.append(message).removeClass(options.classCssBlockSeparate).addClass(options.classCssBlockDelete);

                recoverBtn.removeClass('d-none');

            });

        });

    };

    var checkboxInputInit = function(){

        var checkboxes = $(options.groupBlockInput);

        $.each(checkboxes, function (key, value) {

            var check = $(this);
            var block = $(check).closest(options.group);

            check.on('change', function() {

                if(block.hasClass(options.classCssBlockSeparate)){

                    if((check).is(':checked')) {
                        block.removeClass(options.classCssBlockDelete);
                    } else {
                        block.removeClass(options.classCssBlockSeparate);
                    }
                }else{
                    block.addClass(options.classCssBlockSeparate);
                }

            });

        });

    };

    var recoverButtonInit = function () {

        var btnsRec = $(options.recoverButton);

        $.each(btnsRec, function (key, value) {

            var btn = $(this);
            var groupId = btn.data("group-id");
            var block = $(btn).closest(options.group);
            var delBtn =  block.find(options.delButton);
            var checkbox = block.find(options.groupBlockInput);

            btn.on('click', function (event) {

                event.preventDefault();

                //флаг подтверждения удаления всех товаров. Снимаем
                options.isAllDeleteAware = false;

                var groupData = options.proposalDataStorage[groupId];

                //Удаляем элементы, из списка для удаления и обновления
                __removeFromObject(groupData, options.dataToDelete);

                delBtn.prop("disabled", false);

                if( block.hasClass(options.classCssBlockUneditable)){
                    checkbox.prop('checked', true).prop("disabled", "disabled");
                }else{
                    checkbox.prop('checked', true).prop("disabled", false);
                }

                block.find('.' + options.messageClass).remove();
                block.removeClass( options.classCssBlockDelete).addClass(options.classCssBlockSeparate);
                btn.addClass('d-none');

            });
        });

    };

    var __logResult = function(message){

        if(! options.finalMessageResult.length > 0 && typeof message == undefined ){

            options.logCompleted = false;
            return false;
        }

        //Check if we should use passed variable
        if (message != undefined) {
            // argument passed and not undefined
            var dataToLog = message;
        } else {
            // argument not passed or undefined
            var dataToLog = options.finalMessageResult;
        }

        var data = { 'action': options.logAction, 'orderId': parseInt(options.orderId), 'dataToLog': dataToLog };

        $.ajax({
            type: "POST",
            url: options.baseUrl ,
            data: data,
            success: function (response) {

                if(!response){
                    return;
                }

                response = response.trim();

                if(! __isJsonString(response) ){
                    getModalForm('error', 'Логгирование результатов - что-то пошло не так во время получения результатов об этой операции. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                data = JSON.parse(response);

                if(!data){

                    options.logCompleted =  false;
                    getModalForm('error', 'Логгирование результатов - пришедшие данные не соответсвуют формату. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                if(data.result == 'success'){

                    options.logCompleted =  true;

                    var logResultString = '<br> Произведено логгирование результатов в Комментарии заказа.';

                    options.finalMessageResult += logResultString;

                    return;
                }

                if(data.result == 'error'){
                    getModalForm('error', options.finalMessageResult + data.message, options.finishBtnText, window.location.href );
                }

            }
        });

    };

    var __sendNotification = function(){

        if(! options.newSeparatedOrders.length > 0){
            options.sendNotifyCompleted = false;
            return false;
        }

        var dataToNotify = __createRequest({'separatedOrdersId': options.newSeparatedOrders});

        var data = {'action': options.notifyAction, 'orderId': parseInt(options.orderId), 'dataToNotify': dataToNotify};

        $.ajax({
            type: "POST",
            url: options.baseUrl ,
            data: data,
            success: function (response) {

                if(!response){
                    return;
                }

                response = response.trim();

                if(! __isJsonString(response) ){
                    getModalForm('error', 'Отправка уведомления - что-то пошло не так во время получения результатов об этой операции. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                data = JSON.parse(response);

                if(!data){

                    options.sendNotifyCompleted =  false;
                    getModalForm('error', 'Отправка уведомления - пришедшие данные не соответсвуют формату. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                if(data.result == 'success'){

                    options.sendNotifyCompleted =  true;

                    //var emailResultString = '<br> Отправлено письмо о разделении заказа.';
                    var emailResultString = '<br>' + data.message;

                    options.finalMessageResult += emailResultString;

                    //Сматываем удочки и показываем сообщение о результатах работ
                    //getModalForm('success', options.finalMessageResult + options.endMessage);
                    getModalForm('success', options.finalMessageResult + options.endMessage, options.finishBtnText, window.location.href );

                }

                if(data.result == 'error'){
                    getModalForm('error', options.finalMessageResult + data.message, options.finishBtnText, window.location.href );
                }

            }
        });

    };


    var __addToObject = function (groupData, savedArr) {

        if ( ! savedArr[groupData.groupId]) {
            savedArr[groupData.groupId] = groupData;
        }

    };

    var __removeFromObject = function (groupData, savedArr) {

        if ( savedArr[groupData.groupId]) {
            delete savedArr[groupData.groupId];
            return true;
        }

        return false;

    };

    var __isAllDelete = function(){

        if(JSON.stringify(options.proposalDataStorage) === JSON.stringify(options.dataToDelete)){
            return true;
        }

        return false;

    };

    var __getGroupDataToStay = function(storeHousesId){

        var result = false;

        $.each(options.dataToSeparate, function (key, groupData) {

            $.each(groupData.data, function (index, data) {

                if(data.storehouse_id == storeHousesId){

                    result =  options.dataToSeparate[key];
                    return false;
                }

            });

            if(result){
                return false;
            }


        });

        return result;

    };

    var __preventEmptyMainOrder = function(){

        var numberToDelete =  Object.keys( options.dataToDelete).length;
        var numberToSeparate =  Object.keys( options.dataToSeparate).length;

        //Если все группы будут разделены\удалены, то основной заказ может быть пустым.
        //Предотвратим же это!
        if(options.proposalDataStorageLength !== (numberToDelete + numberToSeparate)){
            return false;
        }

        var groupToStay = false;

        $.each(options.storeHousesStrength, function (key, storeHousesId) {

            groupToStay = __getGroupDataToStay(storeHousesId);
            if(groupToStay){
                return false;
            }
        });

        if(groupToStay){

            __removeFromObject(groupToStay, options.dataToSeparate);

        }else{

            var randomIndex = pickRandomProperty(options.dataToSeparate);
            __removeFromObject(options.dataToSeparate[randomIndex], options.dataToSeparate);

        }

        return true;

    };

    var __createRequest = function (dataObject) {

        if( $.isEmptyObject(dataObject) ){
            var request = false;
        }else{
            var request = JSON.stringify(dataObject);
        }

        return request;

    };

    var updateOrderInit = function () {

        var btn = $(options.updateOrderButton);

        btn.on('click', function (event) {

            event.preventDefault();

            btn.attr( "disabled", "disabled" );
            btn.text('Идет обработка запроса');

            __getGroupsToUpdate();

           //Check if all items will be deleted
           if( __isAllDelete() && !options.isAllDeleteAware){

               getModalForm('error', 'С сожалением сообщаю, что Вы намереваетесь удалить все позиции по заказу. Но Вы еще можете отменить удаление выбранных групп', 'Ознакомлен');

               options.isAllDeleteAware = true;

           }else if( __isAllDelete() && options.isAllDeleteAware){
               __doOperations();

           }else{
               __doOperations();

           }

        });

    };

    //Validate correct JSON string
    var __isJsonString = function(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    };

    var __getGroupsToUpdate = function () {

        var checkedGroupsToSeparate = $(options.groupBlockInput + ':checked');

        options.dataToSeparate = {};

        $.each(checkedGroupsToSeparate, function (key, value) {

            var groupId = $(this).data("group-id");
            var groupData = options.proposalDataStorage[groupId];

            if (!options.dataToDelete[groupId]) {
                //Добавляем в обьект отделяеміх элементов, если такого элемента еще нет
                // Используется рекурсивно
                __addToObject(groupData, options.dataToSeparate);

            }

        });


    };

    var __doOperations = function () {

        var dataToDelete = __createRequest(options.dataToDelete);
        var data = {'action': options.deleteAction, 'orderId': options.orderId, 'dataToDelete': dataToDelete};

        if($.isEmptyObject(options.dataToSeparate) == false){
            __preventEmptyMainOrder();
        }

        //Start from Deleting

        $.ajax({
            type: "POST",
            url: options.baseUrl,
            data: data,
            beforeSend : function (){
                doPreloader(true);
            },
            success: function (response) {

                if(!response){
                    return;
                }

                response = response.trim();

                if(! __isJsonString(response) ){
                    getModalForm('error', 'Удаление позиций - что-то пошло не так во время получения результатов об этой операции. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                data = JSON.parse(response);

                if(!data){
                    getModalForm('error', 'Удаление позиций - пришедшие данные не соответсвуют формату. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                if(data.result == 'success'){

                    options.finalMessageResult = data.message;

                    // В случае успешного удаления данных отсылаем запрос на разделение продуктов
                    if( $.isEmptyObject(options.dataToSeparate) == true){
                        // Nothing to separate

                        //getModalForm('success', options.finalMessageResult + options.endMessage);
                        getModalForm('success', options.finalMessageResult + options.endMessage, options.finishBtnText, window.location.href );
                        return;

                    }

                    //Начинаем разделение заказа по размеченным группам

                    //Первая группа товаров
                    var firstGroupData = __getOneGroupDataFromDataToSeparate();

                    //Рекурсивно вызываемая функция - отсылает первую группу на разделение,
                    // удаляет ее из массива options.dataToSeparate, вызывает саму себя для отсылки следующей группы
                    //Потом вызывает функцию на отправку email, которая вызывает функцию на логгирование
                    __sendProductsToSeparate(firstGroupData);

                }

                if(data.result == 'error'){
                    getModalForm('error', options.finalMessageResult + data.message, options.finishBtnText, window.location.href );
                }

            },
            complete : function (){

                doPreloader(false);
            }
        });

    };

    var __separateProductsByGroup = function ( resultDeleteMessage ) {

        $.each(options.dataToSeparate, function (groupId, groupData) {

            var groupsProductsId = [];

            $.each(groupData.data, function (key, data) {

                groupsProductsId.push(data.products_id);

            });

        });

    };

    var __getProductsByGroupId = function ( groupId ) {

        var groupsProductsId = [];
        var groupId = groupId ? groupId : false;

        if(!groupId){
            return false;
        }

        if(!options.dataToSeparate[groupId]){
            return false;
        }

        $.each(options.dataToSeparate[groupId].data, function (key, groupData) {

            groupsProductsId.push(groupData.products_id);

        });

        return groupsProductsId;

    };

    var __getOneGroupDataFromDataToSeparate = function () {

        for (var first in options.dataToSeparate){
            break;
        }

        if(options.dataToSeparate[first]){
            return options.dataToSeparate[first];
        }

        return false;

    };

    var __sendProductsToSeparate = function ( groupData ) {

        if(!groupData){
            return false;
        }

        var groupProductsIds = __getProductsByGroupId(groupData.groupId);

        var data = {'action': options.separateAction, 'oid': parseInt(options.orderId), 'availability_products': groupProductsIds};

        $.ajax({
            type: "POST",
            //Maybe should be used URL with action
            url: options.separateUrl ,
            data: data,
            success: function (response) {

                if(!response){
                    return;
                }

                response = response.trim();

                if(! __isJsonString(response) ){
                    getModalForm('error', 'Разделении заказа - что-то пошло не так во время получения результатов об этой операции. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                data = JSON.parse(response);

                if(!data || !data.separated_id){
                    getModalForm('error', 'Разделении заказа - пришедшие данные не соответсвуют формату. Проверьте результаты ожидаемых действий.');
                    return false;
                }

                //Сохраняем id новых элементов
                options.newSeparatedOrders.push(data.separated_id);

                __removeFromObject(groupData, options.dataToSeparate);

                var nextGroupData = __getOneGroupDataFromDataToSeparate();

                if(nextGroupData){
                    __sendProductsToSeparate(nextGroupData);
                }else{
                    // Закончились группы для разделения
                    var newOrdersString = '<br> От текущего заказа отделены заказы: ' + options.newSeparatedOrders.join(', ') + '.';

                    __logResult('Разделение - ' + newOrdersString);

                    options.finalMessageResult += newOrdersString;
                    //Отправляем письмо покупателю - ajax request
                    __sendNotification();

                }

            }
        });

    };

    init();


})();
