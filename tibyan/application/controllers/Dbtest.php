<?php
class Dbtest extends CI_Controller {
    public function index() {
        echo json_encode(scandir(APPPATH . 'controllers/'));
    }
}
