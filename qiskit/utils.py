def convert_to_binary(number, length):

    binary = bin(number)[2:] # convert num to binary and remove '0b' prefix
    zeros_to_add = length - len(binary) # calculate number of zeros to add

    if zeros_to_add > 0:
        binary = '0' * zeros_to_add + binary # add zeros to the front if needed

    return binary