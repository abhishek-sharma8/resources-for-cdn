<?php
/**
 * Replaces query version in registered scripts or styles with file modified time
 *
 * @param $src
 *
 * @return string
 */
function add_modified_time( $src ) {

    $clean_src = remove_query_arg( 'ver', $src );
    $path      = wp_parse_url( $src, PHP_URL_PATH );

    if ( $modified_time = @filemtime( untrailingslashit( ABSPATH ) . $path ) ) {
        $src = add_query_arg( 'ver', $modified_time, $clean_src );
    } else {
        $src = add_query_arg( 'ver', time(), $clean_src );
    }

    return $src;

}

add_filter( 'style_loader_src', 'add_modified_time', 99999999, 1 );
add_filter( 'script_loader_src', 'add_modified_time', 99999999, 1 );

add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles');

function storefront_child_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    // Enqueue required styles
    wp_deregister_script('jquery');
    wp_enqueue_script('jquery', get_stylesheet_directory_uri() . '/assets/bootstrap-4/js/jquery.min.js', array(), true);

    wp_enqueue_style('bootstrapcss', get_stylesheet_directory_uri() . '/assets/bootstrap-4/css/bootstrap.min.css', null, true);
    wp_enqueue_script('bootstrapjs', get_stylesheet_directory_uri() . '/assets/bootstrap-4/js/bootstrap.min.js', array('jquery'), null, true);
    //magnific
    wp_enqueue_style('magnific-popup', get_stylesheet_directory_uri() . '/assets/magnific/magnific-popup.css');
    wp_enqueue_script('jquery.magnific-popup.min.js', get_stylesheet_directory_uri() . '/assets/magnific/jquery.magnific-popup.min.js', array('jquery'), '20151215', true);

    wp_enqueue_style('custom-style', get_stylesheet_directory_uri() . '/assets/css/style.css');

    wp_enqueue_script(
        'custom-js', get_stylesheet_directory_uri() . '/js/custom.js', array('jquery')
);

}

//Custom code
add_action('wp_enqueue_scripts', 'single_product_page_script');

function single_product_page_script() {
    global $post;
    if (is_product() && $post->ID == 14) {
        
        $id = $post->ID;
        wp_enqueue_script(
                'single-product-page-js', get_stylesheet_directory_uri() . '/js/single-product.js', array('jquery')
        );
        wp_localize_script('single-product-page-js', 'php__vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'product_id' => $id
        ));
    } else{
        wp_enqueue_style('normal-prod', get_stylesheet_directory_uri() . '/assets/css/normal-prod.css');
    }
    if (is_checkout()) {

        wp_enqueue_script(
                'pmnm-checkout-js', get_stylesheet_directory_uri() . '/js/pmnm-checkout.js', array('jquery')
        );
        wp_localize_script('pmnm-checkout-js', 'php__vars', array(
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }
}

function get_price_by_attributes($productId, $week, $size) {
    $vars = array();
    $vars['attribute_dispatch-week'] = $week;
    $vars['attribute_size'] = $size;
    $vars['product_id'] = $productId;

    $variable_product = wc_get_product(absint($productId));
    $data_store = WC_Data_Store::load('product');
    $variation_id = $data_store->find_matching_product_variation($variable_product, wp_unslash($vars));
    $variation = $variation_id ? $variable_product->get_available_variation($variation_id) : false;
    if (isset($variation['price_html']) && !empty($variation['price_html'])) {
        return $variation['price_html'];
    } else {
        return "Not available";
    }
}

function get_variation_prices() {
    $prices = array();
    if (isset($_POST['product_id'])) {

        $product = wc_get_product($_POST['product_id']);
        $attributes = $product->get_attributes();
        $week = array();
        $week = explode('|', $attributes['dispatch-week']['value']);
        $size = array();
        $size = explode('|', $attributes['size']['value']);


        foreach ($week as $we) {
            foreach ($size as $si) {
                $prices[trim($we) . '-' . trim($si)] = get_price_by_attributes($_POST['product_id'], trim($we), trim($si));
            }
        }
    }
    echo json_encode($prices);
    die();
}

add_action('wp_ajax_get_variation_prices', 'get_variation_prices');
add_action('wp_ajax_nopriv_get_variation_prices', 'get_variation_prices');

/**
 * Include Bootstrap Nav Walker
 */
require_once 'wp-bootstrap-navwalker.php';

//Google fonts
add_action('wp_enqueue_scripts', 'my_google_fonts');

function my_google_fonts() {
    wp_enqueue_style('my-google-fonts', 'https://fonts.googleapis.com/css?family=Baloo|Open+Sans:400,600&display=swap', false);
}

//Nishit Manjarawala 
//Change Qty box
//Blog : https://businessbloomer.com/woocommerce-change-add-cart-quantity-drop/
function woocommerce_quantity_input($args = array(), $product = null, $echo = true) {
    if (is_product() && has_term( 'mangos', 'product_cat' )) {
        if (is_null($product)) {
            $product = $GLOBALS['product'];
        }

        $defaults = array(
            'input_id' => uniqid('quantity_'),
            'input_name' => 'quantity',
            'input_value' => '1',
            'classes' => apply_filters('woocommerce_quantity_input_classes', array('input-text', 'qty', 'text'), $product),
            'max_value' => apply_filters('woocommerce_quantity_input_max', -1, $product),
            'min_value' => apply_filters('woocommerce_quantity_input_min', 0, $product),
            'step' => apply_filters('woocommerce_quantity_input_step', 1, $product),
            'pattern' => apply_filters('woocommerce_quantity_input_pattern', has_filter('woocommerce_stock_amount', 'intval') ? '[0-9]*' : ''),
            'inputmode' => apply_filters('woocommerce_quantity_input_inputmode', has_filter('woocommerce_stock_amount', 'intval') ? 'numeric' : ''),
            'product_name' => $product ? $product->get_title() : '',
        );

        $args = apply_filters('woocommerce_quantity_input_args', wp_parse_args($args, $defaults), $product);

        // Apply sanity to min/max args - min cannot be lower than 0.
        $args['min_value'] = max($args['min_value'], 0);
        // Note: change 20 to whatever you like
        $args['max_value'] = 0 < $args['max_value'] ? $args['max_value'] : 10;

        // Max cannot be lower than min if defined.
        if ('' !== $args['max_value'] && $args['max_value'] < $args['min_value']) {
            $args['max_value'] = $args['min_value'];
        }

        $options = '';

        for ($count = $args['min_value']; $count <= $args['max_value']; $count = $count + $args['step']) {

            // Cart item quantity defined?
            if ('' !== $args['input_value'] && $args['input_value'] >= 1 && $count == $args['input_value']) {
                $selected = 'checked';
            } else
                $selected = '';

            //$options .= '<option value="' . $count . '"' . $selected . '>' . $count . '</option>';
            $options .= '<li><input type="radio" class="pmnm-qty" name="' . $args['input_name'] . '" value="' . $count . '" ' . $selected . ' id="qty_' . $count . '"/> <label for="qty_' . $count . '"> ' . $count . ' <span class="active"></span></label></li>';
        }

        //$string = '<div class="quantity"><span>Qty</span><select name="' . $args['input_name'] . '">' . $options . '</select></div>';
        $string = '<div class="quantity order-option">
   <div class="steps">
            <span class="option-label">Step 3</span>
                    Select Quantity (In dozen):
    </div>
    <div class="qty-option-parent"><ul class="qty-option">' . $options . '</ul></div>
    </div>';
        if ($echo) {
            echo $string;
        } else {
            return $string;
        }
    } else {
        if (is_null($product)) {
            $product = $GLOBALS['product'];
        }

        $defaults = array(
            'input_name' => 'quantity',
            'input_value' => '1',
            'max_value' => apply_filters('woocommerce_quantity_input_max', -1, $product),
            'min_value' => apply_filters('woocommerce_quantity_input_min', 0, $product),
            'step' => apply_filters('woocommerce_quantity_input_step', 1, $product),
            'pattern' => apply_filters('woocommerce_quantity_input_pattern', has_filter('woocommerce_stock_amount', 'intval') ? '[0-9]*' : ''),
            'inputmode' => apply_filters('woocommerce_quantity_input_inputmode', has_filter('woocommerce_stock_amount', 'intval') ? 'numeric' : ''),
        );

        $args = apply_filters('woocommerce_quantity_input_args', wp_parse_args($args, $defaults), $product);

        // Apply sanity to min/max args - min cannot be lower than 0. 
        $args['min_value'] = max($args['min_value'], 0);
        $args['max_value'] = 0 < $args['max_value'] ? $args['max_value'] : '';

        // Max cannot be lower than min if defined. 
        if ('' !== $args['max_value'] && $args['max_value'] < $args['min_value']) {
            $args['max_value'] = $args['min_value'];
        }

        ob_start();

        wc_get_template('global/quantity-input.php', $args);

        if ($echo) {
            echo ob_get_clean();
        } else {
            return ob_get_clean();
        }
    }
}

//Blog : https://wpsites.net/web-design/remove-woocommerce-single-thumbnail-images-from-product-details-page/
//remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);

//Blog : https://www.codegearthemes.com/blogs/woocommerce/remove-product-meta-categories-in-a-product-page-woocommerce
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);

//Blog : https://silicondales.com/tutorials/woocommerce/remove-tab-woocommerce/
add_filter('woocommerce_product_tabs', 'woo_remove_product_tabs', 98);
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
function woo_remove_product_tabs($tabs) {
    $tabs = array();
    return $tabs;
}

//Niketan
//29-oct 6.25
// Change html
//add_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_side_calaculation', 10);

function woocommerce_output_product_side_calaculation() {
    ?>
    <div class="pmnm-right-product-price col-lg-6 tab-40">
        <div class="product-od">
            <div class="od-body">
                <div class="od-head">Selected Order Details<span class="close">×</span></div>
                <div class="pm-cart-loader-wrapper">
                          <div class="pm-cart-loader d-none"></div>
                <div class="content">
                    <p>Dispatch Week <span class="pmnm-dynamic-grade-week value"></span></p>
                    <p>Grade (Size) <span class="pmnm-dynamic-grade"></span> per Dz. <span class="pmnm-dynamic-grade-price value">Not Selected</span></p>
                    <p>Quantity (Dz.) <span class="pmnm-dynamic-qty value">1</span></p>

                <!-- <p class="final-amount">Total Amount <span class="pmnm-dynamic-total value">0</span></p>
                <span>*(Above Amount will vary based on delivery charge)</span>-->
                </div></div>
            </div>
			            <!-- Commented by Abhi for Backup -->
<!--             <div class="pm-mobile-amt ">
                <p class="final-amount d-lg-none d-block">Total Amount : <span class="pmnm-dynamic-total value">0</span></p>
                <button class="pm-view-cart-btn d-lg-none d-block">Your Cart<span class="pmn-cart-count"></span></button>
            </div> -->
			<div class="pm-mobile-amt ">
                <div>
                    <p class="final-amount strike d-lg-none d-block">Total Amount : <span class="pmnm-dynamic-total value">0</span></p>
                    <p class="discount-final-amount d-lg-none pmas-hide">Total Amount : <span class="pmas-dynamic-discount-total value">0</span></p>
                </div>
                <button class="pm-view-cart-btn d-lg-none d-block">Your Cart<span class="pmn-cart-count"></span></button>
            </div>
            <div class="od-footer">
                <span class="od-btn pm-add-to-cart pmn-addtocart-btn"><a href="javascript:void(0);" class="pmn-addto-cart">Add to Cart</a></span>
                <p class="pm-cart-totle">Total Amount<span class="pmnm-dynamic-total value">0</span></p>
                <?php
                global $woocommerce; //Delete This | NIKHIL
                $checkout_url = $woocommerce->cart->get_checkout_url();
                ?>
                <span class="pm-divider"></span>
<!--                <span class="pm-go-to-checkout pm-cart-count-0 pmn-direct-checkout"><a href="javascript:void(0);">Order Now</a></span>-->
                <span class="pm-go-to-checkout pm-cart-count-0 pmn-direct-checkout d-none d-lg-block"><a href="javascript:void(0);">Order Now</a></span>
                <span class="pm-go-to-checkout pmn-mb-order d-block d-lg-none"><a href="javascript:void(0);">Order Now</a></span>
            </div>
        </div>

        <div class="pmn-cart" style="display: none;" >
            <h3 class="pm-cart-title">Your Cart<span class="pmn-cart-count d-none d-lg-block"></span><span class="pm-close-cart-popup d-lg-none d-md-block d-sm-block"></span></h3>
			<?php
            if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $wallet_balance = str_replace(".00","", woo_wallet()->wallet->get_wallet_balance( $current_user->ID, 'edit' ) );
            if( $wallet_balance >= '1' ){ ?>
            <span class="pmn-bitecoin-avail">You have <?php echo $wallet_balance; ?> Balance, You can utilise them on Checkout Page.</span>
            <?php } } ?>
            <span class="pm-amt-note d-sm-block d-lg-none d-md-block">*The total amount will vary based on delivery charge</span>
			<div class="pm-cart-loader-wrapper">
                <div class="pm-cart-loader d-none"></div>
                <div class="pmn-cart-items">
                    <ul id="pmn-cart-items-header" class="pmn-single-cart-item-header">
                        <li class="pm-cart-delete"></li>
                        <li class="pm-rispective-week">Dispatch Week</li>
                        <li class="pm-mango-grade">Grade</li>
                        <li class="pm-quantity-counter">Qty.</li>
                        <li class="pm-mango-amt">Total</li>
                    </ul>

                </div>
			</div>
            <div class="pm-product-amt d-none d-lg-block d-xl-block">
				<!-- Commented by Abhi for Backup -->
<!--                 <p class="final-amount">Total Amount : <span class="pmnm-dynamic-total value">0</span></p> -->
				<!-- Start Abhi is here -->
                <p class="final-amount"><span class="pmas-final-total-text">Total Amount :</span><span class="pmnm-dynamic-total value">0</span></p>
                <p class="discount-final-amount pmas-hide">Total Amount: <span class="pmas-dynamic-discount-total value">0</p>
                <!-- End Abhi is here -->
                <span>*(Above amount will vary based on delivery charge)</span>
                <span class="pm-go-to-checkout pmn-fulfilled-checkout"><a href="javascript:void(0);">Order Now</a></span>
            </div>

            <div class="pm-error-msg">
                <p class="pm-strong-text" id="pm-error-messagecart">Your cart is empty!</p>
                <a class="pm-modal-order-btn" href="javascript:void(0);">Order Mangoes</a>
            </div>
			
			<div class="pm-ajax-error-msg d-none">
                <p class="pm-error-message" id="pm-error-messagecart">"You can only order 10 Dozen of the same mango grade in the same week."</p>
            </div>
			
			
        </div>
    </div>
    <?php
}

//Blog : https://stackoverflow.com/questions/21181911/woocommerce-delete-all-products-from-cart-and-add-current-product-to-cart
//add_filter( 'woocommerce_add_cart_item_data', 'pmnm_empty_cart', 10,  3);
function pmnm_empty_cart($cart_item_data, $product_id, $variation_id) {
    global $woocommerce;
    $woocommerce->cart->empty_cart();
    return $cart_item_data;
}

//Blog : https://rudrastyh.com/woocommerce/redirect-to-checkout-skip-cart.html
//add_filter('woocommerce_add_to_cart_redirect', 'pmnm_skip_cart_redirect_checkout');
//function pmnm_skip_cart_redirect_checkout($url) {
//    return wc_get_cart_url();
//    //return wc_get_checkout_url();
//}
//add_filter('wc_add_to_cart_message_html', 'pmnm_remove_add_to_cart_message');
//
//function pmnm_remove_add_to_cart_message($message) {
//    return '';
//}
// add_action('template_redirect','nmpm_redirect_to_checkout');
// function nmpm_redirect_to_checkout() {
//     if (is_page('cart') || is_cart()) {
//         wp_safe_redirect(wc_get_checkout_url());
//         die();
//     }
// }

