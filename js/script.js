let username = localStorage.getItem("username");
let user_id = localStorage.getItem("userId");
$("#username").html(username);
$("#username2").html(username);
$("#popUpUsername").text(username);
$("#user_id").text(user_id);
$("#popUpId").text(user_id);
let table = ``;
let busList = [];
let scheduleList = [];
let reserveList = [];
let usersList = [];
let list = [];

let selectedData = {};
$("#popUpReserveDialog").hide();
$("#reserveSeat").hide();
$("#notificationPane").hide();
$(document).ready(function () {
  loadData();

  $("#searchBar").on("keyup", function () {
    searchTable($(this).val());
  });

  // Add filter event listeners
  $("#filterDate, #filterOrigin, #filterDestination").on("change", function () {
    applyFilters();
  });
});

function loadData() {
  $.ajax({
    url: "http://localhost/BusReserve/transactions.php",
    method: "POST",
    data: { action: "getBus" },
    dataType: "json",
    success: function (response) {
      busList = response.data;
      generateTable();
      generateList();
    },
  });

  $.ajax({
    url: "http://localhost/BusReserve/transactions.php",
    method: "POST",
    data: { action: "getSchedule" },
    dataType: "json",
    success: function (response) {
      scheduleList = response.data;
      generateTable();
      generateList();
    },
  });

  $.ajax({
    url: "http://localhost/BusReserve/transactions.php",
    method: "POST",
    data: { action: "getReservations" },
    dataType: "json",
    success: function (response) {
      reserveList = response.data;
      generateTable();
      generateList();
    },
  });
}

function generateNotifications() {
  // console.log(scheduleList);
  // console.log(reserveList)

  let reservedTrips = [];

  let text = ``;

  reserveList.forEach(function (r) {
    if (r.user_id == user_id) {
      reservedTrips.push(r);
    }
  });

  let finalList = [];
  reservedTrips.forEach(function (r) {
    let a = scheduleList.find((s) => s.trip_id == r.trip_id);
    let obj = {};
    obj.reservationId = r.reservation_id;
    obj.username = username;
    obj.seat = r.seat;
    obj.origin = a.home;
    obj.destination = a.destination;
    obj.date = a.date;
    obj.time = a.departure_time;
    finalList.push(obj);
  });

  finalList.forEach(function (x) {
    text += `
        <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
        <span><b>Reservation Id:</b> ${x.reservationId}</span><br>
        You reserved seat number <b>${x.seat}</b>
        on a bus from <b>${x.origin}</b> to <b>${x.destination}</b>
        set for <b>${x.date}</b> at <b>${x.time}</b> hrs
        <br><button onclick='printTicket(${JSON.stringify(x).replace(
          /'/g,
          "&apos;"
        )})' style="margin-top: 10px; padding: 5px 10px; background-color: #711313; color: white; border: none; border-radius: 3px; cursor: pointer;">Print Ticket</button>
        </div>`;
  });

  if (finalList.length === 0) {
    text =
      '<p style="text-align: center; color: #666;">No reservations found.</p>';
  }

  $("#notificationPane").toggle(200);
  $("#notificationPane").html(text);
}

function generateList() {
  scheduleList.forEach(function (schedule) {
    let obj = { trip_id: schedule.trip_id, seat: JSON.parse(schedule.seats) };
    list.push(obj);
  });
}

