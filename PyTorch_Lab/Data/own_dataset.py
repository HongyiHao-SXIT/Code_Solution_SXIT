import torch
from torch.utils.data import Dataset
from torch.utils.data import DataLoader


class CustomImageDataset(Dataset):
    """Demonstrate how to create a custom PyTorch Dataset.

    This example creates a synthetic dataset of 1000 samples where each
    sample is a random 8x8 feature map with a label derived from the sum.
    """

    def __init__(self, num_samples: int = 1000, num_features: int = 3, size: int = 8):
        # Generate random synthetic data
        self.num_samples = num_samples
        self.data = torch.randn(num_samples, num_features, size, size)
        # Label = 0 if channel-0 sum < 0, else 1 (toy classification)
        self.labels = (self.data[:, 0].sum(dim=(1, 2)) >= 0).long()

    def __len__(self) -> int:
        return self.num_samples

    def __getitem__(self, index: int):
        return self.data[index], self.labels[index]


def main():
    """Create a custom dataset and iterate with DataLoader."""
    dataset = CustomImageDataset(num_samples=1000)

    print(f"Dataset size:      {len(dataset)}")
    print(f"Sample shape:      {dataset[0][0].shape}")
    print(f"Sample label:      {dataset[0][1]}")
    print()

    dataloader = DataLoader(dataset, batch_size=4, shuffle=True)
    print("Iterating over DataLoader:")
    for batch_idx, (features, labels) in enumerate(dataloader):
        print(f"  Batch {batch_idx}: features={list(features.shape)}, labels={list(labels.shape)}")
        if batch_idx >= 2:
            break

    print("\nAll done!")


if __name__ == "__main__":
    main()