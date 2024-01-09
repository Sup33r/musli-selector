<?php
/**
 * Plugin Name: Musli Selector
 * Plugin URI: https://muslimix.se
 * Description: A plugin for selecting musli in WordPress and WooCommerce.
 * Version: 1.0.6
 * Author: Viktor :)
 * Author URI: https://muslimix.se
 * License: GPL2
 */

// Enqueue CSS and JS filess
function musli_selector_enqueue_scripts() {
    wp_enqueue_style( 'musli-selector', plugin_dir_url( __FILE__ ) . 'musli-selector.css' );
    wp_enqueue_script( 'musli-selector', plugin_dir_url( __FILE__ ) . 'musli-selector.js', array( 'jquery' ), '1.0.0', true );
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css' );
}
add_action( 'wp_enqueue_scripts', 'musli_selector_enqueue_scripts' );
// Add musli selector shortcode

function formatText($text) {
    // Handle bold text
    $text = str_replace('*', '<strong>', $text);
    $text = preg_replace('/<strong>(.*?)<strong>/', '<strong>$1</strong>', $text);

    // Handle headings
    $text = str_replace('^', '<h1>', $text);
    $text = preg_replace('/<h1>(.*?)<h1>/', '<h1>$1</h1>', $text);

    // Handle line breaks
    $text = str_replace('\n', '<br>', $text);

    // Handle italic text
    $text = str_replace('`', '<i>', $text);
    $text = preg_replace('/<i>(.*?)<i>/', '<i>$1</i>', $text);

    return $text;
}


function musli_selector_shortcode( $atts ) {
    ob_start();
    if (is_admin()) {
        return ''; // Shortcode output is empty in the admin area
    }
    $special_products = get_posts(array(
        'post_type' => 'product',
        'numberposts' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'slug',
                'terms' => 'special',
            ),
        ),
    ));

    $has_special = !empty($special_products);
    ?>
    <div class="musli-selector">
    <div id="overlay" style="display: none;">
    <div id="cart-popup">
    <div class="popup-content">
    <p>Du har redan föremål i din kundvagn</p>
    <button onclick="window.location.href='<?php echo wc_get_cart_url(); ?>'">Gå till kundvagnen</button>
    <button onclick="emptyCart(event)">Töm kundvagnen</button>
    </div>
    </div>
