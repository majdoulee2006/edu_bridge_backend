# Edu Bridge — App Distribution & Update Guide (briefing for an AI assistant)

> Paste this whole file to any AI assistant so it understands the project and how the Android app is distributed and updated.
> The team speaks Arabic (Syrian dialect); answer in simple Arabic unless asked otherwise.

## 1. What the project is
**Edu Bridge** is a graduation project (Computer & Information Systems dept., Damascus Intermediate Technical Institute, Syria). It connects administration, teachers, students and parents.

- **Backend:** Laravel (PHP). Folder: `D:\Graduation Project\laravel\edu_bridge_backend`. Also serves a web admin panel.
- **Mobile app:** Flutter (Android). Folder: `D:\Graduation Project\Flutter\Edu_Pridge_flutter`. Package/applicationId: `com.example.edu_pridge_flutter`.
- **Roles:** student, teacher, parent, department head, affairs officer, admin (one app for all; role decided after login).
- **Firebase** (project "EduBridge"): FCM push notifications + **phone-number OTP login** (`verifyPhoneNumber`). Chat uses Pusher.
- **Intro website (separate, Netlify):** https://edu-bradge.netlify.app — React, dark/light theme, gold `#ffcc00` / `#c5a029`, font Cairo. The download page copies its style.

## 2. Hosting constraints (important)
- Backend runs on the institute server: `http://82.137.250.43:8080/edu_bridge/public` (**HTTP, no HTTPS**).
- The team has **no FTP, no SSH, no GitHub deploy**. A third person opens a control panel and uploads the **whole backend folder manually**, as-is (including `vendor/` and `storage/`). So anything the server needs must live inside the backend project folder.
- The app is **NOT on Google Play**. A supervisor requires that the APK be downloaded **only from the institute server**. Users include non-technical elderly parents → the download flow must be one tap.
- Syria: slow/expensive internet, many old phones, Google Drive is awkward for parents.

## 3. Distribution design (implemented)
Files (all inside the backend project):
| Path | Purpose |
|---|---|
| `app/Http/Controllers/AppReleaseController.php` | page, version API, APK download |
| `resources/views/app-download.blade.php` | public download page (light default + dark toggle, QR, share buttons, team, FAQ) |
| `public/js/qrcode.min.js`, `public/images/edubridge-logo.png` | local assets (no external CDN except Google Fonts for Cairo) |
| `routes/web.php` | `GET /app`, `GET /app/download` |
| `routes/api.php` | `GET /api/app-version` (public) |
| `storage/app/app-release/edubridge.apk` | arm64-v8a build (default, most phones) |
| `storage/app/app-release/edubridge-v7a.apk` | armeabi-v7a build (old 32-bit phones; optional) |
| `storage/app/app-release/release.json` | `{"version_name","version_code","min_version_code","changelog"}` |

Public URLs: page `…/public/app`, download `…/public/app/download`, 32-bit `…/public/app/download?abi=v7a`, API `…/public/api/app-version?abi=arm64|v7a`.
API returns `available, version_name, version_code, min_version_code, changelog, size_bytes, sha256, apk_url` (size and sha256 are computed from the file automatically).

## 4. In-app update flow (Flutter)
File: `lib/services/app_update_service.dart`, called from `lib/main.dart` ~3 s after launch.
1. Reads `supported64BitAbis` (device_info_plus): empty → `abi=v7a`, else `arm64`.
2. `GET {ApiService().baseUrl}/app-version?abi=…` (errors are ignored silently).
3. Compares installed base version with `version_code`. **Installed `versionCode` is taken `% 1000`** because `flutter build --split-per-abi` adds an offset (armeabi-v7a +1000, arm64 +2000, x86_64 +4000) to the pubspec build number.
4. If lower → dialog "تحديث الآن / لاحقاً". If lower than `min_version_code` → forced (no "later").
5. Downloads with Dio to temp, verifies SHA-256, opens it with `open_filex` → Android's installer shows **Update** (data is kept).
- Manifest has `REQUEST_INSTALL_PACKAGES`. The user must allow "install unknown apps" once.
- `ApiService.instituteServerUrl` is the default server on first launch. The login screen has a hidden server dialog (USB 127.0.0.1 / WiFi / external) for developers; a saved choice is kept.

