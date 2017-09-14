<?php

/**
 * Criando classe para acessar o banco de forma mais organizada
	$database = new Database();
	$results = $database->get_results("SELECT * FROM wp_options");
	print_r($results);
 */
class Database {

    private $wpdb = false;

    public function __construct() {
        global $wpdb;

        if (is_object($wpdb)) {
            $this->wpdb = $wpdb;
        }
    }

    public function create_database () {

    }

    public function get_results($data) {
        return $this->wpdb->get_results($data);
    }

    public function list () {}
    public function insert () {}
    public function update () {}
    public function delete () {}
} 