</div>
    <div id="status-bar" style="width: 0%;"></div>
    <form method="post" onsubmit="return checkFormSubmission();">
        <input type="hidden" name="action" value="add_musli_to_cart">
        <div class="navigation-buttons">
            <button id="prev-button" disabled><i class="fa fa-arrow-left"></i></button>
            <button id="next-button" disabled><i class="fa fa-arrow-right"></i></button>
        </div>
        <div id="step-monitor">Steg 1/2</div>
        <div id="step1">
            <h1 class="heading-class">Välj bas för din müsli</h1>
            <?php
            $bases = get_posts(array(
                'post_type' => 'product',
                'numberposts' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_tag',
                        'field' => 'slug',
                        'terms' => 'base',
                    ),
                ),
            ));
            foreach ($bases as $base) {
                $product = wc_get_product($base->ID);
                $price_per_dl = $product->get_price();
                $stock_status = $product->get_stock_status();
                $stock_quantity = $product->get_stock_quantity();
                $out_of_stock_class = ($stock_status === 'outofstock') ? 'out-of-stock' : '';
                ?>
                <div class="product-box <?php echo $out_of_stock_class; ?>">
                    <div class="image-container">
                    <img src="<?php echo get_the_post_thumbnail_url($base->ID); ?>" alt="<?php echo $product->get_name(); ?>">
                        <?php if($out_of_stock_class === 'out-of-stock') : ?>
                            <div class="ribbon">Ej i lager</div>
                        <?php endif; ?>
                        <?php
                    $extra_innehall = $product->get_attribute('extra_innehall');
                    if (!empty($extra_innehall)) : ?>
                        <button class="info-button">
                        <span class="info">i</span>
                        <span class="close" style="display: none;">X</span>
                        </button>
                        <div class="info-popup"><?php echo formatText($extra_innehall); ?></div>
                    <?php endif; ?>
                    </div>

                    <h4><?php echo $product->get_name(); ?></h4>
                    <p><?php echo $price_per_dl; ?> kr/dl</p>
                    <?php if($stock_quantity > 0 && $stock_quantity <= 5) : ?>
                        <p class="low_quantity">Endast <?php echo $stock_quantity; ?>dl i lager!</p>
                    <?php endif; ?>
                    <div class="input-container">
                        <button type="button" class="decrement" data-input-id="base_<?php echo $base->ID; ?>" data-stock="<?php echo $stock_quantity; ?>">−</button>
                        <input type="text" other="base" id="base_<?php echo $base->ID; ?>" name="bases[<?php echo $base->ID; ?>]" value="0 dl" onchange="updateBases()" readonly>
                        <button type="button" class="increment" data-input-id="base_<?php echo $base->ID; ?>" data-stock="<?php echo $stock_quantity; ?>">+</button>
                    </div>
                </div>
            <?php
            }
            ?>
            </div>
            <div id="step2" style="display: none;">
                <h1 class="heading-class">Välj tilläggsingredienser</h1>
                <div id="ingredients-heading">
                <?php
                $additionalIngredients = get_posts(array(
                    'post_type' => 'product',
                    'numberposts' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_tag',
                            'field' => 'slug',
                            'terms' => 'addition',
                        ),
                    ),
                ));
                foreach ($additionalIngredients as $ingredient) {
                    $product = wc_get_product($ingredient->ID);
                    $price_per_05_dl = $product->get_price();
                    $stock_status = $product->get_stock_status();
                    $stock_quantity = $product->get_stock_quantity();
                    $out_of_stock_class = ($stock_status === 'outofstock') ? 'out-of-stock' : '';
                    ?>
                    <div class="product-box <?php echo $out_of_stock_class; ?>">
                        <div class="image-container">
                            <img src="<?php echo get_the_post_thumbnail_url($ingredient->ID); ?>" alt="<?php echo $product->get_name(); ?>">
                            <?php if($out_of_stock_class === 'out-of-stock') : ?>
                                <div class="ribbon">Ej i lager</div>
                            <?php endif; ?>
                            <?php
                    $extra_innehall = $product->get_attribute('extra_innehall');
                    if (!empty($extra_innehall)) : ?>
                        <button class="info-button">
                        <span class="info">i</span>
                        <span class="close" style="display: none;">X</span>
                        </button>
                        <div class="info-popup><?php echo formatText($extra_innehall); ?></div>
                    <?php endif; ?>
                        </div>
                        <h4><?php echo $product->get_name(); ?></h4>
                        <p><?php echo $price_per_05_dl; ?> kr/0.5 dl</p>
                        <?php if($stock_quantity > 0 && $stock_quantity <= 4) : ?>
                            <p class="low-quantity">Endast <?php echo $stock_quantity/2; ?>dl i lager!</p>
                        <?php endif; ?>
                        <div class="input-container">
                            <button type="button" class="decrement" data-input-id="ingredient_<?php echo $ingredient->ID; ?>" data-stock="<?php echo $stock_quantity; ?>">−</button>
                            <input type="text" other="ingredient" id="ingredient_<?php echo $ingredient->ID; ?>" name="ingredients[<?php echo $ingredient->ID; ?>]" value="0 dl" onchange="updateIngredients()" readonly>
                            <button type="button" class="increment" data-input-id="ingredient_<?php echo $ingredient->ID; ?>" data-stock="<?php echo $stock_quantity; ?>">+</button>
                    </div>
                </div>
                    <?php
                }
                ?>
            </div>
            <!--<input type="submit" id="checkout-button" value="Till kassan" onClick="submitForm()" disabled> -->
        </div>
        </form>
        <div id="special-popup" style="display: none;">
            <h4 class="product-text">Vi har under julen ett specialerbjudande på kryddor, som gör sig bra i müslin. För bara några fåtals extra kronor kan du få en helt annan smak på din müsli.</h4>
            <?php if($has_special):
                foreach ($special_products as $special_product_post) {
                    $special_product = wc_get_product($special_product_post->ID); ?>
                    <div class="special-product-box">
                        <img src="<?php echo get_the_post_thumbnail_url($special_product->get_id()); ?>" alt="<?php echo $special_product->get_name(); ?>">
                        <h4><?php echo $special_product->get_name(); ?></h4>
                        <p><?php echo $special_product->get_price(); ?> kr</p>
                        <button onclick="addToCartSpecial(<?php echo $special_product->get_id(); ?>, this)">Lägg till</button>
                    </div>
                    <?php
                } ?>
                <div class="skip-button-container">
                    <button class="skip-button-mobile" onclick="skipSpecial()">Inte idag</button> <!-- Visible only on mobile -->
                    <button class="skip-button" onclick="skipSpecial()">Inte idag</button> <!-- Always visible -->
                </div>
            <?php endif; ?>
        </div>

    </div>
