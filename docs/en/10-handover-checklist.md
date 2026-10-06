# Handover Checklist

For use when transferring the project to a buyer or a new developer. Tick each item when done.

## 1) Code and repository

- [ ] Both repositories (Laravel and Flutter) in their final version, with the approved branches explained
- [ ] Merge the security-fix branch (`chore/security-cleanup-and-docs`) or document why it was not merged
- [ ] The repository is free of large and sensitive files (`ngrok.exe`, `.sql` files, `accounts.xlsx`, `*creds*` files)
- [ ] Review `.gitignore` in both projects
- [ ] Review git history: did any secrets enter it? (the data is said to be sample data, but check keys)
- [ ] Decide the new repository owner, and transfer ownership or hand over a copy (fork/export)

## 2) Accounts and keys (transferred, or created by the buyer)

| Service | Use | Transfer? | Needed |
|---|---|---|---|
| Firebase (project `edu-bridge-246fd`) | FCM push and Firebase Web | ☐ transfer ownership / ☐ new project | `google-services.json`, the service account, the web keys in `main.dart` |
| Pusher | Real-time chat | ☐ / ☐ | App ID/Key/Secret/Cluster (the public key and `cluster` are written in `chat_service.dart`) |
| Telegram Bot | The bot and OTP | ☐ / ☐ | A new token from BotFather, and `TELEGRAM_WEBHOOK_SECRET` |
| Google Gemini | AI assistant | ☐ / ☐ | `GEMINI_API_KEY` |
| SMTP | OTP emails | ☐ / ☐ | Mail credentials |
| Hosting / domain | Production | ☐ | Not currently in the repository |
| Google Play / App Store | Publishing the app | ☐ | Publisher accounts and the signing key |

> **Important:** every current key must be **rotated or replaced** at handover, so the previous owner keeps no access to the new system.

## 3) Data

- [ ] No real data in any copy that is handed over (all data is said to be sample data: check `*.sql` files and local backups)
- [ ] Seeders produce a complete demo environment (roles, students, courses, attendance, grades)
- [ ] Demo accounts are documented with passwords that are changed at handover
- [ ] Face embeddings and photos: wipe them from any sample copy

## 4) Licenses and intellectual property

- [ ] **`mobile_face_net.tflite` model:** license not explicitly documented (`assets/models/NOTICE.md`). Re-derive it from `sirius-ai/MobileFaceNet_TF` (Apache-2.0) or obtain written confirmation
- [ ] Review the licenses of Composer, pub and npm packages (`composer licenses`, `flutter pub deps`)
- [ ] Fonts, images and logos (`public/images/logos`: `dtc`, `unrwa`, others): do you have the right to hand them over? (they may belong to third parties)
- [ ] **Project rights:** if this is a university graduation project or was built for an organization, make sure the university/supervisor/organization agrees to the transfer of ownership. (An ownership-transfer contract, an NDA and a legal review are recommended)
- [ ] Credit the contributors (the development team)

## 5) Documentation and knowledge

- [ ] `docs/` is complete in Arabic and English
- [ ] Try `06-setup-and-deploy.md` literally on a clean machine
- [ ] A recorded or written walkthrough of the most important flows
- [ ] The known-issues list (`08-project-status.md`) is updated on handover day
- [ ] A named contact for post-handover questions, and a support period if any

## 6) Production (if a system is running)

- [ ] A full backup of the database and files
- [ ] Server configuration (Nginx/cron/Supervisor) documented
- [ ] Transfer the domain and HTTPS certificates
- [ ] Stop or transfer any tunnels (ngrok) and auto-start services on the developer's machine (`سيرفر/سيرفر.bat`)