/* add_action('woocommerce_checkout_before_customer_details','pmnm_gravity_form_on_checkout');
  function pmnm_gravity_form_on_checkout(){
  echo do_shortcode('[gravityform id="2"]');
  } */

add_action('gform_pre_submission_2', 'after_submission_checkout', 10);

function after_submission_checkout($form) {
    global $wpdb;
    $pincode = rgpost('input_4_5');
    $row = $wpdb->get_row('select * from ' . $wpdb->prefix . 'pincode where pincode="' . $pincode . '"');

    if (!isset($row->status)) {
        //gform_update_meta( $entry['id'], '6', 'no' );
        $_POST['input_6'] = 'no';
    } else {
        if ($row->status == '1') {
            //gform_update_meta( $entry['id'], '6', 'yes' );
            $_POST['input_6'] = 'yes';
        } else {
            //gform_update_meta( $entry['id'], '6', 'no' );
            $_POST['input_6'] = 'no';
        }
    }
}

function delivery_available_func($attr) {
    global $wpdb, $woocommerce;
    $script = '';
    $entry = GFAPI::get_entry($attr['entry_id']);

    $name = $entry['1'];
    $pincode = $entry['4.5'];
    $email = $entry['2'];
    $phone = $entry['3'];
    if ($entry['6'] == 'yes') {// This means we are delivering to the pincode entered by the customer. @niketan please take a note of this.
        //echo '<p id="available_msg">Great! we deliver in your area :) <br/>Please add the remaining details.</p>';
        /**
         * @Niketan has to remove [display none] the "Thanks for contacting us!!! We will get in touch with you shortly." message" 
         * Also remove [display none] the coupon code section
         * 
         * Heading : <h2> Check availability with Pin Code </h2> 
         * Size should be as that of title size (h1)
         */

        $city = "";
        $state = "";
        $delivery_charge = 0;
        $pickup_charge = 0;
        $row = $wpdb->get_row("select *," . $wpdb->prefix . "pincode.id pid from " . $wpdb->prefix . "pincode," . $wpdb->prefix . "city," . $wpdb->prefix . "state where " . $wpdb->prefix . "pincode.city_id=" . $wpdb->prefix . "city.id and " . $wpdb->prefix . "city.state_id=" . $wpdb->prefix . "state.id and pincode='" . $pincode . "'");
        $jq = "";
        if (email_exists($email)) {
            $jq = 'jQuery("body").find("#createaccount").prop("checked", false);jQuery("body").find("#createaccount").parents(".woocommerce-account-fields").css("display","none");';
        }
        //echo '<pre>';print_r($row);
        if (isset($row->city)) {
            $city = $row->city;
        }
        if (isset($row->short_name)) {
            $state = $row->short_name;
        }
        if (isset($row->zone_id)) {
            $zone = $wpdb->get_row("select * from " . $wpdb->prefix . "zone where id='" . $row->zone_id . "'");
            $item_count = WC()->cart->get_cart_contents_count();
            global $woocommerce;
$items = $woocommerce->cart->get_cart();
$item_count=0;
foreach($items as $item => $values) {
    if($values['product_id']==14){
        $item_count+=$values['quantity'];
    }
}
            $temp_item_count = 0;
            $array_cart = array(4, 2, 1, 0);
            rsort($array_cart);
            $temp_array_cart = array();
            $delivery_charge_doz = array();
            $pickup_charge_doz = array();
            $delivery_charge_doz[0] = 0;
            $delivery_charge_doz[1] = $zone->delivery_charge_doz1;
            $delivery_charge_doz[2] = $zone->delivery_charge_doz2;
            $delivery_charge_doz[4] = $zone->delivery_charge_doz4;
            $pickup_charge_doz[0] = 0;
            $pickup_charge_doz[1] = $zone->pickup_charge_doz1;
            $pickup_charge_doz[2] = $zone->pickup_charge_doz2;
            $pickup_charge_doz[4] = $zone->pickup_charge_doz4;
            while ($item_count != $temp_item_count) {
                foreach ($array_cart as $ac) {
                    if ($ac <= ($item_count - $temp_item_count)) {
                        $temp_item_count += $ac;
                        $delivery_charge += $delivery_charge_doz[$ac];
                        $pickup_charge += $pickup_charge_doz[$ac];
                        break;
                    }
                }
            }
            WC()->session->set('shipping_calculated_cost', $delivery_charge);
        }
        $pickup_list = '';
        if (isset($row->pid)) {
            $pickup_loacations = $wpdb->get_results("select * from " . $wpdb->prefix . "pickup where pincode='" . $pincode . "' and status='1'");
            
            // Check for pickup points
            if( !empty($pickup_loacations) ){
                $is_pickup_available = 'yes';
                $item_count = WC()->cart->get_cart_contents_count();
                $pickup_charge = array();
                foreach ($pickup_loacations as $pickup_loacation) {
                    $pickup_list .= "<div class=pm-pickup-point>";
                    $pickup_list .= "<input type='radio' class='input-radio' name='pickup_address' value='" . $pickup_loacation->address . "' data-id='".$pickup_loacation->id."' id='pickup-address-" . $pickup_loacation->id . "' />";
//                     $pickup_list .= "<label class='radio' for='pickup-address-" . $pickup_loacation->id . "'>" . $pickup_loacation->address . "<p><strong>Shipping at:</strong>&nbsp;&nbsp;<i class='fas fa-rupee-sign'></i> " . $pickup_loacation->rate * $item_count . "</p><p><strong>Travel Partner: </strong>" . $pickup_loacation->travel."</p>";
					$pickup_list .= "<label class='radio' for='pickup-address-" . $pickup_loacation->id . "'>" . $pickup_loacation->address;
                    if($pickup_loacation->rate === '0'){
                        $pickup_list .= "<p><strong>Shipping at:</strong> Free</p>";
                    }
                    else{
                        $pickup_list .= "<p><strong>Shipping at:</strong>&nbsp;&nbsp;<i class='fas fa-rupee-sign'></i> " . $pickup_loacation->rate * $item_count . "</p>";
                    }
                    $pickup_list .= "<p><strong>Travel Partner: </strong>" . $pickup_loacation->travel."</p>";
                    if(!empty($pickup_loacation->map)){
                        $pickup_list .= "<p><a href='javascript:void(0)' class='map-button' onclick='modalcall(this);' attr-href='" . $pickup_loacation->map . "'><strong>View on Google map</strong></a></p>";
                    }
                    $pickup_list .= "</label></div>";
                    $pickup_charge[] = ($pickup_loacation->rate * $item_count);
                }
            }
            else{
                $is_pickup_available = 'no';
            }
        }
        $great_msg = "class='form-row pm-delivery-message pm-p0'";
        $blank_field_classes = "class='form-row form-row-wide'";
        $script .= '<script>jQuery("document").ready(function(){';
        $script .= 'jQuery(".checkout-form .woocommerce-billing-fields h1.baloo-font").after("<p ' . $great_msg . '>Great! we deliver in your area :)</p>");jQuery(".pmnm-checkout-div").css("display","block");jQuery("#entry_id").val("' . $attr['entry_id'] . '");jQuery("#billing_postcode").val("' . $pincode . '").attr("readonly",true);jQuery("#billing_email").val("' . $email . '");jQuery("#billing_phone").val("' . $phone . '");jQuery("#billing_first_name").val("' . $name . '");jQuery("#billing_city").val("' . $city . '");jQuery("#billing_state").val("' . $state . '");jQuery(".pma-delivery-modes-wrapper").after("<div class=pma-pickup-point-list><h4>Pick up address</h4>' . $pickup_list . '</div>");jQuery("input[type=radio][name=delivery_mode][value=home]").attr("tax","' . $delivery_charge . '");jQuery("body").trigger("update_checkout");' . $jq ;
        if($is_pickup_available == 'yes'){
            $script .= 'jQuery("input[type=radio][name=delivery_mode][value=pickup]").attr("mintax","'. min($pickup_charge) .'");jQuery("input[type=radio][name=delivery_mode][value=pickup]").attr("maxtax","'.max($pickup_charge).'");';
        }
        
        $script .= 'jQuery("input[type=radio][name=delivery_mode][value=pickup]").attr("pickupAvailable", "'.$is_pickup_available.'");
            if(jQuery("input[type=radio][name=delivery_mode][value=pickup]").attr("pickupAvailable") === "no"){
                jQuery("input[type=radio][name=delivery_mode][value=pickup]").attr("disabled", "disabled");
                jQuery("label[for=delivery_mode_pickup]").addClass("disabled");
            }
            jQuery("input[type=radio][name=delivery_mode][value=home]").attr("deliveryavailable", "yes");
            jQuery(".pma-pickup-point-list input[type=radio][name=pickup_address]").change(function(){
                jQuery("#billing_address_1").val(jQuery(this).val());
                jQuery.ajax({
                    url:php__vars.ajaxurl,
                    data:{"action":"pmas_update_pickup_charge","pickup_id": jQuery(this).data("id")},
                    type:"post",
                    success:function(response){
                        jQuery("body").trigger("update_checkout");
                    }
                });
            });
        ';
        if(isset($_COOKIE['pincode']) && $_COOKIE['pincode'] == $pincode){
            if(isset($_COOKIE['order_address1']) && isset($_COOKIE['order_delivery_mode'])){
                if($_COOKIE['order_delivery_mode'] == 'home')
                {
                    $script .= 'jQuery("input[type=radio][name=delivery_mode][value=home]").prop("checked", true).trigger("change");
                    jQuery("#billing_address_1").val("'.$_COOKIE['order_address1'].'");';
                    if(isset($_COOKIE['order_address2'])){
                        $script .= 'jQuery("#billing_address_2").val("'.$_COOKIE['order_address2'].'");';
                    }
                }
                else if($_COOKIE['order_delivery_mode'] == 'pickup'){
                    $script .= 'jQuery("#billing_address_2").val("");
                    jQuery("#customer_details .woocommerce-billing-fields__field-wrapper").hide();jQuery("#customer_details h3").hide();
                    jQuery("input[type=radio][name=delivery_mode][value=pickup]").prop("checked", true).trigger("true");
                    jQuery(".pm-pickup-point input[type=radio][name=pickup_address]").each(function(){
                        if(jQuery(this).val() == "'.$_COOKIE['order_address1'].'"){
                            jQuery("input[type=radio][name=delivery_mode]").trigger("change");
                            jQuery(this).prop("checked", true).trigger("change");
                            jQuery.ajax({
                                url:php__vars.ajaxurl,
                                data:{"action":"pmas_update_pickup_charge","pickup_id": jQuery(this).data("id")},
                                type:"post",
                                success:function(response){
                                    jQuery("body").trigger("update_checkout");
                                }
                            });
                        }
                    });
                        
                    ';

                }
            }
        }
        $script .= '});</script>';
        
    } else if( $entry['6'] == 'no' ) {
        global $woocommerce;
        global $wpdb;

        $jq = "";
        if (email_exists($email)) {
            $jq = 'jQuery("body").find("#createaccount").prop("checked", false);jQuery("body").find("#createaccount").parents(".woocommerce-account-fields").css("display","none");';
        }
        //$script='<script>alert("currently location not available");</script>';
        //Niketan design popup msg - 31 oct 3.34

        $item_count = WC()->cart->get_cart_contents_count();
        
        $checkout_url = $woocommerce->cart->get_checkout_url();

        $cart_url = wc_get_cart_url();
        /* $checkout_url = add_query_arg(array(
          'uname' => $name,
          'email' => $email,
          'mobile' => $phone,
          'pincode' => $pincode
          ), $checkout_url); */
        

            $pickup_loacations = $wpdb->get_results("select * from " . $wpdb->prefix . "pickup where status='1' and pincode='".$pincode."'");
            
            if(!empty($pickup_loacations)){

                // //echo '<pre>';print_r($pickup_loacations);
                $is_pickup_available = 'yes';
                $item_count = WC()->cart->get_cart_contents_count();

                $row = $wpdb->get_row("select *," . $wpdb->prefix . "pincode.id pid from " . $wpdb->prefix . "pincode," . $wpdb->prefix . "city," . $wpdb->prefix . "state where " . $wpdb->prefix . "pincode.city_id=" . $wpdb->prefix . "city.id and " . $wpdb->prefix . "city.state_id=" . $wpdb->prefix . "state.id and pincode='" . $pincode . "'");
                if(!empty($row->short_name)){
                    $state = $row->short_name;
                }
                
                $pickup_charge =array();
                foreach ($pickup_loacations as $pickup_loacation) {
                    
                    $city = $pickup_loacation->city;
                    
                    $pickup_list .= "<div class=pm-pickup-point>";
                    $pickup_list .= "<input type='radio' class='input-radio' name='pickup_address' value='" . $pickup_loacation->address . "' data-id='".$pickup_loacation->id."' id='pickup-address-" . $pickup_loacation->id . "' />";
//                     $pickup_list .= "<label class='radio' for='pickup-address-" . $pickup_loacation->id . "'>" . $pickup_loacation->address . "<p><strong>Shipping at:</strong>&nbsp;&nbsp;<i class='fas fa-rupee-sign'></i> " . $pickup_loacation->rate * $item_count . "</p><p><strong>Travel Partner: </strong>" . $pickup_loacation->travel."</p>";
					$pickup_list .= "<label class='radio' for='pickup-address-" . $pickup_loacation->id . "'>" . $pickup_loacation->address;
                    if($pickup_loacation->rate === '0'){
                        $pickup_list .= "<p><strong>Shipping at:</strong> Free</p>";
                    }
                    else{
                        $pickup_list .= "<p><strong>Shipping at:</strong>&nbsp;&nbsp;<i class='fas fa-rupee-sign'></i> " . $pickup_loacation->rate * $item_count . "</p>";
                    }
                    $pickup_list .= "<p><strong>Travel Partner: </strong>" . $pickup_loacation->travel."</p>";
                    if(!empty($pickup_loacation->map)){
                        $pickup_list .= "<p><a href='javascript:void(0)' class='map-button' onclick='modalcall(this);' attr-href='" . $pickup_loacation->map . "'><strong>View on Google map</strong></a></p>";
                    }
                    $pickup_list .= "</label></div>";
                    $pickup_charge[] = ($pickup_loacation->rate * $item_count);
                }

                $great_msg = "class='form-row pm-delivery-message pm-p0'";
                $blank_field_classes = "class='form-row form-row-wide'";
                
                

                $script .= '<script>jQuery("document").ready(function(){';
                    $script .= 'jQuery(".checkout-form .woocommerce-billing-fields h1.baloo-font").after("<p ' . $great_msg . '>Great! we deliver in your area :)</p>");jQuery(".pmnm-checkout-div").css("display","block");jQuery("#entry_id").val("' . $attr['entry_id'] . '");jQuery("#billing_postcode").val("' . $pincode . '").attr("readonly",true);jQuery("#billing_email").val("' . $email . '");jQuery("#billing_phone").val("' . $phone . '");jQuery("#billing_first_name").val("' . $name . '");jQuery("#billing_city").val("' . $city . '");jQuery("#billing_state").val("' . $state . '");jQuery(".pma-delivery-modes-wrapper").after("<div class=pma-pickup-point-list><h4>Pick up address</h4>' . $pickup_list . '</div>");jQuery("input[type=radio][name=delivery_mode][value=pickup]").attr("mintax","'.min($pickup_charge).'");jQuery("input[type=radio][name=delivery_mode][value=pickup]").attr("maxtax","'.max($pickup_charge).'");jQuery("body").trigger("update_checkout");' . $jq ;
                    $script .= 'jQuery(".pmnm-checkout-div #customer_details .woocommerce-billing-fields .pma-delivery-modes-wrapper label").css("width", "auto");jQuery(".pma-delivery-modes-wrapper label[for=delivery_mode_pickup]").html("Pickup<br/> <span>(Home delivery is not available in this area.)</span>");jQuery(".pma-delivery-modes-wrapper label[for=delivery_mode_home]").addClass("disabled");jQuery("input[type=radio][name=delivery_mode][value=home]").each(function(){jQuery(this).prop("disabled", true);});jQuery("input[type=radio][name=delivery_mode][value=home]").removeAttr("checked");jQuery("input[type=radio][name=delivery_mode][value=pickup]").prop("checked", true).trigger("change");
                        jQuery(".pma-pickup-point-list input[type=radio][name=pickup_address]").change(function(){
                            jQuery("#billing_address_1").val(jQuery(this).val());
                            
                            jQuery.ajax({
                                url:php__vars.ajaxurl,
                                data:{"action":"pmas_update_pickup_charge","pickup_id": jQuery(this).data("id")},
                                type:"post",
                                success:function(response){
                                    jQuery("body").trigger("update_checkout");
                                }
                            });
                        });
                    ';
                    if(isset($_COOKIE['pincode']) && $_COOKIE['pincode'] == $pincode){
                        if(isset($_COOKIE['order_address1']) && isset($_COOKIE['order_delivery_mode'])){
                            if($_COOKIE['order_delivery_mode'] == 'pickup'){
                                $script .= 'jQuery("#billing_address_2").val("");
                                jQuery("#customer_details .woocommerce-billing-fields__field-wrapper").hide();jQuery("#customer_details h3").hide();
                                jQuery("input[type=radio][name=delivery_mode][value=pickup]").prop("checked", true);
                                
                                jQuery(".pm-pickup-point input[type=radio][name=pickup_address]").each(function(){
                                    if(jQuery(this).val() == "'.$_COOKIE['order_address1'].'"){
                                    
                                        jQuery(this).prop("checked", true).trigger("change");
                                        jQuery.ajax({
                                            url:php__vars.ajaxurl,
                                            data:{"action":"pmas_update_pickup_charge","pickup_id": jQuery(this).data("id")},
                                            type:"post",
                                            success:function(response){
                                                jQuery("body").trigger("update_checkout");
                                            }
                                        });
                                    }
                                });
                                    
                                ';
            
                            }
                        }
                    }
                    $script .= '});</script>';

            }
            else{
                //$script = '<script>jQuery(".woocommerce-checkout").prepend("<div class=delivery-not-available><div class=dna-containt><div class=title baloo-font>We are Sorry!</div><p>We regret we are currently finding it difficult to serve in your area,<br> due to the stringent lockdown conditions imposed by <br>the government recently due to the coronavirus outbreak.<br><br>We will inform you as soon we get an update regarding <br>the serviceability of your area.<br><br>In the meantime, we invite you to order Devgad Aamras, <br>in case the lockdown conditions don`t improve. <br>Deliveries starting in July 2020.</p><a class=baloo-font >View cart</a><a class=baloo-font>Check another Pin</a></div></div>");jQuery(".delivery-not-available a").eq(0).attr("href", "' . $cart_url . '");jQuery(".delivery-not-available a").eq(1).attr("href", "https://devgadmango.com/products/");jQuery(".delivery-not-available a").eq(1).html("Order Aamras");</script>';
                $script = '<script>jQuery(".woocommerce-checkout").prepend("<div class=delivery-not-available><div class=dna-containt><div class=title baloo-font>We are Sorry!<span>As of now we do not serve in your area.</span></div><p>But we are planning to exceed in some areas.<br>We will let you know the update about it.</p><a class=baloo-font>Check another Pin</a></div></div>");jQuery(".delivery-not-available a").attr("href", "' . $checkout_url . '");</script>';

            }
        
            
        }
        else{
            
            $script = '<script>jQuery(".woocommerce-checkout").prepend("<div class=delivery-not-available><div class=dna-containt><div class=title baloo-font>We are Sorry!<span>As of now we do not serve in your area.</span></div><p>But we are planning to exceed in some areas.<br>We will let you know the update about it.</p><p>You may remove mango from cart and continue <br> purchase other than mango product</p><a class=baloo-font >View cart</a><a class=baloo-font >Check another Pin</a></div></div>");jQuery(".delivery-not-available a").eq(0).attr("href", "' . $cart_url . '");jQuery(".delivery-not-available a").eq(1).attr("href", "' . $checkout_url . '");</script>';
        }
        
        


        /**
         * This is the place where @niketan has to have a popup saying we are not serving to the location. The design and content for this screen is available.
         * Alert box mentioned above must be disabled.
         */
    

    setcookie('uname', $name, time() + 86400, '/');
    setcookie('pincode', $pincode, time() + 86400, '/');
    setcookie('email', $email, time() + 86400, '/');
    setcookie('phone', $phone, time() + 86400, '/');
    //$script='<script>alert('.$entry['6'].');</script>';
    return $script;
}