<script type="text/javascript">
function checkFormSubmission() {
    if (jQuery('#step2').is(':visible')) {
        // If on step 2, prevent normal form submission
        return false;
    }
    // Otherwise, allow normal form submission
    return true;
}


jQuery(document).ready(function() {
jQuery('.info-button').click(function() {
    event.preventDefault();
    jQuery(this).toggleClass('clicked');
    jQuery(this).find('.info, .close').toggle();
    jQuery(this).next('.info-popup').toggleClass('open');
});

jQuery(document).click(function(event) {
    if (!jQuery(event.target).closest('.info-button, .info-popup').length) {
        jQuery('.info-button').removeClass('clicked');
        jQuery('.info-button .info').show();
        jQuery('.info-button .close').hide();
        jQuery('.info-popup').removeClass('open');
    }
});

    if (<?php echo WC()->cart->get_cart_contents_count(); ?> > 0) {
        // Show the popup
        jQuery('body').addClass('modal-open');
        jQuery('#overlay').show();
        jQuery('#prev-button').prop('disabled', true);
    }

    // Initialize the page
    updateStep(1);

    function fixShitBug(a,b,c) {
        if(c === "base") {
            return a < b;
        }
        if(c === "ingredient") {
            return a*2 < b;
        }
    }
    function fixShitBug2(a,b) {
        return a > b;
    }

    jQuery(document).on('click', '.increment', function() {
        var inputId = jQuery(this).attr('data-input-id');
        var input = jQuery('#' + inputId);
        var currentVal = parseFloat(input.val().split(" ")[0]);
        var type = input.attr('other');
        var stock = parseFloat(jQuery(this).attr('data-stock'));

        var totalQuantity = calculateTotalQuantity(type);
        var allowedMax = (type === 'base') ? 7 : 4;

        if (fixShitBug2(allowedMax,totalQuantity) && fixShitBug(currentVal, stock, type)) {
            if(type === 'base') {
                input.val((currentVal + 1) + " dl");
                updateBases(); // Update bases related UI components
            } else {
                input.val((currentVal + 0.5) + " dl");
                updateIngredients(); // Update ingredients related UI components
            }
        }
    });

    jQuery(document).on('click', '.decrement', function() {
        var inputId = jQuery(this).attr('data-input-id');
        var input = jQuery('#' + inputId);
        var currentVal = parseFloat(input.val().split(" ")[0]);
        var type = input.attr('other');

        if (currentVal > 0) {
            if(type === 'base') {
                input.val((currentVal - 1) + " dl");
                updateBases(); // Update bases related UI components
            } else {
                input.val((currentVal - 0.5) + " dl");
                updateIngredients(); // Update ingredients related UI components
            }
        }
    });

    function calculateTotalQuantity(type) {
        var total = 0;
        jQuery('input[other="' + type + '"]').each(function() {
            total += parseFloat(jQuery(this).val().split(" ")[0]);
        });
        return total;
    }

function submitForm() {
    // Show a loading spinner and disable the button
    jQuery('#checkout-button').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

    // Submit the form
    jQuery('form').submit();
}


    jQuery('#prev-button').click(function(event) {
        event.preventDefault();
        updateStep(1);
    });

    jQuery('#next-button').click(function(event) {
    event.preventDefault();
    console.log(hasSpecial);
    if (jQuery('#step1').is(':visible')) {
        // If on step 1, go to step 2
        updateStep(2);
    } else if (jQuery('#step2').is(':visible') && hasSpecial) {
        // If on step 2, go to special
        updateStep(3);
    } else if (jQuery('#step2').is(':visible')) {
        // If on step 2, handle form submission in JavaScript
        handleFormSubmission();
        jQuery('#next-button').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
    }
});

function handleFormSubmission() {
    // Get the form data
    var formData = jQuery('form').serialize();

    // Make an AJAX request to submit the form
    jQuery.ajax({
        type: 'POST',
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        data: formData,
        success: function(response) {
            // On success, redirect to the cart page
            window.location.href = '<?php echo wc_get_cart_url(); ?>';
        },
        error: function() {
            // Handle errors here...
        }
    });
}

});
var hasSpecial = <?php echo $has_special ? 'true' : 'false'; ?>;

