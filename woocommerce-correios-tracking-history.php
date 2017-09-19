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

require_once "includes/class/Database.class.php";
require_once "includes/phpQuery.php";

function formatandoMensagem ($tracking_code_history) {
    $content = "Histórico de Rastreamento \n";
    $content .= "Segue tabela abaixo \n";
    $content .= $tracking_code_history;
    $content .= "\n";
    $content .= "\n";
    $content .= "Obrigado por comprar em nossas lojas";
    return $content;
}

function renomeandoClasseTabelaHistorico($content) {
    $table = preg_replace( "/<table class=\"listEvent sro\">/", '<table class="tracking_history" border="1">', $content );
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
    
    $post = [
        'objetos' => $tracking_code,
        'btnPesq' => 'Buscar'
    ];

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

    // Renomeia html
    // $output = preg_replace( '/<td class="sroDtEvent" valign="top">/', '<td class="informacoes">', $output );
    // $output = preg_replace( '/<td class="sroLbEvent">/', '<td class="descricao">', $output );
    // $output = preg_replace( '/<label style="text-transform:capitalize;">/', '<label>', $output );
    // $output = preg_replace("/(.*) <br>/", "$0", $output);

    // $output = preg_replace( '/(.*) <br>/', '', $output );
    // $output = preg_replace( '/<br>/s', '', $output[0] );

    phpQuery::newDocumentHTML($output[0], $charset = 'utf-8');

    $informacoes = [
        'data' => pq('.sroDtEvent'),
        'label' => pq('.sroLbEvent')
    ];

    echo $informacoes['data'];

    return $output[0];
}

function tratarDatas ($table) {

    preg_match('/<td class="sroDtEvent" valign="top">.*<\/td>/', $table, $table);

    // print_r($table[0]);

    // foreach ($table as $column) {
    //     echo "coluna" . $column;
    // }

    // return $table;
}

function pegarHistoricoMD5 ($historic) {
    return md5($historic);
}

function pegarTodosHistoricos () {
    $orders = pegarTodosPedidosCompletados();

    foreach($orders as $order) {
        $tracking_code = pegarCodigoRastreamento($order);
        $tracking_code_history = pegarHistoricoRastreamento($tracking_code);
        $tracking_code_history = renomeandoClasseTabelaHistorico($tracking_code_history);

        echo $tracking_code_history;

        // echo "Código MD5: ".pegarHistoricoMD5($tracking_code_history)."<br>";
        // echo verificaObjetoPostado($tracking_code_history) ? "Postado<br>" : "Não Postado<br>";
        // echo verificaObjetoSaiuParaEntrega($tracking_code_history) ? "Saiu<br>" : "Não saiu<br>";
        // echo verificaObjetoEntregue($tracking_code_history) ? "Entregue<br>" : "Não Entregue<br>";

        // Sempre colocar o content-type quando enviar email html
        // $headers = "MIME-Version: 1.0" . "\r\n";
        // $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

        // Pegando conteúdo de arquivo html
        // $file = plugins_url( '/templates/emails/tracking_history.html', __FILE__ );
        // $html = file_get_contents($file);
        // $body = str_replace('{historico_rastreamento}', $tracking_code_history, $html);

        // Enviando e-mail
        // mail( "shinzootk@gmail.com", "aprendendo a enviar email", $body, $headers);
    }
}

// add_action( 'woocommerce_before_my_account', 'pegarTodosHistoricos' );

// function testando () {
//     $instance = new WC_Correios_Tracking_History();
//     $result = $instance->get_tracking_history(['DV857306727BR']);
//     echo "<pre>";
//     print_r($result[0]->evento[0]->descricao);
//     echo "</pre>";
// }

// add_action( 'woocommerce_before_my_account', 'pegarTodosHistoricos' );

function pegaTodasDatas ($data) {
    preg_match_all("/\d{2}\/\d{2}\/\d{4}/", $data, $matches);
    return $matches;
}

function pegaTodasHoras ($data) {
    preg_match_all("/\d{2}:\d{2}/", $data, $matches);
    return $matches;
}

function pegarTodasLocalizacoes () {
    $data = "<label style='text-transform:capitalize;'>ARACAJU&nbsp;/&nbsp;SE</label>
        <label style='text-transform:capitalize;'>MARAGOJI&nbsp;/&nbsp;SE</label>";

    preg_match_all("<label style='text-transform:capitalize;'>.*<\/label>/", $data, $matches);
    print_r($matches);
}

// add_action( 'woocommerce_before_my_account', 'pegarTodasLocalizacoes' );

// verifica se foi adicionado


add_action( 'added_post_meta', 'atualizaCodigoRastreamento', 10, 4 );
add_action( 'update_post_meta', 'atualizaCodigoRastreamento', 10, 4 );

// verifica se foi atualizado
function atualizaCodigoRastreamento ($meta_id, $object_id, $meta_key, $meta_value) {
    if ($meta_key == "_correios_tracking_code") {
        echo "meta_id: {$meta_id} \n object_id: {$object_id} \n meta_key: {$meta_key} \n meta_value: {$meta_value} \n";
        die;
    }
}

