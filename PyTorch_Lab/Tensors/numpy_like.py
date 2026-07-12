import torch


def main():
    """Demonstrate NumPy-like indexing and concatenation operations."""
    tensor = torch.ones(4, 4)
    print(f"First row: {tensor[0]}")
    print(f"First column: {tensor[:, 0]}")
    print(f"Last column: {tensor[..., -1]}")
    tensor[:, 1] = 0
    print(f"After modifying column 1:\n{tensor}")

    t1 = torch.cat([tensor, tensor, tensor], dim=1)
    print(f"Concatenated along dim=1:\n{t1}")


if __name__ == "__main__":
    main()
