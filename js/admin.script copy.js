let table = ``;
let busList = ``;
let scheduleList = ``;
let reserveList = ``;
let usersList = ``;


let addButton = $("#add");
let deleteButton = $("#delete");
let editButton = $("#edit");



$(document).ready(function() {
  $.ajax({
    url: 'http://localhost/BusReserve/transactions.php',
    method: 'POST',
    data: { action: 'getBus' }, 
    dataType: 'json',
    success: function(response) {
        busList = response.data;
        generateTable("busList");

        $("#busListLink").on("click", function(){
            generateTable("busList")
        });
    },
    error: function(xhr, status, error) {
      console.error('AJAX Error: ', error);
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
        });

    },
    error: function(xhr, status, error) {
      console.error('AJAX Error: ', error);
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
            console.log("bit")
            generateTable("reservationsList");
        });
    },
    error: function(xhr, status, error) {
      console.error('AJAX Error: ', error);
    }
  });

  $.ajax({
    url: 'http://localhost/BusReserve/transactions.php',
    method: 'POST',
    data: { action: 'getUsers' }, 
    dataType: 'json',
    success: function(response) {
        usersList = response.data;
    },
    error: function(xhr, status, error) {
      console.error('AJAX Error: ', error);
    }
  });

});



function generateTable(type){


    switch(type){
        case "busList":
            $("#title").text("Bus List");
            table = `
                <thead>
                    <th>Bus Id</th>
                    <th>Bus Name</th>
                    <th>Number Plate</th>
                    <th>Number of Seats</th>
                    <th>Location</th>
                </thead>
                <tbody>         
            `

            busList.forEach(function(busList) {
                table += `
                  <tr>
                    <td>${busList.bus_id}</td>
                    <td>${busList.bus}</td>
                    <td>${busList.number_plate}</td>
                    <td>${busList.seats}</td>
                    <td>${busList.home}</td>
                  </tr>
                `;
              });
            table +=`</tbody>`;

        break;

    case "scheduleList":
        $("#title").text("Schedule List");

        table = `
                <thead>
                    <th>trip_id</th>
                    <th>Date</th>
                    <th>Bus Name</th>
                    <th>Number Plate</th>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Departure Time</th>
                    <th>Arrival Time</th>
                    <th>Seats</th>
                </thead>
                <tbody>  
        `
        scheduleList.forEach(function(scheduleList) {
        let busName, numberPlate;
            for (let i = 0; i < busList.length; i++) {
                if(scheduleList.bus_id == busList[i].bus_id){
                    busName = busList[i].bus;
                    numberPlate = busList[i].number_plate;
                }
            }
            table += `
              <tr>
                <td>${scheduleList.trip_id}</td>
                <td>${scheduleList.date}</td>
                <td>${busName}</td>
                <td>${numberPlate}</td>
                <td>${scheduleList.home}</td>
                <td>${scheduleList.destination}</td>
                <td>${scheduleList.departure_time}</td>
                <td>${scheduleList.arrival_time}</td>
                <td>${scheduleList.seats}</td>
              </tr>
            `;
          });
          table +=`</tbody>`;
    break;

    case "reservationsList":
        $("#title").text("Reservations List");        
        table = `
                <thead>
                    <th>trip_id</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Seat</th>
                </thead>
                <tbody>  
        `
        reserveList.forEach(function(reserveList) {
        let date, origin, destination, customer, email;

            for (let i = 0; i < scheduleList.length; i++) {
                if(reserveList.trip_id == scheduleList[i].trip_id){
                    origin = scheduleList[i].home;
                    destination = scheduleList[i].destination;
                    date = scheduleList[i].date;
                }
            }
            for (let i = 0; i < usersList.length; i++) {
                if(reserveList.bus_id == usersList[i].user_id){
                    customer = usersList[i].username;
                   email = usersList[i].email;
                }
            }
            table += `
              <tr>
                <td>${reserveList.trip_id}</td>
                <td>${date}</td>
                <td>${customer}</td>
                <td>${email}</td>
                <td>${origin}</td>
                <td>${destination}</td>
                <td>${reserveList.seat}</td>
              </tr>
            `;
          });
          table +=`</tbody>`;
    break;
}

$('table').html(table);


function addBus() { 

 }

 function addReservation() { 

 }

 function addSchedule() { 

 }


 function editBus() { 

 }


 function editReservation() { 

 }

 function editSchedule() { 

 }

 function DeleteBus() { 

 }


 function DeleteReservation() { 

 }

 function DeleteSchedule() { 

 }


}
