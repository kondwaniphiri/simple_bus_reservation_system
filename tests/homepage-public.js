/* Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
const assert=require('node:assert/strict'),http=require('node:http');
const base=process.env.BUS_TEST_URL||'http://127.0.0.1:8080';
function request(path,data){const body=data?new URLSearchParams(data).toString():'';return new Promise((resolve,reject)=>{const q=http.request(base+path,{method:data?'POST':'GET',headers:{Connection:'close',...(data?{'Content-Type':'application/x-www-form-urlencoded','Content-Length':Buffer.byteLength(body)}:{})}},r=>{let text='';r.setEncoding('utf8');r.on('data',v=>text+=v);r.on('end',()=>resolve({status:r.statusCode,text}));});q.on('error',reject);q.end(body);});}
(async()=>{
 for(const page of ['/','/index.html']){const r=await request(page);assert.equal(r.status,200);assert.match(r.text,/id="popular-routes-section"/);assert.match(r.text,/id="booking-guide"/);assert.match(r.text,/simple-faq/);assert.match(r.text,/meta name="author" content="Kondwani Phiri"/);assert.match(r.text,/Developed by Kondwani Phiri/);}
 const routes=JSON.parse((await request('/Backend/BusReserve/transactions.php',{action:'getPublicInfo'})).text).routes;
 assert.ok(routes.length>0);for(const route of routes){assert.ok(Number(route.starting_fare)>=0);assert.ok(new Date(route.next_departure.replace(' ','T')+'+02:00')>new Date());assert.ok(!('seats' in route)&&!('trip_id' in route));}
 for(const path of ['/.env','/.env?raw=1','/%2eenv','/.env.example'])assert.equal((await request(path)).status,404,path+' must stay private');
 assert.equal((await request('/Backend/BusReserve/transactions.php',{action:'getSchedule'})).status,401);
 console.log('PASS: public route fares/departures, guide, FAQs, author attribution, booking gate and blocked environment files.');
})().catch(e=>{console.error(e);process.exitCode=1;});
