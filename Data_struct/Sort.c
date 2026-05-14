#include <stdio.h>

int get_digit(int num, int d) {
    int p = 1;
    while (d--) p *= 10;
    return (num / p) % 10;
}

void RadixSort(int a[], int n) {
    int bucket[10][10], count[10];
    for (int d = 0; d < 3; d++) {
        for (int i = 0; i < 10; i++) count[i] = 0;
        for (int i = 0; i < n; i++) {
            int digit = get_digit(a[i], d);
            bucket[digit][count[digit]++] = a[i];
        }
        int k = 0;
        for (int i = 0; i < 10; i++)
            for (int j = 0; j < count[i]; j++) a[k++] = bucket[i][j];
    }
}

int main() {
    int a[10] = {123, 456, 121, 987, 342, 555, 102, 888, 765, 432};
    RadixSort(a, 10);
    for (int i = 0; i < 10; i++) printf("%d ", a[i]);
    return 0;
}