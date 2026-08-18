<?php
/**
 * MLService.php
 * Calls the Python prediction script via shell and returns structured results.
 * Dependencies: config.php
 */

require_once __DIR__ . '/../config/config.php';

class MLService
{
    /**
     * Send features to Python and return prediction array, or null on failure.
     *
     * @param array $features {amount, term_months, annual_income, credit_score,
     *                         employment_status, purpose}
     * @return array|null
     */
    public function predict(array $features): ?array
    {
        // Encode features as JSON and pass as CLI argument
        $json      = base64_encode(json_encode($features));
        $scriptPath = escapeshellarg(ML_SCRIPT);
        $pyBin      = escapeshellcmd(PYTHON_BIN);
        $cmd        = "{$pyBin} {$scriptPath} {$json} 2>&1";

        $output = shell_exec($cmd);

        if (!$output) {
            error_log('[MLService] No output from Python script.');
            return $this->fallbackPrediction($features);
        }

        // The last line of Python output should be JSON
        $lines = array_filter(explode("\n", trim($output)));
        $last  = end($lines);
        $data  = json_decode($last, true);

        if (!is_array($data) || !isset($data['risk_score'])) {
            error_log('[MLService] Invalid Python output: ' . $output);
            return $this->fallbackPrediction($features);
        }

        $data['model_version'] = $data['model_version'] ?? '1.0';
        return $data;
    }

    /**
     * Rule-based fallback when Python is unavailable.
     * Uses credit score + debt-to-income ratio heuristics.
     */
    private function fallbackPrediction(array $f): array
    {
        $creditScore = (int)($f['credit_score'] ?? 600);
        $income      = max((float)($f['annual_income'] ?? 1), 1);
        $amount      = (float)($f['amount'] ?? 0);
        $term        = max((int)($f['term_months'] ?? 12), 1);

        // Monthly payment estimate (simple)
        $monthlyPayment = $amount / $term;
        $monthlyIncome  = $income / 12;
        $dti            = $monthlyPayment / $monthlyIncome;  // debt-to-income ratio

        // Score: 0.0 (best) to 1.0 (worst)
        $creditFactor = 1 - (($creditScore - 300) / 550);   // lower score = higher risk
        $dtiFactor    = min($dti * 2, 1.0);

        $riskScore          = round(($creditFactor * 0.6 + $dtiFactor * 0.4), 4);
        $defaultProbability = round($riskScore * 0.9, 4);
        $approvalProb       = round(1 - $riskScore, 4);

        return [
            'risk_score'           => $riskScore,
            'approval_probability' => $approvalProb,
            'default_probability'  => $defaultProbability,
            'model_version'        => 'fallback-1.0',
        ];
    }
}