add_shortcode('delivery_available', 'delivery_available_func');





add_action('woocommerce_before_calculate_totals', 'change_tax_class_based_on_delivery_method', 10, 1);

function change_tax_class_based_on_delivery_method($cart) {
    global $woocommerce;
    /* $items = $woocommerce->cart->get_cart();
      foreach($items as $item => $values) {
      $size=$values['variation']['attribute_size'];
      $nmpm_prod_weight=get_option('nmpm_prod_weight');
      $nmpm_prod_box_weight=get_option('nmpm_prod_box_weight');
      $prod_weight=1;
      $prod_box_weight=1;
      if(isset($nmpm_prod_weight[$size])){
      $prod_weight=$nmpm_prod_weight[$size];
      }
      if(isset($nmpm_prod_box_weight[$size])){
      $prod_box_weight=$nmpm_prod_box_weight[$size];
      }
      $quantity=$values['quantity'];
      $gram=(($prod_weight*12)+$prod_box_weight)*$quantity;
      $kg=$gram/1000;
      }

      $cost=(WC()->session->get( 'shipping_calculated_cost'))?WC()->session->get( 'shipping_calculated_cost'):0;

      $woocommerce->cart->add_fee( __('Shipping', 'woocommerce'), $cost*$kg ); */
    /* if(WC()->cart->get_cart_contents_count()>0 && WC()->session->get( 'shipping_calculated_cost')){
      $woocommerce->cart->add_fee( __('Shipping', 'woocommerce'), WC()->cart->get_cart_contents_count()*WC()->session->get( 'shipping_calculated_cost'));
      } */

      
      global $woocommerce;
      $items = $woocommerce->cart->get_cart();
      $item_count=0;
      $item_weight = 0;
      $nitem_total = 0;
      $ditem_total = 1000;
        $d_weight = .5;
        $d_fee = 60;
        $shipping_charge= 0;
    if (WC()->session->get('shipping_calculated_cost')) {

        foreach($items as $item => $values) {
            if($values['product_id']==14){
                $item_count+=$values['quantity'];
            } 
            
        }
        
        if($item_count > 0){
            // $woocommerce->cart->add_fee(__('Shipping', 'woocommerce'), WC()->session->get('shipping_calculated_cost'));
            $shipping_charge  +=  WC()->session->get('shipping_calculated_cost');
        }

    }

        //echo '<pre>';
        // print_r();
        // echo '</pre>';
        foreach($items as $item => $values) {
            if($values['product_id']==14){
                $item_count+=$values['quantity'];
            } 
            else{
                $item_weight += $values['data']->get_weight() * $values['quantity'];
                $nitem_total +=$values['line_subtotal'];
        
            }
        }
        
        if($item_weight > 0 && $nitem_total < $ditem_total){
            $shipping_charge += ceil($item_weight/$d_weight)*60;
           
        }

        $woocommerce->cart->add_fee(__('Shipping', 'woocommerce'), $shipping_charge);
}

function pmnm_count_tax() {
    if (isset($_POST['tax'])) {
        WC()->session->set('shipping_calculated_cost', $_POST['tax']);
    }
    die();
}

add_action('wp_ajax_pmnm_count_tax', 'pmnm_count_tax');
add_action('wp_ajax_nopriv_pmnm_count_tax', 'pmnm_count_tax');


add_action('woocommerce_thankyou', 'bbloomer_checkout_save_user_meta');

function bbloomer_checkout_save_user_meta($order_id) {
    $entry_id = get_post_meta($order_id, 'entry_id', true);
    gform_update_meta($entry_id, '7', "yes");
}

function custom_my_account_menu_items($items) {
    $items = array(
        'edit-account' => __('My Profile', 'woocommerce'),
        'orders' => __('My Orders', 'woocommerce'),
        'customer-logout' => __('Logout', 'woocommerce')
    );

    return $items;
}

add_filter('woocommerce_account_menu_items', 'custom_my_account_menu_items');

//svg upload 
function my_myme_types($mime_types) {
    $mime_types['svg'] = 'image/svg+xml'; //Adding svg extension
    return $mime_types;
}

add_filter('upload_mimes', 'my_myme_types', 1, 1);

function new_excerpt_more($more) {
    return '  <a href="' . get_permalink() . '" class="pm-read-more" rel="bookmark"></a>';
}

add_filter('excerpt_more', 'new_excerpt_more');

function custom_excerpt_length($length) {
    return 40;
}

add_filter('excerpt_length', 'custom_excerpt_length', 999);
// trim excerpt whitespace
if (!function_exists('mp_trim_excerpt_whitespace')) {

    function mp_trim_excerpt_whitespace($excerpt) {
        return trim($excerpt);
    }

    add_filter('get_the_excerpt', 'mp_trim_excerpt_whitespace', 1);
}

// Change Phone Format
add_filter('gform_phone_formats', 'in_phone_format');

function in_phone_format($phone_formats) {
    $phone_formats['in'] = array(
        'label' => 'India',
        'mask' => "9999999999",
        'regex' => '/[7-9]{1}[0-9]{9}/',
        'instruction' => 'Please Enter 10 digit number',
    );

    return $phone_formats;
}

add_action('woocommerce_process_registration_errors', 'validatePasswordReg', 10, 2);

function validatePasswordReg($errors, $user) {
    // change value here to set minimum required password chars
    if (strlen($_POST['password']) < 12) {
        $errors->add('woocommerce_password_error', __('Password must be at least 12 characters long.'));
    }
    // adding ability to set maximum allowed password chars -- uncomment the following two (2) lines to enable that
    //elseif (strlen($_POST['password']) > 16 )
    //$errors->add( 'woocommerce_password_error', __( 'Password must be shorter than 16 characters.' ) );
    return $errors;
}

// Niketan 11 Nov, 12.11
// Function for split archive title
add_filter('get_the_archive_title', function ($title) {
    if (is_category()) {
        $title = single_cat_title('', false);
    } elseif (is_tag()) {
        $title = single_tag_title('', false);
    } elseif (is_author()) {
        $title = '<span class="vcard">' . get_the_author() . '</span>';
    } elseif (is_tax()) { //for custom post types
        $title = sprintf(__('%1$s'), single_term_title('', false));
    }
    return $title;
});

add_action('admin_menu', 'nmpm_wp_dashboard_products_new_page', 9999);

function nmpm_wp_dashboard_products_new_page() {
    add_submenu_page('edit.php?post_type=product', 'Product Weight', 'Product Weight', 'edit_products', 'new_page_slug', 'nmpm_wp_dashboard_products_new_page_callback', 9999);
    add_submenu_page('edit.php?post_type=product', 'Import Address', 'Import Address', 'edit_products', 'address_page', 'nmpm_wp_dashboard_products_address_page_callback', 9999);
    add_submenu_page('edit.php?post_type=product', 'Zone', 'Zone', 'edit_products', 'zone_page', 'nmpm_wp_dashboard_products_zone_page_callback', 9999);
    add_submenu_page('edit.php?post_type=product', 'SMS Integration', 'SMS Integration', 'edit_products', 'sms_page', 'aspm_wp_dashboard_products_sms_page_callback', 9999);
	add_submenu_page( 'edit.php?post_type=product', 'Import Pickup Points', 'Import Pickup Points', 'edit_products', 'pickup_points', 'nmpm_wp_dashboard_products_pickup_page_callback', 9999 );
}

function nmpm_wp_dashboard_products_new_page_callback() {
    ?>
    <div class="wrap"><h1 class="">Product Weight</h1>
        <form method="post">
            <table border="1">
                <tr>
                    <th>Size</th>
                    <th>Weight(gm)</th>
                    <th>Box Weight(gm)</th>
                </tr>
                <?php
                if (isset($_POST["save_product_size_field"]) && wp_verify_nonce($_REQUEST['save_product_size_field'], 'save_product_size_action')) {
                    if (isset($_POST["prod_weight"])) {
                        update_option("nmpm_prod_weight", $_POST["prod_weight"]);
                    }
                    if (isset($_POST["prod_box_weight"])) {
                        update_option("nmpm_prod_box_weight", $_POST["prod_box_weight"]);
                    }
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e('Updated Successfully!', 'sample-text-domain'); ?></p>
                    </div>
                    <?php
                }
                $nmpm_prod_weight = get_option('nmpm_prod_weight');
                $nmpm_prod_box_weight = get_option('nmpm_prod_box_weight');

                $product = wc_get_product(14);
                $attributes = $product->get_attributes();
                $size = array();
                $size = explode('|', $attributes['size']['value']);
                foreach ($size as $si) {
                    $si = trim($si);
                    echo'<tr>';
                    echo'<td>' . $si . '</td>';
                    echo'<td><input type="number" min="1" name="prod_weight[' . $si . ']" value="' . (isset($nmpm_prod_weight[$si]) ? $nmpm_prod_weight[$si] : "") . '" required /></td>';
                    echo'<td><input type="number" min="1" name="prod_box_weight[' . $si . ']" required value="' . (isset($nmpm_prod_box_weight[$si]) ? $nmpm_prod_box_weight[$si] : "") . '" /></td>';
                    echo'</tr>';
                }
                wp_nonce_field('save_product_size_action', 'save_product_size_field');
                ?>
                <tr>
                    <th colspan="3"><input type="submit" value="Save" class="button button-primary button-large"/></th>

                </tr>
            </table>
        </form>
    </div>
    <?php
}

