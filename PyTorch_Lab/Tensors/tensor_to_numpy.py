import torch


def main():
    """Demonstrate Tensor-to-NumPy conversion and shared memory."""
    tensor = torch.ones(5)
    print(f"Tensor (before):       {tensor}")

    np_array = tensor.numpy()
    print(f"NumPy  (before):       {np_array}")

    tensor.add_(1)
    print(f"Tensor (after add_):   {tensor}")
    print(f"NumPy  (after add_):   {np_array}")
    print("(Tensor and NumPy array share memory — modifying one affects both)")


if __name__ == "__main__":
    main()