## 5. Signing — the critical rule
Android only installs an update over an existing app if **same applicationId, same signing certificate, and higher versionCode**. Otherwise the user must uninstall first.
- Release signing is configured in `android/app/build.gradle.kts` via `android/key.properties` + `android/edubridge-release.jks`. Both are git-ignored.
- A **new keystore was created on 2026-10-06** (alias `edubridge`, CN=Edu Bridge, Damascus). Backup folder: `Desktop\EduBridge_KEYS_BACKUP` (must also be copied to a USB/email). **If it is lost, no user can ever be updated without reinstalling.** Never commit it, never share passwords publicly.
- Certificate fingerprints of the new key (registered in Firebase → Project settings → the Android app → SHA certificate fingerprints):
  - SHA-1 `238ee0dd32e7dd8752419bb3d6aad4e3bb83f7f3`
  - SHA-256 `791e35fff2bb104b161580a31ba356abe275fbbe0690d4130457c53905bfab27`
- The APK distributed earlier (built on teammate Magdolin's machine, her key unavailable, SHA-1 `94d09a43d39a54c60213067a477b6f4459f4ec11`) is signed with a **different** key. Users of that APK must **uninstall once**, then install the new one. After that, updates are seamless.
- Never change `applicationId`, never build a release without the keystore, never lower the version.

## 6. APK size note
Old APK was ~148 MB (universal: arm64 + v7a + x86_64). New builds use `--split-per-abi`: arm64 ≈ 57 MB, v7a ≈ 52 MB. Verified: assets (11.8 MB), dex and minSdk (24) are identical to the old APK; only other-ABI native libraries are dropped, so features are the same. Phones with a 32-bit OS get the v7a build (offered by the page's small link and chosen automatically by the in-app updater).

## 7. How to publish a new version (checklist)
1. In `pubspec.yaml` raise the number after `+` (e.g. `1.0.1+2`). Must strictly increase; keep it < 1000.
2. Build **both** APKs with ONE command (run inside the Flutter project folder):
   ```
   cd "D:\Graduation Project\Flutter\Edu_Pridge_flutter"
   flutter build apk --release --split-per-abi
   ```
   (first build ≈ 15–20 min). It prints several files in `build/app/outputs/flutter-apk/`; we use two of them (ignore `app-x86_64-release.apk`, that is for emulators):
   - `app-arm64-v8a-release.apk`  → **new phones** (64-bit, the vast majority), ~57 MB
   - `app-armeabi-v7a-release.apk` → **old phones** (32-bit), ~52 MB
3. Copy them into the backend project under these exact names (overwrite):
   - `app-arm64-v8a-release.apk`   → `storage/app/app-release/edubridge.apk`
   - `app-armeabi-v7a-release.apk` → `storage/app/app-release/edubridge-v7a.apk`
4. Edit `storage/app/app-release/release.json`: new `version_name`, `version_code` (= number after `+`), `changelog` (shown on the page and in the dialog). Raise `min_version_code` **only** to force an update.
5. Upload the backend folder to the server (manually, by the team). Verify `/app`, `/api/app-version`, and that an old install updates over itself.
6. (Optional) verify the signature: `apksigner verify --print-certs edubridge.apk` must show SHA-1 `238ee0dd…`.

## 8. Known limits / TODO ideas
- HTTP only: Chrome may warn "insecure download"; the page explains «تنزيل على أي حال». An HTTPS certificate would help.
- Firebase phone login: fingerprints were added to Firebase but login with the new build was **not yet tested on a real device**.
- The update flow was tested only for server endpoints and static analysis, not on a physical phone.
- Android only; iPhone users see a notice.
- `applicationId` is still `com.example.*` (changing it would break updates for existing installs).
