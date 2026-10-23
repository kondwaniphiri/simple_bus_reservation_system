/* Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
/* Customer pages remain plain jQuery. The server owns fares, identities and seats. */
var scheduleList = [], reserveList = [], siteList = [];
var selectedTrip = null, selectedSeat = null, bookingTab = 'upcoming';

function currency(value) { return 'ZMW ' + Number(value).toLocaleString('en-ZM', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
function tripMoment(date, time) { return new Date(date + 'T' + time + '+02:00'); }
function readableDate(date) { return new Date(date + 'T12:00:00+02:00').toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric', timeZone:'Africa/Lusaka'}); }
function todayZambia() { return new Intl.DateTimeFormat('en-CA', {timeZone:'Africa/Lusaka', year:'numeric', month:'2-digit', day:'2-digit'}).format(new Date()); }
function isUpcoming(r) { return r.status === 'scheduled' && tripMoment(r.date, r.departure_time) > new Date(); }
function duration(t) {
    var minutes = Math.round((tripMoment(t.arrival_date, t.arrival_time) - tripMoment(t.date, t.departure_time)) / 60000);
    return Math.floor(minutes / 60) + 'h' + (minutes % 60 ? ' ' + minutes % 60 + 'm' : '');
}
function openDialog(id) { var d = document.getElementById(id); if (d && !d.open) d.showModal(); }
function closeDialog(id) { var d = document.getElementById(id); if (d && d.open) d.close(); }
function fillSelect(id, values, firstLabel) {
    var select = $('#' + id), current = select.val();
    select.empty().append($('<option>').val('').text(firstLabel));
    values.forEach(function(value) { select.append($('<option>').val(value).text(value)); });
    if (current && values.includes(current)) select.val(current);
}
async function loadData() {
    var result = await Promise.all([BusApp.request('getSchedule'), BusApp.request('getReservations'), BusApp.request('getSites')]);
    scheduleList = result[0].data.filter(function(t) { return t.status === 'scheduled' && tripMoment(t.date, t.departure_time) > new Date(); });
    reserveList = result[1].data;
    siteList = result[2].data.filter(function(site) { return site.status === 'active'; });
    var origins = Array.from(new Set(scheduleList.map(function(t) { return t.home; }))).sort();
    var destinations = Array.from(new Set(scheduleList.map(function(t) { return t.destination; }))).sort();
    fillSelect('filterOrigin', origins, 'All locations'); fillSelect('filterDestination', destinations, 'All locations');
    fillSelect('quick-origin', origins, 'Any origin'); fillSelect('quick-destination', destinations, 'Any destination');
    var branch = $('#filterSite'), current = branch.val();
    branch.empty().append($('<option>').val('').text('All branches'));
    siteList.forEach(function(site) { branch.append($('<option>').val(site.site_id).text(site.name)); });
    if (current) branch.val(current);
    $('#booking-count').text(reserveList.filter(isUpcoming).length);
    renderRoutes(); renderBranches(); generateTable();
}
function renderRoutes() {
    var routes = {};
    scheduleList.forEach(function(t) {
        var key = t.home + '|' + t.destination;
        if (!routes[key]) routes[key] = {trip:t, fare:Number(t.fare), count:0};
        routes[key].fare = Math.min(routes[key].fare, Number(t.fare)); routes[key].count++;
    });
    renderRouteSummaries(Object.values(routes).map(function(route){return {home:route.trip.home,destination:route.trip.destination,starting_fare:route.fare,next_departure:route.trip.date+' '+route.trip.departure_time};}));
}
function renderRouteSummaries(routes) {
    var target=$('#popular-routes').empty();
    routes.slice(0,3).forEach(function(route) {
        var parts=route.next_departure.split(' ');
        var params=new URLSearchParams({from:route.home,to:route.destination});
        var href=BusApp.user?'reserve.html?'+params:'account.html?next=reserve.html&'+params;
        var card = $('<article class="route-card">');
        card.append($('<h3>').text(route.home+' → '+route.destination),$('<p>').text('Next departure: '+readableDate(parts[0])+' at '+parts[1].slice(0,5)+' CAT'));
        card.append($('<footer>').append($('<div>').append($('<span>').text('From '),$('<strong>').text(currency(route.starting_fare))),$('<a class="quiet-link">').attr('href',href).text(BusApp.user?'View trips':'Sign in to book')));
        target.append(card);
    });
    if (!routes.length) target.append($('<div class="empty-state">').text('No future departures are listed yet. Please check with your branch.'));
}
function renderBranches() {
    var contactBranch=$('#contact-site'),chosen=contactBranch.val();
    contactBranch.empty().append($('<option>').val('').text('General enquiry'));
    siteList.forEach(function(site){contactBranch.append($('<option>').val(site.site_id).text(site.name));});
    if(chosen)contactBranch.val(chosen);
    var directory = $('#branch-directory').empty();
    siteList.forEach(function(site) {
        var card = $('<article class="branch-card">');
        card.append($('<span class="city-label">').text(site.city), $('<h3>').text(site.name));
        card.append($('<p>').text(site.address || 'Confirm the boarding counter with your branch.'));
        if (/^\+?[\d\s()-]{7,25}$/.test(site.phone)) card.append($('<a>').attr('href','tel:' + site.phone.replace(/[^\d+]/g,'')).text(site.phone));
        else card.append($('<p>').text(site.phone || 'Contact details are available at the branch.'));
        directory.append(card);
    });
    if (!siteList.length) directory.append($('<p class="empty-state">').text('Branch details are currently unavailable.'));
}
function generateTable() {
    var target = $('#trip-results').empty(); if (!target.length) return;
    var query = ($('#searchBar').val() || '').toLowerCase();
    var rows = scheduleList.filter(function(t) {
        return (!$('#filterDate').val() || t.date === $('#filterDate').val()) &&
            (!$('#filterOrigin').val() || t.home === $('#filterOrigin').val()) &&
            (!$('#filterDestination').val() || t.destination === $('#filterDestination').val()) &&
            (!$('#filterSite').val() || t.site_id == $('#filterSite').val()) &&
            [t.home,t.destination,t.bus,t.number_plate,t.branch_name].join(' ').toLowerCase().includes(query);
    });
    var sort = $('#sortTrips').val();
    rows.sort(function(a,b) {
        if (sort === 'price') return Number(a.fare) - Number(b.fare) || tripMoment(a.date,a.departure_time) - tripMoment(b.date,b.departure_time);
        if (sort === 'seats') return JSON.parse(b.seats).length - JSON.parse(a.seats).length;
        return tripMoment(a.date,a.departure_time) - tripMoment(b.date,b.departure_time);
    });
    $('#result-count').text(rows.length);
    $('#results-status').text(rows.length ? 'Select a departure to view the seat map and boarding details.' : 'Try a different route or date.');
    rows.slice(0, displayedLimit).forEach(function(t) {
        var available = JSON.parse(t.seats).length;
        var card = $('<article class="trip-card">');
        card.append($('<div class="trip-card-top">').append($('<strong>').text(readableDate(t.date)), $('<span>').text(t.bus + ' · ' + t.number_plate)));
        var main = $('<div class="trip-card-main">');
        main.append($('<div class="trip-endpoint">').append($('<strong>').text(t.departure_time.slice(0,5)), $('<b>').text(t.home), $('<small>').text(readableDate(t.date))));
        main.append($('<div class="trip-endpoint arrival">').append($('<strong>').text(t.arrival_time.slice(0,5)), $('<b>').text(t.destination), $('<small>').text(duration(t) + ' journey' + (t.arrival_date !== t.date ? ' · arrives ' + readableDate(t.arrival_date) : ' · same day'))));
        main.append($('<div class="trip-price">').append($('<strong>').text(currency(t.fare)), $('<small>').text('per seat'), $('<button class="button primary" type="button">').text(available ? 'Choose seat' : 'Sold out').prop('disabled',available === 0).on('click',function() { pickTrip(t); })));
        card.append(main, $('<div class="trip-card-bottom">').append($('<span>').text('Boarding: ' + (t.branch_name || t.home)), $('<span class="seats-left">').toggleClass('limited', available <= 5).text(available + ' seats available')));
        target.append(card);
    });
    if (!rows.length) target.append($('<div class="empty-state">').append($('<h3>').text('No departures found.'), $('<p>').text('Try another route or date.'), $('<button class="button outline" type="button">').text('Reset filters').on('click',clearFilters)));
    if (rows.length > displayedLimit) target.append($('<button class="button outline" type="button">').text('Show more departures (' + (rows.length-displayedLimit) + ')').on('click',function() { displayedLimit += 20; generateTable(); }));
}
var displayedLimit = 20;
function clearFilters() { $('#filterDate,#filterOrigin,#filterDestination,#filterSite,#searchBar').val(''); displayedLimit=20; generateTable(); }
function pickTrip(t) {
    selectedTrip = t; selectedSeat = null;
    $('#selectedSeatText').text('Choose a seat'); $('#reserveButton').prop('disabled',true); $('#seat-message').text('');
    $('#seat-route').text(t.home + ' → ' + t.destination);
    $('#seat-departure').text(readableDate(t.date) + ' · ' + t.departure_time.slice(0,5));
    $('#seat-arrival').text(readableDate(t.arrival_date) + ' · ' + t.arrival_time.slice(0,5));
    $('#seat-vehicle').text(t.bus + ' · ' + t.number_plate);
    $('#seat-branch').text((t.branch_name || t.home) + (t.boarding_address ? ' · ' + t.boarding_address : ''));
    $('#seat-passenger').text(BusApp.user.username); $('#seat-fare').text(currency(t.fare));
    var available = JSON.parse(t.seats).map(Number), capacity = Number(t.capacity) || Math.max.apply(null,available);
    var grid = $('#selectedSeat').empty();
    for (var seat = 1; seat <= capacity; seat++) {
        var button = $('<button class="seat" type="button">').text(seat).attr({'data-seat':seat,'aria-label':'Seat ' + seat + (available.includes(seat) ? ', available' : ', unavailable'),'aria-pressed':'false'}).prop('disabled',!available.includes(seat));
        button.css('grid-column', ((seat-1)%4) + (((seat-1)%4)>=2 ? 2 : 1));
        button.on('click',function() {
            selectedSeat = Number($(this).attr('data-seat'));
            $('.seat').removeClass('chosen').attr('aria-pressed','false'); $(this).addClass('chosen').attr('aria-pressed','true');
            $('#selectedSeatText').text('Seat ' + selectedSeat); $('#reserveButton').prop('disabled',false);
        });
        grid.append(button);
    }
    openDialog('seat-dialog');
}
function generateNotifications() {
    var pane = $('#notificationPane').empty();
    var tabs = $('<div class="booking-tabs">');
    ['upcoming','history'].forEach(function(tab) { tabs.append($('<button type="button">').toggleClass('active',bookingTab===tab).text(tab==='upcoming'?'Upcoming trips':'Travel history').on('click',function() { bookingTab=tab; generateNotifications(); })); });
    pane.append(tabs);
    var rows = reserveList.filter(function(r) { return bookingTab==='upcoming' ? isUpcoming(r) : !isUpcoming(r); });
    if (!rows.length) pane.append($('<div class="empty-state">').append($('<h3>').text(bookingTab==='upcoming'?'No upcoming bookings.':'No past journeys yet.'), $('<p>').text('Your confirmed reservations will appear here.'), $('<a class="button primary" href="reserve.html">').text('Find a trip')));
    rows.forEach(function(r) {
        var card = $('<article class="booking-card">');
        card.append($('<div class="booking-card-header">').append($('<strong>').text('RESERVATION #' + r.reservation_id), $('<span class="status-badge">').text(isUpcoming(r)?'Upcoming':r.status==='scheduled'?'Departure passed':r.status)));
        card.append($('<h3>').text(r.home+' → '+r.destination), $('<p>').text(readableDate(r.date) + ' · ' + r.departure_time.slice(0,5) + ' CAT · Seat ' + r.seat));
        card.append($('<p>').text((r.branch_name || r.home) + (r.boarding_address ? ' · '+r.boarding_address : '')), $('<p>').text(r.username + ' · Fare ' + currency(r.amount) + ' · Payment verified at branch'));
        var actions = $('<div class="booking-actions">').append($('<button class="button primary" type="button">').text('Print ticket').on('click',function() { printTicket(r); }));
        if (isUpcoming(r)) actions.append($('<button class="button outline" type="button">').text('Cancel reservation').on('click',async function() {
            if (!confirm('Cancel reservation #' + r.reservation_id + '? The seat will be released. Discuss any payments with your branch.')) return;
            this.disabled=true;
            try { var result=await BusApp.request('deleteReservation',{reservation_id:r.reservation_id}); await loadData(); generateNotifications(); showToast(result.message); }
            catch(e) { showToast(e.message); } finally { this.disabled=false; }
        }));
        card.append(actions); pane.append(card);
    });
}
$(async function() {
    var today=todayZambia(); $('#filterDate,#quick-date').attr('min',today);
    $('[data-close]').on('click',function() { closeDialog($(this).attr('data-close')); });
    $('#clear-filters').on('click',clearFilters);
    $('#searchBar').on('input',function() { displayedLimit=20; generateTable(); });
    $('#filterDate,#filterOrigin,#filterDestination,#filterSite,#sortTrips').on('change',function() { displayedLimit=20; generateTable(); });
    $('#quick-search').on('submit',function(e) {
        e.preventDefault(); var params=new URLSearchParams();
        if($('#quick-origin').val())params.set('from',$('#quick-origin').val());
        if($('#quick-destination').val())params.set('to',$('#quick-destination').val());
        if($('#quick-date').val())params.set('date',$('#quick-date').val());
        location.href='reserve.html?'+params.toString();
    });
    $('#notifications').on('click',function() { generateNotifications(); openDialog('bookings-dialog'); });
    $('#logout').on('click',function(e) { e.preventDefault(); BusApp.logout(); });
    $('#contact-form').on('submit',async function(e) {
        e.preventDefault();var button=$('#contact-submit').prop('disabled',true);$('#contact-status').text('Sending your enquiry...');
        try {
            var result=await BusApp.request('contact',Object.fromEntries(new FormData(this)));
            this.reset();$('#contact-status').text(result.message);
        }catch(error){$('#contact-status').text(error.message);}finally{button.prop('disabled',false);}
    });
    $('#password-form').on('submit',async function(e) {
        e.preventDefault(); var button=$(this).find('[type=submit],button:not([type])').prop('disabled',true);
        try { var r=await BusApp.request('changePassword',Object.fromEntries(new FormData(this))); this.reset();closeDialog('password-dialog');showToast(r.message); }
        catch(err) { showToast(err.message); } finally { button.prop('disabled',false); }
    });
    $('#reserveButton').on('click',async function() {
        if (!selectedTrip || !selectedSeat) return;
        this.disabled=true;$('#seat-message').text('Confirming your seat…');
        try {
            var r=await BusApp.request('addReservation',{trip_id:selectedTrip.trip_id,seat:selectedSeat,user_id:BusApp.user.id});
            closeDialog('seat-dialog'); selectedTrip=null;selectedSeat=null;
            await loadData(); bookingTab='upcoming'; generateNotifications(); openDialog('bookings-dialog');
            showToast('Reservation #'+r.reservation_id+' confirmed. Please verify payment at the branch.');
        } catch(e) {
            $('#seat-message').text(e.message);
            try { await loadData(); var fresh=selectedTrip && scheduleList.find(function(t) { return t.trip_id==selectedTrip.trip_id; }); if(fresh){pickTrip(fresh);$('#seat-message').text(e.message);}else{closeDialog('seat-dialog');showToast(e.message);} } catch(refreshError) { showToast(refreshError.message); }
        }
    });
    try {
        var requiresAccount=$('#trip-results').length>0;
        var user=await BusApp.session(requiresAccount);
        $('#contact-submit').prop('disabled',false);
        $('[data-member]').prop('hidden',!user);
        $('[data-guest]').prop('hidden',!!user);
        if(!user) {
            var info=await BusApp.request('getPublicInfo');
            siteList=info.sites;
            renderBranches();
            renderRouteSummaries(info.routes);
            return;
        }
        $('#username').text(user.username);
        $('#contact-name').val(user.username);$('#contact-email').val(user.email);
        if (['admin','manager'].includes(user.position)) $('#links').append($('<li>').append($('<a href="admin.dashboard.html">').text('Management')));
        $('#links').append($('<li>').append($('<button type="button">').text('Password').on('click',function() { openDialog('password-dialog'); })));
        await loadData();
        var params=new URLSearchParams(location.search);
        [['from','filterOrigin'],['to','filterDestination'],['date','filterDate']].forEach(function(pair) { if(params.has(pair[0]))$('#'+pair[1]).val(params.get(pair[0])); });
        generateTable();
    } catch(e) {
        showToast(e.message);$('#results-status').text('Departures could not be loaded. Please refresh to try again.');
        $('#trip-results,#popular-routes,#branch-directory').empty().append($('<p class="empty-state">').text('We could not load this information. Please refresh the page or contact your branch.'));
    }
});
