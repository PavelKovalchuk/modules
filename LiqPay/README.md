**LiqPay invoice module**

**ОСНОВЫ**

1. Установка публичного и приватного ключа от API - class LiqPaySettings. Получить к ним доступ можно,
если вызвать метод getSettings() у обьекта, который использует trait LiqPayTrait.

2. Точка входа на отправку инвойса осуществляется через _**admin/liq_pay_action.php**_ .
3. Точка входа на принятия callback запроса от LiqPay об оплате инвойса **_liqpay_callback.php_** .
4. Запрос на отправку инвойса через **_admin/includes/javascript/orders_functions.js_**, **sendLiqPayInvoice()** (onclick="sendLiqPayInvoice()"). Важный атрибут data-invoice-type="prepaid", где устанавливается тип инвойса магазина.
5. Кнопки выводятся в **_admin/includes/classes/payments/PaymentsModal.php_** .  getPayingCounting($orderId) и getPrepaymentCounting($orderId)
6. class LiqPaySDK - поставляется от LiqPay. Его, желательно не менять.
7. Шаблоны отправляемых писем - templates/vamshop_new/mail/russian/letter_liqpay.html и templates/vamshop_new/mail/ukrainian/letter_liqpay.html.
8. Отправка писем **_inc/SendCustomMail.php_**  - sendLiqPayInvoiceLetter($data).

**_ОТЛАДКА И ТЕСТИРОВАНИЕ_**

1. В таблице инвойсов (liqpay_invoices) выставить нужный инкремент по полю generated_order_id.

2. Для тестирования без посылания реального запроса можно использовать class FakeResult ( inc/LiqPay/models/fake/FakeResult.php ).
Для этого в sendInvoicePaymentAction (LiqPayController) активировать ->setIsTestMode(true), 
в методе request (AcquireAbstract) проверяется isTestMode() и там можно у $fake_result_creator вызвать нужный метод с результатами.

3. Во избежания послания одинаковых номеров generated_order_id с живого и тестового сервера есть специальные проверки.
в sendInvoiceRequest (class LiqPayInvoiceModel) есть проверка $this->isLiveServer(). Если false к generated_order_id добавляется '-test'.
То же самое и для отмены инвойса в cancelInvoiceRequest(class LiqPayInvoiceModel).
То же самое и для отмены инвойса в liqpay_callback.php для запрета оплаты тестовых инвойсов. 
Для тестирования  - раскоментировать $_POST['data'] и $_POST['signature']. Подставить свои значения. Тестовые значения можно взять тут backup/LiqPay_callback_results.txt .

4. Все возможные значения статусов определены в соответствующих классах. Ищите!