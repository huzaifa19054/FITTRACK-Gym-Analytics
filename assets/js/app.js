// FitTrack: File overview
// This file controls page interactions.

window.addEventListener("load", () => {
  document.body.classList.add("loaded");
});

// Store the element or value needed by this code.
const navbar = document.getElementById("navbar");
window.addEventListener("scroll", () => {
  navbar.classList.toggle("scrolled", window.scrollY > 30);
});

// Store the element or value needed by this code.
const revealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("show");
        revealObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.12 },
);

document
  .querySelectorAll(".reveal")
  .forEach((el) => revealObserver.observe(el));

// Store the element or value needed by this code.
const menuBtn = document.getElementById("menuBtn");
// Store the element or value needed by this code.
const navLinks = document.querySelector(".nav-links");

menuBtn.addEventListener("click", () => {
// Store the element or value needed by this code.
  const open = navLinks.classList.toggle("mobile-open");
  menuBtn.setAttribute("aria-expanded", open);
});

document.querySelectorAll(".nav-links a").forEach((link) => {
  link.addEventListener("click", () =>
    navLinks.classList.remove("mobile-open"),
  );
});

// FitTrack theme preference
(() => {
  const key = "fittrack-theme";
  document.documentElement.dataset.theme = localStorage.getItem(key) || "dark";
// Store the element or value needed by this code.
  const navBtn = document.querySelector(".nav-btn");
  if (!navBtn) return;
// Store the element or value needed by this code.
  const btn = document.createElement("button");
  btn.type = "button";
  btn.className = "theme-toggle-landing";
  btn.setAttribute("aria-label", "Toggle theme");
// Store the element or value needed by this code.
  const update = () =>
    (btn.textContent =
      document.documentElement.dataset.theme === "light"
        ? "☾ Dark"
        : "☀ Light");
  update();
  navBtn.parentNode.insertBefore(btn, navBtn);
  btn.addEventListener("click", () => {
// Store the element or value needed by this code.
    const next =
      document.documentElement.dataset.theme === "light" ? "dark" : "light";
    document.documentElement.dataset.theme = next;
    localStorage.setItem(key, next);
    update();
  });
})();
