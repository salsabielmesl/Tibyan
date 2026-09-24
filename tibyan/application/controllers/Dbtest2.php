<?php
class Dbtest extends CI_Controller {
    public function index() {
        echo json_encode(scandir(APPPATH . 'controllers/'));
        $db2 = $this->load->database('analytical', TRUE);
        if ($db2->conn_id) {
            echo "Successfully connected to tibyan_analytical on port 3308!";
        } else {
            echo "Connection failed.";
        }
    }
}