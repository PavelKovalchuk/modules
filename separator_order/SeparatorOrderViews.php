<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 09.01.2018
 * Time: 12:49
 */

class SeparatorOrderViews
{
    protected $block_form_header = 'Разделение заказа №';

    protected $block_form_text = 'Доступные варианты';

    protected $block_form_notices = 'ESC - для зыкрытия';

    protected $group_counter = 1;

    protected $cdata = [];

    public function getAnalyzedInfoBlock($data)
    {
        if($data['result'] == 'ok' || $data['result'] == 'skip'){
            return;
        }

        ?>
        <div class="admin-bg-primary">
            <h2 class="admin-header center"><?php echo 'Система деления заказов (СДЗ)'; ?></h2>
        </div>

        <div class="admin-ribbon-wrapper">

            <!-- Ribbon Start -->
            <div class="admin-ribbon-header <?php echo $data['css_class']; ?>">
                <span class="inner"><?php echo $data['title']; ?></span>
            </div>
            <!-- Ribbon End -->

            <div class="admin-ribbon-content <?php echo $data['css_class']; ?>">
                <?php echo $data['text']; ?>
            </div>

        </div>

        <?php if($data['result'] == 'need_to_separate'){ ?>

        <div class="center">

            <button class="head-btn admin-btn admin-btn-success js-btn-push-content"
                    id="get-separated-form"
                    type="button">
                <?php echo $data['btn_text']; ?>
            </button>

        </div>



        <?php } ?>

        <?php
    }

    public function getFormBlock($order_id, $filtered_data, $categories_data, $dropshipping_data, $time_delivery_data)
    {
        ?>

        <div class="js-side-pushed-content js-side-closed admin-side-pushed-content" data-order-id='<?php echo $order_id; ?>'>

            <div class="admin-side-pushed-content-inner">

                <div><span class="js-btn-close-content admin-loader-close">×</span></div>

                <div><span class="side-pushed-content-notice admin-bg-blue admin-white-color"><?php echo $this->getBlockFormNotices(); ?></span></div>

                <h2 class="center"><?php echo $this->getBlockFormHeader() . $order_id ?></h2>

                <p><?php echo $this->getBlockFormText(); ?></p>

                <div class="admin-separated-form-outer">

                    <form action="#">

                        <?php

                        if( count($filtered_data['uneditable']['categories']) > 0){

                            foreach ($filtered_data['uneditable']['categories'] as $category_id => $products){

                                $this->printGroupBlock($categories_data[$category_id]['categories_name'], $products, true,  true,'uneditable-block uneditable-block-category');

                            }
                        }

                        if( count($filtered_data['uneditable']['dropshipping']) > 0){

                            foreach ($filtered_data['uneditable']['dropshipping'] as $storehouse_id => $products){

                                $this->printGroupBlock('Склад отгрузки:  ' . $dropshipping_data[$storehouse_id], $products, true,  true, 'uneditable-block uneditable-block-dropshipping');

                            }
                        }

                        if( count($filtered_data['uneditable']['time_delivery']) > 0){

                            foreach ($filtered_data['uneditable']['time_delivery'] as $delivery_time_code => $products){

                                $this->printGroupBlock('Срок отправки: ' . $time_delivery_data[$delivery_time_code], $products, true,  true, 'uneditable-block uneditable-block-time-delivery');

                            }
                        }

                        if( count($filtered_data['editable']['other']) > 0){

                            //CHECK FOR ERRORS
                            echo 'ERORR IN FILTERING!!!!!!!!!';

                            foreach ($filtered_data['editable']['other'] as $cat_id => $products){

                                $this->printGroupBlock($cat_id, $products, false,  false, 'uneditable-block uneditable-block-other');

                            }
                        }

                        ?>
                        <button class="head-btn admin-btn admin-btn-success js_update_order">Выполнить отмеченные операции</button>

                        <?php $this->getCdataBlock( $this->getCdata() ); ?>

                    </form>

                </div>

            </div>


        </div>

        <div class="js-fixed-cover "></div>

        <?php
    }

    protected function printInput($name, $title, $is_checked = false, $is_disabled = false)
    {
        ?><input type="checkbox" class="separated-group-checkbox js-separated-group-block-input"
                 name="<?php echo $name; ?>"  <?php if($is_checked){ echo ' checked '; } ?> <?php if($is_disabled){ echo ' disabled '; } ?>
                 data-group-id = '<?php echo $this->getGroupCounter(); ?>'
                 title = '<?php if($is_disabled){ echo ' Эти позиции не могут быть быть в этом заказе. Можно или удалить или разделить '; } else{ echo ' Разделить эти позиции ?'; } ?>' >
        <span><?php echo $title; ?></span><br><?php
    }