function nmpm_wp_dashboard_products_address_page_callback() {
    ?>
    <div class="wrap"><h1 class="">Import Address</h1>
        <h4>CSV Format</h4>
        <table border="1">
            <tr>
                <th>City</th>
                <th>Pincode</th>
                <th>Has Cod</th>
                <th>Has Prepaid</th>
                <th>Has Reverse</th>
                <th>Shiprocket Delivery Zone</th>
                <th>Delivery Charge 1kg</th>
                <th>Pickup Charge 1kg</th>
                <th>Routing Code</th>
                <th>State</th>
                <th>State Short Name</th>
                <th>TAT/SLA (Days)</th>
            </tr>
            <tr>
                <td>AHMEDABAD</td>
                <td>380001</td>
                <td>TRUE</td>
                <td>TRUE</td>
                <td>TRUE</td>
                <td>z_d</td>
                <td>300</td>
                <td>200</td>
                <td>NCM</td>
                <td>GUJARAT</td>
                <td>GJ</td>
                <td>2 Days</td>
            </tr>
        </table>
        <?php
        if (isset($_POST['importSubmit'])) {
            global $wpdb;
            // Allowed mime types
            $csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
            // Validate whether selected file is a CSV file
            if (!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $csvMimes)) {
                // If the file is uploaded
                if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                    // Open uploaded CSV file with read-only mode
                    $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
                    // Skip the first line
                    fgetcsv($csvFile);
                    // Parse data from CSV file line by line
                    while (($line = fgetcsv($csvFile)) !== FALSE) {
                        //echo'<pre>'; print_r($line); echo'</pre>';
                        $city = strtoupper($line[0]);
                        $pincode = $line[1];
                        $has_cod = $line[2];
                        $has_prepaid = $line[3];
                        $has_reverse = $line[4];
                        $shiprocket_delivery_zone = strtoupper($line[5]);
                        $delivery_charge = $line[6];
                        $pickup_charge = $line[7];
                        $routing_code = $line[8];
                        $state = strtoupper($line[9]);
                        $state_short_name = strtoupper($line[10]);
                        $tat_sla = $line[11];

                        $zone_row = $wpdb->get_row("select * from " . $wpdb->prefix . "zone where name ='" . $shiprocket_delivery_zone . "'");
                        if (isset($zone_row->id)) {
                            $zone_id = $zone_row->id;
                        } else {
                            $wpdb->insert($wpdb->prefix . "zone", array(
                                'name' => $shiprocket_delivery_zone,
                                'delivery_charge' => $delivery_charge,
                                'pickup_charge' => $pickup_charge
                            ));
                            $zone_id = $wpdb->insert_id;
                        }

                        $state_row = $wpdb->get_row("select * from " . $wpdb->prefix . "state where state ='" . $state . "'");
                        if (isset($state_row->id)) {
                            $state_id = $state_row->id;
                        } else {
                            $wpdb->insert($wpdb->prefix . "state", array(
                                'state' => $state,
                                'short_name' => $state_short_name
                            ));
                            $state_id = $wpdb->insert_id;
                        }

                        $city_row = $wpdb->get_row("select * from " . $wpdb->prefix . "city where city ='" . $city . "'");
                        if (isset($city_row->id)) {
                            $city_id = $city_row->id;
                        } else {
                            $wpdb->insert($wpdb->prefix . "city", array(
                                'city' => $city,
                                'state_id' => $state_id
                            ));
                            $city_id = $wpdb->insert_id;
                        }

                        $pincode_row = $wpdb->get_row("select * from " . $wpdb->prefix . "pincode where pincode ='" . $pincode . "'");
                        if (isset($pincode_row->id)) {
                            $pincode_id = $pincode_row->id;
                        } else {
                            $wpdb->insert($wpdb->prefix . "pincode", array(
                                'pincode' => $pincode,
                                'city_id' => $city_id,
                                'zone_id' => $zone_id,
                                'has_cod' => $has_cod,
                                'has_prepaid' => $has_prepaid,
                                'has_reverse' => $has_reverse,
                                'routing_code' => $routing_code,
                                'tat_sla' => $tat_sla
                            ));
                            $pincode_id = $wpdb->insert_id;
                        }
                    }
                    echo'<div class="notice notice-success is-dismissible"><p>';
                    _e('Imported Successfully!', 'sample-text-domain');
                    echo'</p></div>';
                }
            }
        }
        ?>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file" />
            <input type="submit" class="btn btn-primary" name="importSubmit" value="IMPORT">
        </form>
    </div>
    <?php
}

function nmpm_wp_dashboard_products_zone_page_callback() {
    global $wpdb;
    ?>
    <div class="wrap"><h1 class="">Zone</h1>
        <?php
        if (isset($_POST['add_new_zone'])) {
            $wpdb->insert($wpdb->prefix . "zone", array(
                'name' => $_POST['new_zone_name'],
                'delivery_charge_doz1' => $_POST['new_delivery_charge_doz1'],
                'pickup_charge_doz1' => $_POST['new_pickup_charge_doz1'],
                'delivery_charge_doz2' => $_POST['new_delivery_charge_doz2'],
                'pickup_charge_doz2' => $_POST['new_pickup_charge_doz2'],
                'delivery_charge_doz4' => $_POST['new_delivery_charge_doz4'],
                'pickup_charge_doz4' => $_POST['new_pickup_charge_doz4']
            ));
            echo'<div class="notice notice-success is-dismissible"><p>';
            _e('Successfully Inserted!', 'sample-text-domain');
            echo'</p></div>';
        }

        if (isset($_POST['update_zone'])) {
            if (isset($_POST['zone_name']) && is_array($_POST['zone_name'])) {
                foreach ($_POST['zone_name'] as $id => $zone) {
                    $wpdb->update($wpdb->prefix . "zone", array(
                        'name' => $zone,
                        'delivery_charge_doz1' => $_POST['delivery_charge_doz1'][$id],
                        'pickup_charge_doz1' => $_POST['pickup_charge_doz1'][$id],
                        'delivery_charge_doz2' => $_POST['delivery_charge_doz2'][$id],
                        'pickup_charge_doz2' => $_POST['pickup_charge_doz2'][$id],
                        'delivery_charge_doz4' => $_POST['delivery_charge_doz4'][$id],
                        'pickup_charge_doz4' => $_POST['pickup_charge_doz4'][$id]), array('id' => $id)
                    );
                }
                echo'<div class="notice notice-success is-dismissible"><p>';
                _e('Successfully Updated!', 'sample-text-domain');
                echo'</p></div>';
            }
        }
        ?>
        <table border="1" >
            <tr>
                <th>Zone</th>
                <th>Delivery Charge Doz 1</th>
                <th>Pickup Charge Doz 1</th>
                <th>Delivery Charge Doz 2</th>
                <th>Pickup Charge Doz 2</th>
                <th>Delivery Charge Doz 4</th>
                <th>Pickup Charge Doz 4</th>
                <th></th>
            </tr>
            <tr>
            <form method="post">
                <th><input type="text" style="text-transform:uppercase" name="new_zone_name" required /></th>
                <th><input type="number" min="1" name="new_delivery_charge_doz1" required /></th>
                <th><input type="number" min="1" name="new_pickup_charge_doz1" required /></th>
                <th><input type="number" min="1" name="new_delivery_charge_doz2" required /></th>
                <th><input type="number" min="1" name="new_pickup_charge_doz2" required /></th>
                <th><input type="number" min="1" name="new_delivery_charge_doz4" required /></th>
                <th><input type="number" min="1" name="new_pickup_charge_doz4" required /></th>
                <th><input type="submit" class="btn btn-primary" name="add_new_zone" value="Insert"></th>
            </form>
            </tr>
            <form method="post">
                <?php
                $zones = $wpdb->get_results("select * from " . $wpdb->prefix . "zone");
                foreach ($zones as $zone) {
                    ?>
                    <tr>
                        <td><input type="text" value="<?php echo $zone->name; ?>" name="zone_name[<?php echo $zone->id; ?>]" style="text-transform:uppercase" required /></td>
                        <td><input type="number" min="1" value="<?php echo $zone->delivery_charge_doz1; ?>" name="delivery_charge_doz1[<?php echo $zone->id; ?>]" required /></td>
                        <td><input type="number" min="1" value="<?php echo $zone->pickup_charge_doz1; ?>" name="pickup_charge_doz1[<?php echo $zone->id; ?>]" required /></td>
                        <td><input type="number" min="1" value="<?php echo $zone->delivery_charge_doz2; ?>" name="delivery_charge_doz2[<?php echo $zone->id; ?>]" required /></td>
                        <td><input type="number" min="1" value="<?php echo $zone->pickup_charge_doz2; ?>" name="pickup_charge_doz2[<?php echo $zone->id; ?>]" required /></td>
                        <td><input type="number" min="1" value="<?php echo $zone->delivery_charge_doz4; ?>" name="delivery_charge_doz4[<?php echo $zone->id; ?>]" required /></td>
                        <td><input type="number" min="1" value="<?php echo $zone->pickup_charge_doz4; ?>" name="pickup_charge_doz4[<?php echo $zone->id; ?>]" required /></td>
                        <td></td>
                    </tr>
                    <?php
                }
                ?>
                <tr><th colspan="8"><input type="submit" class="btn btn-primary" name="update_zone" value="Update"></th></tr>
            </form>
        </table>
    </div>
    <?php
}

function nmpm_wp_dashboard_products_pickup_page_callback(){

    global $wpdb;
    $pickup_table = $wpdb->prefix.'pickup';


    // Latest Imported data
    $latest_imported_data = array();

    // Logical Code Goes here
    if(isset($_POST['pma-pickup-csv-import'])){
        
        $pma_current_timestamp = date("Y-m-d-h-i-sa");
        $pma_csv_file_array = $_FILES['pma-pickup-csv'];
        
        // $pma_csv_name = $pma_current_timestamp.'-'.$pma_csv_file['name'];
        // Fetch CSV data in Assoc Array
        if($pma_csv_file_array){
            $pma_csv_file_read = fopen($pma_csv_file_array['tmp_name'], 'r');
                $pma_csv_assoc_data = array();
                $header = null;

                while(($row = fgetcsv($pma_csv_file_read)) !== false){
                if($header === null){
                    $header = $row;
                    continue;
                }

                $newRow = array();
                for($i = 0; $i<count($row); $i++){
                $newRow[$header[$i]] = $row[$i];
                }

                $pma_csv_assoc_data[] = $newRow;
                }

            fclose($pma_csv_file_read);

            /**
             * Defines required csv header array
             * 
             */
            $keys_array = array( 'city', 'travel', 'address', 'pincode', 'map', 'rate', 'status' );
            $not_found_key = array();

            // If records is greater than 0
            if(count($pma_csv_assoc_data) > 0)
            {
                foreach( $keys_array as $key )
                {
                    if( !array_key_exists($key,  $pma_csv_assoc_data[0]) ){
                        // Pattern not found
                        array_push( $not_found_key, $key );
                    }
                }

                // if csv header pattern not found
                if( count( $not_found_key ) > 0 )
                {
                    $imploded_str = implode( ', ', $not_found_key );
                    /**
                     * Defines error if csv is blank
                     */
                    echo'<div class="notice notice-error is-dismissible"><p>';
                        _e( $imploded_str.' columns not found in CSV' ); 
                    echo'</p></div>';
                }

                // Now , if all is good
                if( count( $not_found_key ) < 1 ){
                    $all_latest_records = array();
                    $total_count = count($pma_csv_assoc_data);
                    $import_count = 0;
                    // Loop through all records
                    foreach( $pma_csv_assoc_data as $record )
                    {

                            $pma_query = $wpdb->prepare("INSERT INTO $pickup_table (city, travel, address, pincode, map, rate, status) VALUES('".$record['city']."', '".$record['travel']."', '".$record['address']."', '".$record['pincode']."','".$record['map']."', '".$record['rate']."', '1')");
                            $wpdb->query($pma_query);

                            $record['Status'] = 'Pincode Found';
                            array_push($all_latest_records, $record);

                    }

                /**
                 * Defines csv data is imported message
                 */
                echo'<div class="notice notice-success is-dismissible"><p>';
                    _e( 'CSV imported successfully.' ); 
                echo'</p></div>';

                }
                
            }
            else{
                /**
                 * Defines error if csv is blank
                 */
                echo'<div class="notice notice-error is-dismissible"><p>';
                    _e( 'CSV must contains at least 1 record.' ); 
                echo'</p></div>';
            }
            
        }
        
    }
    ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Import Pickup Points</h1>
            <div style='height: 30px;'></div>
            <h2 class="wp-subheading-inline">CSV Format</h2>
            <table class='wp-list-table widefat fixed'>
                <tr>
                    <thead>
                        <th>city</th>
                        <th>travel</th>
                        <th>address</th>
                        <th>pincode</th>
                        <th>map</th>
                        <th>rate</th>
                        <th>status</th>
                    </thead>
                </tr>
            </table>
            <div style='height: 30px;'></div>
            <h2 class='wp-subheading-inline'>Upload CSV</h2>
            <form method='POST' enctype='multipart/form-data'>
                <div>
                    <input type='file' id='pma-pickup-csv' name='pma-pickup-csv' accept='.csv'>
                </div>
                <p>
                    <input type="submit" name='pma-pickup-csv-import' class='button button-primary' value='Import File' />
                </p>
            </form>

            <!-- Start Latest Imported -->
        <?php if(!empty($all_latest_records)){ ?>
            <div style='height: 30px;'></div>
            <h2 class='wp-subheading-inline'>Latest imported records</h2>
            <table class='wp-list-table widefat fixed'>
                <thead>
                    <tr>
                        <th><strong>city</strong></th>
                        <th><strong>travel</strong></th>
                        <th><strong>address</strong></th>
                        <th><strong>pincode</strong></th>
                        <th><strong>map</strong></th>
                        <th><strong>rate</strong></th>
                        <th><strong>status</strong></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    
                    foreach( $all_latest_records as $latest_single_record ){

                       
                ?>
                    <tr>
                        <td><?php echo $latest_single_record['city']; ?></td>
                        <td><?php echo $latest_single_record['travel']; ?></td>
                        <td><?php echo $latest_single_record['address']; ?></td>
                        <td><?php echo $latest_single_record['pincode']; ?></td>
                        <td><?php echo $latest_single_record['map']; ?></td>
                        <td><?php echo $latest_single_record['rate']; ?></td>
                        <td><?php echo $latest_single_record['status']; ?></td>
                    </tr>
                <?php
                    }
                ?>
                </tbody>
            </table>
        <?php } ?>
        <!-- End Latest imported -->

        <!-- All Pickup records -->
        <div style='height: 30px;'></div>
        <h2 class='wp-subheading-inline'>All Pickup Points</h2>
        <table class='wp-list-table widefat fixed'>
                <thead>
                    <tr>
                        <th><strong>id</strong></th>
                        <th><strong>city</strong></th>
                        <th><strong>travel</strong></th>
                        <th><strong>address</strong></th>
                        <th><strong>pincode</strong></th>
                        <th><strong>map</strong></th>
                        <th><strong>rate</strong></th>
                        <th><strong>status</strong></th>
                    </tr>
                </thead>
                <tbody>
        <?php
            $pickup_all_data = $wpdb->get_results(" SELECT * FROM $pickup_table");

            foreach( $pickup_all_data as $pickup_record )
            {
                ?>
                    <tr>
                        <td><?php echo $pickup_record->id; ?></td>
                        <td><?php echo $pickup_record->city; ?></td>
                        <td><?php echo $pickup_record->travel; ?></td>
                        <td><?php echo $pickup_record->address; ?></td>
                        <td><?php echo $pickup_record->pincode; ?></td>
                        <td><?php echo $pickup_record->map; ?></td>
                        <td><?php echo $pickup_record->rate; ?></td>
                        <td><?php echo $pickup_record->status; ?></td>
                    </tr>

                <?php
            }

        ?>
        <!-- End All pickup records -->
        </div>
    <?php
}

// End Admin Page

add_action('woocommerce_order_status_changed', 'seccow_send_email', 10, 4);

