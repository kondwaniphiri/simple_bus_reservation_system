/* Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
const assert=require('node:assert/strict'),http=require('node:http');
const base=process.env.BUS_TEST_URL||'http://127.0.0.1:8080';
class Client{constructor(){this.cookie='';this.csrf='';}async call(action,data={},expected=200,token=true){const body=new URLSearchParams({action,...data}).toString();const r=await new Promise((resolve,reject)=>{const q=http.request(base+'/Backend/BusReserve/transactions.php',{method:'POST',headers:{Connection:'close',Cookie:this.cookie,'X-CSRF-Token':token?this.csrf:'','Content-Type':'application/x-www-form-urlencoded','Content-Length':Buffer.byteLength(body)}},s=>{let text='';s.setEncoding('utf8');s.on('data',v=>text+=v);s.on('end',()=>resolve({status:s.statusCode,headers:s.headers,value:JSON.parse(text)}));});q.on('error',reject);q.end(body);});assert.equal(r.status,expected,action+': '+JSON.stringify(r.value));if(r.headers['set-cookie'])this.cookie=r.headers['set-cookie'][0].split(';')[0];if(r.value.csrf)this.csrf=r.value.csrf;return r.value;}async login(email,password='DemoTravel2026!'){await this.call('session');await this.call('login',{email,password});return this;}}
(async()=>{
 const guest=new Client();await guest.call('session');const sites=(await guest.call('getPublicInfo')).sites;const site=sites.find(s=>s.name==='Lusaka Travel Centre');assert.ok(site);
 const data={name:'Contact Form Test',email:'contact-form-test@hardwork.local',phone:'',site_id:site.site_id,subject:'Contact form verification',message:'Please confirm the boarding details for the next available coach.'};
 await guest.call('contact',data,403,false);await guest.call('contact',{...data,email:'invalid'},422);await guest.call('contact',{...data,message:'short'},422);
 const sent=await guest.call('contact',data);assert.ok(sent.enquiry_id);
 const honey=await guest.call('contact',{...data,website:'spam.example'});assert.ok(!honey.enquiry_id);
 await guest.call('getEnquiries',{},401);
 const customer=await new Client().login('customer@hardwork.local','Customer2025!');await customer.call('getEnquiries',{},403);
 const own=await new Client().login('lusaka.manager@hardwork.local');assert.ok((await own.call('getEnquiries')).data.some(e=>e.enquiry_id==sent.enquiry_id));
 const other=await new Client().login('kitwe.manager@hardwork.local');assert.ok(!(await other.call('getEnquiries')).data.some(e=>e.enquiry_id==sent.enquiry_id));await other.call('saveEnquiry',{enquiry_id:sent.enquiry_id,status:'closed'},403);
 const admin=await new Client().login('operations@hardwork.local');await admin.call('saveEnquiry',{enquiry_id:sent.enquiry_id,status:'in_progress'});const row=(await admin.call('getEnquiries')).data.find(e=>e.enquiry_id==sent.enquiry_id);assert.equal(row.status,'in_progress');assert.ok(!row.sender_key);
 for(let i=0;i<4;i++)await guest.call('contact',{...data,subject:'Contact form verification '+i});await guest.call('contact',data,429);
 for(const client of [customer,own,other,admin])await client.call('logout');
 console.log('PASS: contact submission, CSRF, validation, honeypot, rate limit, admin inbox and branch isolation.');
})().catch(e=>{console.error(e);process.exitCode=1;});
