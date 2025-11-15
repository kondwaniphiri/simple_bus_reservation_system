let table = ``;
let busList = [];
let scheduleList = [];
let reserveList = [];
let usersList = [];

let addButton = $("#add");
let deleteButton = $("#delete");
let editButton = $("#edit");

let currentList = "busList";

$(document).ready(function() {
  loadData();

  $("#searchBar").on("keyup", function() {
    searchTable($(this).val());
  });

  addButton.on("click", function() {
    if(currentList === "busList") addBus();
    else if(currentList === "scheduleList") addSchedule();
    else if(currentList === "reservationsList") addReservation();
  });

  editButton.on("click", function() {
    if(currentList === "busList") editBus();
    else if(currentList === "scheduleList") editSchedule();
    else if(currentList === "reservationsList") editReservation();
  });

  deleteButton.on("click", function() {
    if(currentList === "busList") DeleteBus();
    else if(currentList === "scheduleList") DeleteSchedule();
    else if(currentList === "reservationsList") DeleteReservation();
  });
});

function loadData() {
  $.ajax({
    url: 'http://localhost/BusReserve/transactions.php',
    method: 'POST',
    data: { action: 'getBus' }, 
    dataType: 'json',
    success: function(response) {
        busList = response.data;
        generateTable("busList");
        $("#busListLink").on("click", function(){
            generateTable("busList");
            $("#add").show();
        });
    }
  });

  $.ajax({
    url: 'http://localhost/BusReserve/transactions.php',
    method: 'POST',
    data: { action: 'getSchedule' }, 
    dataType: 'json',
    success: function(response) {
        scheduleList = response.data;
        $("#scheduleListLink").on("click", function(){
            generateTable("scheduleList");
            $("#add").show();
        });
    }
  });

  $.ajax({
    url: 'http://localhost/BusReserve/transactions.php',
    method: 'POST',
    data: { action: 'getReservations' }, 
    dataType: 'json',
    success: function(response) {
        reserveList = response.data;
        $("#reservationsListLink").on("click", function(){
            generateTable("reservationsList");
            $("#add").hide();
        });
    }
  });

  $.ajax({
    url: 'http://localhost/BusReserve/transactions.php',
    method: 'POST',
    data: { action: 'getUsers' }, 
    dataType: 'json',
    success: function(response) {
        usersList = response.data;
    }
  });
}

function generateTable(type) {
    currentList = type;
    table = `<thead><tr>`;

    switch(type){
      case "busList":
        $("#title").text("Bus List");
        table += `
          <th>Bus Id</th><th>Bus Name</th><th>Number Plate</th><th>Number of Seats</th><th>Location</th>
        </tr></thead><tbody>`;

        busList.forEach(function(bus) {
          table += `
            <tr>
              <td>${bus.bus_id}</td>
              <td>${bus.bus}</td>
              <td>${bus.number_plate}</td>
              <td>${bus.seats}</td>
              <td>${bus.home}</td>
            </tr>`;
        });
        break;

      case "scheduleList":
        $("#title").text("Schedule List");
        table += `
          <th>Trip ID</th><th>Date</th><th>Bus Name</th><th>Number Plate</th><th>Origin</th><th>Destination</th><th>Departure</th><th>Arrival</th><th>Seats</th>
        </tr></thead><tbody>`;

        scheduleList.forEach(function(schedule) {
          let bus = busList.find(b => b.bus_id == schedule.bus_id) || {};
          table += `
            <tr>
              <td>${schedule.trip_id}</td>
              <td>${schedule.date}</td>
              <td>${bus.bus || ""}</td>
              <td>${bus.number_plate || ""}</td>
              <td>${schedule.home}</td>
              <td>${schedule.destination}</td>
              <td>${schedule.departure_time}</td>
              <td>${schedule.arrival_time}</td>
              <td>${schedule.seats}</td>
            </tr>`;
        });
        break;

      case "reservationsList":
        $("#title").text("Reservations List");
        table += `
          <th>Reservation ID</th><th>Trip ID</th><th>Date</th><th>Customer</th><th>Email</th><th>Origin</th><th>Destination</th><th>Seat</th>
        </tr></thead><tbody>`;

        reserveList.forEach(function(reserve) {

          let schedule = scheduleList.find(s => s.trip_id == reserve.trip_id) || {};
          let user = usersList.find(u => u.id == reserve.user_id) || {};
          table += `
            <tr>
              <td>${reserve.reservation_id}</td>
              <td>${reserve.trip_id}</td>
              <td>${schedule.date || ""}</td>
              <td>${user.username || ""}</td>
              <td>${user.email || ""}</td>
              <td>${schedule.home || ""}</td>
              <td>${schedule.destination || ""}</td>
              <td>${reserve.seat}</td>
            </tr>`;
        });
        break;
    }

    table += `</tbody>`;
    $("table").html(table);
}

