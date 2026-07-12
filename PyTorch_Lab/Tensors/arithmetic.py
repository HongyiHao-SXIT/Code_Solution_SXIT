import torch


def main():
    """Demonstrate matrix multiplication and element-wise operations."""
    tensor = torch.ones(4, 4)

    # Matrix multiplication
    y1 = tensor @ tensor.T
    y2 = tensor.matmul(tensor.T)
    y3 = torch.rand_like(y1)
    torch.matmul(tensor, tensor.T, out=y3)

    # Element-wise multiplication
    z1 = tensor * tensor
    z2 = tensor.mul(tensor)
    z3 = torch.rand_like(tensor)
    torch.mul(tensor, tensor, out=z3)

    print(f"Matrix multiplication (@ operator):\n{y1}\n")
    print(f"Matrix multiplication (matmul):\n{y2}\n")
    print(f"Matrix multiplication (out=):\n{y3}\n")
    print(f"Element-wise multiplication (* operator):\n{z1}\n")
    print(f"Element-wise multiplication (mul):\n{z2}\n")
    print(f"Element-wise multiplication (out=):\n{z3}")


if __name__ == "__main__":
    main()
