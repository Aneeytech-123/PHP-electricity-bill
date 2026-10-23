// Live estimate while typing, plus a count-up animation for the final total.
// The official bill is always calculated by PHP on the server.
(function () {
  "use strict";

  function estimate(units) {
    if (units <= 50) return units * 3.5;
    if (units <= 150) return 50 * 3.5 + (units - 50) * 4.0;
    if (units <= 250) return 50 * 3.5 + 100 * 4.0 + (units - 150) * 5.2;
    return 50 * 3.5 + 100 * 4.0 + 100 * 5.2 + (units - 250) * 6.5;
  }

  var fmt = new Intl.NumberFormat("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  var money = function (n) { return "Rs. " + fmt.format(n); };

  var input = document.getElementById("units");
  var live = document.getElementById("live-estimate");
  var liveAmount = document.getElementById("live-amount");

  function updateLive() {
    if (!input || !live) return;
    var v = input.value.trim();
    if (!/^\d+$/.test(v) || Number(v) > 100000) {
      live.hidden = true;
      return;
    }
    live.hidden = false;
    liveAmount.textContent = money(estimate(Number(v)));
  }

  if (input) {
    input.addEventListener("input", updateLive);
    updateLive();
  }

  // Count-up animation for the total
  var total = document.querySelector(".total-amount[data-amount]");
  var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (total && !reduceMotion) {
    var target = parseFloat(total.getAttribute("data-amount")) || 0;
    var start = null;
    var duration = 700;
    var step = function (ts) {
      if (start === null) start = ts;
      var t = Math.min(1, (ts - start) / duration);
      var eased = 1 - Math.pow(1 - t, 3);
      total.textContent = money(target * eased);
      if (t < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }
})();