function updateStep(step) {
    if (step === 1) {
        jQuery('#step-monitor').html('Steg 1/2');
        jQuery('#step1').show();
        jQuery('#step2').hide();
        jQuery('#prev-button').prop('disabled', true); // Always enable the previous button on step 2
        // Update button states based on step 1
        updateBases(); // This will disable the next button if no bases are selected
    } else if (step === 2) {
        jQuery('#step-monitor').html('Steg 2/2');
        jQuery('#step1').hide();
        jQuery('#step2').show();
        jQuery('#prev-button').prop('disabled', false); // Always enable the previous button on step 2
        jQuery('#next-button').prop('disabled', true); // Disable the next button on step 2 (until an ingredient is selected
        updateIngredients(); // This will disable the next button if no ingredients are selected
    } else if (step === 3 && hasSpecial) {
        jQuery('#step-monitor').html('Specialerbjudande');
        jQuery('#step1').hide();
        jQuery('#step2').hide();
        jQuery('#special-popup').show();
        jQuery('#prev-button').prop('disabled', true);
        jQuery('#next-button').prop('disabled', true);
    }
}

function addToCartSpecial(productId, buttonElement) {
    // Add AJAX call to add the special product to the cart
    // Then redirect to checkout
    // You might reuse or modify your existing addToCart function
    var button = jQuery(buttonElement);
    button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
jQuery.ajax({
    type: 'POST',
    url: '<?php echo admin_url('admin-ajax.php'); ?>',
    data: {
        action: 'add_product_to_cart',
        product_id: productId
    },
    success: function (response) {
        var formData = jQuery('form').serialize();

        // Make an AJAX request to submit the form
        jQuery.ajax({
            type: 'POST',
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: formData,
            success: function(response) {
                // On success, redirect to the cart page
                window.location.href = '<?php echo wc_get_cart_url(); ?>';
            },
            error: function() {
                // Handle errors here...
            }
        });
        },
    error: function () {
        alert('Fel vid ingredienstilläggning - var vänlig försök igen. Om problemet kvarstår, var vänlig kontakta supporten. ');
        jQuery('.add-to-cart-button').prop('disabled', false).html('Add to Cart');
    }
});
}

function skipSpecial() {
    var skipButtons = jQuery('.skip-button, .skip-button-mobile');
    skipButtons.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
    var formData = jQuery('form').serialize();

    // Make an AJAX request to submit the form
    jQuery.ajax({
        type: 'POST',
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        data: formData,
        success: function(response) {
            // On success, redirect to the cart page
            window.location.href = '<?php echo wc_get_cart_url(); ?>';
        },
        error: function() {
            // Handle errors here...
        }
    });
    jQuery('#next-button').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
}

