{{-- image_cropper partial
     Usage: @include('partials.image_cropper')
     Add to any <input type="file" accept="image/*">:
       data-crop="true"
     Optional data attrs for preview wiring:
       data-preview-img="<img-element-id>"
       data-preview-wrap="<wrapper-id>"   (gets display:block)
       data-placeholder="<placeholder-id>" (gets display:none)
       data-preview-name="<name-span-id>"
       data-simple-preview="<img-id>"      (simple standalone preview)
--}}

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>

<div id="cropModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.88); z-index:99999; align-items:center; justify-content:center; padding:1rem; font-family:'Cairo',sans-serif;" dir="rtl">
    <div style="background:#1e293b; border-radius:1.5rem; padding:1.5rem; max-width:680px; width:100%; max-height:95vh; display:flex; flex-direction:column; gap:1rem; box-shadow:0 30px 80px rgba(0,0,0,0.5); overflow-y:auto;">

        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <div style="font-weight:800; color:white; font-size:1.1rem;">قص الصورة</div>
                <div style="font-size:0.78rem; color:#94a3b8; margin-top:0.25rem;">اسحب لتحريك المنطقة &bull; الزوايا للتكبير والتصغير</div>
            </div>
            <button id="cropCancel" style="background:#334155; border:none; border-radius:0.75rem; min-width:36px; height:36px; color:white; font-size:1.1rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">✕</button>
        </div>

        <div style="border-radius:1rem; overflow:hidden; background:#0f172a; max-height:52vh; display:flex; align-items:center; justify-content:center;">
            <img id="cropperImg" src="" style="max-width:100%; display:block;">
        </div>

        <div style="display:flex; gap:0.5rem; justify-content:center; flex-wrap:wrap; display:none;">
            <button class="crop-ratio-btn" data-ratio="1.7777777777777777" style="padding:0.3rem 0.9rem; border-radius:2rem; border:1px solid #f2f20d; background:#f2f20d; color:#1a1a1a; cursor:pointer; font-size:0.8rem; font-family:inherit; font-weight:700;">أفقي 16:9</button>
        </div>

        <div style="display:flex; gap:0.75rem;">
            <button id="cropConfirm" style="flex:1; padding:0.9rem; background:#f2f20d; color:#1a1a1a; border:none; border-radius:0.75rem; font-weight:800; cursor:pointer; font-size:0.95rem; font-family:inherit;">
                ✓ قص وتأكيد
            </button>
            <button id="cropCancelBtn" style="padding:0.9rem 1.5rem; background:transparent; border:2px solid #475569; color:white; border-radius:0.75rem; font-weight:700; cursor:pointer; font-size:0.95rem; font-family:inherit;">
                إلغاء
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal       = document.getElementById('cropModal');
    var cropperImg  = document.getElementById('cropperImg');
    var cropper     = null;
    var activeInput = null;
    var activeMeta  = {};

    function openCropper(input, meta) {
        if (typeof Cropper === 'undefined') {
            alert('خطأ: لم يتم تحميل أداة قص الصور (CropperJS). يرجى التأكد من الاتصال بالإنترنت وإعادة المحاولة.');
            input.value = '';
            return;
        }
        var file = input._pendingFile;
        if (!file) return;
        activeInput = input;
        activeMeta  = meta || {};
        var reader  = new FileReader();
        reader.onload = function (e) {
            cropperImg.src = e.target.result;
            modal.style.display = 'flex';
            if (cropper) { cropper.destroy(); cropper = null; }
            cropper = new Cropper(cropperImg, {
                viewMode: 1, dragMode: 'move',
                aspectRatio: 1.7777777777777777,
                autoCropArea: 0.9,
                guides: true, center: true, highlight: true,
                cropBoxResizable: true, cropBoxMovable: true,
                toggleDragModeOnDblclick: false,
            });
        };
        reader.readAsDataURL(file);
    }

    function closeCropper() {
        modal.style.display = 'none';
        if (cropper) { cropper.destroy(); cropper = null; }
        if (activeInput) { activeInput.value = ''; activeInput._pendingFile = null; }
        activeInput = null; activeMeta = {};
    }

    document.getElementById('cropCancel').addEventListener('click', closeCropper);
    document.getElementById('cropCancelBtn').addEventListener('click', closeCropper);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeCropper(); });

    // Ratio buttons
    document.querySelectorAll('.crop-ratio-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.crop-ratio-btn').forEach(function (b) {
                b.style.background = 'transparent'; b.style.color = '#94a3b8'; b.style.borderColor = '#475569'; b.style.fontWeight = '';
            });
            btn.style.background = '#f2f20d'; btn.style.color = '#1a1a1a'; btn.style.borderColor = '#f2f20d'; btn.style.fontWeight = '700';
            var r = parseFloat(btn.dataset.ratio);
            if (cropper) cropper.setAspectRatio(isNaN(r) ? NaN : r);
        });
    });

    // Confirm
    document.getElementById('cropConfirm').addEventListener('click', function () {
        if (!cropper || !activeInput) return;
        var canvas   = cropper.getCroppedCanvas({ maxWidth: 2000, maxHeight: 2000 });
        var inputRef = activeInput;
        var meta     = activeMeta;
        canvas.toBlob(function (blob) {
            var file = new File([blob], 'cropped_image.jpg', { type: 'image/jpeg' });
            var dt   = new DataTransfer();
            dt.items.add(file);
            inputRef.files = dt.files;

            var url = URL.createObjectURL(blob);
            var get = function (id) { return id ? document.getElementById(id) : null; };

            // Admin-style upload zone
            var previewImg  = get(meta.previewImg);
            var previewWrap = get(meta.previewWrap);
            var placeholder = get(meta.placeholder);
            var previewName = get(meta.previewName);
            if (previewImg)  { previewImg.src = url; }
            if (previewWrap) { previewWrap.classList.remove('hidden'); previewWrap.style.display = ''; }
            if (placeholder) { placeholder.classList.add('hidden'); placeholder.style.display = 'none'; }
            if (previewName) { previewName.textContent = 'cropped_image.jpg'; }

            // Simple preview (HOD style)
            var simple = get(meta.simplePreview);
            if (simple) {
                simple.src = url;
                simple.style.cssText = 'display:block; max-height:120px; border-radius:0.5rem; margin-top:0.5rem; object-fit:cover;';
            }

            modal.style.display = 'none';
            if (cropper) { cropper.destroy(); cropper = null; }
            activeInput = null; activeMeta = {};
        }, 'image/jpeg', 0.92);
    });

    // Auto-init
    function initCropperInputs() {
        document.querySelectorAll('input[type="file"][data-crop="true"]').forEach(function (input) {
            if (input._cropperInitialized) return;
            input._cropperInitialized = true;
            
            var meta = {
                previewImg:    input.dataset.previewImg    || null,
                previewWrap:   input.dataset.previewWrap   || null,
                placeholder:   input.dataset.placeholder   || null,
                previewName:   input.dataset.previewName   || null,
                simplePreview: input.dataset.simplePreview || null,
            };
            input.addEventListener('change', function (e) {
                var file = e.target.files[0];
                if (!file || !file.type.startsWith('image/')) return;
                input._pendingFile = file;
                openCropper(input, meta);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCropperInputs);
    } else {
        initCropperInputs();
    }
})();
</script>
