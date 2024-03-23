const a = document.getElementById("liveAlertPlaceholder"),
	s = document.getElementById("liveAlertBtn"),
	r = (t, n) => {
		const e = document.createElement("div");
		e.innerHTML = [`<div class="alert mb-2 alert-${n} alert-dismissible" role="alert">`, `   <div>${t}</div>`, '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button>', "</div>"].join(''), a.append(e)
	};
s && s.addEventListener("click", () => {
	r("Nice, you triggered this alert message!", "success")
}), document.querySelectorAll('.bd-example-indeterminate [type="checkbox"]').forEach(e => {
	e.indeterminate = !0
}), document.querySelectorAll('.bd-content [href="#"]').forEach(e => {
	e.addEventListener("click", e => {
		e.preventDefault()
	})
});