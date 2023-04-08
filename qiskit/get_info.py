import sys
import json
from qiskit import QuantumCircuit, Aer, quantum_info

# Parse command-line arguments
num_qubits = int(sys.argv[1])
gates = json.loads(sys.argv[2])

# Create a quantum circuit with the specified number of qubits
circuit = QuantumCircuit(num_qubits)

# Apply the gates to the circuit
for gate in gates:
    if gate["gate"] == 'H':
        circuit.h(gate["qubits"][0])
    if gate["gate"] == 'X':
        circuit.x(gate["qubits"][0])
    if gate["gate"] == 'CX':
        circuit.cx(gate["qubits"][0], gate["qubits"][1])
    
backend = Aer.get_backend('statevector_simulator')

outputstate = backend.run(circuit, shots=1).result().get_statevector()

probs = quantum_info.Statevector(outputstate).probabilities()

res = {
    "probabilities": {}
}
for i in range(2 << num_qubits - 1):
    res["probabilities"][i] = probs[i]

print(json.dumps(res))