// ========== SEARCH FUNCTION ==========
function searchTable(query) {
    query = query.toLowerCase();
    $("tbody tr").each(function() {
        let row = $(this).text().toLowerCase();
        if(row.indexOf(query) > -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// ========== CRUD FUNCTIONS ==========

function addBus() {
    let busName = prompt("Enter Bus Name:");
    let numberPlate = prompt("Enter Number Plate:");
    let seats = prompt("Enter Number of Seats:");
    let location = prompt("Enter Location:");

    if(busName && numberPlate && seats && location){
      $.post('http://localhost/BusReserve/transactions.php', {
        action: 'addBus',
        bus: busName,
        numberPlate: numberPlate,
        seats: seats,
        home: location
      }, function(response) {
        alert(response.message);
        loadData();
      }, 'json');
    }
}

function editBus() {
    let busId = prompt("Enter Bus ID to edit:");
    if(!busId) return;

    let bus = busList.find(b => b.bus_id == busId);
    if (!bus) {
        alert("Bus not found!");
        return;
    }

    let newName = prompt("Enter new Bus Name:", bus.bus);
    let newPlate = prompt("Enter new Number Plate:", bus.number_plate);
    let newSeats = prompt("Enter new Number of Seats:", bus.seats);
    let newHome = prompt("Enter new Location:", bus.home);

    $.post('http://localhost/BusReserve/transactions.php', {
        action: 'editBus',
        bus_id: busId,
        bus: newName || bus.bus,
        number_plate: newPlate || bus.number_plate,
        seats: newSeats || bus.seats,
        home: newHome || bus.home
    }, function(response) {
        alert(response.message);
        loadData();
    }, 'json');
}


function DeleteBus() {
    let busId = prompt("Enter Bus ID to delete:");

    if(busId){
      $.post('http://localhost/BusReserve/transactions.php', {
        action: 'deleteBus',
        bus_id: busId
      }, function(response) {
        alert(response.message);
        loadData();
      }, 'json');
    }
}

// --- Reservation Functions ---
function addReservation() {
    let tripId = prompt("Enter Trip ID:");
    let userId = prompt("Enter Customer ID:");
    let seat = prompt("Enter Seat Number:");

    if(tripId && userId && seat){
      $.post('http://localhost/BusReserve/transactions.php', {
        action: 'addReservation',
        trip_id: tripId,
        bus_id: userId,
        seat: seat
      }, function(response) {
        alert(response.message);
        loadData();
      }, 'json');
    }
}

function editReservation() {
    let reserveId = prompt("Enter Reservation ID to edit:");
    if (!reserveId) return;

    let reservation = reserveList.find(r => r.reservation_id == reserveId);
    if (!reservation) {
        alert("Reservation not found!");
        return;
    }

    let newSeat = prompt("Enter new Seat:", reservation.seat);
    let trip_id = prompt("Enter new trip_Id:", reservation.trip_id);

    $.post('http://localhost/BusReserve/transactions.php', {
        action: 'editReservation',
        reservation_id: reserveId,
        trip_id: trip_id || reservation.trip_idc,
        seat: newSeat || reservation.seat
    }, function(response) {
        alert(response.message);
        loadData();
    }, 'json');
}

function DeleteReservation() {
    let reserveId = prompt("Enter Reservation ID to delete:");

    if(reserveId){
      $.post('http://localhost/BusReserve/transactions.php', {
        action: 'deleteReservation',
        reserve_id: reserveId
      }, function(response) {
        alert(response.message);
        loadData();
      }, 'json');
    }
}

// --- Schedule Functions ---
function addSchedule() {
    let busId = prompt("Enter Bus ID:");
    if (!busId) return;

    let bus = busList.find(b => b.bus_id == busId);
    if (!bus) {
        alert("Bus not found!");
        return;
    }

    let date = prompt("Enter Date (YYYY-MM-DD):");
    let home = prompt("Enter Origin:");
    let destination = prompt("Enter Destination:");
    let departure = prompt("Enter Departure Time (HH:MM):");
    let arrival = prompt("Enter Arrival Time (HH:MM):");

    if (date && home && destination && departure && arrival) {
        // Use the seats count from the found bus
        let seatsArray = [];
        for (let i = 1; i <= parseInt(bus.seats); i++) {
            seatsArray.push(i);
        }

        $.post('http://localhost/BusReserve/transactions.php', {
            action: 'addSchedule',
            bus_id: busId,
            date: date,
            home: home,
            destination: destination,
            departure_time: departure,
            arrival_time: arrival,
            seats: JSON.stringify(seatsArray) // send seats as JSON string
        }, function(response) {
            alert(response.message);
            loadData();
        }, 'json');
    } else {
        alert("All fields must be filled.");
    }
}



function editSchedule() {
    let tripId = prompt("Enter Trip ID to edit:");
    if (!tripId) return;

    let schedule = scheduleList.find(s => s.trip_id == tripId);
    if (!schedule) {
        alert("Schedule not found!");
        return;
    }

    let newDate = prompt("Enter new Date (YYYY-MM-DD):", schedule.date);
    let newHome = prompt("Enter new Origin:", schedule.home);
    let newDestination = prompt("Enter new Destination:", schedule.destination);
    let newDeparture = prompt("Enter new Departure Time (HH:MM):", schedule.departure_time);
    let newArrival = prompt("Enter new Arrival Time (HH:MM):", schedule.arrival_time);
    let newSeats = prompt("Enter new Number of Seats:", schedule.seats);

    $.post('http://localhost/BusReserve/transactions.php', {
        action: 'editSchedule',
        trip_id: tripId,
        date: newDate || schedule.date,
        home: newHome || schedule.home,
        destination: newDestination || schedule.destination,
        departure_time: newDeparture || schedule.departure_time,
        arrival_time: newArrival || schedule.arrival_time,
        seats: newSeats || schedule.seats
    }, function(response) {
        alert(response.message);
        loadData();
    }, 'json');
}


function DeleteSchedule() {
    let tripId = prompt("Enter Trip ID to delete:");

    if(tripId){
      $.post('http://localhost/BusReserve/transactions.php', {
        action: 'deleteSchedule',
        trip_id: tripId
      }, function(response) {
        alert(response.message);
        loadData();
      }, 'json');
    }
}
