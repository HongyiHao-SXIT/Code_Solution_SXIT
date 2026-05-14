#include <windows.h>
#include <process.h>
#include <stdio.h>
#include <stdlib.h>
#include <time.h>
HANDLE full, empty;
HANDLE pMutex, cMutex;

int buffer[10] = {0};
int inPtr = 0, outPtr = 0;

DWORD WINAPI Producer(LPVOID lpParameter) {
	int pid = (long long)lpParameter;
	srand((unsigned)time(NULL)) ;
	while (TRUE) {
		int item = rand() ;
		printf("Producer %d created an item %d. \n", pid, item);
		WaitForSingleObject (empty, INFINITE);
		WaitForSingleObject(pMutex, INFINITE);
		buffer[inPtr] = item;
		inPtr = (inPtr + 1) % 10;
		printf("Producer %d pushed the item into buffer. \n", pid) ;
		Sleep(500);
		ReleaseSemaphore(pMutex, 1, NULL) ;
		ReleaseSemaphore(full, 1, NULL);
	}
}

DWORD WINAPI Consumer(LPVOID lpParameter) {
	int item;
	int cid = (long long)lpParameter;
	while (TRUE) {
		WaitForSingleObject(full, INFINITE);
		WaitForSingleObject(cMutex, INFINITE);
		item = buffer[outPtr];
		buffer[outPtr] = 0;
		outPtr = (outPtr + 1) % 10;
		printf("Consumer %d got an item %d from buffer. \n", cid, item);
		Sleep(500) ;
		ReleaseSemaphore(cMutex, 1, NULL);
		ReleaseSemaphore(empty, 1, NULL);
	}
}

int main(int argc, char *argv[]) {
	long i ;
	full = CreateSemaphore (NULL, 0, 10, NULL);
	empty = CreateSemaphore(NULL, 10, 10, NULL);
	pMutex = CreateSemaphore (NULL, 1, 1, NULL);
	cMutex = CreateSemaphore (NULL, 1, 1, NULL);
	for (i = 0 ; i < 10 ; i ++) {
		CreateThread(NULL, 0, Producer, (void *)i, 0, NULL);
		CreateThread(NULL, 0, Consumer, (void *)i, 0, NULL) ;
		Sleep(1000);
	}
	Sleep(1000 * 10);
	return 0;
}