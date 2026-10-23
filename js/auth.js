/* Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
document.querySelectorAll('.switcher').forEach(function(button) {
  button.addEventListener('click',function(){document.querySelectorAll('.form-wrapper').forEach(function(w){w.classList.remove('is-active');});button.parentElement.classList.add('is-active');});
});
var accountParams=new URLSearchParams(location.search);
if(accountParams.get('mode')==='signup') document.querySelector('.switcher-signup').click();
function accountDestination(user) {
  if(['admin','manager'].includes(user.position)) return 'admin.dashboard.html';
  if(accountParams.get('next')==='reserve.html'){
    var filters=new URLSearchParams();
    ['from','to'].forEach(function(key){if(accountParams.get(key))filters.set(key,accountParams.get(key));});
    return 'reserve.html'+(filters.toString()?'?'+filters:'');
  }
  return 'index.html';
}
var ready=BusApp.session(false).catch(function(e){message(e.message);});
function message(text) { var box=document.getElementById('auth-message'); if(!box){box=document.createElement('p');box.id='auth-message';box.setAttribute('role','status');document.querySelector('.forms-section').prepend(box);} box.textContent=text; }
['login','signup'].forEach(function(kind){
  document.querySelector('.form-'+kind).addEventListener('submit',async function(e){
    e.preventDefault(); var button=this.querySelector('[type=submit]');button.disabled=true;
    try {
      await ready;
      if(!BusApp.csrf) await BusApp.session(false);
      var data={email:document.getElementById(kind+'-email').value.trim(),password:document.getElementById(kind+'-password').value};
      if(kind==='signup') {
        if(data.password!==document.getElementById('signup-password-confirm').value) throw new Error('Passwords do not match.');
        data.username=document.getElementById('signup-username').value.trim();data.location=document.getElementById('signup-location').value.trim();
      }
      var result=await BusApp.request(kind==='signup'?'register':'login',data);message(result.message);
      if(kind==='login') location.href=accountDestination(result);
      else {
        var signedIn=await BusApp.request('login',{email:data.email,password:data.password});
        location.href=accountDestination(signedIn);
      }
    } catch(error){message(error.message);} finally {button.disabled=false;}
  });
});
