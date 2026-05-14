#include <stdio.h>
#include <string.h>

typedef struct {
    int weight, parent, lchild, rchild;
} HTNode;

void Select(HTNode ht[], int n, int *s1, int *s2) {
    int min1 = 10000, min2 = 10000;
    for (int i = 1; i <= n; i++) {
        if (ht[i].parent == 0) {
            if (ht[i].weight < min1) {
                min2 = min1; *s2 = *s1;
                min1 = ht[i].weight; *s1 = i;
            } else if (ht[i].weight < min2) {
                min2 = ht[i].weight; *s2 = i;
            }
        }
    }
}

int main() {
    int n; printf("输入字符个数: "); scanf("%d", &n);
    HTNode ht[2 * n];
    for (int i = 1; i <= n; i++) {
        printf("输入权值 %d: ", i); scanf("%d", &ht[i].weight);
        ht[i].parent = ht[i].lchild = ht[i].rchild = 0;
    }
    for (int i = n + 1; i < 2 * n; i++) {
        int s1, s2; Select(ht, i - 1, &s1, &s2);
        ht[s1].parent = ht[s2].parent = i;
        ht[i].lchild = s1; ht[i].rchild = s2;
        ht[i].weight = ht[s1].weight + ht[s2].weight;
        ht[i].parent = 0;
    }
    printf("赫夫曼树构造完成（此处可扩展生成编码逻辑）\n");
    return 0;
}