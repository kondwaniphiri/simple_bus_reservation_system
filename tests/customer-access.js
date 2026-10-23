/* Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
/* Check customer versus administrator access on the running local application. */
const assert=require('node:assert/strict');
const http=require('node:http');
const base=process.env.BUS_TEST_URL||'http://127.0.0.1:8080';
class Client {
 constructor(){this.cookie='';this.csrf='';}
 async api(action,data={},status=200){
  const body=new URLSearchParams({action,...data}).toString();
  const response=await new Promise((resolve,reject)=>{
   const request=http.request(base+'/Backend/BusReserve/transactions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','Content-Length':Buffer.byteLength(body),'X-CSRF-Token':this.csrf,Cookie:this.cookie,Connection:'close'}},res=>{
    let text='';res.setEncoding('utf8');res.on('data',chunk=>text+=chunk);res.on('end',()=>resolve({status:res.statusCode,headers:res.headers,body:JSON.parse(text)}));
   });request.on('error',reject);request.end(body);
  });
  assert.equal(response.status,status,action+': '+JSON.stringify(response.body));
  if(response.headers['set-cookie'])this.cookie=response.headers['set-cookie'][0].split(';')[0];
  if(response.body.csrf)this.csrf=response.body.csrf;
  return response.body;
 }
}
(async()=>{
 const customer=new Client();await customer.api('session');
 const login=await customer.api('login',{email:'customer@hardwork.local',password:'Customer2025!'});
 assert.equal(login.position,'customer');assert.ok(!login.password);
 const own=await customer.api('getReservations');assert.ok(own.data.length>0);assert.ok(own.data.every(r=>r.user_id==login.id));
 for(const action of ['getUsers','getAudit','getBus','saveSite','saveStaff'])await customer.api(action,{position:'admin'},403);
 const admin=new Client();await admin.api('session');
 const staff=await admin.api('login',{email:'operations@hardwork.local',password:'DemoTravel2026!'});assert.equal(staff.position,'admin');
 const users=await admin.api('getUsers');assert.ok(users.data.some(u=>u.email==='customer@hardwork.local'&&u.position==='customer'));
 const branches=await customer.api('getSites');assert.ok(branches.data.every(s=>!/demo|sample|demonstrat/i.test([s.name,s.address,s.phone].join(' '))));
 await customer.api('logout');await admin.api('logout');
 console.log('PASS: customer sign-in, own bookings, five blocked staff actions, administrator access and clean branch labels.');
})().catch(e=>{console.error(e);process.exitCode=1;});
