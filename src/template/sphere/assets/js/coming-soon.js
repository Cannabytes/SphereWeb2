
function updateTimer() {
    future = Date.parse("Dec 19, 2026 11:30:00");
    now = new Date();
    diff = future - now;

    days = Math.floor(diff / (1000 * 60 * 60 * 24));
    hours = Math.floor(diff / (1000 * 60 * 60));
    mins = Math.floor(diff / (1000 * 60));
    secs = Math.floor(diff / 1000);

    d = days;
    h = hours - days * 24;
    m = mins - hours * 60;
    s = secs - mins * 60;

    document.getElementById("timer")
        .innerHTML =
        '<div class="col-xxl-3 col-xl-6 col-lg-6 col-md-3 col-sm-3 col-6"><div class="p-3 coming-soon-time rounded"><h3 class="fw-semibold text-fixed-white mb-0">' + d + '</h3><p class="mt-1 mb-0 fs-12 op-5">DAYS</p></div></div>' +
        '<div class="col-xxl-3 col-xl-6 col-lg-6 col-md-3 col-sm-3 col-6"><div class="p-3 coming-soon-time rounded"><h3 class="fw-semibold text-fixed-white mb-0">' + h + '</h3><p class="mt-1 mb-0 fs-12 op-5">HOURS</p></div></div>' +
        '<div class="col-xxl-3 col-xl-6 col-lg-6 col-md-3 col-sm-3 col-6"><div class="p-3 coming-soon-time rounded"><h3 class="fw-semibold text-fixed-white mb-0">' + m + '</h3><p class="mt-1 mb-0 fs-12 op-5">MINUTES</p></div></div>' +
        '<div class="col-xxl-3 col-xl-6 col-lg-6 col-md-3 col-sm-3 col-6"><div class="p-3 coming-soon-time rounded"><h3 class="fw-semibold text-fixed-white mb-0">' + s + '</h3><p class="mt-1 mb-0 fs-12 op-5">SECONDS</p></div></div>'
}
setInterval('updateTimer()', 10);