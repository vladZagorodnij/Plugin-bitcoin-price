<?php
/**
* Plugin Name: Bitcoin-price
* Description: This plugin help you to know Bitcoin exchange rate! Please add this shortcode [bitcoin_price] on your page, where you want to see Bitcoin Price.
* Version: 3.0
* Author: VladZ
* License: ...
*/

//add style.css

add_action('init', 'register_script');
function register_script() {
    wp_register_style( 'bitcoin_vlad', plugins_url('css/style.css', __FILE__), false, '1.0.0', 'all');
}
add_action('wp_enqueue_scripts', 'enqueue_style');

function enqueue_style(){
    wp_enqueue_style( 'bitcoin_vlad' );
}

//add URL for plugin

add_action( 'rest_api_init', function () {
    register_rest_route( 'bitcoin-vlad', '/get-bitcoin-info/', array(
        'methods' => 'GET',
        'callback' => 'bitcoin_price_function',
    ) );
} );

// add table to wp database

function bitcoin_vlad_activate () {
    global $wpdb;

    $table_name = $wpdb->prefix . "bitcoin_price";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
binance VARCHAR(30) NOT NULL,
bitstamp VARCHAR(30) NOT NULL,
average VARCHAR(30) NOT NULL,
time VARCHAR(30) NOT NULL
) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

register_activation_hook( __FILE__, 'bitcoin_vlad_activate' );


// plugin brain

function bitcoin_price_function()
{
    global $binance_price;
    global $bitstamp_price;
    global $average;
    global $time;

    $json_result_binance = file_get_contents("https://www.binance.com/api/v1/ticker/24hr?symbol=BTCUSDT");
    $obj = json_decode($json_result_binance, true);
    $binance_price = round($obj["askPrice"], 2);

    $json_result_bitstamp = file_get_contents("https://www.bitstamp.net/api/v2/ticker/btcusd/");
    $obj = json_decode($json_result_bitstamp, true);
    $bitstamp_price = $obj["bid"];

    $average = ($binance_price + $bitstamp_price)/2;

    date_default_timezone_set('Europe/Kiev');
    $time = date("d.m.y H:i:s");

    //add data to table

    function add_bitcoin_data() {

        global $wpdb;
        global $binance_price;
        global $bitstamp_price;
        global $average;
        global $time;

        $table_name = $wpdb->prefix . "bitcoin_price";

        $wpdb->insert(
            $table_name,
            array(
                'binance' => $binance_price,
                'bitstamp' => $bitstamp_price,
                'average' => $average,
                'time' => $time
            )
        );
    }

    add_bitcoin_data();
    return $binance_price . $bitstamp_price . $average . $time ;
}

// function for shortcode

function bitcoin_price_show(){

    global $wpdb;

    $table_name = $wpdb->prefix . "bitcoin_price";

    $myrows = $wpdb->get_results( "SELECT * FROM $table_name" );
    
    ?>

<table class='plugin_table'>
    <caption><h4>BITCOIN PRICE</h4></caption>
    <tbody>
        <tr>
            <th class='time'>Update time:</th>
            <th class='binance'>Binance:</th>
            <th class='bitstamp'>Bitstamp:</th>
            <th class='average'>Average value:</th>
        </tr>

<?php
    foreach ($myrows as $myrowsItem) {
        ?>

        <tr>
            <td class='time'><?php echo $myrowsItem->time ?></td>
            <td class='binance'><?php echo $myrowsItem->binance ?></td>
            <td class='bitstamp'><?php echo $myrowsItem->bitstamp ?></td>
            <td class='average'><?php echo $myrowsItem->average ?></td>
        </tr>

       <?php
    }
    ?>

    </tbody>
</table>

<?php

}

add_shortcode('bitcoin_price', 'bitcoin_price_show');

//delete table from database

function delete_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . "bitcoin_price";
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
}
register_deactivation_hook( __FILE__, 'delete_table' );
