#include <math.h>
#include <stdio.h>

int main() {
  int i;
  int j;
  int k;
  int count = 0;
  for (i = 101; i <= 200; i += 2) {
    k = sqrt(i);
    for (j = 2; j <= k; j++) {
      if (i % j == 0) {
        break;
      }
    }
    if (j > k) {
      printf("%d ", i);
      count++;
      if (count % 5 == 0) {
        printf("\n");
      }
    }
  }
  return 0;
}