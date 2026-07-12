import torch
import numpy as np


def main():
    """Demonstrate NumPy-to-Tensor conversion and memory sharing."""
    np_array = np.ones(5)
    tensor = torch.from_numpy(np_array)

    np.add(np_array, 1, out=np_array)
    print(f"Tensor  : {tensor}")
    print(f"NumPy   : {np_array}")
    print("(Tensor and NumPy array share memory — modifying one affects both)")


if __name__ == "__main__":
    main()
