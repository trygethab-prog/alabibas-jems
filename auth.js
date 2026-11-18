/* ALABIBAS login/register - now checked by the server (admin/auth_api.php) instead of localStorage. */
(function () {
  var API = "/admin/auth_api.php", USER_KEY = "alabibasUser";
  var $ = function (id) { return document.getElementById(id); };
  var params = new URLSearchParams(location.search);

  function nextPage() {
    var next = params.get("next") || "";
    var ok = ["index.html", "products.html", "cart.html", "aboutus.html", "contact.html", "help.html",
              "orderstatus.html", "privacy.html", "returns.html", "storelocator.html", "terms.html"];
    return ok.indexOf(next) > -1 ? next : "index.html";
  }
  function call(action, body) {
    return fetch(API + "?action=" + action, body ? { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(body) } : {})
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { ok: r.ok, j: j }; }); })
      .catch(function () { return { ok: false, j: { error: "Cannot reach the server. Please try again." } }; });
  }
  function showMsg(box, t) { box.textContent = t; box.hidden = false; }
  function done(user) {
    try { localStorage.setItem(USER_KEY, JSON.stringify({ name: user.name, email: user.email })); } catch (e) {}
    location.href = nextPage();
  }

  var tabLogin = $("tabLogin"), tabRegister = $("tabRegister"), loginPanel = $("loginPanel"), registerPanel = $("registerPanel");
  function setTab(name) {
    var isLogin = name === "login";
    tabLogin.setAttribute("aria-selected", isLogin); tabRegister.setAttribute("aria-selected", !isLogin);
    loginPanel.hidden = !isLogin; registerPanel.hidden = isLogin;
    $("loginMsg").hidden = true; $("registerMsg").hidden = true;
  }
  tabLogin.onclick = function () { setTab("login"); };
  tabRegister.onclick = function () { setTab("register"); };
  $("toRegister").onclick = function () { setTab("register"); };
  $("toLogin").onclick = function () { setTab("login"); };
  if (params.get("tab") === "register") setTab("register");

  registerPanel.addEventListener("submit", function (e) {
    e.preventDefault();
    var msg = $("registerMsg"), pw = $("regPassword").value;
    if (pw !== $("regConfirm").value) return showMsg(msg, "The passwords do not match.");
    call("register", { name: $("regName").value, email: $("regEmail").value, password: pw }).then(function (r) {
      r.ok ? done(r.j) : showMsg(msg, r.j.error || "Something went wrong.");
    });
  });

  loginPanel.addEventListener("submit", function (e) {
    e.preventDefault();
    var msg = $("loginMsg");
    call("login", { email: $("loginEmail").value, password: $("loginPassword").value }).then(function (r) {
      r.ok ? done(r.j) : showMsg(msg, r.j.error || "Something went wrong.");
    });
  });

  // already logged in?
  call("me").then(function (r) {
    if (!r.ok) { try { localStorage.removeItem(USER_KEY); } catch (e) {} return; }
    $("authForms").hidden = true; $("loggedIn").hidden = false;
    $("loggedInText").textContent = "Hi, " + (r.j.name || "there") + "! You're signed in as " + r.j.email + ".";
    $("continueLink").href = nextPage();
    $("logoutBtn").onclick = function () {
      call("logout", {}).then(function () { try { localStorage.removeItem(USER_KEY); } catch (e) {} location.reload(); });
    };
  });
})();
