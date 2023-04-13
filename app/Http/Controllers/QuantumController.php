<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuantumController extends Controller
{
    private static $PY_COMMAND = "python3";
    private static $PY_DIR = "/../qiskit"; // Qiskit scripts directory relative to the /public folder
    public static $GATES = [
        'I', 'H', 'X', 'CX', 'Tfl', 'SWAP', 'Z', 'S', 'S+', 'T', 'T+', 'P', 'RX', 'RY', 'RZ', 'Y', 'U', 'SX', 'SX+'
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
        ],
        'RX' => [
            'qubits' => 1,
            'parameters' => 1
        ],
        'RY' => [
            'qubits' => 1,
            'parameters' => 1
        ],
        'RZ' => [
            'qubits' => 1,
            'parameters' => 1
        ],
        'Y' => [
            'qubits' => 1,
            'parameters' => 0
        ],
        'U' => [
            'qubits' => 1,
            'parameters' => 3
        ],
        'SX' => [
            'qubits' => 1,
            'parameters' => 0
        ],
        'SX+' => [
            'qubits' => 1,
            'parameters' => 0
        ],
    ];

    public static function getInfo($qubits, $gates) {
        $res = [
            "probabilities" => null,
            "qubits" => null
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
            } else  if ($gate->gate == "P" || $gate->gate == "RX" || $gate->gate == "RY" || $gate->gate == "RZ") {
                $instructionsStr .= $gate->gate . " " . implode(" ", $gate->qubits) . " '" . $gate->params[0] . "' ";
            } else  if ($gate->gate == "U") {
                $instructionsStr .= $gate->gate . " " . implode(" ", $gate->qubits) 
                . " '" . $gate->params[0] . "'"
                . " '" . $gate->params[1] . "'"
                . " '" . $gate->params[2] . "' ";
            } else {
                $instructionsStr .= $gate->gate . " " . implode(" ", $gate->qubits) . " ";
            }
        }

        $command = self::$PY_COMMAND . " " . getcwd() . self::$PY_DIR . "/get_info.py $qubits $instructions $instructionsStr";
        $output = null;
        $resultCode = null;
        $result = exec(stripslashes($command), $output, $resultCode);

        if ($resultCode == 0) {
            $probabilities_arr = [];
            $qubits_arr = [];
            $i = 0;
            foreach ($output as $line) {
                // Get the probabilities of all possible outcomes
                if ($i < $qubits ** 2 - 1) {
                    $probabilities_arr[] = [
                        "value" => explode(" ", $line)[0], 
                        "probability" => round(floatval(explode(" ", $line)[1]), 6)
                    ];
                }
                // Get phase disk data
                // Get the probability of each qubit to be in state 1
                if ($i >= $qubits ** 2 - 1 && $i < $qubits ** 3) {
                    $qubits_arr[$i - $qubits ** 2 + 1] = [
                        "probability_1" => round(floatval(explode(" ", $line)[1]), 6),
                        "phase" => round(floatval(explode(" ", $line)[2]), 6),
                        "phase_expr" => explode(" ", $line)[3]
                    ];
                }
                $i += 1; 
            }
            $res["probabilities"] = $probabilities_arr;
            $res["qubits"] = $qubits_arr;
        }

        $res["_command"] = $command;

        return $res;
    }

    public static function validateSimpleFraction($expression) {
        $re = '/^\-?(\d+|\d*pi)(\/(\-?(\d+|\d*pi)))?$/';
        return preg_match($re, $expression);
    }

}