function seccow_send_email($order_id, $old_status, $new_status, $order) {
    if ($new_status == 'failed') {
		$wc_emails = WC()->mailer()->get_emails();
        // change the recipient of this instance
        $wc_emails['WC_Email_Failed_Order']->recipient = 'devgad@devgadmango.com';
        // Sending the email from this instance
        $wc_emails['WC_Email_Failed_Order']->trigger( $order_id );
		
        $email_cliente = $order->get_billing_email();
        $pay_now_url = add_query_arg(array(
            'order' => $order_id,
            'nmpm_action' => 'repay',
                ), site_url());
        $headers = array('Content-Type: text/html; charset=UTF-8');
/* Start v1 Email Template | Commented by Abhijeet*/
//         $emsg = 'We feel bad when a valuable customer like you is just one step away from a lip-smacking taste!<br/>';
//         $emsg .= 'Click the following link and complete your order.<br/>';
//         $emsg .= '<a href="' . $pay_now_url . '">Click Here.</a>';
        /* End v1 Email Template | Commented by Abhijeet*/
		/* Start v2 Email Template | Added by Abhijeet*/
		$emsg .= '<div style="background-color: #f7f7f7; margin: 0; padding: 70px 0; width: 100%; -webkit-text-size-adjust: none;">
        <table border="0" width="100%" cellspacing="0" cellpadding="0">
            <tbody>
                <tr>
                    <td align="center" valign="top">
                        <div id="template_header_image">
                            <p style="margin-top: 0;text-align: center;"><img class="alignnone"
                                    style="border: none; display: inline-block; font-size: 14px; font-weight: bold; height: auto; outline: none; text-decoration: none; text-transform: capitalize; vertical-align: middle; margin-left: 0; margin-right: 0;"
                                    src="https://devgadmango.com/wp-content/uploads/2019/11/logo-devgad.png"
                                    alt="Devgad Mango" /></p>
    
                        </div>
                        <table id="template_container" style="background-color: #ffffff; border: 1px solid #dedede; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1); border-radius: 3px;" border="0" width="600" cellspacing="0" cellpadding="0">
                            <tbody>
                                <tr>
                                    <td align="center" valign="top">
                                        <!-- Header -->
                                        <table id="template_header"
                                            style="background-color: #ffb300; color: #202020; border-bottom: 0; font-weight: bold; line-height: 100%; vertical-align: middle; font-family: Helvetica Neue, Helvetica, Roboto, Arial, sans-serif; border-radius: 3px 3px 0 0;"
                                            border="0" width="600" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td id="header_wrapper" style="padding: 36px 48px; display: block;">
                                                        <h1 style="font-family: Helvetica Neue, Helvetica, Roboto, Arial, sans-serif; font-size: 30px; font-weight: 300; line-height: 150%; margin: 0; text-align: left; text-shadow: 0 1px 0 #ffc233; color: #202020;">Order failed! You are just one step away!</h1>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <!-- End Header -->
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" valign="top">
                                        <!-- Body -->
                                        <table id="template_body" border="0" width="600" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td id="body_content" style="background-color: #ffffff;" valign="top">
                                                        <!-- Content -->
                                                        <table border="0" width="100%" cellspacing="0" cellpadding="20">
                                                            <tbody>
                                                                <tr>
                                                                    <td style="padding: 48px 48px 32px;" valign="top">
                                                                        <div id="body_content_inner"
                                                                            style="color: #636363; font-family: Helvetica Neue, Helvetica, Roboto, Arial, sans-serif; font-size: 14px; line-height: 150%; text-align: left;">
                                                                            <p style="margin: 0 0 16px;">We feel bad when a valuable customer like you is just one step away from a lip-smacking taste!</p>
                                                                            <p style="margin: 0 0 16px;">Click the following link and complete your order.</p>
    
                                                                            <p style="margin: 0 0 16px;"><a href="'.$pay_now_url.'"><span style="background-color: #f96c00; color: #fff; border-radius: 30px; display: inline-block; padding: 8px 16px; font-weight: 600; font-size: 16px;">Click here</span></a></p>
                                                                            
                                                                            <p style="margin: 0 0 16px;">Thanks,</p>
    
                                                                            <p style="margin: 0 0 16px;">देवगड तालुका आंबा उत्पादक सहकारी संस्था मर्यादित <br/>
                                                                                At Post. Jamsande, Tal. Devgad, Dist Sindhudurg, Maharashtra, India.<br/>
                                                                                https://devgadmango.com</p>
    
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <!-- End Content -->
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <!-- End Body -->
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="center" valign="top"><!-- Footer -->
                        <table id="template_footer" style="background-color: #391d00; color: #fff; font-size: 14px; font-family: Helvetica Neue, Helvetica, Roboto, Arial, sans-serif; margin-top: -20%; padding: 20px;" border="0" width="600" cellspacing="0" cellpadding="1">
                        <tbody>
                        <tr>
                        <td style="margin: 0 0; text-align: left;"><a class="link" style="font-weight: normal; text-decoration: underline; color: #ffb300;" href="https://devgadmango.com/about-devgad-alphonso-mango/">About Devgad Mango</a> / <a class="link" style="font-weight: normal; text-decoration: underline; color: #ffb300;" href="https://devgadmango.com/product/mango/">Order Mangoes</a> / <a class="link" style="font-weight: normal; text-decoration: underline; color: #ffb300;" href="https://devgadmango.com/blog/">Blog</a></td>
                        <td style="margin: 0 0; text-align: right;">©2019-20 Devgad Mango</td>
                        </tr>
                        </tbody>
                        </table>
                        <!-- End Footer --></td>
                        </tr>
                        </tbody>
                        </table>
                        </div>';
        if(get_post_meta($order->get_id(),"nmpm_fail_notification",true)!='sent' &&  (date("Y-m-d H:i:s",strtotime("+1 day",strtotime($order->get_date_created()))) > date("Y-m-d H:i:s"))){
            update_post_meta($order->get_id(),"nmpm_fail_notification",'sent');
        wp_mail($email_cliente, 'Devgad Mango Order failed! You are just one step away!', $emsg, $headers);
        /** Start SMS Integration */
        $mobile_number = $order->billing_phone;
        $message .= 'Order failed.';
        $message .= 'You can repay with the following URL: ' . $pay_now_url;
        send_sms($mobile_number, $message);
        
        }
        /** End SMS Integration */
    } else if ($new_status == 'cancelled') {
        $order = new WC_Order($order_id);
        $order->update_status('failed', 'Changed cancelled to failed');
    } else if ($new_status == 'processing') {
        $order = new WC_Order($order_id);

        $order_data = $order->get_data();

        $dispatch_week = '';
        $order_size = '';
        $total_price = $order->get_total();

        $ordered_items = $order->get_items();
        foreach ($ordered_items as $item) {
            $product_name = $item['name'];
            $product_id = $item['product_id'];
            $product_variation_id = $item['variation_id'];
            $product = wc_get_product($item['variation_id']);
            // var_dump($product->get_attributes());
            // var_dump($product->get_attribute('dispatch-week'));
            if( $product_variation_id){
            $dispatch_week = $product->get_attribute('dispatch-week');
            $order_size = $product->get_attribute('size');
			}
        }

        /** Start SMS Integration */
        $mobile_number = $order->billing_phone;
        $message .= 'Thank you for your order ' . $order_id . '\n';
        $message .= 'Dispatch Week is ' . $dispatch_week . '\n';
        $message .= 'Mango Size is ' . $order_size . '\n';
        $message .= 'Total Price is ' . $total_price;
        send_sms($mobile_number, $message);
        /** End SMS Integration */
    }
}

add_action('template_redirect', 'redirect_for_repay');

function redirect_for_repay() {
    if (isset($_GET['order']) && isset($_GET['nmpm_action']) && $_GET['nmpm_action'] == 'repay') {
        $order = wc_get_order($_GET['order']);
        if (!empty($order)) {
            wp_safe_redirect($order->get_checkout_payment_url());
            die();
        } else {
            wp_safe_redirect(site_url());
            die();
        }
    }
}

/* Function that returns custom product hyperlink */

function wc_cart_item_name_hyperlink($link_text, $product_data) {
    $title = get_the_title($product_data['product_id']);
    if ($title == 'Order Devgad Mangoes') {
        $link_text = 'Devgad Mango';
    }
    return $link_text;
}

/* Filter to override cart_item_name */
add_filter('woocommerce_cart_item_name', 'wc_cart_item_name_hyperlink', 10, 2);

/* add_filter( 'gform_input_masks', 'nmpm_add_mask' );
  function nmpm_add_mask( $masks ) {
  $masks['No Lead 0'] = '[1-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]';
  return $masks;
  } */
add_filter('gform_phone_formats', 'uk_phone_format');

function uk_phone_format($phone_formats) {
    $phone_formats['no_lead_zero'] = array(
        'label' => 'No Lead 0',
        //'mask'        => '9999999999',
        'regex' => '/[6-9]{1}[0-9]{9}/', //'/^[1-9][0-9]{9}$/', //'/[1-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]/',
        'instruction' => "No leading 0 Indian 10 digit",
    );

    return $phone_formats;
}

add_action('restrict_manage_posts', 'nmpm_order_page_filter_html', 50);

function nmpm_order_page_filter_html() {
    global $pagenow;
    if (( $pagenow == 'edit.php' ) && ($_GET['post_type'] == 'shop_order')) {
        $product = wc_get_product(14);
        $attributes = $product->get_attributes();
        $week = array();
        $week = explode('|', $attributes['dispatch-week']['value']);
        $size = array();
        $size = explode('|', $attributes['size']['value']);
        echo'<select name="week[]" id="filter-by-week" multiple="multiple" style="height: auto;"><option value="">Select Week</option>';
        foreach ($week as $w) {
            $selected = '';
            if (isset($_GET['week']) && in_array(trim($w), $_GET['week'])) {
                $selected = 'selected';
            }
            echo '<option value="' . trim($w) . '" ' . $selected . '>' . trim($w) . '</option>';
        }
        echo'</select>';
        echo'<select name="size[]" id="filter-by-size" multiple="multiple" style="height: auto;"><option value="" >Select Size</option>';
        foreach ($size as $s) {
            $selected = '';
            if (isset($_GET['size']) && in_array(trim($s), $_GET['size'])) {
                $selected = 'selected';
            }
            echo '<option value="' . trim($s) . '" ' . $selected . '>' . trim($s) . '</option>';
        }
        echo'</select>';
    }
}

//Code Idea From : WooCommerce Filter Orders by Product
//https://wordpress.org/plugins/woocommerce-filter-orders-by-product/
add_filter('posts_join', 'nmpm_filter_order_search_join');

function nmpm_filter_order_search_join($join) {
    global $pagenow, $wpdb;

    if (( $pagenow == 'edit.php' ) && ($_GET['post_type'] == 'shop_order')) {
        //$join .= 'LEFT JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
        if ((isset($_GET['size']) && !empty($_GET['size'])) || (isset($_GET['week']) && !empty($_GET['week']))) {
            $join .= "LEFT JOIN {$wpdb->prefix}woocommerce_order_items as order_items ON {$wpdb->prefix}posts.ID=order_items.order_id
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta1 ON order_items.order_item_id = order_item_meta1.order_item_id
        ";
        }
    }
    return $join;
}

add_filter('posts_where', 'nmpm_filter_order_where');

function nmpm_filter_order_where($where) {
    global $pagenow, $wpdb;
    if (( $pagenow == 'edit.php' ) && ($_GET['post_type'] == 'shop_order')) {
        if (isset($_GET['size']) && !empty($_GET['size'])) {
            $size = join("','", $_GET['size']);
            $where .= " AND order_item_meta.meta_key = 'size' AND order_item_meta.meta_value in ('$size') ";
        }
        if (isset($_GET['week']) && !empty($_GET['week'])) {
            $week = join("','", $_GET['week']);
            $where .= " AND order_item_meta1.meta_key = 'dispatch-week' AND order_item_meta1.meta_value in ('$week') ";
        }

        $where .= " group by {$wpdb->prefix}posts.ID ";
    }
    return $where;
}

add_action('rest_api_init', function () {
    register_rest_route('cron', 'fail_orders', array(
        'methods' => 'GET',
        'callback' => 'send_email_list_of_fail_order'
    ));
});

function send_email_list_of_fail_order() {
    global $woocommerce;
    $fileu = get_stylesheet_directory() . "/csv/" . date("Y-m-d") . ".csv";
    $csv = '';
    if ($fp = fopen($fileu, 'w+')) {
        $title = array();
        $title = array(
            'ID',
            'Name',
            'Email',
            'Phone',
            'Address',
            'Pincode',
            'Product',
            'Quantity',
            'Amount',
            'Date created',
            'Checkout Url'
        );

        fputcsv($fp, $title);
        $query = new WC_Order_Query(array(
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
            'date_created' => date('d.m.Y', strtotime("-1 days")),
            'status' => array('wc-cancelled', 'wc-failed')
        ));
        $orders = $query->get_orders();
        foreach ($orders as $order) {
            $order = wc_get_order($order);
            $order_data = $order->get_data();

            foreach ($order->get_items() as $item_key => $item) {

                $item_data = $item->get_data();
                $variation = wc_get_product($item_data['variation_id']);

                $product = $variation->get_formatted_name($variation);
                $product = preg_replace('/<[^>]*>/', '', $product);
                $quantity = $item->get_quantity();
                break;
            }
            $pay_now_url = add_query_arg(array(
                'order' => $order->get_id(),
                'nmpm_action' => 'repay',
                    ), site_url());
            $title = array(
                $order->get_id(),
                $order_data['billing']['first_name'],
                $order_data['billing']['email'],
                $order_data['billing']['phone'],
                $order_data['billing']['address_1'] . ", " . $order_data['billing']['address_2'] . ", " . $order_data['billing']['city'] . ", " . $order_data['billing']['state'],
                $order_data['billing']['postcode'],
                $product,
                $quantity,
                $order->get_total(),
                date('d-m-Y', $order_data['date_created']->getTimestamp()),
                $pay_now_url
            );
            fputcsv($fp, $title);
        }
        fclose($fp);
        // $file=stream_get_contents($fp);

        $headers = 'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>';
        $subject = " Failed Orders for " . date('jS F Y', strtotime('-1 days'));
        $message = "Here is list of failed Orders";
        //$attachment_failed_entries = chunk_split(base64_encode($file));
        $mail_attachment = array($fileu);
        $to = array('nishit.m@pugmarker.com');
		if(count($orders) > 0){
			wp_mail($to, $subject, $message, $headers, $mail_attachment);
		}
        unlink($fileu);
    }
    $response = new WP_REST_Response();
    $response->set_status(200);
    return $response;
}

add_filter('woocommerce_create_account_default_checked', function ($checked) {
    return true;
});

/**
 * SMS Integration MSG91 
 * Abhijeet Sonawane
 * @param mobile_number
 * @param message
 */
function send_sms($mobile_number, $message) {

    if (get_option('pmas_sms_mode') === 'live') {
        $AUTH_KEY = get_option('pmas_sms_live_api_key');
        $SENDER = get_option('pmas_sms_live_sender_id');
    } else if (get_option('pmas_sms_mode') === 'sandbox') {
        $AUTH_KEY = get_option('pmas_sms_sandbox_api_key');
        $SENDER = get_option('pmas_sms_sandbox_sender_id');
    }

    if (get_option('pmas_is_sms_active') === 'on') {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.msg91.com/api/v2/sendsms?country=91",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{ \"sender\": \"" . $SENDER . "\", \"route\": \"4\", \"country\": \"91\", \"sms\": [ { \"message\": \"" . $message . "\", \"to\": [ \"" . $mobile_number . "\" ] } ] }",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "authkey: " . $AUTH_KEY . "",
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        }
    }
}