function addToCart(event, productId) {
    var button = jQuery(event.target); // get the clicked button
    button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

    jQuery.ajax({
        type: 'POST',
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        data: {
            action: 'add_product_to_cart',
            product_id: productId
        },
        success: function(response) {
            updateBases();
            updateStatusBar(50);
            jQuery('<input>').attr({
                type: 'hidden',
                id: 'base_product',
                name: 'base',
                value: productId
            }).appendTo('form');
        },
        error: function() {
            button.prop('disabled', false).html('Add to Cart');
            alert('Fel vid ingredienstilläggning - var vänlig försök igen. Om problemet kvarstår, var vänlig kontakta supporten. (info@muslimix.se)');
            jQuery('.add-to-cart-button').prop('disabled', false).html('Add to Cart');
        }
    });
}


function updateIngredients() {
    // Get all the quantity inputs
    var quantities = jQuery('input[other="ingredient"]');

    // Check if any ingredients have been selected
    var anySelected = false;
    quantities.each(function() {
        var quantity = parseFloat(jQuery(this).val().split(" ")[0]); // Split the value and get the number part
        if (quantity > 0) {
            anySelected = true;
            return false; // Break out of the loop
        }
    });

    // Update the status bar and "Checkout" button
    if (anySelected) {
        updateStatusBar(100);
        jQuery('#checkout-button').prop('disabled', false);
        jQuery('#next-button').prop('disabled', false);
        jQuery('#prev-button').prop('disabled', false);
    } else {
        updateStatusBar(50);
        jQuery('#checkout-button').prop('disabled', true);
        jQuery('#next-button').prop('disabled', true);
    }
}

function updateBases() {
    // Get all the quantity inputs for bases
    var quantities = jQuery('input[other="base"]');

    // Check if any bases have been selected
    var anySelected = false;
    quantities.each(function() {
        var quantity = parseFloat(jQuery(this).val().split(" ")[0]); // Split the value and get the number part
        if (quantity > 0) {
            anySelected = true;
            return false; // Break out of the loop
        }
    });

    // Update the status bar and "Checkout" button
    if (anySelected) {
        updateStatusBar(50);
        jQuery('#checkout-button').prop('disabled', false);
        jQuery('#next-button').prop('disabled', false);
    } else {
        updateStatusBar(0);
        jQuery('#checkout-button').prop('disabled', true);
        jQuery('#next-button').prop('disabled', true);
    }
}


function emptyCart() {
    var button = jQuery(event.target); // get the clicked button
    button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
    jQuery.ajax({
        type: 'POST',
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        data: {
            action: 'empty_cart'
        },
        success: function(response) {
            // Hide the popup
            jQuery('#overlay').fadeOut();
            jQuery('body').removeClass('modal-open');
        },
        error: function() {
            alert('Error emptying cart');
        }
    });
}
</script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'musli_selector', 'musli_selector_shortcode' );

function empty_cart() {
    WC()->cart->empty_cart();
    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action('wp_ajax_empty_cart', 'empty_cart');
add_action('wp_ajax_nopriv_empty_cart', 'empty_cart');

function add_product_to_cart() {
    $product_id = $_POST['product_id'];
    WC()->cart->add_to_cart($product_id);
    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action('wp_ajax_add_product_to_cart', 'add_product_to_cart');
add_action('wp_ajax_nopriv_add_product_to_cart', 'add_product_to_cart');

// Handle form submission
function handle_musli_form_submission() {
    if (isset($_POST['action']) && $_POST['action'] === 'add_musli_to_cart') {
        if (function_exists('WC') && !empty(WC()->cart)) {
            $bases = $_POST['bases'];
            $ingredients = $_POST['ingredients'];

            foreach ($bases as $base_id => $quantityString) {
                $quantity = floatval(preg_replace('/[^0-9.]/', '', $quantityString)); // Extract the number from the string
                if ($quantity > 0) {
                    WC()->cart->add_to_cart($base_id, $quantity);
                }
            }

            foreach ($ingredients as $ingredient_id => $quantityString) {
                $quantity = floatval(preg_replace('/[^0-9.]/', '', $quantityString)) * 2;
                if ($quantity > 0) {
                    WC()->cart->add_to_cart($ingredient_id, $quantity);
                }
            }

            wp_redirect(wc_get_cart_url());
            exit;
        }
    }
}
add_action('wp_loaded', 'handle_musli_form_submission');
?>

