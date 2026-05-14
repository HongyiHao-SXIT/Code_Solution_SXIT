<script setup>
import { computed, ref } from 'vue'
import { postForm } from '../lib/api'
import { pushFlash } from '../stores/session'

const selectedFile = ref(null)
const originalPreview = ref('')
const annotatedPreview = ref('')
const latitude = ref('')
const longitude = ref('')
const loading = ref(false)
const imageSize = ref('md')
const resultItems = ref([])
const fileInput = ref(null)

const canDetect = computed(() => Boolean(selectedFile.value))

function chooseFile(file) {
  if (!file) {
    return
  }
  const ext = file.name.split('.').pop()?.toLowerCase()
  if (!['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'].includes(ext)) {
    pushFlash('不支持的文件格式', 'error')
    return
  }

  selectedFile.value = file
  annotatedPreview.value = ''
  resultItems.value = []
  const reader = new FileReader()
  reader.onload = (event) => {
    originalPreview.value = event.target?.result || ''
  }
  reader.readAsDataURL(file)
}

function onDrop(event) {
  event.preventDefault()
  const file = event.dataTransfer?.files?.[0]
  chooseFile(file)
}

function randomLocation() {
  latitude.value = (Math.random() * (35 - 30) + 30).toFixed(6)
  longitude.value = (Math.random() * (115 - 110) + 110).toFixed(6)
}

function resetAll() {
  selectedFile.value = null
  originalPreview.value = ''
  annotatedPreview.value = ''
  resultItems.value = []
  latitude.value = ''
  longitude.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

async function detectImage() {
  if (!selectedFile.value) {
    pushFlash('请先选择图片', 'error')
    return
  }

  loading.value = true
  try {
    const formData = new FormData()
    formData.append('image', selectedFile.value)
    if (latitude.value.trim()) formData.append('latitude', latitude.value.trim())
    if (longitude.value.trim()) formData.append('longitude', longitude.value.trim())

    const payload = await postForm('/api/detect', formData)
    annotatedPreview.value = payload.annotated_image_path ? `/${payload.annotated_image_path}` : ''
    resultItems.value = payload.result || []
  } catch (error) {
    pushFlash(error.message || '识别失败', 'error')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel page-panel">
    <div class="panel-title">手动上传检测</div>
    <div class="upload-layout panel-body text-secondary">
      <div class="upload-col upload-control-col">
        <div class="upload-card">
          <div class="upload-subtitle">选择图片</div>
          <div id="uploadArea" class="upload-drop-area" @click="fileInput?.click()" @dragover.prevent @drop="onDrop">
            点击或拖拽图片到此区域
          </div>
          <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="chooseFile($event.target.files?.[0])">
          <div class="button-row mt-8">
            <button type="button" @click="randomLocation">随机坐标</button>
            <button type="button" @click="latitude = ''; longitude = ''">清除坐标</button>
          </div>
          <div class="form-row mt-8">
            <input v-model="latitude" class="input-control" placeholder="纬度" aria-label="纬度">
            <input v-model="longitude" class="input-control" placeholder="经度" aria-label="经度">
          </div>
          <div class="button-row mt-10">
            <button type="button" :disabled="!canDetect || loading" @click="detectImage">{{ loading ? '识别中...' : '开始识别' }}</button>
            <button type="button" @click="resetAll">重置</button>
          </div>
        </div>
        <div id="resultArea" class="result-area upload-result-area" :style="{ display: resultItems.length || loading ? 'block' : 'none' }">
          <p v-if="loading" class="result-note">正在识别中，请稍候...</p>
          <div v-else-if="resultItems.length">
            <div class="upload-result-header">
              <span class="upload-result-title">检测结果</span>
              <span class="upload-result-count">共 {{ resultItems.length }} 项</span>
            </div>
            <div class="upload-result-list">
              <div v-for="(item, index) in resultItems" :key="`${item.class_name}-${index}`" class="upload-result-card">
                <div class="upload-result-card-head">
                  <span class="upload-result-index">#{{ index + 1 }}</span>
                  <span class="upload-result-tag">{{ item.class_name }}</span>
                  <span class="upload-result-score">{{ item.confidence }}</span>
                </div>
                <div class="upload-result-meta">
                  <span class="upload-result-meta-label">识别类别</span>
                  <span class="upload-result-meta-value">{{ item.class_name }}</span>
                </div>
                <div class="upload-result-progress">
                  <div class="upload-result-progress-bar" :style="{ width: item.confidence }"></div>
                </div>
                <div class="upload-result-meta upload-result-meta-bottom">
                  <span class="upload-result-meta-label">置信度</span>
                  <span class="upload-result-meta-value upload-result-meta-strong">{{ item.confidence }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="upload-col upload-preview-col">
        <div class="image-size-controls upload-image-size-controls">
          <span class="image-size-label">预览大小</span>
          <button type="button" class="image-size-btn upload-image-size-btn" :class="{ active: imageSize === 'sm' }" @click="imageSize = 'sm'">小</button>
          <button type="button" class="image-size-btn upload-image-size-btn" :class="{ active: imageSize === 'md' }" @click="imageSize = 'md'">中</button>
          <button type="button" class="image-size-btn upload-image-size-btn" :class="{ active: imageSize === 'lg' }" @click="imageSize = 'lg'">大</button>
        </div>
        <div id="uploadPreviewStack" class="upload-preview-stack" :class="`size-${imageSize}`">
          <div class="upload-card">
            <div class="section-title section-title-sm">原始图片</div>
            <img :src="originalPreview" class="preview-image upload-preview-image" :class="{ 'preview-hidden': !originalPreview }" alt="上传原始预览图">
          </div>
          <div class="upload-card">
            <div class="section-title section-title-sm">识别结果</div>
            <img :src="annotatedPreview" class="preview-image upload-preview-image" :class="{ 'preview-hidden': !annotatedPreview }" alt="识别结果预览图">
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
