# Startup and Testing Preference

Whenever the user says "Start the project" or "Test the project", you must automatically:
1. Execute `php artisan serve` in the background (using a detached process or send it to background via `WaitMsBeforeAsync`).
2. Execute `npm run dev` in the background (using a detached process or send it to background via `WaitMsBeforeAsync`).
3. Immediately proceed to use the browser control tool to test the local URL (typically http://127.0.0.1:8000 or http://localhost:8000). Do not hang or wait indefinitely for the background tasks to finish if they are blocking.
