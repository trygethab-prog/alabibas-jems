<?php require __DIR__ . '/db.php'; if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; } ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin | ALABIBAS</title>
<style>
:root{--ink:#111;--line:#ddd;--bg:#f4f4f2}*{box-sizing:border-box}
body{margin:0;font:14px system-ui;background:var(--bg);color:var(--ink)}
nav{display:flex;gap:6px;padding:12px 20px;background:#fff;border-bottom:1px solid var(--line);align-items:center;flex-wrap:wrap}
nav b{margin-right:auto;font-size:16px}nav button,nav a{border:1px solid var(--line);background:#fff;padding:8px 14px;cursor:pointer;color:inherit;text-decoration:none;font:inherit}nav button.on{background:var(--ink);color:#fff;border-color:var(--ink)}
main{padding:20px;max-width:1100px;margin:auto}.hide{display:none!important}
.wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;background:#fff}th,td{padding:8px 10px;border-bottom:1px solid var(--line);text-align:left}
td.sub{background:#fafaf8;color:#555;font-size:13px}td.sub input{margin:0 10px 0 4px}
input,select,button.a{padding:7px;font:inherit;border:1px solid #ccc}input[type=number]{width:90px}
button.a{background:var(--ink);color:#fff;border-color:var(--ink);cursor:pointer}button.del{background:#fff;border:1px solid #b00020;color:#b00020;padding:6px 10px;cursor:pointer}
.add{display:flex;gap:8px;margin-bottom:6px;flex-wrap:wrap}.hint{color:#666;margin:0 0 14px;font-size:13px}
.tag{padding:2px 8px;border:1px solid var(--line);font-size:12px}.out{color:#b00020}
#chat{display:grid;grid-template-columns:260px 1fr;height:70vh;background:#fff;border:1px solid var(--line)}
#cl{overflow:auto;border-right:1px solid var(--line)}#cl div{padding:10px 12px;border-bottom:1px solid var(--line);cursor:pointer}#cl div.on{background:var(--bg)}
#cl small{display:block;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.badge{background:#b00020;color:#fff;border-radius:9px;padding:0 6px;font-size:12px;float:right}
#pane{display:flex;flex-direction:column;min-width:0}#ms{flex:1;overflow:auto;padding:12px;display:flex;flex-direction:column;gap:6px}
.m{max-width:70%;padding:7px 10px;background:#eee;border-radius:12px;align-self:flex-start;word-wrap:break-word}.m.me{background:var(--ink);color:#fff;align-self:flex-end}
#rf{display:flex;border-top:1px solid var(--line)}#rf input{flex:1;border:0;padding:12px}
@media(max-width:700px){#chat{grid-template-columns:1fr;height:auto}#cl{max-height:150px;border-right:0;border-bottom:1px solid var(--line)}#pane{height:55vh}}
</style></head><body>
<nav><b>ALABIBAS admin</b><button data-t="inv" class="on">Inventory</button><button data-t="chat">Chat <span id="tot" class="badge hide"></span></button><a href="password.php">Password</a><a href="logout.php">Log out</a></nav>
<main>
<section id="inv"><form class="add" id="af"><input name="name" placeholder="Product name" required>
<input name="category" placeholder="Category (Tops)" style="width:130px"><input name="price" type="number" min="0" step="0.01" placeholder="Price" title="Price in pesos">
<input name="image" placeholder="Image path (images/top1.webp)" style="width:210px"><input name="sizes" placeholder="Sizes (Small,Medium,Large)" style="width:200px">
<select name="stock_type"><option value="limited">Limited stock</option><option value="unlimited">Unlimited stock</option></select>
<input name="quantity" type="number" min="0" value="0" title="Quantity"><button class="a">Add product</button></form>
<p class="hint">Products with a price of 0 are hidden from the store. Use the same category name (like Tops) to group products together.</p>
<div class="wrap"><table><thead><tr><th>Product</th><th>Price (&#8369;)</th><th>Stock type</th><th>In stock</th><th>Sold</th><th>Record sale</th><th></th></tr></thead><tbody id="pb"></tbody></table></div></section>
<section id="chatv" class="hide"><div id="chat"><div id="cl"></div><div id="pane"><div id="ms"><p style="padding:12px;color:#666">Select a customer to start chatting.</p></div>
<form id="rf" class="hide"><input id="rt" placeholder="Write a reply..." maxlength="2000"><button class="a">Send</button></form></div></div></section>
</main>
<script>
const $=q=>document.querySelector(q);
const api=async(a,b,qs='')=>{const r=await fetch('api.php?action='+a+qs,b?{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(b)}:{});
 if(r.status===401)return location.href='login.php';const j=await r.json();if(!r.ok)alert(j.error);return r.ok?j:null};
const esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
document.querySelectorAll('nav [data-t]').forEach(b=>b.onclick=()=>{document.querySelectorAll('nav [data-t]').forEach(x=>x.classList.toggle('on',x===b));
 $('#inv').classList.toggle('hide',b.dataset.t!=='inv');$('#chatv').classList.toggle('hide',b.dataset.t!=='chat')});

// ---- inventory ----
const fld=(id,k,v,ph,w)=>`<input value="${esc(v)}" placeholder="${ph}" style="width:${w}" onchange="up(${id},{${k}:this.value})">`;
async function loadP(){const ps=await api('products');if(!ps)return;
 $('#pb').innerHTML=ps.map(p=>`<tr><td>${fld(p.id,'name',p.name,'Name','180px')}</td>
 <td><input type="number" min="0" step="0.01" value="${p.price}" onchange="up(${p.id},{price:this.value})"></td>
 <td><select onchange="up(${p.id},{stock_type:this.value})"><option ${p.stock_type=='limited'?'selected':''} value="limited">Limited</option><option ${p.stock_type=='unlimited'?'selected':''} value="unlimited">Unlimited</option></select></td>
 <td>${p.stock_type=='unlimited'?'<span class="tag">Unlimited</span>':`<input type="number" min="0" value="${p.quantity}" onchange="up(${p.id},{quantity:this.value})"> ${+p.quantity==0?'<span class="out">Sold out</span>':''}`}</td>
 <td>${p.sold_count}</td><td><input type="number" min="1" value="1" id="q${p.id}" style="width:60px"> <button class="a" onclick="sold(${p.id})">Record sale</button></td>
 <td><button class="del" onclick="del(${p.id},'${esc(p.name).replace(/'/g,'')}')">Delete</button></td></tr>
 <tr><td colspan="7" class="sub">Category ${fld(p.id,'category',p.category,'Tops','110px')} Image path ${fld(p.id,'image',p.image,'images/top1.webp','190px')} Sizes ${fld(p.id,'sizes',p.sizes,'Small,Medium,Large','190px')}</td></tr>`).join('')||'<tr><td colspan="7">No products yet. Add your first one above.</td></tr>'}
const up=async(id,b)=>{await api('update_product',{id,...b});loadP()};
const sold=async id=>{await api('sold',{id,qty:$('#q'+id).value});loadP()};
const del=async(id,n)=>{if(confirm('Delete '+n+'? This cannot be undone.')){await api('delete_product',{id});loadP()}};
$('#af').onsubmit=async e=>{e.preventDefault();const f=e.target;
 if(await api('add_product',{name:f.name.value,category:f.category.value,price:f.price.value,image:f.image.value,sizes:f.sizes.value,stock_type:f.stock_type.value,quantity:f.quantity.value}))f.reset();loadP()};

// ---- chat (checks for new messages every few seconds) ----
let cur=null,last=0;
const bub=m=>`<div class="m ${m.sender==='admin'?'me':''}">${esc(m.text)}</div>`;
async function loadC(){const cs=await api('conversations');if(!cs)return;
 const tot=cs.reduce((a,c)=>a+ +c.unread,0);$('#tot').textContent=tot;$('#tot').classList.toggle('hide',!tot);
 document.title=(tot?'('+tot+') ':'')+'Admin | ALABIBAS';
 $('#cl').innerHTML=cs.map(c=>`<div data-id="${esc(c.customer_id)}" class="${c.customer_id===cur?'on':''}">${+c.unread?`<span class="badge">${c.unread}</span>`:''}<b>${esc(c.name||'Customer '+c.customer_id)}</b><small>${esc(c.last_text)}</small></div>`).join('')||'<p style="padding:12px;color:#666">No conversations yet.</p>';
 document.querySelectorAll('#cl [data-id]').forEach(d=>d.onclick=()=>{cur=d.dataset.id;last=0;$('#ms').innerHTML='';$('#rf').classList.remove('hide');loadM();loadC()})}
async function loadM(){if(!cur)return;const ms=await api('messages',null,`&cid=${encodeURIComponent(cur)}&after=${last}`);if(!ms||!ms.length)return;
 $('#ms').insertAdjacentHTML('beforeend',ms.map(bub).join(''));last=+ms[ms.length-1].id;$('#ms').scrollTop=1e9}
$('#rf').onsubmit=async e=>{e.preventDefault();const t=$('#rt').value.trim();if(!t||!cur)return;$('#rt').value='';await api('reply',{cid:cur,text:t});loadM();loadC()};
loadP();loadC();setInterval(loadM,2000);setInterval(loadC,4000);
</script></body></html>
