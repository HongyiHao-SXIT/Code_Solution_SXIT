#include <stdio.h>
#include <stdlib.h>

typedef struct AVLNode {
    int data, height;
    struct AVLNode *lchild, *rchild;
} AVLNode, *AVLTree;

int GetHeight(AVLTree p) { return p ? p->height : 0; }
int Max(int a, int b) { return a > b ? a : b; }

AVLTree LeftRotation(AVLTree a) {
    AVLTree b = a->rchild;
    a->rchild = b->lchild;
    b->lchild = a;
    a->height = Max(GetHeight(a->lchild), GetHeight(a->rchild)) + 1;
    b->height = Max(GetHeight(b->lchild), GetHeight(b->rchild)) + 1;
    return b;
}

AVLTree RightRotation(AVLTree a) {
    AVLTree b = a->lchild;
    a->lchild = b->rchild;
    b->rchild = a;
    a->height = Max(GetHeight(a->lchild), GetHeight(a->rchild)) + 1;
    b->height = Max(GetHeight(b->lchild), GetHeight(b->rchild)) + 1;
    return b;
}

AVLTree LeftRightRotation(AVLTree a) {
    a->lchild = LeftRotation(a->lchild);
    return RightRotation(a);
}

AVLTree RightLeftRotation(AVLTree a) {
    a->rchild = RightRotation(a->rchild);
    return LeftRotation(a);
}

AVLTree Insert(AVLTree T, int val) {
    if (!T) {
        T = (AVLTree)malloc(sizeof(AVLNode));
        T->data = val; T->height = 1;
        T->lchild = T->rchild = NULL;
    } else if (val < T->data) {
        T->lchild = Insert(T->lchild, val);
        if (GetHeight(T->lchild) - GetHeight(T->rchild) == 2)
            T = (val < T->lchild->data) ? RightRotation(T) : LeftRightRotation(T);
    } else if (val > T->data) {
        T->rchild = Insert(T->rchild, val);
        if (GetHeight(T->rchild) - GetHeight(T->lchild) == 2)
            T = (val > T->rchild->data) ? LeftRotation(T) : RightLeftRotation(T);
    }
    T->height = Max(GetHeight(T->lchild), GetHeight(T->rchild)) + 1;
    return T;
}

void InOrder(AVLTree T) {
    if (T) {
        InOrder(T->lchild);
        printf("%d ", T->data);
        InOrder(T->rchild);
    }
}

int main() {
    AVLTree T = NULL;
    int val;
    printf("请输入10个无序的数: ");
    for (int i = 0; i < 10; i++) {
        scanf("%d", &val);
        T = Insert(T, val);
    }
    printf("中序遍历结果: ");
    InOrder(T);
    return 0;
}