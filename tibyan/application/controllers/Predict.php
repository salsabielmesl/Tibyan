<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Predict extends MY_Controller {

    private $ML_BASE = 'https://tibyan-api-tfm64sq5hq-uc.a.run.app';

    public function __construct() {
        parent::__construct();
        $this->load->model('HealthLogs_model');
    }

    // ── POST /predict/run ────────────────────────────────────────────────
    public function run() {
        $input = $this->json_body;

        if (empty($input['upid'])) {
            return $this->_json_response(400, ['error' => 'UPID is required']);
        }

        $upid = $input['upid'];

        // 1. Get latest health log
        $log = $this->HealthLogs_model->get_latest_log($upid);
        if (!$log) {
            return $this->_json_response(404, ['error' => 'No health log found for this UPID']);
        }

        // 2. Map DB columns → Python NHANES field names (skip zeros and nulls)
        $payload = array_filter([
            'LBXGLU'       => ($log->glucose        !== null && $log->glucose        > 0) ? (float)$log->glucose        : null,
            'LBXIN'        => ($log->insulin         !== null && $log->insulin         > 0) ? (float)$log->insulin         : null,
            'BMXWAIST'     => ($log->circumference   !== null && $log->circumference   > 0) ? (float)$log->circumference   : null,
            'BMXBMI'       => ($log->bmi             !== null && $log->bmi             > 0) ? (float)$log->bmi             : null,
            'LBXCP'        => ($log->cpeptide        !== null && $log->cpeptide        > 0) ? (float)$log->cpeptide        : null,
            'LBDHDD'       => ($log->cholesterol_hdl !== null && $log->cholesterol_hdl > 0) ? (float)$log->cholesterol_hdl : null,
            'LBDLDL'       => ($log->ldl             !== null && $log->ldl             > 0) ? (float)$log->ldl             : null,
            'LBXTR'        => ($log->triglycerides   !== null && $log->triglycerides   > 0) ? (float)$log->triglycerides   : null,
            'LBXSAL'       => ($log->albumin         !== null && $log->albumin         > 0) ? (float)$log->albumin         : null,
            'LBXSC3SI'     => ($log->bicarbonate     !== null && $log->bicarbonate     > 0) ? (float)$log->bicarbonate     : null,
            'LBXSATSI'     => ($log->alt             !== null && $log->alt             > 0) ? (float)$log->alt             : null,
            'URXUMA'       => ($log->ualbumin        !== null && $log->ualbumin        > 0) ? (float)$log->ualbumin        : null,
            'LBDSBUSI'     => ($log->nitrogen        !== null && $log->nitrogen        > 0) ? (float)$log->nitrogen        : null,
            'VNAVEBPXSY'   => ($log->systolic_bp     !== null && $log->systolic_bp     > 0) ? (float)$log->systolic_bp     : null,
            'VNLBAVEBPXDI' => ($log->diastolic_bp    !== null && $log->diastolic_bp    > 0) ? (float)$log->diastolic_bp    : null,
            'BMPWHR'       => ($log->waist_hip_ratio !== null && $log->waist_hip_ratio > 0) ? (float)$log->waist_hip_ratio : null,
            'BMXSAD1'      => ($log->sagittal        !== null && $log->sagittal        > 0) ? (float)$log->sagittal        : null,
        ], fn($v) => $v !== null);

        // 3. Get ML JWT token
        $token_resp = $this->_ml_call('/auth/token', 'POST', ['upid' => $upid]);
        if (empty($token_resp['access_token'])) {
            return $this->_json_response(502, ['error' => 'Failed to get ML auth token', 'detail' => $token_resp]);
        }
        $token = $token_resp['access_token'];

        // 4. Ensure consent is registered
        $this->_ml_call('/consent/agree', 'POST', [
            'upid'                  => $upid,
            'policy_agreed_version' => 'v1.0',
            'research_opt_in'       => false
        ], $token);

        // 5. Call Python /predict
        $ml = $this->_ml_call('/predict', 'POST', $payload, $token);

        if (empty($ml['status']) || $ml['status'] !== 'success') {
            return $this->_json_response(422, [
                'error'  => 'ML prediction rejected or failed',
                'status' => $ml['status'] ?? 'unknown',
                'issues' => $ml['issues'] ?? [],
                'detail' => $ml
            ]);
        }

        // 6. Extract subtype — only present when prediction=1 (diabetic)
        // Python API returns it as 'subtype_classification'
        $subtype_data = $ml['subtype_classification'] ?? [];
        // If it was rejected by the pipeline it returns an 'issues' key
        if (!empty($subtype_data['issues'])) {
            $subtype_data = [];
        }

        // 7. Save to prediction_records
        $db_analyt = $this->load->database('analytical', TRUE);
        $db_analyt->insert('prediction_records', [
            'upid'                => $upid,
            'model_version_id'    => $ml['model_version']  ?? 'v4_fixed',
            'risk_level_dm'       => $ml['risk_level']     ?? '',
            'risk_level'          => $ml['risk_level']     ?? '',
            'probability'         => $ml['probability']    ?? 0,
            'confidence_score'    => isset($ml['confidence']) ? (strpos($ml['confidence'], 'High') !== false ? 0.9 : (strpos($ml['confidence'], 'Medium') !== false ? 0.6 : 0.3)) : 0,
            'safe_fail_triggered' => 0,
            'feature_input_type'  => $ml['confidence']    ?? '',
            'threshold_used'      => $ml['threshold_used'] ?? 0,
            'explanation_json'    => json_encode($ml['explanation'] ?? []),
            'subtype'             => $subtype_data['predicted_subtype'] ?? null,
            'subtype_json'        => json_encode($subtype_data),
            'integrity_json'      => json_encode($ml['data_integrity'] ?? []),
            'longitudinal_json'   => json_encode($ml['longitudinal'] ?? []),
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        // 8. Return full result to frontend
        return $this->_json_response(200, [
            'status'        => $ml['status'],
            'prediction'    => $ml['prediction'],
            'probability'   => $ml['probability'],
            'risk_level'    => $ml['risk_level'],
            'confidence'    => $ml['confidence'],
            'threshold_used'=> $ml['threshold_used'],
            'model_version' => $ml['model_version'],
            'timestamp'     => $ml['timestamp'],
            'explanation'   => $ml['explanation']    ?? [],
            'subtype'       => $subtype_data,
            'distribution'  => $ml['distribution_data'] ?? [],
            'integrity'     => $ml['data_integrity']    ?? [],
            'longitudinal'  => $ml['longitudinal']      ?? [],
        ]);
    }

    // ── POST /predict/report ─────────────────────────────────────────────
    public function report() {
        // Support both JSON body and GET params
        $upid      = $this->json_body['upid']      ?? $this->input->get('upid')      ?? null;
        $date_from = $this->json_body['date_from'] ?? $this->input->get('date_from') ?? null;
        $date_to   = $this->json_body['date_to']   ?? $this->input->get('date_to')   ?? null;

        // Also support session UPID if not passed
        if (empty($upid)) {
            $upid = $this->session->userdata('upid');
        }

        if (empty($upid) || empty($date_from) || empty($date_to)) {
            return $this->_json_response(400, ['error' => 'upid, date_from, date_to required']);
        }

        // Get ML token
        $token_resp = $this->_ml_call('/auth/token', 'POST', ['upid' => $upid]);
        if (empty($token_resp['access_token'])) {
            return $this->_json_response(502, ['error' => 'Failed to get ML token']);
        }
        $token = $token_resp['access_token'];

        // Ensure consent
        $this->_ml_call('/consent/agree', 'POST', [
            'upid'                  => $upid,
            'policy_agreed_version' => 'v1.0',
            'research_opt_in'       => false
        ], $token);

        // Call PDF endpoint — returns raw PDF bytes
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->ML_BASE . '/report/generate');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'date_from' => $date_from,
            'date_to'   => $date_to,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        $pdf_bytes = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || empty($pdf_bytes)) {
            return $this->_json_response(502, [
                'error' => 'PDF generation failed',
                'code'  => $http_code,
                'hint'  => 'No predictions found for this date range, or ML service error'
            ]);
        }

        // Stream PDF directly
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="tibyan_report_' . $date_from . '_' . $date_to . '.pdf"');
        header('Content-Length: ' . strlen($pdf_bytes));
        echo $pdf_bytes;
        exit;
    }

    // ── Internal: call ML API ────────────────────────────────────────────
    private function _ml_call($path, $method = 'GET', $data = null, $token = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->ML_BASE . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) return ['error' => $err];
        return json_decode($response, true) ?? [];
    }
}