<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuantumController extends Controller
{
    private static $PY_COMMAND = "python3";
    private static $PY_DIR = "/../qiskit"; // Qiskit scripts directory relative to the /public folder
    public static $GATES = [
        'I', 'H', 'X', 'CX', 'Tfl', 'SWAP', 'Z', 'S', 'S+', 'T', 'T+', 'P'
    ];
    public static $GATES_DATA = [
        'I' => [
            'qubits' => 1,
            'parameters' => 0
        ],
        'H' => [
            'qubits' => 1,
            'parameters' => 0
        ],
        'X' => [
            'qubits' => 1,
            'parameters' => 0
        ],
        'CX' => [
            'qubits' => 2,
            'parameters' => 0
        ],
        'Tfl' => [
            'qubits' => 3,
            'parameters' => 0
        ],
        'SWAP' => [
            'qubits' => 2,
            'parameters' => 0
        ],
        'Z' => [
            'qubits' => 1,
            'parameters' => 0
        ],
        'S' => [
            'qubits' => 1,
            'parameters' => 0
        ],
        'S+' => [
            'qubits' => 1,
            'parameters' => 0
        ],
        'T' => [
            'qubits' => 1,
            'parameters' => 0
        ],
        'T+' => [
            'qubits' => 1,
            'parameters' => 0
        ],
        'P' => [
            'qubits' => 1,
            'parameters' => 1
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
                if ($gate->gate == "P") {
                    $instructionsStr .= $gate->gate . " " . implode(" ", $gate->qubits) . " '" . $gate->params[0] . "' ";
                } else {
                    $instructionsStr .= $gate->gate . " " . implode(" ", $gate->qubits) . " ";
                }
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
            // $res["_command"] = $command;
        }

        return $res;
    }

    public static function validateSimpleFraction($expression) {
        $re = '/^\-?(\d+|\d*pi)(\/(\-?(\d+|\d*pi)))?$/';
        return preg_match($re, $expression);
    }

}
