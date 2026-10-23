/* Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
const assert=require('node:assert/strict');
const http=require('node:http');
const base=process.env.BUS_TEST_URL||'http://127.0.0.1:8080';
let cookie='',csrf='';
function request(path,data){
 const body=data?new URLSearchParams(data).toString():'';
 return new Promise((resolve,reject)=>{const req=http.request(base+path,{method:data?'POST':'GET',headers:{Connection:'close',Cookie:cookie,'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':csrf,...(data?{'Content-Length':Buffer.byteLength(body)}:{})}},res=>{let text='';res.setEncoding('utf8');res.on('data',v=>text+=v);res.on('end',()=>{if(res.headers['set-cookie'])cookie=res.headers['set-cookie'][0].split(';')[0];resolve({status:res.statusCode,text});});});req.on('error',reject);req.end(body);});
}
async function api(action,data={},expected=200){const r=await request('/Backend/BusReserve/transactions.php',{action,...data});const value=JSON.parse(r.text);assert.equal(r.status,expected,action+': '+r.text);if(value.csrf)csrf=value.csrf;return value;}
(async()=>{
 for(const path of ['/','/index.html']){const r=await request(path);assert.equal(r.status,200);assert.ok(r.text.includes('id="about"'));assert.ok(r.text.includes('data-member hidden'));assert.ok(r.text.includes('account.html?mode=signup'));assert.ok(!r.text.includes('form-login'));}
 const account=await request('/account.html?mode=signup');assert.equal(account.status,200);assert.ok(account.text.includes('signup-email'));
 await api('session');const info=await api('getPublicInfo');assert.ok(info.sites.length);assert.ok(info.sites.every(s=>!s.email&&!s.username&&!s.password));assert.ok(info.routes.every(r=>Object.keys(r).every(k=>['home','destination','starting_fare','next_departure'].includes(k))));assert.ok(info.routes.every(r=>Number(r.starting_fare)>=0&&new Date(r.next_departure.replace(' ','T')+'+02:00')>new Date()));
 await api('getSchedule',{},401);await api('getReservations',{},401);await api('getUsers',{},401);
 const email='onboarding.'+Date.now()+'@hardwork.local';
 await api('register',{email,username:'Onboarding Test',password:'Onboard2025!',location:'Kitwe',position:'admin'});
 const login=await api('login',{email,password:'Onboard2025!'});assert.equal(login.position,'customer');
 await api('getSchedule');await api('getReservations');await api('getUsers',{},403);
 await api('logout');await api('getReservations',{},401);await api('getPublicInfo');
 console.log('PASS: public homepage, account forms, limited public information, protected booking APIs, registration, customer sign-in and logout.');
 console.log('Temporary account: '+email);
})().catch(e=>{console.error(e);process.exitCode=1;});
