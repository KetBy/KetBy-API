import sys
from qiskit import QuantumCircuit, Aer, quantum_info
from utils import convert_to_binary, eval_simple_fraction
import numpy as np
from fractions import Fraction
import math

# Call example: 
# `python3 get_info.py 2 5 CX 0 1 H 0 H 0 I 1 H 1`
# for the instructions:
# [
#   {'gate': 'CX', 'qubits': [0, 1]}, 
#   {'gate': 'H', 'qubits': [0]}, 
#   {'gate': 'H', 'qubits': [0]}, 
#   {'gate': 'I', 'qubits': [1]}, 
#   {'gate': 'H', 'qubits': [1]}
# ]

# Parse command-line arguments
num_qubits = int(sys.argv[1])
num_instructions = int(sys.argv[2])

# Read the instructions
instructions = []
pos = 3
for _ in range(num_instructions):
    gate = sys.argv[pos]
    qubits = []
    parameters = []
    if gate in ['I', 'H', 'X', 'Z', 'S', 'S+', 'T', 'T+', 'Y', 'SX', 'SX+']:
        qubits = [int(sys.argv[pos+1])]
        pos += 2
    elif gate in ['CX', 'SWAP']:
        qubits = [int(sys.argv[pos + 1]), int(sys.argv[pos + 2])] # KetBy first saves the target, then the control (as opposed to qiskit)
        pos += 3
    elif gate in ['Tfl']:
        qubits = [int(sys.argv[pos + 1]), int(sys.argv[pos + 2]), int(sys.argv[pos + 3])]
        pos += 4
    elif gate in ['P', 'RX', 'RY', 'RZ']:
        qubits = [int(sys.argv[pos + 1])]
        parameters = [eval_simple_fraction(sys.argv[pos + 2])]
        pos += 3
    elif gate in ['U']:
        qubits = [int(sys.argv[pos + 1])]
        parameters = [
            eval_simple_fraction(sys.argv[pos + 2]), 
            eval_simple_fraction(sys.argv[pos + 3]), 
            eval_simple_fraction(sys.argv[pos + 4])
        ]
        pos += 5
    instructions.append({"gate": gate, "qubits": qubits, "parameters": parameters})

# Create a quantum circuit with the specified number of qubits
circuit = QuantumCircuit(num_qubits)

# Apply the gates to the circuit
for instruction in instructions:
    if instruction["gate"] == 'H':
        circuit.h(instruction["qubits"][0])
    if instruction["gate"] == 'X':
        circuit.x(instruction["qubits"][0])
    if instruction["gate"] == 'CX':
        circuit.cx(instruction["qubits"][0], instruction["qubits"][1])
    if instruction["gate"] == "Tfl":
        circuit.toffoli(instruction["qubits"][0], instruction["qubits"][1], instruction["qubits"][2])
    if instruction["gate"] == "SWAP":
        circuit.swap(instruction["qubits"][0], instruction["qubits"][1])
    if instruction["gate"] == "Z":
        circuit.z(instruction["qubits"][0])
    if instruction["gate"] == "S":
        circuit.s(instruction["qubits"][0])
    if instruction["gate"] == "S+":
        circuit.sdg(instruction["qubits"][0])
    if instruction["gate"] == "T":
        circuit.t(instruction["qubits"][0])
    if instruction["gate"] == "T+":
        circuit.tdg(instruction["qubits"][0])
    if instruction["gate"] == "P":
        circuit.p(instruction["parameters"][0], instruction["qubits"][0])
    if instruction["gate"] == "RX":
        circuit.rx(instruction["parameters"][0], instruction["qubits"][0])
    if instruction["gate"] == "RY":
        circuit.ry(instruction["parameters"][0], instruction["qubits"][0])
    if instruction["gate"] == "RZ":
        circuit.rz(instruction["parameters"][0], instruction["qubits"][0])
    if instruction["gate"] == 'Y':
        circuit.y(instruction["qubits"][0])
    if instruction["gate"] == 'U':
        circuit.u(instruction["parameters"][0], instruction["parameters"][1], instruction["parameters"][2], instruction["qubits"][0])
    if instruction["gate"] == "SX":
        circuit.sx(instruction["qubits"][0])
    if instruction["gate"] == "SX+":
        circuit.sxdg(instruction["qubits"][0])
    
backend = Aer.get_backend('statevector_simulator')
result = backend.run(circuit, shots=1).result()
statevector = np.array(result.get_statevector())

probs = quantum_info.Statevector(statevector).probabilities()
counts = result.get_counts()

# Find probabilities of all possible outcomes
for i in range(2**num_qubits):
    outcome = convert_to_binary(i, num_qubits)
    print("{} {}".format(outcome, counts.get(outcome) * 100 if counts.get(outcome) else 0))

# Compute the probability of each qubit being in state "1"
num_shots = sum(counts.values())
qubit_probs = [0] * num_qubits
for outcome, count in counts.items():
    for qubit, bit in enumerate(outcome[::-1]):
        if bit == '1':
            qubit_probs[qubit] += count
for qubit in range(num_qubits):
    qubit_probs[qubit] /= num_shots
    qubit_probs[qubit] *= 100

# Compute the phase of each qubit
phases = []
for i in range(num_qubits):
    basis_state_0 = np.zeros(2**num_qubits)
    basis_state_0[0] = 1
    basis_state_1 = np.zeros(2**num_qubits)
    basis_state_1[2**i] = 1
    projection_0 = np.dot(basis_state_0, statevector)
    projection_1 = np.dot(basis_state_1, statevector)
    phase = np.angle(projection_1 / projection_0)
    numerator, denominator = Fraction(phase / np.pi).limit_denominator(100).as_integer_ratio()
    expression = ""
    if numerator == 0:
        expression = "0"
    elif abs(numerator) == 1:
        if numerator == -1:
            if denominator == 1:
                expression = "-pi"
            else:
                expression = "-pi/{}".format(denominator)
        else:
            if denominator == 1:
                expression = "-pi"
            else:
                expression = "-pi/{}".format(denominator)
    else:
        if denominator == 1:
            expression = "{}pi".format(numerator)
        else:
            expression = "{}pi/{}".format(numerator, denominator)
    phase_deg = math.degrees(phase)
    phases.append((phase_deg, expression))

for i in range(num_qubits):
    print(i, qubit_probs[i], phases[i][0], phases[i][1])

