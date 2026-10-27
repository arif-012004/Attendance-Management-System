
document.addEventListener("DOMContentLoaded", function () {

    const locationBtn = document.getElementById("getLocationBtn");
    const locationMessage = document.getElementById("locationMessage");

    const latitudeInput = document.getElementById("latitude");
    const longitudeInput = document.getElementById("longitude");

    const latitudeDisplay = document.getElementById("latitudeDisplay");
    const longitudeDisplay = document.getElementById("longitudeDisplay");

    const lectureForm = document.getElementById("lectureForm");

    const createLectureBtn = document.getElementById("createLectureBtn");

    // Set today's date as the default date
    const dateInput = document.getElementById("lecture_date");

    if (dateInput) {
        const today = new Date();

        const localDate =
            today.getFullYear() + "-" +
            String(today.getMonth() + 1).padStart(2, "0") + "-" +
            String(today.getDate()).padStart(2, "0");

        dateInput.value = localDate;
        dateInput.min = localDate;
    }

    if (!locationBtn || !lectureForm) {
        return;
    }

    let locationCaptured = false;

    locationBtn.addEventListener("click", function () {

        if (!navigator.geolocation) {

            locationMessage.className =
                "location-message location-error";

            locationMessage.textContent =
                "Your browser does not support location services.";

            return;
        }

        locationBtn.disabled = true;

        locationBtn.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Capturing Location...';

        locationMessage.className = "location-message";

        locationMessage.innerHTML =
            '<i class="fa-solid fa-location-dot"></i> Please allow location permission and wait...';

        navigator.geolocation.getCurrentPosition(

            function (position) {

                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;

                const accuracy = position.coords.accuracy;

                latitudeInput.value = latitude;
                longitudeInput.value = longitude;

                latitudeDisplay.value = latitude.toFixed(8);
                longitudeDisplay.value = longitude.toFixed(8);

                locationCaptured = true;

                locationMessage.className =
                    "location-message location-success";

                locationMessage.innerHTML =
                    '<i class="fa-solid fa-circle-check"></i> ' +
                    'Location captured. GPS accuracy: approximately ' +
                    Math.round(accuracy) + ' metres.';

                if (accuracy > 20) {
                    locationMessage.innerHTML +=
                        '<br>GPS accuracy is limited. Try again outdoors or near a window for a better reading.';
                }

                locationBtn.disabled = false;

                locationBtn.innerHTML =
                    '<i class="fa-solid fa-rotate"></i> Refresh Location';

            },

            function (error) {

                locationCaptured = false;

                latitudeInput.value = "";
                longitudeInput.value = "";

                latitudeDisplay.value = "";
                longitudeDisplay.value = "";

                locationMessage.className =
                    "location-message location-error";

                let errorText = "Could not capture location.";

                if (error.code === error.PERMISSION_DENIED) {
                    errorText =
                        "Location permission denied. Allow location access in your browser settings.";
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    errorText =
                        "Location is unavailable. Check your device GPS.";
                } else if (error.code === error.TIMEOUT) {
                    errorText =
                        "Location request timed out. Please try again.";
                }

                locationMessage.innerHTML =
                    '<i class="fa-solid fa-circle-exclamation"></i> ' +
                    errorText;

                locationBtn.disabled = false;

                locationBtn.innerHTML =
                    '<i class="fa-solid fa-crosshairs"></i> Try Again';

            },

            {
                enableHighAccuracy: true,
                timeout: 20000,
                maximumAge: 0
            }

        );

    });


    // Prevent creating lecture without location
    lectureForm.addEventListener("submit", function (event) {

        if (
            !locationCaptured ||
            latitudeInput.value === "" ||
            longitudeInput.value === ""
        ) {

            event.preventDefault();

            locationMessage.className =
                "location-message location-error";

            locationMessage.innerHTML =
                '<i class="fa-solid fa-circle-exclamation"></i> ' +
                'Please capture the classroom location before creating the lecture.';

            locationMessage.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

            return;
        }

        createLectureBtn.disabled = true;

        createLectureBtn.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Creating Lecture...';

    });

});