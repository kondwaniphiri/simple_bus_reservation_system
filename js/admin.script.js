/* Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
var store={},view='buses',visibleRows=[],formAction='',refreshing=false,page=1,pageSize=25;
var definitions={
 buses:{title:'Fleet',id:'bus_id',action:'saveBus',columns:['bus_id','bus','number_plate','site','seats','status','odometer','service_due'],fields:[['bus','Vehicle name'],['number_plate','Number plate'],['site_id','Branch','sites'],['seats','Capacity','number'],['status','Status',['active','maintenance','retired']],['odometer','Odometer (km)','number'],['service_due','Next service','date']]},
 sites:{title:'Branches',id:'site_id',action:'saveSite',columns:['site_id','name','city','address','phone','status'],fields:[['name','Branch name'],['city','City'],['address','Address'],['phone','Phone'],['status','Status',['active','inactive']]]},
 drivers:{title:'Drivers',id:'driver_id',action:'saveDriver',columns:['driver_id','name','site','phone','licence','licence_expiry','status'],fields:[['name','Full name'],['site_id','Branch','sites'],['phone','Phone'],['licence','Licence number'],['licence_expiry','Licence expiry','date'],['status','Status',['active','inactive']]]},
 schedule:{title:'Trips',id:'trip_id',action:'saveSchedule',columns:['trip_id','site','bus','driver','home','destination','date','departure_time','arrival_date','arrival_time','fare','available','status'],fields:[['bus_id','Vehicle','buses'],['driver_id','Driver','drivers'],['home','Origin'],['destination','Destination'],['date','Departure date','date'],['departure_time','Departure time','time'],['arrival_date','Arrival date','date'],['arrival_time','Arrival time','time'],['fare','Fare (ZMW)','decimal']]},
 reservations:{title:'Bookings',id:'reservation_id',action:'addReservation',columns:['reservation_id','site','username','email','trip_id','home','destination','date','seat','amount'],fields:[['user_id','Customer','users'],['trip_id','Trip','schedule'],['seat','Seat','number']]},
 maintenance:{title:'Maintenance',id:'maintenance_id',action:'saveMaintenance',columns:['maintenance_id','site','bus','number_plate','service_date','odometer','cost','notes'],fields:[['bus_id','Vehicle','buses'],['service_date','Service date','date'],['odometer','Odometer (km)','number'],['cost','Cost (ZMW)','decimal'],['service_due','Next service','date'],['notes','Work performed','textarea']]},
 users:{title:'People & access',id:'id',action:'saveStaff',columns:['id','username','email','location','position','site'],fields:[['position','Role',['customer','manager','admin']],['site_id','Assigned branch','sites']]},
 enquiries:{title:'Enquiries',id:'enquiry_id',action:'saveEnquiry',columns:['enquiry_id','site','name','email','phone','subject','message','status','created_at'],fields:[['status','Status',['new','in_progress','closed']]]},
 audit:{title:'Activity',id:'audit_id',columns:['audit_id','username','action','details','created_at'],fields:[]}
};
var headings={bus_id:'Vehicle ID',site_id:'Branch ID',trip_id:'Trip ID',reservation_id:'Booking ID',driver_id:'Driver ID',service_due:'Service due',licence_expiry:'Licence expires',number_plate:'Plate',departure_time:'Departs',arrival_time:'Arrives',arrival_date:'Arrival date',home:'Origin',amount:'Value (ZMW)',fare:'Fare (ZMW)',cost:'Cost (ZMW)',position:'Role'};
function label(key){return headings[key]||key.replace(/_/g,' ').replace(/^./,function(c){return c.toUpperCase();});}
function notice(text){$('#message').text(text);}
async function refresh(){
 if(refreshing)return;refreshing=true;$('#refresh').prop('disabled',true);
 try{
  var keys=['sites','buses','drivers','schedule','reservations','maintenance','users','enquiries'];var actions=['getSites','getBus','getDrivers','getSchedule','getReservations','getMaintenance','getUsers','getEnquiries'];
  if(BusApp.user.position==='admin'){keys.push('audit');actions.push('getAudit');}
  var values=await Promise.all(actions.map(function(action){return BusApp.request(action);}));keys.forEach(function(key,i){store[key]=values[i].data;});
  var select=$('#branch'),value=select.val();select.empty().append($('<option>').val('').text('All accessible branches'));store.sites.forEach(function(s){select.append($('<option>').val(s.site_id).text(s.name));});select.val(value||'');render();
 }catch(e){notice(e.message);}finally{refreshing=false;$('#refresh').prop('disabled',false);}
}
function decorate(row){var r=Object.assign({},row);var site=(store.sites||[]).find(function(s){return s.site_id==r.site_id;});r.site=site?site.name:'';if(view==='schedule'){var bus=store.buses.find(function(b){return b.bus_id==r.bus_id;});var driver=store.drivers.find(function(d){return d.driver_id==r.driver_id;});r.bus=bus?bus.bus:'';r.driver=driver?driver.name:'Unassigned';r.available=JSON.parse(r.seats).length;}return r;}
function render(){
 var def=definitions[view],branch=view==='audit'?'':$('#branch').val(),search=$('#searchBar').val().toLowerCase();
 visibleRows=(store[view]||[]).map(decorate).filter(function(r){return (!branch||(view==='sites'?r.site_id==branch:r.site_id==branch))&&Object.values(r).join(' ').toLowerCase().includes(search);});
 $('#branch').prop('disabled',view==='audit');
 $('#title').text(def.title);$('nav button').removeClass('active').filter('[data-view="'+view+'"]').addClass('active');
 $('#add').toggle(!!def.action && view!=='users' && view!=='enquiries' && !(view==='sites'&&BusApp.user.position!=='admin'));
 var head=$('<tr>');def.columns.forEach(function(k){head.append($('<th>').text(label(k)));});head.append($('<th>').text('Actions'));$('thead').empty().append(head);var body=$('tbody').empty();
 var pages=Math.max(1,Math.ceil(visibleRows.length/pageSize));page=Math.min(page,pages);
 visibleRows.slice((page-1)*pageSize,page*pageSize).forEach(function(r){var row=$('<tr>');def.columns.forEach(function(k){row.append($('<td>').text(r[k]===null?'—':r[k]));});var actions=$('<td>');
  if(def.action && view!=='maintenance' && !(view==='sites'&&BusApp.user.position!=='admin'))actions.append($('<button>').text('Edit').on('click',function(){edit(r);}));
  if(view==='schedule'){
   if(['scheduled','departed'].includes(r.status))actions.append($('<button>').text(r.status==='scheduled'?'Mark departed':'Mark completed').on('click',function(){mutate('setTripStatus',{trip_id:r.trip_id,status:r.status==='scheduled'?'departed':'completed'},'Update this trip status?');}));
   actions.append($('<button>').text('Manifest').on('click',function(){manifest(r);}));
   if(r.status==='scheduled')actions.append($('<button>').text('Cancel trip').on('click',function(){mutate('cancelTrip',{trip_id:r.trip_id},'Cancel this trip? All bookings must be cancelled first.');}));
  }
  if(view==='reservations')actions.append($('<button>').text('Cancel booking').on('click',function(){mutate('deleteReservation',{reservation_id:r.reservation_id},'Cancel this booking and release the seat?');}));
  if(view==='enquiries')row.find('td').eq(6).css({whiteSpace:'normal',minWidth:'260px',maxWidth:'420px'});row.append(actions);body.append(row);
 });
 if(!visibleRows.length)body.append($('<tr>').append($('<td>').attr('colspan',def.columns.length+1).text('No records found.')));
 $('#paging').remove();var paging=$('<div id="paging" class="toolbar">');paging.append($('<button>').text('Previous').prop('disabled',page===1).on('click',function(){page--;render();}),$('<span>').text('Page '+page+' of '+pages+' · '+visibleRows.length+' records'),$('<button>').text('Next').prop('disabled',page===pages).on('click',function(){page++;render();}));$('.panel').append(paging);
 var fleet=(store.buses||[]).filter(function(b){return !branch||b.site_id==branch;});var trips=(store.schedule||[]).filter(function(t){return (!branch||t.site_id==branch)&&t.status==='scheduled'&&new Date(t.date+'T'+t.departure_time+'+02:00')>new Date();});var bookings=(store.reservations||[]).filter(function(r){return !branch||r.site_id==branch;});
 $('#metrics').empty();[['Active vehicles',fleet.filter(function(b){return b.status==='active';}).length],['Upcoming trips',trips.length],['Bookings',bookings.length],['Booking value (ZMW)',bookings.reduce(function(sum,r){return sum+Number(r.amount);},0).toFixed(2)]].forEach(function(m){$('#metrics').append($('<div class="metric">').append($('<span>').text(m[0]),$('<strong>').text(m[1])));});
 $('#list-help').text(view==='buses'?'Vehicles with upcoming trips must be reassigned before changing capacity, branch or status.':view==='maintenance'?'Record completed work and the next service date. Vehicle status is managed separately in Fleet.':view==='reservations'?'Booking value records fares; payment collection is handled at the branch.':'Showing '+visibleRows.length+' records.');
}
function edit(record){
 var def=definitions[view];formAction=record&&view==='reservations'?'editReservation':def.action;var form=$('#record-form')[0];form.reset();$('#fields').empty();$('#form-error').text('');$('#form-title').text((record?'Edit ':'Add ')+def.title);
 $('<input type="hidden">').attr('name',def.id).val(record?record[def.id]:0).appendTo('#fields');
 def.fields.forEach(function(field){var key=field[0],type=field[2]||'text',input;
  if(Array.isArray(type)||['sites','buses','drivers','users','schedule'].includes(type)){
   input=$('<select>').append($('<option>').val('').text('Choose…'));
   if(Array.isArray(type))type.forEach(function(value){input.append($('<option>').val(value).text(value));});
   else (store[type]||[]).filter(function(item){return !$('#branch').val()||type==='users'||item.site_id==$('#branch').val();}).forEach(function(item){var id=definitions[type].id;var text=type==='sites'?item.name:type==='buses'?item.number_plate+' — '+item.bus:type==='drivers'?item.name:type==='users'?item.username+' ('+item.email+')':item.trip_id+' | '+item.home+' ? '+item.destination+' | '+item.date;input.append($('<option>').val(item[id]).text(text));});
  }else if(type==='textarea')input=$('<textarea rows="3" maxlength="1000">');
  else input=$('<input>').attr('type',type==='decimal'?'number':type);
  input.attr({name:key,id:'field-'+key,required:!(key==='service_due'&&view==='buses')&&!(view==='sites'&&['address','phone'].includes(key))});
  if(type==='number'||type==='decimal')input.attr({min:['seats','seat'].includes(key)?1:0,step:type==='decimal'?'0.01':'1'});
  var value=record?record[key]:(['odometer','fare','cost'].includes(key)?0:key==='status'?'active':key==='site_id'?$('#branch').val():'');
  input.val(value==null?'':value);$('<label>').attr('for','field-'+key).text(field[1]).append(input).appendTo('#fields');
 });
 $('#editor')[0].showModal();
}
async function mutate(action,data,prompt){if(!confirm(prompt))return;try{var r=await BusApp.request(action,data);notice(r.message);await refresh();}catch(e){notice(e.message);}}
function csvCell(value){return '"'+String(value==null?'':value).replace(/^[=+\-@\t\r]/,"'$&").replace(/"/g,'""')+'"';}
function exportRows(rows,columns,name){var csv='\ufeff'+[columns.map(label)].concat(rows.map(function(r){return columns.map(function(k){return r[k];});})).map(function(row){return row.map(csvCell).join(',');}).join('\r\n');var url=URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8;'}));var a=document.createElement('a');a.href=url;a.download=name+'.csv';a.click();setTimeout(function(){URL.revokeObjectURL(url);},1000);}
function manifest(trip){exportRows(store.reservations.filter(function(r){return r.trip_id==trip.trip_id;}).sort(function(a,b){return a.seat-b.seat;}),['reservation_id','username','email','seat','home','destination','date','amount'],'trip-'+trip.trip_id+'-manifest');}
$(async function(){
 try{var user=await BusApp.session(true,true);$('#operator').text(user.username+' · '+user.position);$('.admin-only').toggle(user.position==='admin');await refresh();}catch(e){notice(e.message);}
 $('nav button').on('click',function(){view=$(this).data('view');page=1;$('#searchBar').val('');render();});$('#branch').on('change',function(){page=1;render();});$('#searchBar').on('input',function(){page=1;render();});$('#refresh').on('click',refresh);$('#add').on('click',function(){edit(null);});$('#close-editor').on('click',function(){$('#editor')[0].close();});$('#logout').on('click',function(){BusApp.logout();});$('#export').on('click',function(){exportRows(visibleRows,definitions[view].columns,view);});
 $('#record-form').on('submit',async function(e){e.preventDefault();$('#save').prop('disabled',true);$('#form-error').text('');try{var r=await BusApp.request(formAction,Object.fromEntries(new FormData(this)));$('#editor')[0].close();notice(r.message);await refresh();}catch(err){$('#form-error').text(err.message);}finally{$('#save').prop('disabled',false);}});
});
