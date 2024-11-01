<?php

defined('ABSPATH') || exit;
?>
<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 1200px; margin: auto; padding: 20px; }
    a { color: #0073aa; text-decoration: none; }
    a:hover { text-decoration: underline; }
    h1, h2, h3, h4 { color: #23282d; }
    .green-link { color: green; }
    .hr { margin-top: 20px; border: 0; border-top: 1px solid #ccc; }
    .table { width: 100%; border-collapse: collapse; }
    .table td { padding: 10px; vertical-align: top; }
    .markup-img { width: 100%; max-width: 300px; }
    .half {
        width:50%;
        float:left;
    }
    .clear{
        clear: both;
    }
</style>
<div class="container">
    <h1 class="wp-heading-inline"><?php esc_html_e('Markup For WooCommerce', 'wpiron-wc-markup'); ?></h1>
    <hr class="hr">
    <div class="half">
        <strong>Useful links</strong>
        <p style="margin:0;">Settings for Markup: <a href="admin.php?page=wc-settings&tab=products&section=markups">Product Markup Settings</a></p>
        <p style="margin:0;">Contact us: <a href="https://wpiron.com/contacts/">Contact page</a></p>
        <br>
        <strong>How to use the plugin?</strong>
        <ol>
            <li>Go to <a href="edit.php?post_type=product"><strong>Add New Product</strong></a></li>
            <li>Click <strong>ADD NEW</strong></li>
            <li>Go Down To <strong>PRODUCT DATA</strong></li>
            <li>On the left hand side click <strong>MARKUP</strong></li>
        </ol>
    </div>
    <div class="half">
        <strong>Upgrade your plan: <a class="green-link" href="https://wpiron.com/products/markup-for-woocommerce/" target="_blank">Upgrade Markup For WooCommerce plugin</a></strong>
        <h4>What you will get with premium version?</h4>
        <ol>
            <li>Add <strong>markup to categories</strong> and after you will check category - markup automatically will be added to a product</li>
            <li><strong>Sale Price</strong> Markup Setting - calculate markups on sale price</li>
            <li>Round Markup Setting - round final prices</li>
            <li><strong>Analytics</strong> - check how much money our plugin generated for you!</li>
            <li>Edit batch products of markup</li>
            <li>Set <strong>global markup</strong> price to all products in WooCommerce.</li>
            <li>Set markup price for <strong>variation products</strong>.</li>
        </ol>
    </div>
    <div class="clear">
    </div>
    <hr class="hr">
    <table class="table">
        <tr>
            <td>
                <strong>Add fixed and percentage value markups to your products</strong>
                <p>You can add fixed price markup to any product individually. This will calculate a price based on the
                    total cost of that product, plus your desired fixed markup. This is a great time-saving feature for
                    stores that have a lot of inventory.</p>
            </td>
            <td>
                <img class="markup-img" src="<?php echo plugin_dir_url(__FILE__); ?>img/markup.png" alt="">
            </td>
        </tr>
        <tr>
            <td>
                <img class="markup-img" src="<?php echo plugin_dir_url(__FILE__); ?>img/variable.png" alt="">
            </td>
            <td>
                <strong>Create a markup in all types of WooCommerce products and categories</strong>
                <p>This plugin helps you to create the price markup in all the types of WooCommerce products and categories.
                    It is a powerful and easy to use plugin with many options for your needs by using this plugin you can
                    create any kind of markup on products, categories and more things.</p>
            </td>
        </tr>
    </table>
</div>