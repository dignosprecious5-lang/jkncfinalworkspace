document.addEventListener("DOMContentLoaded", () => {
  const search = document.querySelector("#projectSearch");
  search?.addEventListener("input", () => {
    const q = search.value.trim().toLowerCase();
    document
      .querySelectorAll("tbody tr[data-search]")
      .forEach((row) => (row.hidden = !row.dataset.search.includes(q)));
  });
  function draw() {
    document.querySelectorAll("[data-seconds]").forEach((clock) => {
      const started = Number(clock.dataset.started);
      const seconds =
        Number(clock.dataset.seconds) +
        (started ? Math.max(0, Math.floor((Date.now() - started) / 1000)) : 0);
      clock.textContent = [
        Math.floor(seconds / 3600),
        Math.floor((seconds % 3600) / 60),
        seconds % 60,
      ]
        .map((x) => String(x).padStart(2, "0"))
        .join(":");
    });
  }
  draw();
  setInterval(draw, 1000);
  document.querySelectorAll("form").forEach((form) =>
    form.addEventListener("submit", () => {
      if (form.checkValidity())
        setTimeout(
          () =>
            form.querySelectorAll("button").forEach((b) => (b.disabled = true)),
          0,
        );
    }),
  );
});
