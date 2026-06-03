#include <stdio.h>

void reverse(int *p, int n) {
  int temp;
  int *i;
  int *j;
  for (i = p, j = p + n - 1; i < j; i++, j--) {
    temp = *i;
    *i = *j;
    *j = temp;
  }
}

int main() {
  int n;
  int a[100];
  printf("输入n: ");
  scanf("%d", &n);
  for (int i = 0; i < n; i++) {
    scanf("%d", &a[i]);
  }
  reverse(a, n);  // 数组名作为实参
  for (int i = 0; i < n; i++) {
    printf("%d ", a[i]);
  }
  return 0;
}