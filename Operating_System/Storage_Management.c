#include <stdio.h>
#include <stdlib.h>
#include <time.h>

#define PAGE_SEQ_LEN 20  // 访问序列长度
#define VIRTUAL_PAGES 10 // 虚页范围 0-9

// 打印当前物理块状态
void print_status(int frames[], int m) {
    printf("[ ");
    for (int i = 0; i < m; i++) {
        if (frames[i] == -1) printf(". ");
        else printf("%d ", frames[i]);
    }
    printf("]\t");
}

// FIFO 算法
void do_FIFO(int pages[], int n, int m) {
    int frames[m];
    for (int i = 0; i < m; i++) frames[i] = -1; // 初始化

    int fault = 0;
    int pointer = 0; // 指向最早进入的页面位置

    printf("\n--- FIFO 算法置换过程 ---\n");
    for (int i = 0; i < n; i++) {
        int hit = 0;
        for (int j = 0; j < m; j++) {
            if (frames[j] == pages[i]) {
                hit = 1;
                break;
            }
        }

        if (!hit) {
            frames[pointer] = pages[i];
            pointer = (pointer + 1) % m; // 循环移动指针
            fault++;
            print_status(frames, m);
            printf("缺页 (Page %d)\n", pages[i]);
        } else {
            print_status(frames, m);
            printf("命中 (Page %d)\n", pages[i]);
        }
    }
    printf("FIFO 缺页次数: %d, 命中率: %.2f%%\n", fault, (float)(n - fault) / n * 100);
}

// LRU 算法
void do_LRU(int pages[], int n, int m) {
    int frames[m];
    int time_stamp[m]; // 记录页面最近一次被访问的时间（模拟计数器）
    for (int i = 0; i < m; i++) {
        frames[i] = -1;
        time_stamp[i] = 0;
    }

    int fault = 0;
    printf("\n--- LRU 算法置换过程 ---\n");
    for (int i = 0; i < n; i++) {
        int hit = -1;
        for (int j = 0; j < m; j++) {
            if (frames[j] == pages[i]) {
                hit = j;
                break;
            }
        }

        if (hit != -1) {
            // 命中，更新该页面的时间戳为当前计数
            time_stamp[hit] = i;
            print_status(frames, m);
            printf("命中 (Page %d)\n", pages[i]);
        } else {
            // 缺页，寻找时间戳最小的（最久未使用的）
            int min_idx = 0;
            for (int j = 1; j < m; j++) {
                if (time_stamp[j] < time_stamp[min_idx]) {
                    min_idx = j;
                }
            }
            frames[min_idx] = pages[i];
            time_stamp[min_idx] = i;
            fault++;
            print_status(frames, m);
            printf("缺页 (Page %d)\n", pages[i]);
        }
    }
    printf("LRU 缺页次数: %d, 命中率: %.2f%%\n", fault, (float)(n - fault) / n * 100);
}

int main() {
    int pages[PAGE_SEQ_LEN];
    int m;

    srand((unsigned int)time(NULL));
    printf("随机产生的页面访问序列: ");
    for (int i = 0; i < PAGE_SEQ_LEN; i++) {
        pages[i] = rand() % VIRTUAL_PAGES;
        printf("%d ", pages[i]);
    }
    printf("\n\n请输入分配给该进程的实页数 (物理块数): ");
    scanf("%d", &m);

    if (m <= 0) {
        printf("块数必须大于0\n");
        return 1;
    }

    do_FIFO(pages, PAGE_SEQ_LEN, m);
    do_LRU(pages, PAGE_SEQ_LEN, m);

    return 0;
}