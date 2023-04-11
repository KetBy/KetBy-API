<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuantumController extends Controller
{
    private static $PY_COMMAND = "python3";
    private static $PY_DIR = "/../qiskit"; // Qiskit scripts directory relative to the /public folder
    public static $GATES = [
        'I', 'H', 'X', 'CX', 'Tfl', 'SWAP'
    ];
    public static $GATES_DATA = [
        'I' => [
            'qubits' => 1
        ],
        'H' => [
            'qubits' => 1
        ],
        'X' => [
            'qubits' => 1
        ],
        'CX' => [
            'qubits' => 2
        ],
        'Tfl' => [
            'qubits' => 3
        ],
        'SWAP' => [
            'qubits' => 2
        ]
    ];

    public static function getInfo($qubits, $gates) {
        $res = [
            "probabilities" => null
        ];

        if ($qubits > 5) {
            return $res;
        }

        $instructions = count($gates);
        $instructionsStr = "";

        // Prepare the instructions string
        foreach ($gates as $gate) {
            if ($gate->gate == "CX" || $gate->gate == "Tfl") {
                $instructionsStr .= $gate->gate . " " . implode(" ", array_reverse($gate->qubits)) . " ";
            } else {
                $instructionsStr .= $gate->gate . " " . implode(" ", $gate->qubits) . " ";
            }
        }

        $command = self::$PY_COMMAND . " " . getcwd() . self::$PY_DIR . "/get_info.py $qubits $instructions $instructionsStr";
        $output = null;
        $resultCode = null;
        $result = exec(stripslashes($command), $output, $resultCode);

        if ($resultCode == 0) {
            $probabilities = [];
            foreach ($output as $line) {
                $probabilities[] = [
                    "value" => explode(" ", $line)[0], 
                    "probability" => floatval(explode(" ", $line)[1])
                ];
            }
            $res["probabilities"] = $probabilities;
        }

        return $res;
    }

}
