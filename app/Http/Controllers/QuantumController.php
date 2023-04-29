<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuantumController extends Controller
{
    private static $PY_COMMAND = "python3";
    private static $PY_DIR = "/../qiskit"; // Qiskit scripts directory relative to the /public folder
    public static $GATES = [
        'I', 'H', 'X', 'CX', 'Tfl', 'SWAP', 'Z', 'S', 'S+', 'T', 'T+', 'P', 'RX', 'RY', 'RZ', 'Y', 'U', 'SX', 'SX+', 'M'
    ];
    public static $GATES_DATA = [
        'I' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'H' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'X' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'CX' => [
            'qubits' => 2,
            'parameters' => 0,
            'bits' => 0
        ],
        'Tfl' => [
            'qubits' => 3,
            'parameters' => 0,
            'bits' => 0
        ],
        'SWAP' => [
            'qubits' => 2,
            'parameters' => 0,
            'bits' => 0
        ],
        'Z' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'S' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'S+' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'T' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'T+' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'P' => [
            'qubits' => 1,
            'parameters' => 1,
            'bits' => 0
        ],
        'RX' => [
            'qubits' => 1,
            'parameters' => 1,
            'bits' => 0
        ],
        'RY' => [
            'qubits' => 1,
            'parameters' => 1,
            'bits' => 0
        ],
        'RZ' => [
            'qubits' => 1,
            'parameters' => 1,
            'bits' => 0
        ],
        'Y' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'U' => [
            'qubits' => 1,
            'parameters' => 3,
            'bits' => 0
        ],
        'SX' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'SX+' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 0
        ],
        'M' => [
            'qubits' => 1,
            'parameters' => 0,
            'bits' => 1
        ]
    ];

    public static function getInfo($gates, $qubits, $bits) {
        $res = [
            "probabilities" => null,
            "probabilities_message" => null,
            "qubits" => null,
            "statevector" => null
        ];

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
            } else if ($gate->gate == "M") {
                $instructionsStr .= $gate->gate . " " . $gate->qubits[0] . " " . $gate->bits[0] . " ";
            } else {
                $instructionsStr .= $gate->gate . " " . implode(" ", $gate->qubits) . " ";
            }
        }

        $command = self::$PY_COMMAND . " " . getcwd() . self::$PY_DIR . "/get_info.py $qubits $bits $instructions $instructionsStr";
        $output = null;
        $resultCode = null;
        $result = exec(stripslashes($command), $output, $resultCode);

        if ($resultCode == 0) {
            $json = $output[0];
            $res = json_decode($json);
        }

        $res->_command = $command;

        return $res;
    }

    public static function validateSimpleFraction($expression) {
        $re = '/^\-?(\d+|\d*pi)(\/(\-?(\d+|\d*pi)))?$/';
        return preg_match($re, $expression);
    }

}
