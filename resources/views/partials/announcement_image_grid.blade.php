@php
    $imgs = $images ?? [];
    $count = count($imgs);
    $gridId = 'grid_' . ($id ?? rand(1000, 9999));
@endphp

@if($count > 0)
<div class="w-full h-full relative overflow-hidden bg-slate-900 rounded-xl" id="{{ $gridId }}">
    @if($count === 1)
        {{-- صورة واحدة --}}
        <div class="relative w-full h-full overflow-hidden group cursor-pointer" onclick="openLightbox('{{ $gridId }}', 0)">
            <img src="{{ $imgs[0] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
        </div>

    @elseif($count === 2)
        {{-- صورتان: نصفين متساويين بجانب بعضهما --}}
        <div class="grid grid-cols-2 gap-1 w-full h-full overflow-hidden">
            @foreach(array_slice($imgs, 0, 2) as $index => $img)
                <div class="relative h-full overflow-hidden group cursor-pointer" onclick="openLightbox('{{ $gridId }}', {{ $index }})">
                    <img src="{{ $img }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
                </div>
            @endforeach
        </div>

    @elseif($count === 3)
        {{-- 3 صور: صورة كبيرة يميناً وصورتان يساراً --}}
        <div class="grid grid-cols-2 gap-1 w-full h-full overflow-hidden">
            <div class="relative h-full overflow-hidden group cursor-pointer" onclick="openLightbox('{{ $gridId }}', 0)">
                <img src="{{ $imgs[0] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
            </div>
            <div class="grid grid-rows-2 gap-1 h-full">
                @foreach(array_slice($imgs, 1, 2) as $index => $img)
                    <div class="relative h-full overflow-hidden group cursor-pointer" onclick="openLightbox('{{ $gridId }}', {{ $index + 1 }})">
                        <img src="{{ $img }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
                    </div>
                @endforeach
            </div>
        </div>

    @elseif($count >= 4)
        {{-- 4 صور أو أكثر: شبكة 2x2 مع عداد على الصورة الأخيرة --}}
        <div class="grid grid-cols-2 grid-rows-2 gap-1 w-full h-full overflow-hidden">
            @foreach(array_slice($imgs, 0, 4) as $index => $img)
                <div class="relative h-full overflow-hidden group cursor-pointer" onclick="openLightbox('{{ $gridId }}', {{ $index }})">
                    <img src="{{ $img }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
                    @if($index === 3 && $count > 4)
                        <div class="absolute inset-0 bg-black/65 backdrop-blur-xs flex items-center justify-center text-white font-extrabold text-xl">
                            +{{ $count - 3 }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
if (typeof window.announcementGalleries === 'undefined') {
    window.announcementGalleries = {};
}
window.announcementGalleries['{{ $gridId }}'] = @json($imgs);
</script>

@once
<div id="globalLightboxModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:99999; align-items:center; justify-content:center; flex-direction:column; padding:1rem;" dir="rtl">
    <div style="position:absolute; top:1rem; right:1.5rem; display:flex; items-center; gap:1rem; z-index:100000;">
        <span id="lightboxCounter" style="color:white; font-weight:700; font-size:0.95rem; background:rgba(255,255,255,0.15); padding:0.3rem 0.8rem; border-radius:1rem;">1 / 1</span>
        <button onclick="closeLightbox()" style="background:rgba(255,255,255,0.2); border:none; color:white; width:40px; height:40px; border-radius:50%; font-size:1.2rem; cursor:pointer;">✕</button>
    </div>
    
    <button id="lightboxPrevBtn" onclick="navLightbox(-1)" style="position:absolute; right:1.5rem; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.2); border:none; color:white; width:48px; height:48px; border-radius:50%; font-size:1.5rem; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:100000;">❯</button>
    
    <div style="max-width:90vw; max-height:85vh; display:flex; align-items:center; justify-content:center; overflow:hidden;">
        <img id="lightboxImg" src="" style="max-width:90vw; max-height:85vh; object-fit:contain; border-radius:0.75rem; box-shadow:0 25px 50px rgba(0,0,0,0.5);">
    </div>

    <button id="lightboxNextBtn" onclick="navLightbox(1)" style="position:absolute; left:1.5rem; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.2); border:none; color:white; width:48px; height:48px; border-radius:50%; font-size:1.5rem; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:100000;">❮</button>
</div>

<script>
let currentLightboxGallery = [];
let currentLightboxIndex = 0;

function openLightbox(gridId, index) {
    if (!window.announcementGalleries || !window.announcementGalleries[gridId]) return;
    currentLightboxGallery = window.announcementGalleries[gridId];
    currentLightboxIndex = index;
    updateLightboxUI();
    document.getElementById('globalLightboxModal').style.display = 'flex';
}

function closeLightbox() {
    document.getElementById('globalLightboxModal').style.display = 'none';
}

function navLightbox(dir) {
    if (!currentLightboxGallery.length) return;
    currentLightboxIndex = (currentLightboxIndex + dir + currentLightboxGallery.length) % currentLightboxGallery.length;
    updateLightboxUI();
}

function updateLightboxUI() {
    if (!currentLightboxGallery.length) return;
    document.getElementById('lightboxImg').src = currentLightboxGallery[currentLightboxIndex];
    document.getElementById('lightboxCounter').textContent = (currentLightboxIndex + 1) + ' / ' + currentLightboxGallery.length;
    
    document.getElementById('lightboxPrevBtn').style.display = currentLightboxGallery.length > 1 ? 'flex' : 'none';
    document.getElementById('lightboxNextBtn').style.display = currentLightboxGallery.length > 1 ? 'flex' : 'none';
}

document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('globalLightboxModal');
    if (modal && modal.style.display === 'flex') {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowRight') navLightbox(-1);
        if (e.key === 'ArrowLeft') navLightbox(1);
    }
});
</script>
@endonce
@endif
