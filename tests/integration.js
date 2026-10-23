/* Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
/* Run against a dedicated migrated _test database and PHP server only.
   BUS_TEST_URL=http://127.0.0.1:8087 node tests/integration.js */
const assert=require('node:assert/strict');
const {execFileSync,execFile}=require('node:child_process');
const {promisify}=require('node:util');
const run=promisify(execFile);
const base=process.env.BUS_TEST_URL||'http://127.0.0.1:8087';
let checks=0;
class Client {
 constructor(){this.cookie='';this.csrf='';}
 async call(action,data={},expected=200,csrf=true){
  const r=await fetch(base+'/Backend/BusReserve/transactions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded',Cookie:this.cookie,...(csrf?{'X-CSRF-Token':this.csrf}:{})},body:new URLSearchParams({action,...data})});
  const cookie=r.headers.get('set-cookie');if(cookie)this.cookie=cookie.split(';')[0];
  const body=await r.json();assert.equal(r.status,expected,`${action}: ${JSON.stringify(body)}`);if(body.csrf)this.csrf=body.csrf;checks++;return body;
 }
 async login(email){await this.call('session');const r=await this.call('login',{email,password:'TestingPass123!'});assert.ok(!('password' in r));return this;}
}
(async()=>{
 assert.ok((process.env.BUS_DB_NAME||'').endsWith('_test'),'Set BUS_DB_NAME to the dedicated test database');
 execFileSync(process.env.PHP_BINARY||'php',['tests/fixture.php'],{stdio:'inherit'});
 const anon=new Client();await anon.call('session');await anon.call('getUsers',{},401);await anon.call('register',{email:'bad',password:'short'},422);await anon.call('register',{email:'new@test.example',username:'New',password:'SecurePassword123',location:'Kitwe'},200,false).then(()=>{throw Error('CSRF bypass');},e=>{assert.match(e.message,/403/);});
 const admin=await new Client().login('admin@test.example');const customer=await new Client().login('customer@test.example');const other=await new Client().login('other@test.example');const manager=await new Client().login('manager@test.example');const manager2=await new Client().login('manager2@test.example');
 await customer.call('getUsers',{},403);await customer.call('saveSite',{site_id:0},403);await customer.call('updateScheduleSeats',{trip_id:1,seats:'[]'},403);await customer.call('register',{email:'new@test.example',username:'New',password:'SecurePassword123',location:'Kitwe'});await customer.call('register',{email:'new@test.example',username:'New',password:'SecurePassword123',location:'Kitwe'},409);
 const date=new Date(Date.now()+3*86400000).toISOString().slice(0,10);const expiry=new Date(Date.now()+365*86400000).toISOString().slice(0,10);
 await admin.call('saveDriver',{driver_id:0,site_id:1,name:'Driver',phone:'123',licence:'TEST-L1',licence_expiry:expiry,status:'active'});
 const driver=(await admin.call('getDrivers')).data[0];
 await admin.call('saveBus',{bus_id:0,bus:'Coach',number_plate:'TEST-001',site_id:1,seats:10,status:'active',odometer:100,service_due:expiry});
 const bus=(await admin.call('getBus')).data[0];
 await manager2.call('saveBus',{bus_id:bus.bus_id,bus:'Stolen',number_plate:'TEST-001',site_id:2,seats:10,status:'active',odometer:100,service_due:expiry},403);
 const tripData={trip_id:0,bus_id:bus.bus_id,driver_id:driver.driver_id,home:'Lusaka',destination:'Kitwe',date,departure_time:'09:00',arrival_date:date,arrival_time:'16:00',fare:'250.50'};
 await admin.call('saveSchedule',tripData);
 const trip=(await admin.call('getSchedule')).data[0];
 await admin.call('saveSchedule',{...tripData,departure_time:'10:00'},409);
 await admin.call('saveSchedule',{...tripData,arrival_time:'08:00'},422);
 await admin.call('saveSchedule',{...tripData,departure_time:'25:00'},422);
 await admin.call('saveSchedule',{...tripData,date:'2026-02-30'},422);
 await admin.call('setTripStatus',{trip_id:trip.trip_id,status:'departed'},409);
 await admin.call('setTripStatus',{trip_id:trip.trip_id,status:'completed'},409);
 await customer.call('subscribeNewsletter',{email:'other@test.example'},422);
 await customer.call('subscribeNewsletter',{email:'customer@test.example'});
 await customer.call('unsubscribeNewsletter');
 await manager.call('saveSite',{site_id:0,name:'Unauthorized',city:'Kitwe',status:'active'},403);
 await admin.call('saveSite',{site_id:1,name:'Lusaka HQ',city:'Lusaka',address:'Station',phone:'123',status:'inactive'});
 await customer.call('addReservation',{trip_id:trip.trip_id,seat:1},409);
 await admin.call('saveSite',{site_id:1,name:'Lusaka HQ',city:'Lusaka',address:'Station',phone:'123',status:'active'});
 assert.equal((await manager2.call('getSchedule')).data.length,0);
 await manager2.call('addReservation',{trip_id:trip.trip_id,seat:1,user_id:2},403);
 const r=await customer.call('addReservation',{trip_id:trip.trip_id,seat:1,user_id:4});
 const own=(await customer.call('getReservations')).data;assert.equal(own[0].user_id,2);assert.equal(own[0].amount,'250.50');assert.equal((await other.call('getReservations')).data.length,0);
 await other.call('deleteReservation',{reservation_id:r.reservation_id},403);
 await other.call('addReservation',{trip_id:trip.trip_id,seat:1},409);
 await customer.call('addReservation',{trip_id:trip.trip_id,seat:11},422);
 await admin.call('saveBus',{bus_id:bus.bus_id,bus:'Coach',number_plate:'TEST-001',site_id:1,seats:5,status:'maintenance',odometer:100,service_due:expiry},409);
 await admin.call('saveSchedule',{...tripData,trip_id:trip.trip_id,departure_time:'08:00'},409);
 await admin.call('cancelTrip',{trip_id:trip.trip_id},409);
 await manager.call('editReservation',{reservation_id:r.reservation_id,trip_id:trip.trip_id,seat:2,user_id:2});
 let seats=JSON.parse((await customer.call('getSchedule')).data[0].seats);assert.ok(seats.includes(1));assert.ok(!seats.includes(2));
 await customer.call('deleteReservation',{reservation_id:r.reservation_id});seats=JSON.parse((await customer.call('getSchedule')).data[0].seats);assert.equal(seats.length,10);
 await customer.call('deleteReservation',{reservation_id:r.reservation_id},404);
 const results=await Promise.all([customer.call('addReservation',{trip_id:trip.trip_id,seat:3}).then(()=>200,e=>{assert.match(e.message,/409/);return 409;}),other.call('addReservation',{trip_id:trip.trip_id,seat:3}).then(()=>200,e=>{assert.match(e.message,/409/);return 409;})]);assert.deepEqual(results.sort(),[200,409]);
 const workers=await Promise.all([2,4].map(id=>run(process.env.PHP_BINARY||'php',['tests/reservation-worker.php',String(id),String(trip.trip_id),'5'])));assert.deepEqual(workers.map(r=>r.stdout).sort(),['booked','conflict']);checks++;
 await manager2.call('getReservations').then(r=>assert.equal(r.data.length,0));
 const reservations=(await admin.call('getReservations')).data;for(const reservation of reservations)await admin.call('deleteReservation',{reservation_id:reservation.reservation_id});
 await admin.call('saveMaintenance',{bus_id:bus.bus_id,service_date:new Date().toISOString().slice(0,10),odometer:150,cost:'75.25',service_due:expiry,notes:'Brake inspection'});
 assert.equal((await manager.call('getMaintenance')).data.length,1);assert.equal((await manager2.call('getMaintenance')).data.length,0);
 await admin.call('cancelTrip',{trip_id:trip.trip_id});await customer.call('addReservation',{trip_id:trip.trip_id,seat:4},409);
 await admin.call('saveSchedule',{...tripData,trip_id:0});
 const dispatchTrip=(await admin.call('getSchedule')).data.find(t=>t.status==='scheduled');
 execFileSync(process.env.PHP_BINARY||'php',['tests/fixture.php','ageTrip',String(dispatchTrip.trip_id)]);
 await admin.call('setTripStatus',{trip_id:dispatchTrip.trip_id,status:'departed'});
 await customer.call('addReservation',{trip_id:dispatchTrip.trip_id,seat:1},409);
 await admin.call('setTripStatus',{trip_id:dispatchTrip.trip_id,status:'completed'});
 await admin.call('setTripStatus',{trip_id:dispatchTrip.trip_id,status:'departed'},409);
 await admin.call('saveStaff',{id:1,position:'customer',site_id:1},422);
 await admin.call('saveStaff',{id:3,position:'manager',site_id:2});
 assert.equal((await manager.call('getSchedule')).data.length,0);
 await admin.call('saveStaff',{id:3,position:'manager',site_id:1});
 await manager.call('getAudit',{},403);assert.ok((await admin.call('getAudit')).data.length>0);
 await customer.call('changePassword',{current_password:'wrong',new_password:'UpdatedPass123'},422);await customer.call('changePassword',{current_password:'TestingPass123!',new_password:'UpdatedPass123'});
 await customer.call('logout');await customer.call('getReservations',{},401);
 const reset=await fetch(base+'/Backend/BusReserve/password_utility.php');assert.equal(reset.status,404);
 for(const path of ['/.git/config','/.test-runtime/php-error.log','/Backend/bus_ticket.sql','/tests/fixture.php','/README.md']){const response=await fetch(base+path);assert.equal(response.status,404,path);checks++;}
 console.log(`PASS: ${checks} API checks, booking conflicts, branch isolation, seat restoration, maintenance, CSRF, password change and logout.`);
})().catch(e=>{console.error(e);process.exitCode=1;});
