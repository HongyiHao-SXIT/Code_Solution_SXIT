#include <iostream>
#include <vector>

int main() {
    int rows, cols;
    std::cin >> rows >> cols;

    std::vector<std::vector<int>> matrix(rows, std::vector<int>(cols));

    int top = 0;
    int bottom = rows - 1;
    int left = 0;
    int right = cols - 1;
    int value = 1;

    while (top <= bottom && left <= right) {
        for (int j = left; j <= right; j++) {
            matrix[top][j] = value++;
        }
        top++;

        for (int i = top; i <= bottom; i++) {
            matrix[i][right] = value++;
        }
        right--;

        if (top <= bottom) {
            for (int j = right; j >= left; j--) {
                matrix[bottom][j] = value++;
            }
            bottom--;
        }

        if (left <= right) {
            for (int i = bottom; i >= top; i--) {
                matrix[i][left] = value++;
            }
            left++;
        }
    }

    for (int row = 0; row < rows; row++) {
        for (int col = 0; col < cols; col++) {
            if (col != 0) {
                std::cout << ' ';
            }
            std::cout << matrix[row][col];
        }
        std::cout << std::endl;
    }

    return 0;
}
