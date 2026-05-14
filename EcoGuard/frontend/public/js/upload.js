document.addEventListener('DOMContentLoaded', function () {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const originalPreview = document.getElementById('originalPreview');
    const annotatedPreview = document.getElementById('annotatedPreview');
    const resultArea = document.getElementById('resultArea');
    const latInput = document.getElementById('latInput');
    const lngInput = document.getElementById('lngInput');
    const detectBtn = document.getElementById('detectBtn');
    const resetBtn = document.getElementById('resetBtn');
    const randomLocBtn = document.getElementById('randomLocBtn');
    const clearLocBtn = document.getElementById('clearLocBtn');
    const uploadPreviewStack = document.getElementById('uploadPreviewStack');
    const sizeBtns = document.querySelectorAll('.upload-image-size-btn');

    let selectedFile = null;

    function setDetectLoading(isLoading) {
        detectBtn.disabled = isLoading;
        if (!isLoading) return;
        resultArea.style.display = 'block';
        resultArea.innerHTML = '<p class="result-note">正在识别中，请稍候...</p>';
    }

    function hasAnyCoordinate() {
        const lat = latInput.value.trim();
        const lng = lngInput.value.trim();
        return !(lat === '' && lng === '');
    }

    function getAllowedExtensions() {
        return ['png', 'jpg', 'jpeg', 'gif'];
    }

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (event) => {
        event.preventDefault();
        uploadArea.classList.add('is-dragover');
    });
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('is-dragover');
    });
    uploadArea.addEventListener('drop', (event) => {
        event.preventDefault();
        uploadArea.classList.remove('is-dragover');
        if (event.dataTransfer.files.length) {
            fileInput.files = event.dataTransfer.files;
            chooseFile(fileInput.files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) chooseFile(fileInput.files[0]);
    });

    randomLocBtn.addEventListener('click', () => {
        latInput.value = (Math.random() * (35 - 30) + 30).toFixed(6);
        lngInput.value = (Math.random() * (115 - 110) + 110).toFixed(6);
        refreshDetectButton();
    });
    clearLocBtn.addEventListener('click', () => {
        latInput.value = '';
        lngInput.value = '';
        refreshDetectButton();
    });

    if (uploadPreviewStack && sizeBtns.length) {
        sizeBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const size = btn.dataset.size || 'md';
                uploadPreviewStack.classList.remove('size-sm', 'size-md', 'size-lg');
                uploadPreviewStack.classList.add(`size-${size}`);
                sizeBtns.forEach((buttonItem) => buttonItem.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    }

    function escapeText(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function readConfidence(confidence) {
        if (typeof confidence === 'number' && Number.isFinite(confidence)) {
            return confidence <= 1 ? confidence * 100 : confidence;
        }
        const numeric = parseFloat(String(confidence || '').replace('%', '').trim());
        if (!Number.isFinite(numeric)) return 0;
        return numeric <= 1 ? numeric * 100 : numeric;
    }

    function formatScore(confidence) {
        const value = Math.max(0, Math.min(100, readConfidence(confidence)));
        return `${value.toFixed(2)}%`;
    }

    function paintProgress(container) {
        if (!container) return;
        container.querySelectorAll('.upload-result-progress-bar').forEach((bar) => {
            const width = parseFloat(bar.dataset.width || '0');
            bar.style.width = `${Math.max(0, Math.min(100, width))}%`;
        });
    }

    function buildResultCards(items) {
        const list = Array.isArray(items) ? items : [];
        if (!list.length) {
            return '<p class="success-text">未识别到垃圾类别</p>';
        }

        const cards = list.map((item, index) => {
            const confidenceValue = Math.max(0, Math.min(100, readConfidence(item.confidence)));
            const confidenceLabel = formatScore(item.confidence);
            const className = escapeText(item.class_name || `类别 ${index + 1}`);
            return `
                <div class="upload-result-card">
                    <div class="upload-result-card-head">
                        <span class="upload-result-index">#${index + 1}</span>
                        <span class="upload-result-tag">${className}</span>
                        <span class="upload-result-score">${confidenceLabel}</span>
                    </div>
                    <div class="upload-result-meta">
                        <span class="upload-result-meta-label">识别类别</span>
                        <span class="upload-result-meta-value">${className}</span>
                    </div>
                    <div class="upload-result-progress">
                        <div class="upload-result-progress-bar" data-width="${confidenceValue.toFixed(2)}"></div>
                    </div>
                    <div class="upload-result-meta upload-result-meta-bottom">
                        <span class="upload-result-meta-label">置信度</span>
                        <span class="upload-result-meta-value upload-result-meta-strong">${confidenceLabel}</span>
                    </div>
                </div>
            `;
        }).join('');

        return `
            <div class="upload-result-header">
                <span class="upload-result-title">检测结果</span>
                <span class="upload-result-count">共 ${list.length} 项</span>
            </div>
            <div class="upload-result-list">${cards}</div>
        `;
    }

    function chooseFile(file) {
        const allowed = getAllowedExtensions();
        const ext = file.name.split('.').pop().toLowerCase();
        if (!allowed.includes(ext)) {
            alert('不支持的文件格式');
            return;
        }
        selectedFile = file;
        const fileReader = new FileReader();
        fileReader.onload = (event) => {
            originalPreview.src = event.target.result;
            originalPreview.classList.remove('preview-hidden');
        };
        fileReader.readAsDataURL(file);
        annotatedPreview.classList.add('preview-hidden');
        resultArea.style.display = 'none';
        resultArea.innerHTML = '';
        refreshDetectButton();
    }

    function refreshDetectButton() {
        detectBtn.disabled = !(selectedFile && hasAnyCoordinate());
    }

    latInput.addEventListener('input', refreshDetectButton);
    lngInput.addEventListener('input', refreshDetectButton);

    detectBtn.addEventListener('click', () => {
        if (!selectedFile) {
            alert('请先选择图片');
            return;
        }
        const lat = latInput.value.trim();
        const lng = lngInput.value.trim();
        if (lat === '' && lng === '') {
            alert('请至少填写一个位置信息');
            return;
        }

        const formData = new FormData();
        formData.append('image', selectedFile);
        if (lat) formData.append('latitude', lat);
        if (lng) formData.append('longitude', lng);

        setDetectLoading(true);

        fetch('/api/detect', {method: 'POST', body: formData})
            .then((response) => {
                if (!response.ok) throw new Error('服务器响应异常');
                return response.json();
            })
            .then(data => {
                setDetectLoading(false);
                if (data.status === 'success' || data.ok) {
                    resultArea.innerHTML = buildResultCards(data.result);
                    paintProgress(resultArea);

                    if (data.annotated_image_path) {
                        annotatedPreview.src = '/' + data.annotated_image_path + '?t=' + Date.now();
                        annotatedPreview.classList.remove('preview-hidden');
                    }
                } else {
                    resultArea.innerHTML = `<p class="result-error">识别失败: ${escapeText(data.message || data.error || '未知错误')}</p>`;
                }
            })
            .catch(err => {
                setDetectLoading(false);
                resultArea.innerHTML = `<p class="result-error">错误: ${escapeText(err.message)}</p>`;
            });
    });

    resetBtn.addEventListener('click', () => {
        selectedFile = null;
        fileInput.value = '';
        latInput.value = '';
        lngInput.value = '';
        originalPreview.classList.add('preview-hidden');
        annotatedPreview.classList.add('preview-hidden');
        resultArea.style.display = 'none';
        resultArea.innerHTML = '';
        detectBtn.disabled = true;
    });

});
