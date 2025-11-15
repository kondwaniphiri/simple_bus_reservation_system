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
});

function loadData() {
  $.ajax({
    url: "http://localhost/BusReserve/transactions.php",
    method: "POST",
    data: { action: "getBus" },
    dataType: "json",
    success: function (response) {
      busList = response.data;
    },
  });

  $.ajax({
    url: "http://localhost/BusReserve/transactions.php",
    method: "POST",
    data: { action: "getSchedule" },
    dataType: "json",
    success: function (response) {
      scheduleList = response.data;
    },
  });

  $.ajax({
    url: "http://localhost/BusReserve/transactions.php",
    method: "POST",
    data: { action: "getReservations" },
    dataType: "json",
    success: function (response) {
      reserveList = response.data;
    },
  });
}

function generateNotifications() {
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