// SMS Integration Page
function aspm_wp_dashboard_products_sms_page_callback() {

    if (isset($_POST['sms_is_active_submit'])) {

        $is_sms_integration_enabled = $_POST['is_sms_integration_enabled'];
        if (isset($_POST['is_sms_integration_enabled'])) {
            update_option('pmas_is_sms_active', $is_sms_integration_enabled);
        } else {
            update_option('pmas_is_sms_active', 'off');
        }
        echo'<div class="notice notice-success is-dismissible"><p>';
        _e('Successfully Updated!', 'sample-text-domain');
        echo'</p></div>';
    }

    if (isset($_POST['sms_mode_submit'])) {
        // Update SMS Mode
        if (isset($_POST['pmas_sms_mode'])) {
            update_option('pmas_sms_mode', $_POST['pmas_sms_mode']);
        }
        // Update Live API Key
        if (isset($_POST['pmas_sms_live_api_key'])) {
            update_option('pmas_sms_live_api_key', $_POST['pmas_sms_live_api_key']);
        }
        // Update Sandbox API Key
        if (isset($_POST['pmas_sms_sandbox_api_key'])) {
            update_option('pmas_sms_sandbox_api_key', $_POST['pmas_sms_sandbox_api_key']);
        }
        // Update Live Sender ID
        if (isset($_POST['pmas_sms_live_sender_id'])) {
            update_option('pmas_sms_live_sender_id', $_POST['pmas_sms_live_sender_id']);
        }
        // Update Sandbox Sender ID
        if (isset($_POST['pmas_sms_sandbox_sender_id'])) {
            update_option('pmas_sms_sandbox_sender_id', $_POST['pmas_sms_sandbox_sender_id']);
        }
        echo'<div class="notice notice-success is-dismissible"><p>';
        _e('Successfully Updated!', 'sample-text-domain');
        echo'</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading">MSG91 SMS Integration</h1>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th style="width: 30%">
                            <label for="is_sms_integration_enabled">Activate or Deactivate SMS Integration</label>
                        </th>
                        <td>
                            <input type="checkbox" name="is_sms_integration_enabled" id="is_sms_integration_enabled" <?php
                            if (get_option('pmas_is_sms_active') === 'on') {
                                echo 'checked';
                            } else if (get_option('pmas_is_sms_active') === 'off') {
                                echo ' ';
                            }
                            ?>>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit"><input type="submit" name="sms_is_active_submit" id="submit" class="button button-primary" value="Save Changes"></p>
        </form>

        <div style="height: 30px; width: 5px;"></div>

        <h2 class="wp-subheading">SMS Mode</h2>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th></th>
                        <th><label for="pmas_sms_mode_live">Live Mode </label><input type="radio" name="pmas_sms_mode" value="live" id="pmas_sms_mode_live" <?php
                            if (get_option('pmas_sms_mode') === 'live') {
                                echo 'checked';
                            } else {
                                echo '';
                            }
                            ?>></th>
                        <th><label for="pmas_sms_mode_sandbox">Sandbox Mode </label><input type="radio" name="pmas_sms_mode" value="sandbox" id="pmas_sms_mode_sandbox" <?php
                            if (get_option('pmas_sms_mode') === 'sandbox') {
                                echo 'checked';
                            } else {
                                echo '';
                            }
                            ?>></th>
                    </tr>
                    <tr>
                        <th>API Key</th>
                        <th><input type="text" name="pmas_sms_live_api_key" id="pmas_sms_live_api_key" class="regular-text" value="<?php
                            if (get_option('pmas_sms_live_api_key')) {
                                echo get_option('pmas_sms_live_api_key');
                            }
                            ?>"></th>
                        <th><input type="text" name="pmas_sms_sandbox_api_key" id="pmas_sms_sandbox_api_key" class="regular-text" value="<?php
                            if (get_option('pmas_sms_sandbox_api_key')) {
                                echo get_option('pmas_sms_sandbox_api_key');
                            }
                            ?>"></th>
                    </tr>
                    <tr>
                        <th>Sender ID</th>
                        <th><input type="text" name="pmas_sms_live_sender_id" id="pmas_sms_live_sender_id" class="regular-text" maxlength='6' style="text-transform: uppercase;" value="<?php
                            if (get_option('pmas_sms_live_sender_id')) {
                                echo get_option('pmas_sms_live_sender_id');
                            }
                            ?>"></th>
                        <th><input type="text" name="pmas_sms_sandbox_sender_id" id="pmas_sms_sandbox_sender_id" class="regular-text" maxlength='6' style="text-transform: uppercase;" placeholder="Sender ID will not work for Sandbox" value="<?php
                            if (get_option('pmas_sms_sandbox_sender_id')) {
                                echo get_option('pmas_sms_sandbox_sender_id');
                            }
                            ?>"></th>
                    </tr>
                </tbody>
            </table>
            <p class="submit"><input type="submit" name="sms_mode_submit" id="submit" class="button button-primary" value="Save Changes"></p>
        </form>


    </div>
    <?php
}

/*
 * Start Payment Integration *
 */


add_action('gform_after_submission_3', 'mango_bonds_instamojo_payment', 10, 2);

function mango_bonds_instamojo_payment($entry, $form) {

    $payment_amount = '25000';
    $payment_purpose = 'Mango Bonds';
    $mobile_number = rgar($entry, '3');
    $first_name = rgar($entry, '18.3');
    $last_name = rgar($entry, '18.6');
    $email = rgar($entry, '4');

    $entry_id = $entry['id'];
    $source_url = $entry['source_url'];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://test.instamojo.com/api/1.1/payment-requests/');
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Api-Key:test_a01b44cd4fa19d1266816e58a48",
        "X-Auth-Token:test_189523b50f008c5f49579e2bd7a"));
    $payload = Array(
        'purpose' => $payment_purpose,
        'amount' => $payment_amount,
        'phone' => $mobile_number,
        'buyer_name' => $first_name . ' ' . $last_name,
        'redirect_url' => site_url() . '/thank-you/?entry_id=' . $entry_id,
        'send_email' => false,
        'webhook' => '',
        'send_sms' => false,
        'email' => $email,
        'allow_repeated_payments' => false
    );
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response, TRUE);

    gform_update_meta($entry_id, 'payment_amount', $payment_amount);
    gform_update_meta($entry_id, 'payment_status', $response['payment_request']['status']);
    gform_update_meta($entry_id, 'payment_url', $response['payment_request']['longurl']);


    // echo $response['payment_request']['status'];

    $redirect_url = $response['payment_request']['longurl'];

    wp_redirect($redirect_url);
}

/**
 * Get Transaction Status from Request ID
 * @param $request_id
 * @return array
 */
function get_transaction_status($request_id) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://test.instamojo.com/api/1.1/payments/' . $request_id . '/');
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Api-Key:test_a01b44cd4fa19d1266816e58a48",
        "X-Auth-Token:test_189523b50f008c5f49579e2bd7a"));

    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response, true);

    return $response;
}

/*
 * End Payment Integration *
 */

/*
  Code By : Nishit Manjarawala
  Document : https://docs.google.com/document/d/1-IH-m4407lBydeIsFE3ghtINw7TTRmqh5X2gnLh9MYA/edit?usp=sharing
 */
add_shortcode('pmnm_button', 'pmnm_button_funct');

function pmnm_button_funct($attr) {
    $class = "";
    $id = "";
    $href = "/product/mango/";
    if (isset($attr["text"]) && !empty($attr["text"])) {
        $text = $attr["text"];
    } else {
        $text = 'Click Here to Order Devgad Mango';
    }

    if (isset($attr["class"]) && !empty($attr["class"])) {
        $class = $attr["class"];
    }

    if (isset($attr["id"]) && !empty($attr["id"])) {
        $id = $attr["id"];
    }

    if (isset($attr["href"]) && !empty($attr["href"])) {
        $href = $attr["href"];
    }
    return '<a id="' . $id . '" class="pmas-blog-a-button ' . $class . '" href="' . $href . '">' . $text . '</a>';
}

/* Auto populate Checkout gravityform */
add_filter('gform_field_value_uname', 'pmnm_autopopulate_uname');

function pmnm_autopopulate_uname($value) {
    if (isset($_COOKIE['uname'])) {
        return $_COOKIE['uname'];
    }
    return '';
}

add_filter('gform_field_value_email', 'pmnm_autopopulate_email');

function pmnm_autopopulate_email($value) {
    if (isset($_COOKIE['email'])) {
        return $_COOKIE['email'];
    }
    return '';
}

add_filter('gform_field_value_mobile', 'pmnm_autopopulate_mobile');

function pmnm_autopopulate_mobile($value) {
    if (isset($_COOKIE['phone'])) {
        return $_COOKIE['phone'];
    }
    return '';
}

add_filter('gform_field_value_pincode', 'pmnm_autopopulate_pincode');

function pmnm_autopopulate_pincode($value) {
    if (isset($_COOKIE['pincode'])) {
        return $_COOKIE['pincode'];
    }
    return '';
}

/*
  Pay for order without login
  Blog : https://businessbloomer.com/woocommerce-allow-to-pay-for-order-without-login/
 */
add_filter('user_has_cap', 'nmpm_order_pay_without_login', 9999, 3);

function nmpm_order_pay_without_login($allcaps, $caps, $args) {
    if (isset($caps[0], $_GET['key'])) {
        if ($caps[0] == 'pay_for_order') {
            $order_id = isset($args[2]) ? $args[2] : null;
            $order = wc_get_order($order_id);
            if ($order) {
                $allcaps['pay_for_order'] = true;
            }
        }
    }
    return $allcaps;
}

/**
 * Trim zeros in price decimals
 * */
add_filter('woocommerce_price_trim_zeros', '__return_true');

function pmn_body_classes( $classes ) {

    global $woocommerce;
	global $product;

//     if ( is_product() ) { //Only For Product Page
//         $dispatch_weeks = $product->get_attribute( 'dispatch-week' );
//         if( empty($dispatch_weeks) ){
//             $classes[] = 'pmn-season-ends';
//         }
//     }
//     

//     if ( is_product() ) { //Only For Product Page
//     	$current_date = date('Y-m-d');
//         if( $current_date > '2020-05-30' ){
//             $classes[] = 'pmn-season-ends';
//         }
//     }

    if( $woocommerce->cart->cart_contents_count == 0){
        $classes[] = 'pmn-cart-empty';
    } else {
        $classes[] = 'pmn-cart-fulfilled';
    }

    return $classes;
}
add_filter( 'body_class', 'pmn_body_classes' );

/**
 * Get Products and Cart Total
 * Dev: NIKHIL BHANSI
 */
function cart_details(){
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $cart_total = $woocommerce->cart->get_cart_total();
    $cart_without_currency = str_replace('Rs.', '', $woocommerce->cart->get_cart_total());
    $cart_total_without_currency = floatval(preg_replace('#[^\d.]#', '', $cart_without_currency));
    $cart_details = [];
    $cart_details['cart_total'] = number_format($cart_total_without_currency);
	$cart_details['cart_total_amount'] = $cart_total_without_currency;
   //$cart_details['cart_total_with_currency'] = $cart_total;

    $pq = 0; //Products
    foreach ($items as $item => $values) {

        $cart_details['products'][$pq]['product_key'] = $values['key'];
        $cart_details['products'][$pq]['product_id'] = $values['product_id'];
        $cart_details['products'][$pq]['variation_id'] = $values['variation_id'];
        $cart_details['products'][$pq]['dispatch_week'] = $values['variation']['attribute_dispatch-week'];
        $cart_details['products'][$pq]['product_size'] = $values['variation']['attribute_size'];
        $cart_details['products'][$pq]['quantity'] = $values['quantity'];
        $cart_details['products'][$pq]['total'] = number_format($values['line_total']);
        $pq++;

    }

    wp_send_json($cart_details);
    die();
}
add_action('wp_ajax_cart_details', 'cart_details');
add_action('wp_ajax_nopriv_cart_details', 'cart_details');

function pmn_ajax_add_to_cart(){
    global $woocommerce;
    $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
    $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);
    $variation_id = absint($_POST['variation_id']);
    $dispatch_week = $_POST['dispatch_week'];
    $product_size = $_POST['product_size'];
    $variation    = array(
        'attribute_dispatch-week' => $dispatch_week,
        'attribute_size'  => $product_size
    );
    $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
    $product_status = get_post_status($product_id);

    $items = $woocommerce->cart->get_cart();
    $cart_item_key = get_cart_item_key( $items, $variation_id );
    $in_cart_quantity = $items[$cart_item_key]['quantity'];
    $total_quantity = $in_cart_quantity + $quantity;

    if( $total_quantity <= 10 ) {

        if ( $passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation) && 'publish' === $product_status) {
            $response = cart_details();
        }

    } else {
        $response['status'] = 'Error';
        $response['message'] = 'You can only order 10 Dozen of the same mango grade in the same week.';
    }

    wp_send_json($response);
    die();
}
add_action('wp_ajax_pmn_ajax_add_to_cart', 'pmn_ajax_add_to_cart');
add_action('wp_ajax_nopriv_pmn_ajax_add_to_cart', 'pmn_ajax_add_to_cart');

function pmn_delete_cart_product(){
    $cart_item_key = $_POST['cart_item_key'];
    $response = WC()->cart->remove_cart_item($cart_item_key);
    if( $response == true ){
        $status = "Success";
    } else {
        $status = "Error";
    }

    echo $status;
    die();
}
add_action('wp_ajax_pmn_delete_cart_product', 'pmn_delete_cart_product');
add_action('wp_ajax_nopriv_pmn_delete_cart_product', 'pmn_delete_cart_product');

function update_product_quantity(){

    global $woocommerce;
    $cart_item_key = $_POST['cart_item_key'];
    $type = $_POST['type'];
    $items = $woocommerce->cart->get_cart();
    $current_quantity = $items[$cart_item_key]['quantity'];

    if( $type == "increase" ){
        $product_quantity = $current_quantity + 1;
    } else {
        $product_quantity = $current_quantity - 1;
    }

    $response = $woocommerce->cart->set_quantity( $cart_item_key, $product_quantity, true );

    if( $response == true ){
        $status = "Success";
    } else {
        $status = "Error";
    }

    echo $status;
    die();
}
add_action('wp_ajax_update_product_quantity', 'update_product_quantity');
add_action('wp_ajax_nopriv_update_product_quantity', 'update_product_quantity');

/**
 * Get Cart Item Key from Variation ID
 * Dev: NIKHIL BHANSI
 * @param $items
 * @param $variation_id
 * @return int|string
 */
function get_cart_item_key( $items, $variation_id ){

    foreach ($items as $key => $product) {

        if ($product['variation_id'] == $variation_id) {
            return $key;
        }

    }
}

function pmn_cart_icon_content() {

    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $count = count($items);

    if ($count === 0) {
        ?>
        <span class="count pm-zero-count">0</span>
    <?php } elseif (is_product()) {
        ?>
        <a class="cart-contents"  title="<?php esc_attr_e('View your shopping cart', 'storefront'); ?>">
            <?php /* translators: %d: number of items in cart */ ?>
            <?php echo wp_kses_post(WC()->cart->get_cart_subtotal()); ?> <span class="count"><?php echo wp_kses_data(sprintf(_n('%d', '%d', $count, 'storefront'), $count)); ?></span>
        </a>
    <?php } else {
        ?>
        <a class="cart-contents" href="<?php echo site_url(); ?>/product/mango/" title="<?php esc_attr_e('View your shopping cart', 'storefront'); ?>">
            <?php /* translators: %d: number of items in cart */ ?>
            <?php echo wp_kses_post(WC()->cart->get_cart_subtotal()); ?> <span class="count">101</span>
        </a>
        <?php
    }
}

/**
 * Start Send Email Notifications to User
 * If Check Pincode is Deliverable but user not proceed to order.
 * Abhijeet Sonawane
 */

/**
 * Custom Notification Events for Uberized Services
 * @param array $notification_events
 * @return type
 */
function add_event($notification_events) {
    $notification_events['deliverable_but_not_ordered'] = __('Deliverable but not ordered', 'gravityforms');
    return $notification_events;
}
add_filter('gform_notification_events', 'add_event');

/**
 * Send Email Notification on Custom Notification Event
 * @param $form_id
 * @param $entry_id
 * @param $notification_event
 * @return array
 */
function send_email_notifications($form_id, $entry_id, $notification_event) {

    $form = RGFormsModel::get_form_meta($form_id);
    $entry = RGFormsModel::get_lead($entry_id);
    $status = GFAPI::send_notifications($form, $entry, $notification_event);

    return $status;
}

add_action('rest_api_init', function () {
    register_rest_route( 'cron', 'deliverable_but_not_ordered',array(
        'methods'  => 'GET',
        'callback' => 'send_email_deliverable_but_not_ordered'
    ));
});

