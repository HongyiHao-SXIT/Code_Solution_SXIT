import torch


def main():
    """Demonstrate extracting a single-element value from a tensor."""
    tensor = torch.ones(4, 4)
    agg = tensor.sum()
    agg_item = agg.item()
    print(f"Sum as Python scalar: {agg_item} ({type(agg_item).__name__})")

    print(f"Tensor before in-place addition:\n{tensor}")
    tensor.add_(5)
    print(f"Tensor after in-place addition:\n{tensor}")


if __name__ == "__main__":
    main()
