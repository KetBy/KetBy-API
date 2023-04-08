<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QiskitController extends Controller
{
    private static $PY_COMMAND = "python3";
    private static $PY_DIR = "/../qiskit"; // Qiskit scripts directory relative to the /public folder

    public static function getInfo($qubits, $gates) {
        $qubits = 2;
        $json = json_encode($gates);
        $command = self::$PY_COMMAND . " " . getcwd() . self::$PY_DIR . "/get_info.py $qubits '$json'";
        // $command = self::$PY_COMMAND . " " . getcwd() . self::$PY_DIR . "/get_info.py";
        $output = null;
        $resultCode = null;
        $result = exec(stripslashes($command), $output, $resultCode);

        return $resultCode == 0? json_decode($output[0]) : null;
    }
}