function send_email_deliverable_but_not_ordered(){
    $form_id = 2;

    $search_criteria = array(
        'field_filters' => array(
            'mode' => 'any',
            array(
                'key'   => 6,
                'value' => 'yes'
            )
        )
    );
    $search_criteria['start_date'] = date("Y/m/d",strtotime("-1 days"));
    $search_criteria['end_date'] = date("Y/m/d",strtotime("-1 days"));
    $search_criteria['status'] = 'active';

    $entries = GFAPI::get_entries( $form_id, $search_criteria );

    $email_list = array();

    foreach( $entries as $entry ){
        $email = rgar( $entry, 2 );
        array_push($email_list, $email);
    }

    $unique_user_list = array_unique($email_list);


    $query = new WC_Order_Query(array(
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'ids',
        'date_created'=>date('d.m.Y',strtotime("-1 days")),
    ));
    $orders = $query->get_orders();

    // Get Email of Order
    $ordered_email_list = array();
    foreach($orders as $order){
        $order = wc_get_order( $order );
        $order_data = $order->get_data();
        $shipping_email = $order_data['billing']['email'];

        array_push($ordered_email_list, $shipping_email);
    }
    // var_dump($ordered_email_list);

    // Check User Ordered or not and send email to User
    foreach( $unique_user_list as $user_email ){
        if(!in_array($user_email, $ordered_email_list)){
//             echo '<br/>'.$user_email.' => Send Me the Email';


            $search_criteria = array(
                'field_filters' => array(
                    'mode' => 'all',
                    array(
                        'key'   => 6,
                        'value' => 'yes'
                    ),
                    array(
                        'key' => 2,
                        'value' => $user_email
                    )
                )
            );
            $search_criteria['start_date'] = date("Y/m/d",strtotime("-1 days"));
            $search_criteria['end_date'] = date("Y/m/d",strtotime("-1 days"));
            $search_criteria['status'] = 'active';
            $sorting = array( 'key' => 'id', 'direction' => 'DESC' );
            $paging  = array( 'offset' => 0, 'page_size' => 1 );
            $entries = GFAPI::get_entries( $form_id, $search_criteria, $sorting, $paging );

            $entry_id = $entries[0]['id'];
            // var_dump($entries[0]['id']);
            // Remind User
            send_email_notifications($form_id, $entry_id, 'deliverable_but_not_ordered');

//             GFAPI::add_note( $entry_id, 'Deliverable but not ordered email sent', 'success' );


        }
//         else{
//             echo '<br/>'.$user_email.' => Don\'t send email';
//         }
    }

    $response = new WP_REST_Response();
    $response->set_status(200);
    return $response;
}

add_action( 'template_redirect', 'add_get_paramters_in_cookies' );

function add_get_paramters_in_cookies(){
	if(isset($_GET['uname'])){
		setcookie('uname', $_GET['uname'], time() + 86400, '/');
	}
	if(isset($_GET['pincode'])){
		setcookie('pincode', $_GET['pincode'], time() + 86400, '/');
	}
	if(isset($_GET['email'])){
		setcookie('email', $_GET['email'], time() + 86400, '/');
	}
	if(isset($_GET['phone'])){
	    setcookie('phone', $_GET['phone'], time() + 86400, '/');
	}
}

/**
 * End Send Email Notifications to User
 * If Check Pincode is Deliverable but user not proceed to order.
 */

//Include Manage Locations | NIKHIL
if( file_exists(get_stylesheet_directory() . '/manage-locations/manage-locations.php') ){
    require_once get_stylesheet_directory() . '/manage-locations/manage-locations.php';
}
//Include Manage Locations | NIKHIL
//
//// Abhijeet | Pickup Module

function pmas_update_pickup_charge()
{
    global $wpdb;
    $item_count = WC()->cart->get_cart_contents_count();
    if (isset($_POST['pickup_id'])) {
        $pickup_id = $_POST['pickup_id'];
        $result = $wpdb->get_row("select * from " . $wpdb->prefix . "pickup where id='".$pickup_id."'");
        WC()->session->set('shipping_calculated_cost', $result->rate * $item_count);
    }
    die();
}
add_action('wp_ajax_pmas_update_pickup_charge', 'pmas_update_pickup_charge');
add_action('wp_ajax_nopriv_pmas_update_pickup_charge', 'pmas_update_pickup_charge');

// End Pickup Module
// Set Cookie after order | Abhijeet
add_action('woocommerce_checkout_order_processed', 'pmas_save_form_values_in_cookies', 1, 1);
function pmas_save_form_values_in_cookies($order_id){
    $order = new WC_Order( $order_id );
    $address1 = $order->get_billing_address_1();
    $address2 = $order->get_billing_address_2();
    $delivery_mode = get_post_meta( $order_id, 'delivery_mode', true );

    setcookie('order_address1', $address1, time() + (86400 * 30), '/');
    setcookie('order_address2', $address2, time() + (86400 * 30), '/');
    setcookie('order_delivery_mode', $delivery_mode, time() + (86400 * 30), '/');

}
// End set cookie after order
// 
add_filter( 'wc_order_is_editable', 'nmpm_wc_make_processing_orders_editable', 10, 2 );
function nmpm_wc_make_processing_orders_editable( $is_editable, $order ) {
    if ( $order->get_status() == 'processing' ) {
        $is_editable = true;
    }

    return $is_editable;
}

/**
 * Start Product 40k 2000 Discount
 */
add_filter( 'woocommerce_cart_calculate_fees', 'add_2k_discount', 10, 1 );

function add_2k_discount( $cart ) {

    if( $cart->subtotal >= 40000 ){
        $cart->add_fee( 'Discount', -2000 );
    }
     
}
/**
 * End Product 40k 2000 Discount
 */

/*
 * New orders BCC emails

function nmpm_admin_bcc_emails( $headers, $object ) {
	// email types/objects to add bcc to
	$add_bcc_to = array(
		//'customer_renewal_invoice',		// Renewal invoice from WooCommerce Subscriptions
		'new_order',	// Customer Processing order from WooCommerce
		'failed_order'
		);

	// if our email object is in our array
	if ( in_array( $object, $add_bcc_to ) ) {

		// change our headers
		$headers = array( 
			$headers,
			'Bcc: <nishit.m@pugmarker.com>' ."\r\n",
			'Bcc: <kapil.gonge@pugmarker.com>' ."\r\n",
			);
	}
	return $headers;
}
add_filter( 'woocommerce_email_headers', 'nmpm_admin_bcc_emails', 10, 2 );
* */

function nm_remove_attributes( $attributes ) {
    if ( is_product() ) { 
        
        if(isset($attributes['dispatch-week'])){
            $dates=$attributes['dispatch-week']['options'];
            unset($dates[0]);
            unset($dates[1]);
            unset($dates[2]);
			unset($dates[3]);
			unset($dates[4]);
			unset($dates[5]);
			unset($dates[6]);
			unset($dates[7]);
            $attributes['dispatch-week']['options']=$dates;
        }
        
    }
    return $attributes;
}
//add_filter( 'woocommerce_product_get_attributes', 'nm_remove_attributes' );

add_filter( 'gform_notification_2', 'attach_vcf_file', 10, 3 );
function attach_vcf_file( $notification, $form, $entry ) {
 
    //There is no concept of user notifications anymore, so we will need to target notifications based on other criteria, such as name
    if ( $notification['name'] == 'Available Order' ) {
 
        $notification['attachments'] = ( is_array( rgget('attachments', $notification ) ) ) ? rgget( 'attachments', $notification ) : array();
        $filePath=get_template_directory().'-child/vcf/'.$entry['id'].'.vcf';
        $data=null;
        $data.="BEGIN:VCARD\n";
        $data.="VERSION:2.1\n";
        $data.="FN:".$entry[1]."\n"; 
        $data.="EMAIL:".$entry[2]."\n";
        $data.="TEL;CELL:+91".$entry[3]."\n";
        $data.="END:VCARD";
        $file = fopen($filePath,"w");
        fwrite($file,$data);
        fclose($file);
        $notification['attachments'][] = $filePath;
    }
 
    return $notification;
}

/**
 * Custom Notification Events for Instamojo
 * @param array $notification_events
 * @return type
 */
function instamojo_add_event($notification_events) {
    $notification_events['instamojo_payment_received'] = __('Payment Received - Instamojo', 'gravityforms');
    return $notification_events;
}
add_filter('gform_notification_events', 'instamojo_add_event');


function admin_payment_notification( $feed, $entry, $status,  $transaction_id, $subscriber_id, $amount, $pending_reason, $reason ) {
    if( $status=="Credit" && $entry['form_id']==10) {
        if($user_id=email_exists($entry[3])){
            woo_wallet()->wallet->credit($user_id, 6500, "Mango Bond Purchase");
            send_email_notifications($entry['form_id'], $entry['id'], 'instamojo_payment_received');
        }else{
            $user_id=wp_insert_user(array(
                'user_email'=>$entry[3],
                'user_login'=>$entry[3],
                'display_name' =>$entry[1.3]
            ));
            woo_wallet()->wallet->credit($user_id, 6500, "Mango Bond Purchase");
            send_email_notifications($entry['form_id'], $entry['id'], 'instamojo_payment_received');
        }
    }
}
add_action( 'gform_post_payment_status', 'admin_payment_notification', 10, 8 );

function remove_attributes( $attributes ) {
    
    if ( is_product() ) { //Only For Product Page
        $selected_day = get_field('select_day');
        $selected_date = get_field('select_date');

        $date_slots = $attributes['dispatch-week']['options'];

        $key = pmn_check_date_in_slots($selected_day, $selected_date);
        if ($key) {
            if( $key == '10' ){
                $attributes['dispatch-week']['options'] = array();
                return $attributes;
            }
            $final_key = $key - 1; //As We've Started Mango Slot from Index 1

            if ($final_key == 0) {
                unset($date_slots[0]);
            } else {
                for ($x = $final_key; $x >= 0; $x--) {
                    unset($date_slots[$x]);
                }
            }

            $attributes['dispatch-week']['options'] = $date_slots;
        } else if( $selected_date > '2020-05-30' ) {
            $attributes['dispatch-week']['options'] = array();
        }
    }
    return $attributes;
}
add_filter( 'woocommerce_product_get_attributes', 'remove_attributes' );

function pmn_getWeekStartandEndDate($week, $year) {
    $date = new DateTime();
    $date->setISODate($year, $week);
    $return['start'] = $date->format('Y-m-d');
    $date->modify('+6 days');
    $return['end'] = $date->format('Y-m-d');
    return $return;
}

function pmn_check_date_in_slots($day_count, $selected_date){

    if( $selected_date ){
        $current_date = $selected_date;
    } else {
        $current_date = date('Y-m-d');
    }

    $days_to_add = $day_count; //Mon:7, Tue:6, Wed:5, Thu:4, Fri:3, Sat:2, Sun:1
    $date_to_check = date('Y-m-d', strtotime($current_date . ' + ' . $days_to_add . ' days'));

    $mango_slots = [];
    $week_nos = array('13', '14', '15', '16', '17', '18', '19', '20', '21', '22');
    $slot_no = 1;
    foreach ($week_nos as $week_no) {
        $week_start_end = pmn_getWeekStartandEndDate($week_no, 2020);
        $mango_slots[$slot_no]['start'] = $week_start_end['start'];
        $mango_slots[$slot_no]['end'] = $week_start_end['end'];
        $slot_no++;
    }

    foreach ($mango_slots as $key => $mango_slot) {
        if (($date_to_check >= $mango_slot['start']) && ($date_to_check <= $mango_slot['end'])) {
            $date_found_in_key = $key;
        }
    }

    $final_key = $date_found_in_key ? $date_found_in_key : false;

    return $final_key;
}

/*
15 March 2020 | Nishit Manjarawala
Blog URL : https://businessbloomer.com/woocommerce-add-conversion-tracking-code-thank-page/
Google ads conversion tracking code
*/
add_action( 'woocommerce_thankyou', 'bbloomer_conversion_tracking_thank_you_page' );
function bbloomer_conversion_tracking_thank_you_page($order_id ) {
    ?>
    <script>
      gtag('event', 'conversion', {
          'send_to': 'AW-658983232/yJblCL21mMoBEMCSnboC',
          'transaction_id': "<?php echo $order_id; ?>"
      });
	gtag('event', 'conversion', {
      	'send_to': 'AW-658983232/ftl3CJaPuM8BEMCSnboC',
    	'transaction_id': "<?php echo $order_id; ?>"
  	});
    </script>
    <?php
}

//Allow only Rs.100 to deduct from Wallet
//add_filter('is_valid_payment_through_wallet', '__return_false');
//add_filter('woo_wallet_partial_payment_amount', 'woo_wallet_partial_payment_amount_callback', 10);

function woo_wallet_partial_payment_amount_callback($amount) {
    if (sizeof(wc()->cart->get_cart()) > 0) {
        $cart_total = get_woowallet_cart_total();
        $partial_payment_amount = '100';
        if ($amount >= $partial_payment_amount) {
            $amount = $partial_payment_amount;
        }
    }
    return $amount;
}


function change_wallet_bitecoins( $translated_text, $text, $domain ) {
  
	switch ( $translated_text ) {

		case 'Via wallet' :

			$translated_text = __( 'Via Balance', 'woo-wallet' );
			break;

	}
 
    return $translated_text;
}
add_filter( 'gettext', 'change_wallet_bitecoins', 20, 3 );

add_filter( 'gform_currencies', 'add_inr_currency' );
function add_inr_currency( $currencies ) {
    $currencies['INR'] = array(
        'name'               => __( 'India Rupee', 'gravityforms' ),
        'symbol_left'        => '₹',
        'symbol_right'       => '',
        'symbol_padding'     => ' ',
        'thousand_separator' => ',',
        'decimal_separator'  => '.',
        'decimals'           => 2
    );
 
    return $currencies;
}

add_filter( 'gform_validation_12', 'custom_validation' );
function custom_validation( $validation_result ) {
    $form = $validation_result['form'];
    $entry = GFFormsModel::get_current_lead();
    //supposing we don't want input 1 to be a value of 86
    if ((rgar( $entry, '17.3' ) + rgar( $entry, '18.3' ) + rgar( $entry, '19.3' ) + rgar( $entry, '20.3' ) + rgar( $entry, '21.3' )) < 50) {
 
        // set the form validation to false
        $validation_result['is_valid'] = false;
 
        //finding Field with ID of 1 and marking it as failed validation
        foreach( $form['fields'] as &$field ) {
 
            //NOTE: replace 1 with the field you would like to validate
            if ( $field->id == '24' ) {
                $field->failed_validation = true;
                $field->validation_message = 'Minimum order quantity of 50 petis.';
                break;
            }
        }
 
    }
 
    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;
    return $validation_result;
 
}

//test

// irshad

// https://www.wpbeginner.com/wp-themes/how-to-add-dynamic-widget-ready-sidebars-in-wordpress/
// Dynamic Widget | Register Sidebar

