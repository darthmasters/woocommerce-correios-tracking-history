<?php
/**
 * Plugin Name: WooCommerce Correios - Tracking History
 * Plugin URI: https://github.com/darthmasters/woocommerce-correios-tracking-history
 * Description: Exibe o histórico de entrega do pedido nos correios utilizando o código de rastreamento salvo no WooCommerce Correios.
 * Author: Rafael Barbosa
 * Author URI: http://facebook.com/rafaelbcon
 * Version: 0.0.1
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'woocommerce_view_order', 'wc_correios_order_tracking_history', 2 );

function renomeandoClasseTabelaHistorico($content) {
    $table = preg_replace( "/<table class=\"listEvent sro\">/", '<table class="tracking_history">', $content );
    return $table;
}

function verificaObjetoPostado($content) {
    if ( preg_match('/Objeto postado/', $content) ) {
        return true;
    } else {
        return false;
    }
}

function verificaObjetoEntregue($content) {
    if ( preg_match('/Objeto entregue ao destinatário/', $content) ) {
        return true;
    } else {
        return false;
    }
}

function verificaObjetoSaiuParaEntrega($content) {
    if ( preg_match('/Objeto saiu para entrega ao destinatário/', $content) ) {
        return true;
    } else {
        return false;
    }
}

function pegarTodosPedidosCompletados () {
    $customer_orders = wc_get_orders( array(
        'limit'    => -1,
        'status'   => array( 'wc-completed' )
    ));

    return $customer_orders;
}

function pegarCodigoRastreamento ($order) {
    return get_post_meta($order->id, '_correios_tracking_code', true );
}

function pegarHistoricoRastreamento ($tracking_code) {
    $post = ['objetos' => $tracking_code, 'btnPesq' => 'Buscar'];
    $ch = curl_init('http://www2.correios.com.br/sistemas/rastreamento/resultado.cfm?');

    // header('Content-type: text/html; charset=UTF-8');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Referer: http://www2.correios.com.br/sistemas/rastreamento/',
            'User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36'
        ],
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_ENCODING => '',
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS
    ]);

    $output = utf8_encode(curl_exec($ch));
    curl_close($ch);

    preg_match('/<table class="listEvent sro">.*<\/table>/s', $output, $output);

    return $output[0];
}

function pegarHistoricoMD5 ($historic) {
    return md5($historic);
}

function pegarTodosHistoricos () {
    $orders = pegarTodosPedidosCompletados();

    foreach($orders as $order) {
        $tracking_code = pegarCodigoRastreamento($order);
        $tracking_code_history = pegarHistoricoRastreamento($tracking_code);
        echo $tracking_code_history;

        echo "Código MD5: ".pegarHistoricoMD5($tracking_code_history)."<br>";
        echo verificaObjetoPostado($tracking_code_history) ? "Postado<br>" : "Não Postado<br>";
        echo verificaObjetoSaiuParaEntrega($tracking_code_history) ? "Saiu<br>" : "Não saiu<br>";
        echo verificaObjetoEntregue($tracking_code_history) ? "Entregue<br>" : "Não Entregue<br>";

        wp_mail( "shinzootk@gmail.com", "aprendendo a enviar email", $tracking_code_history, '', array( '' ) );
    }
}

add_action( 'woocommerce_before_my_account', 'pegarTodosHistoricos' );