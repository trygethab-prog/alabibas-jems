/* ALABIBAS live chat (customer side) - messages are stored on the server (admin/chat_api.php).
   Pages load it with:  <script src="chat.js"></script> */
(function () {
  var API = "/admin/chat_api.php", ME = "/admin/auth_api.php", USER_KEY = "alabibasUser", OPEN_KEY = "alabibasChatOpen";
  var MAX_LEN = 500;

  var css = "#ab-chat-btn{position:fixed;right:20px;bottom:20px;z-index:50;width:58px;height:58px;border:0;border-radius:50%;background:#a36233;color:#fff;cursor:pointer;box-shadow:0 3px 8px rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center}#ab-chat-btn:hover{background:#8a5630}#ab-chat-btn svg{width:28px;height:28px}" +
  "#ab-chat-badge{position:absolute;top:-4px;right:-4px;min-width:20px;height:20px;padding:0 5px;border-radius:10px;background:#b3261e;color:#fff;font-size:.68rem;font-weight:600;line-height:20px;text-align:center}[hidden]{display:none!important}" +
  "#ab-chat-panel{position:fixed;right:20px;bottom:90px;z-index:50;width:340px;max-width:calc(100vw - 24px);height:460px;max-height:calc(100vh - 110px);display:flex;flex-direction:column;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.35);font-family:'Poppins',Arial,sans-serif;color:#000}#ab-chat-panel *{box-sizing:border-box}" +
  ".ab-head{background:#b0b998;color:#fff;padding:12px 16px;display:flex;align-items:center;justify-content:space-between}.ab-head strong{display:block;font-size:.95rem;font-weight:600}.ab-head small{font-size:.7rem}.ab-close{background:none;border:0;color:#fff;font-size:1.5rem;cursor:pointer}" +
  ".ab-msgs{flex:1;overflow-y:auto;padding:14px;background:#fbf8f1;display:flex;flex-direction:column;gap:8px}.ab-row{display:flex;max-width:80%}.ab-row.me{align-self:flex-end}.ab-row.them{align-self:flex-start}" +
  ".ab-bubble{padding:8px 13px;border-radius:14px;font-size:.82rem;line-height:1.45;white-space:pre-wrap;overflow-wrap:anywhere}.ab-row.me .ab-bubble{background:#a36233;color:#fff;border-bottom-right-radius:4px}.ab-row.them .ab-bubble{background:#f8f0de;border-bottom-left-radius:4px}" +
  ".ab-form{display:flex;gap:8px;padding:10px 12px;border-top:1px solid #eee6d3;background:#fff}.ab-form input{flex:1;min-width:0;height:38px;border:1px solid #b0b998;border-radius:999px;padding:0 14px;font:inherit;font-size:.82rem;outline:none}.ab-form input:focus{border-color:#a36233}" +
  ".ab-form button{border:0;border-radius:999px;background:#a36233;color:#fff;padding:0 18px;font:inherit;font-size:.82rem;cursor:pointer}" +
  ".ab-login{flex:1;text-align:center;padding:20px;font-size:.85rem;line-height:1.6;background:#fbf8f1;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:14px}.ab-login a{background:#a36233;color:#fff;border-radius:999px;padding:9px 26px;font-weight:600;text-decoration:none}" +
  "@media (max-width:480px){#ab-chat-panel{right:12px;bottom:84px}#ab-chat-btn{right:12px;bottom:14px}}";
  var style = document.createElement("style"); style.textContent = css; document.head.appendChild(style);

  function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text) n.textContent = text; return n; }
  function api(url, body) {
    return fetch(url, body ? { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(body) } : {})
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { ok: r.ok, j: j }; }); })
      .catch(function () { return { ok: false, j: {} }; });
  }

  var btn = el("button"); btn.id = "ab-chat-btn"; btn.type = "button"; btn.setAttribute("aria-label", "Open chat");
  btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg>';
  var badge = el("span"); badge.id = "ab-chat-badge"; badge.hidden = true; btn.appendChild(badge);
  var panel = el("section"); panel.id = "ab-chat-panel"; panel.hidden = true;
  var head = el("div", "ab-head"), info = el("div");
  info.appendChild(el("strong", "", "Chat with ALABIBAS")); info.appendChild(el("small", "", "Support hours: 8AM \u2013 5PM"));
  var closeBtn = el("button", "ab-close", "\u00d7"); closeBtn.type = "button"; closeBtn.setAttribute("aria-label", "Close chat");
  head.appendChild(info); head.appendChild(closeBtn);
  var body = el("div"); body.style.cssText = "display:flex;flex-direction:column;flex:1;min-height:0";
  panel.appendChild(head); panel.appendChild(body);
  document.body.appendChild(panel); document.body.appendChild(btn);

  var msgsEl = null, input = null, mode = "", open = false, last = 0, unread = 0, seenKey = "alabibasChatSeen", seen = 0, hello = null;

  function buildLogin() {
    body.innerHTML = ""; msgsEl = input = null; mode = "login";
    var box = el("div", "ab-login");
    box.appendChild(el("p", "", "Please log in to chat with our team. It only takes a moment."));
    var a = el("a", "", "Login / Register"); a.href = "login.html?next=" + encodeURIComponent(location.pathname.split("/").pop() || "index.html");
    box.appendChild(a); body.appendChild(box);
  }
  function buildChat(user) {
    body.innerHTML = ""; mode = "chat"; last = 0; unread = 0;
    seenKey = "alabibasChatSeen:" + user.email; seen = +(localStorage.getItem(seenKey) || 0);
    msgsEl = el("div", "ab-msgs"); msgsEl.setAttribute("aria-live", "polite");
    hello = el("div", "ab-row them"); hello.appendChild(el("div", "ab-bubble", "Hi, " + (user.name || "there") + "! How can we help you today?")); msgsEl.appendChild(hello);
    var form = el("form", "ab-form"); input = el("input"); input.type = "text"; input.maxLength = MAX_LEN;
    input.placeholder = "Type your message..."; input.autocomplete = "off"; input.setAttribute("aria-label", "Your message");
    var send = el("button", "", "Send"); send.type = "submit";
    form.appendChild(input); form.appendChild(send); body.appendChild(msgsEl); body.appendChild(form);
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var t = input.value.trim().slice(0, MAX_LEN); if (!t) return; input.value = "";
      api(API + "?action=send", { text: t }).then(function (r) { if (!r.ok) alert(r.j.error || "Message could not be sent."); poll(); });
    });
  }
  function addMsg(m) {
    if (hello) { hello.remove(); hello = null; }
    var row = el("div", "ab-row " + (m.sender === "customer" ? "me" : "them"));
    row.appendChild(el("div", "ab-bubble", m.text)); msgsEl.appendChild(row);   // textContent: safe from HTML injection
  }
  function updateBadge() { badge.hidden = unread === 0; badge.textContent = unread > 9 ? "9+" : String(unread); }
  function markSeen() { seen = last; unread = 0; try { localStorage.setItem(seenKey, String(seen)); } catch (e) {} updateBadge(); }

  function checkMe() {
    return api(ME + "?action=me").then(function (r) {
      if (r.ok) { try { localStorage.setItem(USER_KEY, JSON.stringify({ name: r.j.name, email: r.j.email })); } catch (e) {} if (mode !== "chat") buildChat(r.j); poll(); }
      else { try { localStorage.removeItem(USER_KEY); } catch (e) {} if (mode !== "login") buildLogin(); }
    });
  }
  function poll() {
    if (mode !== "chat") return;
    api(API + "?action=messages&after=" + last).then(function (r) {
      if (r.j && r.j.error === "Please log in to chat") return checkMe();
      if (!r.ok || !Array.isArray(r.j) || !r.j.length) return;
      r.j.forEach(function (m) { last = Math.max(last, +m.id); addMsg(m); if (m.sender === "admin" && +m.id > seen) unread++; });
      msgsEl.scrollTop = msgsEl.scrollHeight;
      open ? markSeen() : updateBadge();
    });
  }
  function setOpen(o) {
    open = o; panel.hidden = !o; btn.setAttribute("aria-label", o ? "Close chat" : "Open chat");
    try { sessionStorage.setItem(OPEN_KEY, o ? "1" : "0"); } catch (e) {}
    if (o) { if (mode === "chat") { markSeen(); msgsEl.scrollTop = msgsEl.scrollHeight; } if (input) input.focus(); }
  }
  btn.addEventListener("click", function () { setOpen(!open); });
  closeBtn.addEventListener("click", function () { setOpen(false); btn.focus(); });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape" && open) { setOpen(false); btn.focus(); } });

  checkMe().then(function () { var w = false; try { w = sessionStorage.getItem(OPEN_KEY) === "1"; } catch (e) {} setOpen(w); });
  setInterval(poll, 4000);
})();
