<?php

// Cria o agendamento
function verificarAgendamento () {

    // Removendo o agendamento
    // $proximaExecucao = wp_next_scheduled( 'rafael_cron_hook' );
    // wp_unschedule_event( $proximaExecucao, 'rafael_cron_hook' );

    // Inserindo o agendamento
    if ( !wp_next_scheduled( 'rafael_cron_hook' )) {
        wp_schedule_event( time(), 'two-minutes', 'rafael_cron_hook' ); 
    }
}
add_action('init', 'verificarAgendamento');


// O que será enviado a cada agendamento
function notificaAdm () {
    $email = get_bloginfo('admin_email');
    wp_mail( $email, 'Execução de evento agendado', 'Um agendamento foi executado agora.' );
}
add_action( 'rafael_cron_hook', 'notificaAdm' );

// Cria um novo intervalo
function intervalos ($schedules) {
    $schedules['two-minutes'] = [
        'interval' => 120, // intervalo em segundos, neste caso, 120 segundos
        'display' => 'A cada dois minutos', // descrição do agendamento
    ];
    return $schedules;
}

add_filter( 'cron_schedulels', 'intervalos' );