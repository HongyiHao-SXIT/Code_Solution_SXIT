import torch


def main():
    """Demonstrate basic tensor creation methods."""
    data = [[1, 2], [3, 4]]
    x_data = torch.tensor(data)

    print(f"Tensor from data:\n{x_data}\n")

    shape = (2, 4)
    rand_tensor = torch.rand(shape)
    ones_tensor = torch.ones(shape)
    zeros_tensor = torch.zeros(shape)

    print(f"Random Tensor:\n{rand_tensor}\n")
    print(f"Ones Tensor:\n{ones_tensor}\n")
    print(f"Zeros Tensor:\n{zeros_tensor}")


if __name__ == "__main__":
    main()