function wpb_widgets_init() {

    register_sidebar(array(
        'name' => __('Product Sidebar', 'wpb'),
        'id' => 'product-sidebar',
        'description' => __('Appears on the static front page template', 'wpb'),
        'before_widget' => '<aside id="%1$s" class="widget %2$s">',
        'after_widget' => '</aside>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ));

}

add_action('widgets_init', 'wpb_widgets_init');


function wpb_widgets_init_search() {

    register_sidebar(array(
        'name' => __('Product Searchbar', 'wpb'),
        'id' => 'product-searchbar',
        'description' => __('Appears on the static front page template', 'wpb'),
        'before_widget' => '<aside id="%1$s" class="widget %2$s">',
        'after_widget' => '</aside>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ));
}

add_action('widgets_init', 'wpb_widgets_init_search');


function variation_radio_buttons($html, $args) {
    $args = wp_parse_args(apply_filters('woocommerce_dropdown_variation_attribute_options_args', $args), array(
      'options'          => false,
      'attribute'        => false,
      'product'          => false,
      'selected'         => false,
      'name'             => '',
      'id'               => '',
      'class'            => '',
      'show_option_none' => __('Choose an option', 'woocommerce'),
   ));
  
    if(false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product) {
      $selected_key     = 'attribute_'.sanitize_title($args['attribute']);
      $args['selected'] = isset($_REQUEST[$selected_key]) ? wc_clean(wp_unslash($_REQUEST[$selected_key])) : $args['product']->get_variation_default_attribute($args['attribute']);
    }
  
    $options               = $args['options'];
    $product               = $args['product'];
    $attribute             = $args['attribute'];
    $name                  = $args['name'] ? $args['name'] : 'attribute_'.sanitize_title($attribute);
    $id                    = $args['id'] ? $args['id'] : sanitize_title($attribute);
    $class                 = $args['class'];
    $show_option_none      = (bool)$args['show_option_none'];
    $show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __('Choose an option', 'woocommerce');
  
    if(empty($options) && !empty($product) && !empty($attribute)) {
      $attributes = $product->get_variation_attributes();
      $options    = $attributes[$attribute];
    }
  
    $radios = '<div class="variation-radios">';
  
    if(!empty($options)) {
      if($product && taxonomy_exists($attribute)) {
        $terms = wc_get_product_terms($product->get_id(), $attribute, array(
          'fields' => 'all',
        ));
  
        foreach($terms as $term) {
          if(in_array($term->slug, $options, true)) {
            $radios .= '<input type="radio" name="'.esc_attr($name).'" value="'.esc_attr($term->slug).'" '.checked(sanitize_title($args['selected']), $term->slug, false).'><label for="'.esc_attr($term->slug).'">'.esc_html(apply_filters('woocommerce_variation_option_name', $term->name)).'</label>';
          }
        }
      } else {
        foreach($options as $option) {
          $checked    = sanitize_title($args['selected']) === $args['selected'] ? checked($args['selected'], sanitize_title($option), false) : checked($args['selected'], $option, false);
          $radios    .= '<input type="radio" name="'.esc_attr($name).'" value="'.esc_attr($option).'" id="'.sanitize_title($option).'" '.$checked.'><label for="'.sanitize_title($option).'">'.esc_html(apply_filters('woocommerce_variation_option_name', $option)).'</label>';
        }
      }
    }
  
    $radios .= '</div>';
  
    return $html.$radios;
  }
  
  
  /**
   * Remove product content based on category
   */
  add_action( 'wp', 'remove_product_content' );
  function remove_product_content() {
      // If a product in the 'Cookware' category is being viewed...
      if ( is_product() && has_term( 'mangos', 'product_cat' ) ) {
          //... Remove
  
      remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
      add_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_side_calaculation', 10);
  
  
          // For a full list of what can be removed please see woocommerce-hooks.php
      }
      else{
          add_filter('woocommerce_dropdown_variation_attribute_options_html', 'variation_radio_buttons', 20, 2);
          add_action( 'woocommerce_before_single_product', 'move_variations_single_price', 1 );
  
  
      }
  }
  
  // Utility function to get the default variation (if it exist)
  function get_default_variation( $product ){
      $attributes_count = count($product->get_variation_attributes());
      $default_attributes = $product->get_default_attributes();
      // If no default variation exist we exit
      if( $attributes_count != count($default_attributes) )
          return false;
  
      // Loop through available variations
      foreach( $product->get_available_variations() as $variation ){
          $found = true;
          // Loop through variation attributes
          foreach( $variation['attributes'] as $key => $value ){
              $taxonomy = str_replace( 'attribute_', '', $key );
              // Searching for a matching variation as default
              if( isset($default_attributes[$taxonomy]) && $default_attributes[$taxonomy] != $value ){
                  $found = false;
                  break;
              }
          }
          // If we get the default variation
          if( $found ) {
              $default_variaton = $variation;
              break;
          }
          // If not we continue
          else {
              continue;
          }
      }
      return isset($default_variaton) ? $default_variaton : false;
  }
  
  
  function move_variations_single_price(){
      global $product, $post;
  
      if ( $product->is_type( 'variable' ) ) {
          // removing the variations price for variable products
          remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
  
          // Change location and inserting back the variations price
          add_action( 'woocommerce_single_product_summary', 'replace_variation_single_price', 10 );
      }
  }
  
  function replace_variation_single_price(){
      global $product;
  
      // Main Price
      $prices = array( $product->get_variation_price( 'min', true ), $product->get_variation_price( 'max', true ) );
      $active_price = $prices[0] !== $prices[1] ? sprintf( __( ' %1$s', 'woocommerce' ), wc_price( $prices[0] ) ) : wc_price( $prices[0] );
  
      // Sale Price
      $prices = array( $product->get_variation_regular_price( 'min', true ), $product->get_variation_regular_price( 'max', true ) );
      sort( $prices );
      $regular_price = $prices[0] !== $prices[1] ? sprintf( __( ' %1$s', 'woocommerce' ), wc_price( $prices[0] ) ) : wc_price( $prices[0] );
  
      if ( $active_price !== $regular_price && $product->is_on_sale() ) {
          $price = '<del>' . $regular_price . $product->get_price_suffix() . '</del> <ins>' . $active_price . $product->get_price_suffix() . '</ins>';
      } else {
          $price = $regular_price;
      }
  
      // When a default variation is set for the variable product
      if( get_default_variation( $product ) ) {
          $default_variaton = get_default_variation( $product );
          if( ! empty($default_variaton['price_html']) ){
              $price_html = $default_variaton['price_html'];
          } else {
              if ( ! $product->is_on_sale() )
                  $price_html = $price = wc_price($default_variaton['display_price']);
              else
                  $price_html = $price;
          }
          $availiability = $default_variaton['availability_html'];
      } else {
          $price_html = $price;
          $availiability = '';
      }
      // Styles ?>
      <style>
          div.woocommerce-variation-price,
          div.woocommerce-variation-availability,
          div.hidden-variable-price {
              height: 0px !important;
              overflow:hidden;
              position:relative;
              line-height: 0px !important;
              font-size: 0% !important;
          }
      </style>
      <?php // Jquery ?>
      <script>
      jQuery(document).ready(function($) {
          var a = 'div.wc-availability', p = 'p.price';
  
          $('input.variation_id').change( function(){
              if( '' != $('input.variation_id').val() ){
                  if($(a).html() != '' ) $(a).html('');
                  $(p).html($('div.woocommerce-variation-price > span.price').html());
                  $(a).html($('div.woocommerce-variation-availability').html());
              } else {
                  if($(a).html() != '' ) $(a).html('');
                  $(p).html($('div.hidden-variable-price').html());
              }
          });
      });
      </script>
      <?php
  
      echo '<p class="price">'.$price_html.'</p>
      <div class="wc-availability">'.$availiability.'</div>
      <div class="hidden-variable-price" >'.$price.'</div>';
  }
  
  function buy_now_submit_form() {
   ?>
    <script>
        jQuery(document).ready(function(){
            // listen if someone clicks 'Buy Now' button
            jQuery('#buy_now_button').click(function(){
                // set value to 1
                jQuery('#is_buy_now').val('1');
                //submit the form
                jQuery('form.cart').submit();
            });
        });
    </script>
   <?php
  }
  add_action('woocommerce_after_add_to_cart_form', 'buy_now_submit_form');
  add_filter('woocommerce_add_to_cart_redirect', 'redirect_to_checkout');
  function redirect_to_checkout($redirect_url) {
    if (isset($_REQUEST['is_buy_now']) && $_REQUEST['is_buy_now']) {
       global $woocommerce;
       $redirect_url = wc_get_checkout_url();
    }
    return $redirect_url;
  }
  
  //cart name
  
  
  add_filter( 'woocommerce_cart_item_name', 'custom_variation_item_name', 10, 3 );
  function custom_variation_item_name( $item_name,  $cart_item,  $cart_item_key ){
      // Change item name only if is a product variation
      if( $cart_item['data']->is_type('variation') && $cart_item['product_id'] == 14   ){
          // HERE customize item name
        //   $item_name = __('<span class="pName">Mango ' .  $cart_item['variation']['attribute_size'] . ' </span><small>per Dz.</small>');
         //var_dump($cart_item);
          // For cart page we add back the product link
        //   if(is_cart())
        //       $item_name = sprintf( $item_name );
      } else {
          $product = wc_get_product( $cart_item['product_id'] );
  
          $item_name = __( $product->get_title().' '. '<small class="d-block">'.$cart_item['data']->attribute_summary . '</small>');
      }
      
      return $item_name;
  }
  // add_filter( 'woocommerce_order_item_name', 'custom_variation_item_name1', 10, 3 );
  // function custom_variation_item_name1( $item_name,  $cart_item ){
  
  //     //var_dump($cart_item);
  //     // Change item name only if is a product variation
  //     if( $cart_item->get_variation_id() > 0 && $cart_item['product_id'] == 14   ){
  //         // HERE customize item name
  //         $item_name = __('<span class="pName">Mango ' .  $cart_item['variation']['attribute_size'] . ' </span><small>per Dz.</small>');
  //        //var_dump($cart_item);
  //         // For cart page we add back the product link
  //         if(is_cart())
  //             $item_name = sprintf( $item_name );
  //     }
  //     return $item_name;
  // }
  
 
  
  // Checking and validating when updating cart item quantities when products are added to cart
  add_filter( 'woocommerce_update_cart_validation', 'only_six_items_allowed_cart_update', 10, 4 );
  function only_six_items_allowed_cart_update( $passed, $cart_item_key, $values, $updated_quantity ) {
  
      $cart_items_count = WC()->cart->get_cart_contents_count();
      $original_quantity = $values['quantity'];
      $total_count = $cart_items_count - $original_quantity + $updated_quantity;
      if( $values['product_id'] == 14){
          // if( $cart_items_count > 10 || $total_count > 10 ){
          if( $updated_quantity > 10  ){
              // Set to false
              $passed = false;
              // Display a message
               wc_add_notice( __( "You can only order 10 Dozen of the same mango grade in the same week." ), "error" );
          }
      }
      
      return $passed;
  }
  
  function is_in_cart( $ids ) {
      // Initialise
      $found = false;
  
      // Loop through cart items
      foreach( WC()->cart->get_cart() as $cart_item ) {
          // For an array of product IDs
          if( is_array($ids) && ( in_array( $cart_item['product_id'], $ids ) || in_array( $cart_item['variation_id'], $ids ) ) ){
              $found = true;
              break;
          }
          // For a unique product ID (integer or string value)
          elseif( ! is_array($ids) && ( $ids == $cart_item['product_id'] || $ids == $cart_item['variation_id'] ) ){
              $found = true;
              break;
          }
      }
  
      return $found;
  }
  
  /**
   * @snippet       Change "Place Order" Button text @ WooCommerce Checkout
   * @sourcecode    https://rudrastyh.com/?p=8327#woocommerce_order_button_text
   * @author        Misha Rudrastyh
   */
  add_filter( 'woocommerce_order_button_text', 'misha_custom_button_text' );
   
  function misha_custom_button_text( $button_text ) {
     return 'Proceed to Pay'; // new text is here 
  }
  
  add_filter( 'woocommerce_add_to_cart_fragments', function($fragments) {
  
      ob_start();
      ?>
     
    <?php
    global $woocommerce;
    $pmn_items = $woocommerce->cart->get_cart();
    $pmn_item_count = count($pmn_items);
    ?>
    <p class="pmn-header-cart-count" style="display: none !important;"><?php echo $pmn_item_count; ?></p>
    <?php //the_widget('WC_Widget_Cart', 'title='); ?>
                                          
      
  
      <?php $fragments['.pmn-header-cart-count'] = ob_get_clean();
  
      return $fragments;
  
  } );
  
  add_filter('woocommerce_currency_symbol', 'inr_currency_symbol', 10, 2);
  
  function inr_currency_symbol( $currency_symbol, $currency ) {
      switch( $currency ) {
          case 'INR': $currency_symbol = '&#8377;';
              break;
      }
      return $currency_symbol;
  }
  
  // prodct limit
  add_filter( 'loop_shop_per_page', 'new_loop_shop_per_page', 20 );
  
  function new_loop_shop_per_page( $cols ) {
    // $cols contains the current number of products per page based on the value stored on Options -> Reading
    // Return the number of products you wanna show per page.
    $cols = 9;
    return $cols;
  }
  
  // change drowpdown text
    
  add_filter( 'woocommerce_catalog_orderby', 'bbloomer_rename_sorting_option_woocommerce_shop' );
    
  function bbloomer_rename_sorting_option_woocommerce_shop( $options ) {
     $options['menu_order'] = 'Sort By';   
     return $options;
  }


  /**
* @snippet       Remove Sorting Option @ WooCommerce Shop
* @how-to        Get CustomizeWoo.com FREE
* @author        Rodolfo Melogli
* @testedwith    WooCommerce 3.8
* @donate $9     https://businessbloomer.com/bloomer-armada/
*/
  
add_filter( 'woocommerce_catalog_orderby', 'bbloomer_remove_sorting_option_woocommerce_shop' );
  
function bbloomer_remove_sorting_option_woocommerce_shop( $options ) {
   unset( $options['rating'] );   
   unset( $options['popularity'] );   
   return $options;
}
  
// Note: you can unset other sorting options by adding more "unset" calls... here's the list: 'menu_order', 'popularity', 'rating', 'date', 'price', 'price-desc'


add_filter( 'gettext', 'change_cart_totals_text', 20, 3 );
function change_cart_totals_text( $translated, $text, $domain ) {
    if( is_cart() && $translated == 'Cart totals' ){
        $translated = __('Cart Total', 'woocommerce');
    }
    return $translated;
}

function better_woocommerce_search_result_title( $page_title )
{
	if ( is_search() ) {
		if (! get_search_query()) {
			$page_title = sprintf( __( 'Search Results: “All Products”', 'woocommerce' ), get_search_query() );
		} else {
			$page_title = sprintf( __( 'You searched for &nbsp;“%s”', 'woocommerce' ), get_search_query() );
		}
	}
	return $page_title;
}

// add the filter
add_filter( 'woocommerce_page_title', 'better_woocommerce_search_result_title', 10, 1 );


function nm_action_woocommerce_before_cart_table(  ) { 
	if(is_shop()){
	do_action( 'woocommerce_before_single_product' );
	}
    echo '<p style="margin-top:10px;">Enjoy the original Devgad Alphonso Aamras! <b>Delivery starting from 7th July 2021.</b></p>';
}; 
add_action( 'woocommerce_before_cart_table', 'nm_action_woocommerce_before_cart_table', 10, 0 );
add_action( 'woocommerce_archive_description', 'nm_action_woocommerce_before_cart_table', 10, 0 );

add_filter('woocommerce_default_catalog_orderby', 'nmmisha_default_catalog_orderby');
 
function nmmisha_default_catalog_orderby( $sort_by ) {
	return 'price-desc';
}

add_action( 'woocommerce_after_order_object_save', 'custom_export_pending_order_data' );

function custom_export_pending_order_data( $order ) {
   $order_id= $order->get_id();
    //$order = new WC_Order($order_id);
	$user = $order->get_user();
	
	if( !$user ){
		$order_data = $order->get_data();
		if($user=email_exists($order_data['billing']['email'])){
			update_post_meta($order_id, '_customer_user', $user );
			 
		}else{
			//echo '#'.$order_id.' '.$order_data['billing']['email']."<br/>";
			$user = wp_insert_user( array(
			  'user_login' => $order_data['billing']['email'],
			  'user_email' => $order_data['billing']['email'],
			  'first_name' => $order_data['billing']['first_name'],
			  'last_name' => $order_data['billing']['last_name'],
			  'display_name' => $order_data['billing']['first_name'],
			  'role' => 'subscriber'
			));
			update_post_meta($ord->ID, '_customer_user', $user );
		}
	}
}

/*************************************/
add_filter( 'gform_entry_meta', function ( $entry_meta, $form_id ) {
    $fields = array('utm_source','utm_medium','utm_term', 'utm_content', 'utm_campaign', 'gclid', 'handl_original_ref', 'handl_landing_page', 'handl_ip', 'handl_ref', 'handl_url', 'zoho_api_status');
	foreach ($fields as $field){
    
        $entry_meta[$field] = array(
            'label'                      => $field,
            'is_numeric'                 => false,
            'update_entry_meta_callback' => 'nmpm_update_entry_meta_test',
            'is_default_column'          => false,
            'filter'                     => array(
                'key'       => $field,
                'text'      => $field,
                'operators' => array(
                    'is',
                    'isnot',
                )
            ),
        );
	}
 
    return $entry_meta;
}, 10, 2 );
 
function nmpm_update_entry_meta_test( $key, $entry, $form ){
    $value = "";
    $fields = array('utm_source','utm_medium','utm_term', 'utm_content', 'utm_campaign', 'gclid', 'handl_original_ref', 'handl_landing_page', 'handl_ip', 'handl_ref', 'handl_url');
	foreach ($fields as $field){
	    if($key==$field && isset($_COOKIE[$field]) && $_COOKIE[$field] != ''){
	        $value=esc_attr($_COOKIE[$field]);
	    }
	}
    return $value;
}
