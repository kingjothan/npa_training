document.addEventListener("DOMContentLoaded", function () {
    const calendarEl = document.getElementById("calendar");
    const modal = document.getElementById("modal");
    const closeBtn = document.querySelector(".close");
    const form = document.getElementById("event-form");
    const eventList = document.getElementById("event-list").getElementsByTagName("tbody")[0];

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        events: "fetch_events.php",
        eventClick: (info) => openModal(info.event),
    });

    calendar.render();

    document.getElementById("open-add-event").onclick = () => openModal();

    function openModal(event = null) {
        modal.style.display = "flex";
        form.reset();
        if (event) {
            document.getElementById("event-id").value = event.id;
            document.getElementById("title").value = event.title;
            document.getElementById("description").value = event.extendedProps.description;
            document.getElementById("date").value = event.startStr;
        }
    }

    closeBtn.onclick = () => modal.style.display = "none";

    form.onsubmit = (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        
        fetch("add_event.php", { method: "POST", body: formData })
            .then((res) => res.json())
            .then((data) => {
                alert(data.message);
                if (data.status === "success") {
                    modal.style.display = "none";
                    calendar.refetchEvents();
                    loadEvents();
                }
            });
    };

    function loadEvents() {
        fetch("fetch_events.php")
            .then((res) => res.json())
            .then((data) => {
                eventList.innerHTML = "";
                data.forEach(event => {
                    eventList.innerHTML += `
                        <tr>
                            <td>${event.title}</td>
                            <td>${event.description}</td>
                            <td>${event.start}</td>
                            <td>
                                <button class="edit-btn" onclick="editEvent(${JSON.stringify(event)})">Edit</button>
                                <button class="delete-btn" onclick="deleteEvent(${event.id})">Delete</button>
                            </td>
                        </tr>`;
                });
            });
    }

    loadEvents();

    window.deleteEvent = (id) => {
        if (confirm("Are you sure you want to delete this event?")) {
            fetch("delete_event.php", {
                method: "POST",
                body: JSON.stringify({ id }),
                headers: { "Content-Type": "application/json" },
            }).then(() => {
                calendar.refetchEvents();
                loadEvents();
            });
        }
    };

    window.editEvent = (event) => {
        openModal(event);
    };
});
