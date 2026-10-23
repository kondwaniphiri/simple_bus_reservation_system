/* Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
var BusApp = {
  url: 'Backend/BusReserve/transactions.php', csrf: '', user: null,
  async request(action, data) {
    var response = await fetch(this.url, {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':this.csrf},body:new URLSearchParams(Object.assign({action:action},data||{}))});
    var result; try { result=await response.json(); } catch(e) { throw new Error('The server returned an invalid response. Check PHP configuration.'); }
    if(!response.ok || result.error) { if(response.status===401 && action!=='login') location.href='account.html'; throw new Error(result.message||'Request failed.'); }
    if(result.csrf) this.csrf=result.csrf; return result;
  },
  async session(required, staff) {
    var result=await this.request('session'); this.user=result.user;
    if(required && !this.user) { location.href='account.html?next=reserve.html'; throw new Error('Please sign in.'); }
    if(staff && !['admin','manager'].includes(this.user.position)) { location.href='index.html'; throw new Error('Staff access required.'); }
    return this.user;
  },
  escape(value) { return String(value==null?'':value).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); },
  async logout() { try { await this.request('logout'); localStorage.removeItem('username'); localStorage.removeItem('userId'); location.href='index.html'; } catch(e) { alert(e.message); } }
};
