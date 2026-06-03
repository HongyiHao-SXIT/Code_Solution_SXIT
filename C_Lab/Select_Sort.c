#include <stdio.h>

int main() {
  float a[10];
  float temp;
  int i;
  int j;
  int min;
  printf("请输入10个地区的面积: ");
  for (i = 0; i < 10; i++) {
    scanf("%f", &a[i]);
  }
  for (i = 0; i < 9; i++) {
    min = i;
    for (j = i + 1; j < 10; j++) {
      if (a[j] < a[min]) {
        min = j;
      }
    }
    temp = a[i];
    a[i] = a[min];
    a[min] = temp;
  }
  printf("排序后的面积: ");
  for (i = 0; i < 10; i++) {
    printf("%.2f ", a[i]);
  }
  return 0;
}