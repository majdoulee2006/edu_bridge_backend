<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu Bridge | الرسائل</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --main-yellow: #f9f21a; --bg-cream: #fcfcf3; --white: #ffffff; }
        * { box-sizing: border-box; margin: 0; padding: 0; transition: 0.3s; }
        body { font-family: 'Cairo', sans-serif; background-color: var(--bg-cream); display: flex; height: 100vh; overflow: hidden; }

        /* --- Sidebar --- */
        aside {
            width: 280px; background: var(--white); height: 100vh;
            display: flex; flex-direction: column; padding: 30px 20px;
            border-left: 1px solid #eee; position: fixed; right: 0; top: 0; z-index: 100;
        }
        .logo { font-weight: 900; font-size: 1.8rem; text-align: center; margin-bottom: 40px; }
        .logo span { color: var(--main-yellow); text-shadow: 1px 1px 0 #000; }
        
        .nav-link {
            display: flex; align-items: center; gap: 15px; padding: 12px 18px;
            text-decoration: none; color: #636e72; font-weight: 700; border-radius: 15px; margin-bottom: 8px;
            cursor: pointer;
        }
        .nav-link:hover { background: #fdfdf0; }
        .nav-link.active { background: var(--main-yellow); color: #000; box-shadow: 0 4px 12px rgba(249, 242, 26, 0.3); }

        /* --- Main Content --- */
        main { margin-right: 280px; flex: 1; height: 100vh; padding: 40px; overflow-y: auto; }

        .search-bar {
            background: #fff; padding: 15px 25px; border-radius: 20px;
            display: flex; align-items: center; gap: 15px; margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .search-bar input { border: none; outline: none; width: 100%; font-family: 'Cairo'; font-size: 1rem; }

        .filters { display: flex; gap: 10px; margin-bottom: 30px; }
        .filter-btn {
            padding: 8px 25px; border-radius: 15px; border: none;
            background: #fff; font-weight: 800; cursor: pointer; color: #666;
        }
        .filter-btn.active { background: var(--main-yellow); color: #000; }

        .group-label { color: var(--main-yellow); font-weight: 900; font-size: 0.9rem; margin-bottom: 15px; display: block; }

        .message-card {
            background: #fff; padding: 20px; border-radius: 25px;
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.01);
            border: 1px solid transparent; cursor: pointer;
            animation: fadeIn 0.4s ease;
        }
        .message-card:hover { border-color: var(--main-yellow); transform: translateY(-3px); }
        
        .msg-info { display: flex; gap: 15px; align-items: center; }
        .avatar { width: 55px; height: 55px; border-radius: 15px; object-fit: cover; background: #eee; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.2rem; }
        
        .msg-details h4 { font-weight: 900; font-size: 1rem; margin-bottom: 5px; }
        .msg-details p { color: #888; font-size: 0.85rem; font-weight: 600; }

        .msg-meta { text-align: left; }
        .time { font-size: 0.75rem; color: #aaa; font-weight: 700; margin-bottom: 5px; display: block; }
        .unread-count { background: var(--main-yellow); color: #000; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 900; margin-right: auto; }
        
        .online-dot { width: 12px; height: 12px; background: #2ecc71; border-radius: 50%; border: 2px solid #fff; position: absolute; bottom: -2px; left: -2px; }

        .floating-add {
            position: fixed; bottom: 40px; left: 40px;
            width: 60px; height: 60px; background: var(--main-yellow);
            border-radius: 20px; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; box-shadow: 0 10px 20px rgba(249, 242, 26, 0.4);
            cursor: pointer; z-index: 200;
        }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center; z-index: 1000;
        }
        .modal-card {
            background: #fff; width: 400px; padding: 30px; border-radius: 30px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1); animation: fadeIn 0.3s ease;
        }
        .modal-card h2 { font-weight: 900; margin-bottom: 20px; }
        .modal-card input, .modal-card textarea, .modal-card select {
            width: 100%; padding: 15px; margin-bottom: 15px; border-radius: 15px;
            border: 1px solid #eee; font-family: 'Cairo'; outline: none; background: #f9f9f9;
        }
        .send-btn {
            width: 100%; padding: 12px; background: var(--main-yellow); border: none;
            border-radius: 15px; font-weight: 900; cursor: pointer; font-family: 'Cairo';
        }
        .send-btn:hover { background: #000; color: #fff; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <aside>
        <div class="logo">EDU<span>BRIDGE</span></div>
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-link"><i class="fa-solid fa-house"></i> الرئيسية</a>
            <a href="{{ route('profile') }}" class="nav-link"><i class="fa-solid fa-user"></i> الملف الشخصي</a>
            <a href="{{ route('messages') }}" class="nav-link active"><i class="fa-solid fa-comment"></i> المراسلة</a>
            <a href="{{ route('notifications') }}" class="nav-link"><i class="fa-solid fa-bell"></i> الإشعارات</a>
            <a href="{{ route('schedule') }}" class="nav-link"><i class="fa-solid fa-calendar"></i> الجداول</a>
            <a href="{{ route('assignments') }}" class="nav-link"><i class="fa-solid fa-file-pen"></i> الواجبات</a>
            <a href="{{ route('attendance') }}" class="nav-link"><i class="fa-solid fa-check"></i> الحضور</a>
            <a href="{{ route('lectures') }}" class="nav-link"><i class="fa-solid fa-play"></i> المحاضرات</a>
        </nav>
    </aside>

    <main>
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="font-weight: 900;">الرسائل</h1>
            <a href="{{ route('settings') }}" style="text-decoration: none;">
                <i class="fa-solid fa-gear" style="font-size: 1.2rem; color: #666; cursor: pointer;"></i>
            </a>
        </header>

        <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass" style="color: #aaa;"></i>
            <input type="text" id="searchInput" placeholder="ابحث في المحادثات..." onkeyup="searchMessages()">
        </div>

        <div class="filters">
            <button class="filter-btn active" data-filter="all">الكل</button>
            <button class="filter-btn" data-filter="unread">غير مقروءة</button>
            <button class="filter-btn" data-filter="group">الجروبات</button>
        </div>

        <div id="messagesList">
            <span class="group-label">جهات الاتصال</span>
            
            @forelse($contacts as $contact)
                <div class="message-card" onclick="openMessageModalWith('{{ $contact->user_id }}', '{{ $contact->full_name }}')">
                    <div class="msg-info">
                        <div style="position: relative;">
                            <div class="avatar" style="background: #f9f21a; color: #000;">
                                {{ mb_substr($contact->full_name, 0, 1) }}
                            </div>
                            <div class="online-dot"></div>
                        </div>
                        <div class="msg-details">
                            <h4>{{ $contact->full_name }}</h4>
                            <p>انقر لبدء المحادثة مع {{ $contact->full_name }}..</p>
                        </div>
                    </div>
                    <div class="msg-meta">
                        <span class="time">متاح</span>
                    </div>
                </div>
            @empty
                <p style="text-align: center; color: #888;">لا يوجد جهات اتصال متاحة حالياً.</p>
            @endforelse
        </div>
    </main>

    <div class="floating-add" onclick="openModal()">
        <i class="fa-solid fa-plus"></i>
    </div>

    <div class="modal-overlay" id="messageModal" onclick="closeModal()">
        <div class="modal-card" onclick="event.stopPropagation()">
            <h2>إرسال رسالة</h2>
            
            <select id="recipientSelect">
                <option value="">اختر المستلم...</option>
                @foreach($contacts as $contact)
                    <option value="{{ $contact->user_id }}">{{ $contact->full_name }}</option>
                @endforeach
            </select>

            <textarea rows="4" id="messageInput" placeholder="اكتب رسالتك هنا..."></textarea>
            <button class="send-btn" id="sendBtn" onclick="handleSendMessage()">إرسال الآن</button>
        </div>
    </div>

    <script>
        function openModal() { 
            document.getElementById('messageModal').style.display = 'flex'; 
        }
        
        function closeModal() { 
            document.getElementById('messageModal').style.display = 'none'; 
        }

        // دالة لفتح المودال وتحديد الشخص فوراً عند الضغط على الكارد
        function openMessageModalWith(userId, userName) {
            document.getElementById('recipientSelect').value = userId;
            openModal();
        }

        async function handleSendMessage() {
            const receiverId = document.getElementById('recipientSelect').value;
            const messageText = document.getElementById('messageInput').value;
            const sendBtn = document.getElementById('sendBtn');

            if(!receiverId || !messageText) {
                alert("يرجى اختيار المستلم وكتابة الرسالة!");
                return;
            }

            // تعطيل الزر أثناء الإرسال
            sendBtn.disabled = true;
            sendBtn.innerText = "جاري الإرسال...";

            try {
                const response = await fetch("{{ route('messages.send') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        receiver_id: receiverId,
                        message_text: messageText
                    })
                });

                const result = await response.json();

                if(result.success) {
                    alert(result.message);
                    document.getElementById('messageInput').value = "";
                    closeModal();
                } else {
                    alert("خطأ: " + result.message);
                }
            } catch (error) {
                alert("فشل الاتصال بالسيرفر!");
            } finally {
                sendBtn.disabled = false;
                sendBtn.innerText = "إرسال الآن";
            }
        }

        // كود الفلترة والبحث (اللي كان عندك)
        const filterButtons = document.querySelectorAll('.filter-btn');
        const messageCards = document.querySelectorAll('.message-card');

        function searchMessages() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            messageCards.forEach(card => {
                let name = card.querySelector('h4').innerText.toLowerCase();
                card.style.display = name.includes(input) ? "flex" : "none";
            });
        }

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                // ... منطق الفلترة الخاص بك ...
            });
        });
    </script>
</body>
</html>