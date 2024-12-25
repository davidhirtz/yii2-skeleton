(()=>{var a=o=>{let e=document.querySelector(o),t=e.querySelector("#token"),u=e.querySelector("#honeypot"),l=e.querySelector("#tz");l.value=new Intl.DateTimeFormat().resolvedOptions().timeZone||"UTC",e.onsubmit=r=>{t.value||(fetch(t.dataset.url).then(n=>n.text()).then(n=>t.value=n).then(()=>{u.value="",e.submit()}),r.preventDefault())}};})();
//# sourceMappingURL=signup.js.map
