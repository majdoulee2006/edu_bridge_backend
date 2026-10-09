// يشغّل سكربت الإشعارات المنبثقة الفعلي (resources/views/partials/web_toast_notifications.blade.php) بدون متصفح،
// ويحاكي استعلامات متتالية للتحقق من: عدم ابتلاع أول إشعار جديد، وصحة رابط كل نوع.
// التشغيل:  node tests/js/web_toast_notifications.test.cjs
const fs = require('fs');
const html = fs.readFileSync(process.argv[2] || require('path').join(__dirname, '../../resources/views/partials/web_toast_notifications.blade.php'), 'utf8');
const script = html.match(/<script>([\s\S]*?)<\/script>/)[1];

function run(polls, path) {
  const store = {};
  const toasts = [];
  const timers = [];
  let pollIndex = 0;

  const container = { appendChild: (el) => toasts.push(el) };
  const document = {
    getElementById: (id) => (id === 'web-toast-container' ? container : null),
    createElement: () => ({ className: '', classList: { add() {}, remove() {} }, addEventListener(ev, fn) { if (ev === 'click') this._click = fn; }, remove() {}, set innerHTML(v) { this._h = v; }, get innerHTML() { return this._h; } }),
    querySelector: () => null,
    addEventListener: (ev, fn) => { if (ev === 'DOMContentLoaded') document._ready = fn; },
  };
  const sessionStorage = { getItem: (k) => (k in store ? store[k] : null), setItem: (k, v) => { store[k] = v; } };
  const window = { location: { pathname: path, href: '' } };
  const fetch = () => Promise.resolve({ json: () => Promise.resolve({ latest: polls[Math.min(pollIndex++, polls.length - 1)] }) });
  const setInterval = (fn) => { timers.push(fn); };
  const setTimeout = () => {};

  new Function('document', 'sessionStorage', 'window', 'fetch', 'setInterval', 'setTimeout', script)(document, sessionStorage, window, fetch, setInterval, setTimeout);
  return { window, document, timers, toasts, tick: async () => { await Promise.resolve(); await new Promise((r) => setImmediate(r)); } };
}

async function scenario(name, polls, path, expectToasts) {
  const t = run(polls, path);
  t.document._ready();            // الفحص الأول عند فتح الصفحة
  await t.tick();
  t.timers[0]();                  // الفحص الدوري التالي (بعد 8 ثوانٍ)
  await t.tick();
  const ok = t.toasts.length === expectToasts;
  console.log((ok ? 'PASS ' : 'FAIL ') + name + ' -> toasts=' + t.toasts.length + ' (expected ' + expectToasts + ')');
  return { ok, toasts: t.toasts };
}

(async () => {
  const flagNotif = { id: 900, type: 'attendance_flags', title: '🔎 حالات حضور تحتاج مراجعة', message: 'رُصد 2 اشتباه في جلسة حضور مغلقة', related_id: 5 };
  const digestNotif = { id: 901, type: 'weekly_digest', title: '📊 ملخص أسبوع', message: 'العلامات والواجبات', related_id: 7 };
  const results = [];

  // الحالة التي واجهتك: صندوق فارغ عند فتح الصفحة ثم يصل إشعار جديد
  results.push(await scenario('empty inbox then a new notification', [[], [flagNotif]], '/teacher/attendance', 1));
  // لا تُعرض الإشعارات القديمة عند فتح الصفحة
  results.push(await scenario('old unread items are not toasted on load', [[flagNotif], [flagNotif]], '/teacher/dashboard', 0));
  // إشعار جديد يصل بعد وجود قديم
  results.push(await scenario('new one after an old one', [[digestNotif], [flagNotif, digestNotif]], '/teacher/dashboard', 1));

  // الروابط: الضغط على الإشعار المنبثق يفتح الصفحة الصحيحة لا صفحة الحضور/الواجبات
  const clickTarget = async (notif, path) => {
    const t = run([[], [notif]], path);
    t.document._ready(); await t.tick(); t.timers[0](); await t.tick();
    t.toasts[0]._click();
    return t.window.location.href;
  };
  const checks = [
    ['attendance_flags -> flags page', await clickTarget(flagNotif, '/teacher/attendance'), '/teacher/attendance-flags'],
    ['weekly_digest -> that digest', await clickTarget(digestNotif, '/parent/dashboard'), '/parent/digests/7'],
    ['risk_alert (advisor) -> risk list', await clickTarget({ id: 6, type: 'risk_alert', title: 'طلاب ارتفع خطر تعثرهم', message: 'ارتفع مستوى الخطر الأكاديمي لـ 1 طالب' }, '/teacher/dashboard'), '/teacher/risk'],
    ['risk_alert (head) -> risk list', await clickTarget({ id: 7, type: 'risk_alert', title: 'طلاب ارتفع خطر تعثرهم', message: 'الحضور والعلامات' }, '/hod/dashboard'), '/hod/risk'],
    ['plain attendance still -> attendance', await clickTarget({ id: 5, type: 'attendance', title: 'حضور', message: '' }, '/teacher/dashboard'), '/teacher/attendance'],
  ];
  for (const [name, got, want] of checks) {
    const ok = got === want;
    results.push({ ok });
    console.log((ok ? 'PASS ' : 'FAIL ') + name + ' -> ' + got);
  }

  process.exit(results.every((r) => r.ok) ? 0 : 1);
})();
