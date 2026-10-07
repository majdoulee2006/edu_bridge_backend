@if(!empty($images))
    @if(count($images) > 1)
        <div class="image-slider-container" style="position: relative; border-radius: 1rem; overflow: hidden; margin-bottom: 0.5rem; background: #000; min-height: 180px;">
            <!-- Images -->
            @foreach($images as $index => $img)
            <div class="slider-slide" data-index="{{ $index }}" style="display: {{ $index == 0 ? 'block' : 'none' }};">
                <img src="{{ $img }}" style="width: 100%; height: auto; max-height: 420px; object-fit: contain; display: block; margin: 0 auto;">
            </div>
            @endforeach
            
            <!-- Indicator -->
            <div style="position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(0,0,0,0.6); color: white; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.8rem; font-weight: bold; direction: ltr; backdrop-filter: blur(4px); z-index: 10;">
                <span class="current-slide">1</span> / {{ count($images) }}
            </div>
            
            <!-- Controls -->
            <button type="button" onclick="nextSlide(this)" style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; backdrop-filter: blur(4px); transition: background 0.2s; z-index: 10;" onmouseover="this.style.background='rgba(0,0,0,0.8)'" onmouseout="this.style.background='rgba(0,0,0,0.5)'">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button type="button" onclick="prevSlide(this)" style="position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; backdrop-filter: blur(4px); transition: background 0.2s; z-index: 10;" onmouseover="this.style.background='rgba(0,0,0,0.8)'" onmouseout="this.style.background='rgba(0,0,0,0.5)'">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
        
        <!-- Only Download Icon Buttons -->
        <div style="margin-top: 0.5rem; display: flex; gap: 0.4rem; flex-wrap: wrap;">
            @foreach($images as $index => $img)
            <a href="{{ $img }}" download title="تحميل المرفق {{ $index + 1 }}" style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; color: #1d9bf0; text-decoration: none; font-size: 0.95rem; background: rgba(29, 155, 240, 0.1); border-radius: 50%; transition: all 0.2s;" onmouseover="this.style.background='rgba(29, 155, 240, 0.2)'; this.style.transform='scale(1.08)'" onmouseout="this.style.background='rgba(29, 155, 240, 0.1)'; this.style.transform='scale(1)'">
                <i class="fa-solid fa-download"></i>
            </a>
            @endforeach
        </div>

        @once
            <script>
                function nextSlide(btn) {
                    let container = btn.closest('.image-slider-container');
                    let slides = container.querySelectorAll('.slider-slide');
                    let currentIndicator = container.querySelector('.current-slide');
                    let currentIndex = Array.from(slides).findIndex(s => s.style.display !== 'none');
                    slides[currentIndex].style.display = 'none';
                    let nextIndex = (currentIndex + 1) % slides.length;
                    slides[nextIndex].style.display = 'block';
                    currentIndicator.textContent = nextIndex + 1;
                }
                function prevSlide(btn) {
                    let container = btn.closest('.image-slider-container');
                    let slides = container.querySelectorAll('.slider-slide');
                    let currentIndicator = container.querySelector('.current-slide');
                    let currentIndex = Array.from(slides).findIndex(s => s.style.display !== 'none');
                    slides[currentIndex].style.display = 'none';
                    let prevIndex = (currentIndex - 1 + slides.length) % slides.length;
                    slides[prevIndex].style.display = 'block';
                    currentIndicator.textContent = prevIndex + 1;
                }
            </script>
        @endonce
    @else
        <div style="border-radius: 1rem; overflow: hidden; margin-bottom: 0.5rem; background: #000;">
            <img src="{{ $images[0] }}" style="width: 100%; height: auto; max-height: 420px; object-fit: contain; display: block; margin: 0 auto;">
        </div>
        <div style="margin-top: 0.5rem;">
            <a href="{{ $images[0] }}" download title="تحميل المرفق" style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; color: #1d9bf0; text-decoration: none; font-size: 0.95rem; background: rgba(29, 155, 240, 0.1); border-radius: 50%; transition: all 0.2s;" onmouseover="this.style.background='rgba(29, 155, 240, 0.2)'; this.style.transform='scale(1.08)'" onmouseout="this.style.background='rgba(29, 155, 240, 0.1)'; this.style.transform='scale(1)'">
                <i class="fa-solid fa-download"></i>
            </a>
        </div>
    @endif
@endif