    protected function printGroupHeader($name, $css_class = '')
    {
        ?><h4 class="admin-header admin-font-fat <?php echo $css_class; ?>">
        <span class="separated-group-counter"><?php $this->printGroupCounter(); ?></span>
        <?php echo $name; ?>
        </h4><?php
    }


    protected function printGroupBlock($group_name, $products, $is_checked, $is_disabled, $group_block_css_class = '')
    {
        ?>
        <div class="js-separated-group-block js-separated-group-block-<?php echo $this->getGroupCounter(); ?> separated-group-block admin-card-shadow separated-group-block-separated <?php echo $group_block_css_class; ?>">

            <?php $this->printGroupHeader($group_name); ?>

            <div class="separated-group-block-inner d-flex">

                <ol class="separated-products-list d-flex">
                    <?php

                    foreach ($products as $product){

                        $cdata[] = array(
                            'orders_products_id' => $product['orders_products_id'],
                            'products_id' => $product['products_id'],
                            'storehouse_id' => $product['storehouse_id'],
                            'products_name' => $product['products_name']
                        );

                        ?><li>

                        <span><strong><?php echo $product['products_name']; ?></strong></span> /

                        <?php if($product['parent_category']){ ?>
                            <span><?php echo $product['parent_category'] . ' - ' ; ?></span>
                        <?php } ?>

                        <span><?php echo $product['categories_name']; ?></span> /
                        <span><?php echo $product['quantity']; ?><small> (уп)</small></span>
                        </li>

                    <?php }

                    $this->addCdata($this->getGroupCounter(),$cdata); ?>

                </ol>

                <div class="separated-group-controls d-flex d-flex-horizontal-left">

                    <div class="separated-group-input d-flex d-flex-vertical-center">
                        <?php $this->printInput( 'group-to-separate', 'Отделить от заказа', $is_checked, $is_disabled); ?>
                    </div>

                    <div class="separated-group-btn d-flex">
                        <button class="head-btn admin-btn admin-btn-danger js_delete_products" type="button"
                                data-group-id = '<?php echo $this->getGroupCounter(); ?>'
                        >
                            Удалить
                        </button>

                        <button class="head-btn admin-btn admin-btn-success d-none js_recover_products"
                                data-group-id = '<?php echo $this->getGroupCounter(); ?>'>
                            Отменить удаление
                        </button>
                    </div>
                </div>

            </div>

        </div>

        <?php

        $this->increaseGroupCounter();

    }

    public function getCdataBlock($arr)
    {
        if(! is_array($arr)){
            return false;
        }

        ?>
        <script type="text/javascript">
       /* <![CDATA[ */
        var PROPOSAL_DATA = <?php echo json_encode( $arr ); ?>
       /* ]]> */
        </script>
        <?php
    }

    /**
     * @return array
     */
    public function getCdata()
    {
        return $this->cdata;
    }

    /**
     * @param array $cdata
     */
    public function addCdata($group_id, $proposal_group)
    {
        if(! is_array($proposal_group)){
            return false;
        }

        $this->cdata[$group_id] = array(
                'groupId' => $group_id,
                'data' => $proposal_group
        );

        return true;
    }

    /**
     * @return string
     */
    public function getBlockFormHeader()
    {
        return $this->block_form_header;
    }

    /**
     * @param string $block_form_header
     */
    protected function setBlockFormHeader($block_form_header)
    {
        $this->block_form_header = $block_form_header;
    }

    /**
     * @return string
     */
    public function getBlockFormText()
    {
        return $this->block_form_text;
    }

    /**
     * @param string $block_form_text
     */
    protected function setBlockFormText($block_form_text)
    {
        $this->block_form_text = $block_form_text;
    }

    /**
     * @return int
     */
    protected function getGroupCounter()
    {
        return $this->group_counter;
    }

    protected function increaseGroupCounter()
    {
        $this->group_counter += 1;
    }


    protected function printGroupCounter()
    {
        echo $this->getGroupCounter();

    }

    /**
     * @return string
     */
    protected function getBlockFormNotices()
    {
        return $this->block_form_notices;
    }

}