#include <stdio.h>

int main() {
  char c1;
  char c2;
  printf("请输入一个大写字母: ");
  scanf("%c", &c1);
  c2 = c1 + 32;
  printf("对应的小写字母是: %c\n", c2);
  return 0;
}