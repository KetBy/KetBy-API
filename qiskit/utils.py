import math 
from fractions import Fraction
import numpy as np

def convert_to_binary(number, length):

    binary = bin(number)[2:] # convert num to binary and remove '0b' prefix
    zeros_to_add = length - len(binary) # calculate number of zeros to add

    if zeros_to_add > 0:
        binary = '0' * zeros_to_add + binary # add zeros to the front if needed

    return binary

# Evaluate a simple fraction that contains pi
# Such as +-x, +-pi, +-pi/+-x, +-x/+-pi, +-x/+-piy, +-pix/+-y, +-pix/+-piy (you get it)
def eval_simple_fraction(expression): 
    numerator = 1
    denominator = 1
    # Find the numerator and denominator (they can also contain pi)
    if "/" in expression:
        numeratorStr = expression.split("/")[0]
        denominatorStr = expression.split("/")[1]
    else:
        numeratorStr = expression
        denominatorStr = ""
    # Check if any of them contains pi
    if "pi" in numeratorStr:
        numerator = math.pi 
    if "pi" in denominatorStr:
        denominator = math.pi
    # Get the integers
    numeratorDigits = ""
    for char in numeratorStr:
        if char.isdigit():
            numeratorDigits += char
    if numeratorDigits:
        numerator *= int(numeratorDigits)
    denominatorDigits = ""
    for char in denominatorStr:
        if char.isdigit():
            denominatorDigits += char
    if denominatorDigits:
        denominator *= int(denominatorDigits)
    # Check if either of them is negative
    if numeratorStr.startswith("-"):
        numerator *= -1
    if denominatorStr.startswith("-"):
        denominator *= -1
    return numerator / denominator

# Convert any angle in radians to a point on a circle
def angle_to_expression(angle):
    if np.round(angle, 3) == 0:
        return "0"
    # Reduce angle to [0,2π)
    angle = angle % (2 * np.pi)
    # Convert angle to a fraction of π
    numerator, denominator = Fraction(angle / np.pi).limit_denominator(100).as_integer_ratio()
    # Construct expression in terms of π
    if numerator == 0:
        expression = "0"
    elif abs(numerator) == 1:
        if numerator == -1:
            if denominator == 1:
                expression = "-π"
            else:
                expression = "-π/{}".format(denominator)
        else:
            if denominator == 1:
                expression = "π"
            else:
                expression = "π/{}".format(denominator)
    else:
        if denominator == 1:
            expression = "{}π".format(numerator)
        else:
            expression = "{}π/{}".format(numerator, denominator)
    return expression