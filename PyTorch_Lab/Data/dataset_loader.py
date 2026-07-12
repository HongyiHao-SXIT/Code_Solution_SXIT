import torch
from torchvision import datasets
from torchvision.transforms import v2
import matplotlib.pyplot as plt


def main():
    """Download FashionMNIST dataset and visualize random samples."""
    training_data = datasets.FashionMNIST(
        root="Data/FashionMNIST",
        train=True,
        download=True,
        transform=v2.Compose([v2.ToImage(), v2.ToDtype(torch.float32, scale=True)])
    )

    test_data = datasets.FashionMNIST(
        root="Data/FashionMNIST",
        train=False,
        download=True,
        transform=v2.Compose([v2.ToImage(), v2.ToDtype(torch.float32, scale=True)])
    )

    labels_map = {
        0: "T-Shirt",
        1: "Trouser",
        2: "Pullover",
        3: "Dress",
        4: "Coat",
        5: "Sandal",
        6: "Shirt",
        7: "Sneaker",
        8: "Bag",
        9: "Ankle Boot",
    }

    print(f"Training samples:  {len(training_data)}")
    print(f"Test samples:      {len(test_data)}")
    print(f"Classes:           {len(labels_map)}")
    print()

    figure = plt.figure(figsize=(8, 8))
    cols, rows = 3, 3
    for i in range(1, cols * rows + 1):
        sample_idx = int(torch.randint(len(training_data), size=(1,)).item())
        img, label = training_data[sample_idx]
        figure.add_subplot(rows, cols, i)
        plt.title(labels_map[label])
        plt.axis("off")
        plt.imshow(img.squeeze(), cmap="gray")
    plt.show()


if __name__ == "__main__":
    main()
