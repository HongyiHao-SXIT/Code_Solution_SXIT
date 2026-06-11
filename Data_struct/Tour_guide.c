#include <stdio.h>

#define INF 9999
int map[10][10], lowcost[10], mst[10];

void Prim(int n) {
  int min, k;
  for (int i = 1; i < n; i++) {
    lowcost[i] = map[0][i];
    mst[i] = 0;
  }
  mst[0] = -1;
  for (int i = 1; i < n; i++) {
    min = INF;
    k = 0;
    for (int j = 1; j < n; j++) {
      if (mst[j] != -1 && lowcost[j] < min) {
        min = lowcost[j];
        k = j;
      }
    }
    printf("边: %d - %d 长度: %d\n", mst[k], k, min);
    mst[k] = -1;
    for (int j = 1; j < n; j++) {
      if (mst[j] != -1 && map[k][j] < lowcost[j]) {
        lowcost[j] = map[k][j];
        mst[j] = k;
      }
    }
  }
}

int main() {
  for (int i = 0; i < 10; i++)
    for (int j = 0; j < 10; j++)
      map[i][j] = (i == j) ? 0 : INF;
  map[0][1] = 5;
  map[1][2] = 3;
  Prim(10);
  return 0;
}