function generateTable() {
  table = `<thead><tr>`;
  $("#title").text("Schedule List");
  table += `
          <th>Trip ID</th><th>Date</th><th>Bus Name</th><th>Number Plate</th><th>Origin</th><th>Destination</th><th>Departure</th><th>Arrival</th><th>Seats</th>
        </tr></thead><tbody>`;
  scheduleList.forEach(function (schedule) {
    let bus = busList.find((b) => b.bus_id == schedule.bus_id) || {};
    let arr = JSON.parse(schedule.seats).length;
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
              <td>${arr}</td>
            </tr>`;
  });

  table += `</tbody>`;
  $("table").html(table);
  let table1 = document.querySelector("table");
  for (let i = 0; i < table1.rows.length; i++) {
    table1.rows[i].addEventListener("click", function () {
      let cells = this.cells;
      $("#clickDate").text(cells[1].innerText);
      $("#clickBus").text(cells[2].innerText);
      $("#clickSeats").text(cells[8].innerText);
      $("#clickOrigin").text(cells[4].innerText);
      $("#clickDestination").text(cells[5].innerText);
      $("#clickStart").text(`${cells[6].innerText} Hrs`);
      $("#clickEnd").text(`${cells[7].innerText} Hrs`);
      $("#popUpTripId").text(cells[1].innerText);
      $("#popUpDate").text(cells[2].innerText);
      $("#popUpOrigin").text(cells[4].innerText);
      $("#popUpDestination").text(cells[5].innerText);
      $("#reserveSeat").show();
      $("#selectedSeat").html(generateButton(cells[0].innerText));
      $(".seatButtons").on("click", function (e) {
        $("#selectedSeatText").text($(this).text());
        selectedData.selectedSeat = $(this).val();
      });
      $("#reserveSeat").on("click", function () {
        $("#popUpReserveDialog").show(100);
      });
      $("#exit").on("click", function () {
        $("#popUpReserveDialog").hide(100);
      });
      selectedData.trip_id = cells[0].innerText;
      selectedData.user_id = user_id;
    });
  }
}

$("#notifications").on("click", function () {
  generateNotifications();
});

function generateButton(pickedTripId) {
  let pickedRecord = list.find((t) => t.trip_id == pickedTripId);
  let buttons = ``;

  for (let i = 0; i < pickedRecord.seat.length; i++) {
    buttons += `<button class="seatButtons" value=${pickedRecord.seat[i]} id="seat${pickedRecord.seat[i]}">Seat ${pickedRecord.seat[i]}</button>`;
  }
  return buttons;
}

function searchTable(query) {
  query = query.toLowerCase();
  $("tbody tr").each(function () {
    let row = $(this).text().toLowerCase();
    if (row.indexOf(query) > -1) {
      $(this).show();
    } else {
      $(this).hide();
    }
  });
}

// Apply multiple filters
function applyFilters() {
  const dateFilter = $("#filterDate").val();
  const originFilter = $("#filterOrigin").val().toLowerCase();
  const destinationFilter = $("#filterDestination").val().toLowerCase();

  $("tbody tr").each(function () {
    let show = true;
    const cells = $(this).find("td");

    // Check date filter (column 1)
    if (dateFilter && cells.eq(1).text() !== dateFilter) {
      show = false;
    }

    // Check origin filter (column 4)
    if (originFilter && cells.eq(4).text().toLowerCase() !== originFilter) {
      show = false;
    }

    // Check destination filter (column 5)
    if (
      destinationFilter &&
      cells.eq(5).text().toLowerCase() !== destinationFilter
    ) {
      show = false;
    }

    if (show) {
      $(this).show();
    } else {
      $(this).hide();
    }
  });
}

// Clear all filters
function clearFilters() {
  $("#filterDate").val("");
  $("#filterOrigin").val("");
  $("#filterDestination").val("");
  $("#searchBar").val("");
  $("tbody tr").show();
}

$("#reserveButton").on("click", function () {
  if ($("#selectedSeatText").text() == "-") {
    alert("please select a seat");
  } else if (
    confirm("Are your sure you want to make a reservation for seat?")
  ) {
    let oldSeats = list.find((x) => x.trip_id == selectedData.trip_id).seat;
    let newSeats = oldSeats.filter((item) => item != selectedData.selectedSeat);

    console.log(selectedData.trip_id, newSeats);

    $.ajax({
      url: "http://localhost/BusReserve/transactions.php",
      method: "POST",
      data: {
        action: "updateScheduleSeats",
        trip_id: selectedData.trip_id,
        seats: JSON.stringify(newSeats),
      },
      dataType: "json",
      success: function (response) {
        alert(response.message);
      },
    });
    $.ajax({
      url: "http://localhost/BusReserve/transactions.php",
      method: "POST",
      data: {
        action: "addReservation",
        user_id: selectedData.user_id,
        trip_id: selectedData.trip_id,
        seat: selectedData.selectedSeat,
      },
      dataType: "json",
      success: function (response) {
        alert(response.message);
      },
    });
    location.reload();
  }
});
