(function () {
  "use strict";

  document.documentElement.classList.remove("no-js");

  var body = document.body;
  var menu = document.querySelector("[data-mobile-menu]");
  var menuToggle = document.querySelector("[data-menu-toggle]");
  var menuClose = document.querySelector("[data-menu-close]");
  var menuBackdrop = document.querySelector("[data-menu-backdrop]");

  function setMenu(open) {
    if (!menu || !menuToggle || !menuBackdrop) return;
    menu.classList.toggle("is-open", open);
    menuBackdrop.classList.toggle("is-open", open);
    menu.setAttribute("aria-hidden", String(!open));
    menuToggle.setAttribute("aria-expanded", String(open));
    body.classList.toggle("is-locked", open);
    if (open && menuClose) menuClose.focus();
  }

  function initMobileModelsMenu() {
    if (!menu || menu.querySelector(".mobile-models")) return;
    var source = document.querySelector(".models-nav__list");
    var mobileNav = menu.querySelector(".mobile-nav");
    if (!source || !mobileNav) return;

    var section = document.createElement("div");
    section.className = "mobile-models";
    section.innerHTML = '<div class="mobile-models__title">Модели</div><div class="mobile-models__list"></div>';
    var list = section.querySelector(".mobile-models__list");

    source.querySelectorAll(":scope > .models-nav__item > .models-nav__link").forEach(function (link) {
      var mobileLink = document.createElement("a");
      mobileLink.className = "mobile-models__link";
      mobileLink.href = link.getAttribute("href") || "#";
      mobileLink.textContent = link.textContent.trim();
      mobileLink.addEventListener("click", function () { setMenu(false); });
      list.appendChild(mobileLink);
    });

    mobileNav.insertAdjacentElement("afterend", section);
  }

  function initDesktopDropdownIntent() {
    document.querySelectorAll(".main-nav__item--dropdown, .models-nav__item--dropdown").forEach(function (item) {
      var timer = null;
      var dropdown = item.querySelector(".nav-dropdown, .models-dropdown");

      function open() {
        if (timer) window.clearTimeout(timer);
        timer = null;
        item.classList.add("is-hover-open");
      }

      function close() {
        if (timer) window.clearTimeout(timer);
        timer = window.setTimeout(function () {
          item.classList.remove("is-hover-open");
          timer = null;
        }, 480);
      }

      item.addEventListener("mouseenter", open);
      item.addEventListener("mouseleave", close);
      item.addEventListener("focusin", open);
      item.addEventListener("focusout", function (event) {
        if (!item.contains(event.relatedTarget)) close();
      });

      if (dropdown) {
        dropdown.addEventListener("mouseenter", open);
        dropdown.addEventListener("mouseleave", close);
      }
    });
  }

  if (menuToggle) menuToggle.addEventListener("click", function () { setMenu(true); });
  if (menuClose) menuClose.addEventListener("click", function () { setMenu(false); });
  if (menuBackdrop) menuBackdrop.addEventListener("click", function () { setMenu(false); });
  if (menu) menu.querySelectorAll("a").forEach(function (link) { link.addEventListener("click", function () { setMenu(false); }); });

  initMobileModelsMenu();
  initDesktopDropdownIntent();

  var searchModal = document.querySelector("[data-search-modal]");
  var lastSearchTrigger = null;

  function setSearch(open) {
    if (!searchModal) return;
    searchModal.classList.toggle("is-open", open);
    searchModal.setAttribute("aria-hidden", String(!open));
    body.classList.toggle("is-locked", open);

    if (open) {
      var input = searchModal.querySelector("input[type='search']");
      window.setTimeout(function () { if (input) input.focus(); }, 20);
    } else if (lastSearchTrigger) {
      lastSearchTrigger.focus();
    }
  }

  document.querySelectorAll("[data-search-open]").forEach(function (button) {
    button.addEventListener("click", function () { lastSearchTrigger = button; setSearch(true); });
  });
  document.querySelectorAll("[data-search-close]").forEach(function (button) {
    button.addEventListener("click", function () { setSearch(false); });
  });
  document.addEventListener("keydown", function (event) {
    if (event.key !== "Escape") return;
    if (searchModal && searchModal.classList.contains("is-open")) setSearch(false);
    if (menu && menu.classList.contains("is-open")) setMenu(false);
  });

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function getMapBranchesFromCards() {
    var grouped = {};
    document.querySelectorAll(".branch-card").forEach(function (card) {
      var heading = card.querySelector("h3");
      var phone = card.querySelector("a[href^='tel:']");
      var addressNode = card.querySelector("p");
      if (!heading || !addressNode) return;

      var headingCopy = heading.cloneNode(true);
      var smallCopy = headingCopy.querySelector("small");
      if (smallCopy) smallCopy.remove();
      var name = headingCopy.textContent.trim();
      var officeNode = heading.querySelector("small");
      var office = officeNode ? officeNode.textContent.replace(/[()]/g, "").trim() : "";
      var address = addressNode.textContent.trim();
      var key = address.toLowerCase();

      if (!grouped[key]) grouped[key] = { name: name, address: address, geocodeAddress: "Москва, " + address, offices: [] };
      grouped[key].offices.push({ office: office, phone: phone ? phone.textContent.trim() : "" });
    });
    return Object.keys(grouped).map(function (key) { return grouped[key]; });
  }

  function buildBranchBalloon(branch) {
    var offices = branch.offices.map(function (office) {
      var label = office.office ? "<strong>" + escapeHtml(office.office) + "</strong><br>" : "";
      var phone = office.phone ? "<span>" + escapeHtml(office.phone) + "</span>" : "";
      return "<div style=\"margin-top:8px\">" + label + phone + "</div>";
    }).join("");
    return "<div><div style=\"margin-bottom:8px\">" + escapeHtml(branch.address) + "</div>" + offices + "</div>";
  }

  function initYandexMap() {
    var canvas = document.querySelector("[data-yandex-map-canvas]");
    if (!canvas || !window.ymaps) return;
    var branches = getMapBranchesFromCards();
    if (!branches.length) return;

    window.ymaps.ready(function () {
      var map = new window.ymaps.Map(canvas, { center: [55.751244, 37.618423], zoom: 10, controls: ["zoomControl"] }, { suppressMapOpenBlock: true });
      map.behaviors.disable("scrollZoom");
      var remaining = branches.length;

      function finishPoint() {
        remaining -= 1;
        if (remaining > 0) return;
        var bounds = map.geoObjects.getBounds();
        if (bounds) map.setBounds(bounds, { checkZoomRange: true, zoomMargin: [54, 54, 54, 54] });
      }

      branches.forEach(function (branch) {
        window.ymaps.geocode(branch.geocodeAddress, { results: 1 }).then(function (result) {
          var geoObject = result.geoObjects.get(0);
          if (!geoObject) { finishPoint(); return; }
          map.geoObjects.add(new window.ymaps.Placemark(
            geoObject.geometry.getCoordinates(),
            {
              iconCaption: branch.name,
              hintContent: branch.name,
              balloonContentHeader: "Rover Land — " + escapeHtml(branch.name),
              balloonContentBody: buildBranchBalloon(branch)
            },
            { preset: "islands#blueAutoIcon" }
          ));
          finishPoint();
        }, finishPoint);
      });
    });
  }

  function initOffersSlider() {
    var element = document.querySelector("[data-offers-slider]");
    if (!element || typeof window.Swiper !== "function") return;
    new window.Swiper(element, {
      slidesPerView: 1.08,
      spaceBetween: 16,
      breakpointsBase: "container",
      watchOverflow: true,
      observer: true,
      observeParents: true,
      resizeObserver: true,
      a11y: {
        enabled: true,
        prevSlideMessage: "Предыдущее предложение",
        nextSlideMessage: "Следующее предложение",
        firstSlideMessage: "Первое предложение",
        lastSlideMessage: "Последнее предложение"
      },
      pagination: { el: element.querySelector(".swiper-pagination"), clickable: true },
      breakpoints: {
        640: { slidesPerView: 2, spaceBetween: 18 },
        980: { slidesPerView: 3, spaceBetween: 24 }
      }
    });
  }

  function initAboutGallery() {
    var element = document.querySelector("[data-about-gallery]");
    if (!element || typeof window.Swiper !== "function") return;

    var layout = element.closest(".about-parts__layout");
    var wrapper = element.querySelector(".swiper-wrapper");
    var slides = element.querySelectorAll(".swiper-slide");
    var images = element.querySelectorAll(".swiper-slide img");

    document.documentElement.style.overflowX = "hidden";
    if (layout) layout.style.minWidth = "0";

    element.style.width = "100%";
    element.style.maxWidth = "100%";
    element.style.minWidth = "0";
    element.style.overflow = "hidden";

    if (wrapper) {
      wrapper.style.width = "100%";
      wrapper.style.maxWidth = "100%";
      wrapper.style.minWidth = "0";
    }

    slides.forEach(function (slide) {
      slide.style.minWidth = "0";
      slide.style.maxWidth = "100%";
    });

    images.forEach(function (image) {
      image.style.display = "block";
      image.style.width = "100%";
      image.style.maxWidth = "100%";
      image.style.height = "auto";
    });

    var slider = new window.Swiper(element, {
      slidesPerView: 1,
      spaceBetween: 16,
      breakpointsBase: "container",
      watchOverflow: true,
      observer: true,
      observeParents: true,
      resizeObserver: true,
      pagination: { el: element.querySelector(".swiper-pagination"), clickable: true },
      breakpoints: { 768: { slidesPerView: 2, spaceBetween: 20 } }
    });

    images.forEach(function (image) {
      if (image.complete) return;
      image.addEventListener("load", function () {
        slider.updateSize();
        slider.updateSlides();
        slider.updateProgress();
      }, { once: true });
    });
  }

  function normalizeBranchMetaIcons() {
    document.querySelectorAll(".branch-card a, .branch-card p").forEach(function (element) {
      Array.prototype.some.call(element.childNodes, function (node) {
        if (node.nodeType !== Node.TEXT_NODE) return false;
        node.nodeValue = node.nodeValue.replace(/^[☎⌖]\s*/, "");
        return true;
      });
    });
  }



  function initMaintenanceModelCards() {
    var cards = document.querySelectorAll('[data-maintenance-model]');
    if (!cards.length) return;

    var menuLinks = {};
    document.querySelectorAll('.models-nav__link, .models-dropdown a').forEach(function (link) {
      var key = link.textContent.trim().replace(/\s+/g, ' ');
      if (key && !menuLinks[key]) menuLinks[key] = link.getAttribute('href') || '#';
    });

    cards.forEach(function (card) {
      card.querySelectorAll('.maintenance-model-card__links a').forEach(function (link) {
        var label = link.childNodes[0] ? link.childNodes[0].nodeValue.trim() : link.textContent.trim();
        if (menuLinks[label]) link.setAttribute('href', menuLinks[label]);
      });

      var button = card.querySelector('.maintenance-model-card__toggle');
      if (!button) return;
      button.addEventListener('click', function () {
        var willOpen = !card.classList.contains('is-open');
        cards.forEach(function (other) {
          if (other !== card) {
            other.classList.remove('is-open');
            var otherButton = other.querySelector('.maintenance-model-card__toggle');
            if (otherButton) otherButton.setAttribute('aria-expanded', 'false');
          }
        });
        card.classList.toggle('is-open', willOpen);
        button.setAttribute('aria-expanded', String(willOpen));
        button.textContent = willOpen ? 'Скрыть модели' : 'Выбрать модель';
      });
    });
  }

  function initPageModules() {
    normalizeBranchMetaIcons();
    initOffersSlider();
    initAboutGallery();
    initMaintenanceModelCards();
    initYandexMap();
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initPageModules);
  else initPageModules();

  var form = document.querySelector("[data-service-form]");
  if (form) {
    var phoneInput = form.querySelector("input[type='tel']");
    if (phoneInput) {
      phoneInput.addEventListener("input", function () {
        var digits = phoneInput.value.replace(/\D/g, "").replace(/^8/, "7").slice(0, 11);
        if (!digits) return;
        if (digits.charAt(0) !== "7") digits = "7" + digits;
        var value = "+7";
        if (digits.length > 1) value += " (" + digits.slice(1, 4);
        if (digits.length >= 4) value += ") " + digits.slice(4, 7);
        if (digits.length >= 7) value += "-" + digits.slice(7, 9);
        if (digits.length >= 9) value += "-" + digits.slice(9, 11);
        phoneInput.value = value;
      });
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      var valid = true;
      var status = form.querySelector("[data-form-status]");

      form.querySelectorAll(".field").forEach(function (field) {
        var control = field.querySelector("input, select, textarea");
        if (!control) return;
        var fieldValid = control.checkValidity();
        if (control.type === "tel" && control.value.replace(/\D/g, "").length < 11) fieldValid = false;
        field.classList.toggle("is-invalid", !fieldValid);
        if (!fieldValid) valid = false;
      });

      var consent = form.querySelector("input[name='consent']");
      if (consent && !consent.checked) valid = false;

      if (!valid) {
        if (status) {
          status.className = "service-form__status";
          status.textContent = "Проверьте обязательные поля и согласие на обработку данных.";
        }
        return;
      }

      if (status) {
        status.className = "service-form__status is-success";
        status.textContent = "Спасибо! Заявка принята. В WordPress здесь будет обработчик Contact Form 7.";
      }
    });
  }

  /* Shared service modal */
  function initServiceModal() {
    var localAppointment = document.getElementById("appointment");
    var triggers = document.querySelectorAll('a[href="#appointment"], a[href="index.html#appointment"]');
    if (!triggers.length) return;

    var modal = null;
    var lastTrigger = null;

    function ensureModal() {
      if (modal) return modal;

      modal = document.createElement("div");
      modal.className = "service-modal";
      modal.setAttribute("aria-hidden", "true");
      modal.innerHTML =
        '<div class="service-modal__backdrop" data-service-modal-close></div>' +
        '<div class="service-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="service-modal-title">' +
          '<button class="icon-button service-modal__close" type="button" aria-label="Закрыть форму" data-service-modal-close>' +
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5 19 19M19 5 5 19"></path></svg>' +
          '</button>' +
          '<p class="service-modal__eyebrow">Запись в Rover Land</p>' +
          '<h2 id="service-modal-title">Запишитесь на сервис</h2>' +
          '<p class="service-modal__lead">Оставьте контактные данные — мастер-консультант свяжется с вами для уточнения деталей.</p>' +
          '<form class="service-form service-modal__form" action="#" method="post" data-service-modal-form novalidate>' +
            '<div class="service-form__row">' +
              '<label class="field"><span class="field__label">Ваше имя</span><input class="field__control" type="text" name="your-name" placeholder="Иван" autocomplete="name" required><span class="field__error" data-error>Введите имя</span></label>' +
              '<label class="field"><span class="field__label">Телефон</span><input class="field__control" type="tel" name="your-phone" placeholder="+7 (___) ___-__-__" autocomplete="tel" required><span class="field__error" data-error>Введите телефон</span></label>' +
            '</div>' +
            '<label class="field field--full"><span class="field__label">Выберите филиал</span><select class="field__control" name="branch" required>' +
              '<option value="">Выберите филиал</option><option>Запад — Новорижское шоссе</option><option>Юго-Запад — Нагатинская улица</option><option>Северо-Запад — Сервис</option><option>Северо-Запад — Кузовной</option><option>Юг — Нагатинская улица</option>' +
            '</select><span class="field__error" data-error>Выберите филиал</span></label>' +
            '<label class="service-form__consent"><input type="checkbox" name="consent" required><span>Нажимая на кнопку, я принимаю <a href="#">согласие</a> на обработку <a href="#">персональных данных</a></span></label>' +
            '<button class="button button--primary button--wide" type="submit">Отправить заявку</button>' +
            '<p class="service-form__status" role="status" aria-live="polite" data-form-status></p>' +
          '</form>' +
        '</div>';

      document.body.appendChild(modal);

      modal.querySelectorAll("[data-service-modal-close]").forEach(function (button) {
        button.addEventListener("click", function () { setOpen(false); });
      });

      var form = modal.querySelector("[data-service-modal-form]");
      var phone = form.querySelector("input[type='tel']");

      phone.addEventListener("input", function () {
        var digits = phone.value.replace(/\D/g, "").replace(/^8/, "7").slice(0, 11);
        if (!digits) return;
        if (digits.charAt(0) !== "7") digits = "7" + digits;
        var value = "+7";
        if (digits.length > 1) value += " (" + digits.slice(1, 4);
        if (digits.length >= 4) value += ") " + digits.slice(4, 7);
        if (digits.length >= 7) value += "-" + digits.slice(7, 9);
        if (digits.length >= 9) value += "-" + digits.slice(9, 11);
        phone.value = value;
      });

      form.addEventListener("submit", function (event) {
        event.preventDefault();
        var valid = true;
        var status = form.querySelector("[data-form-status]");

        form.querySelectorAll(".field").forEach(function (field) {
          var control = field.querySelector("input, select, textarea");
          if (!control) return;
          var fieldValid = control.checkValidity();
          if (control.type === "tel" && control.value.replace(/\D/g, "").length < 11) fieldValid = false;
          field.classList.toggle("is-invalid", !fieldValid);
          if (!fieldValid) valid = false;
        });

        var consent = form.querySelector("input[name='consent']");
        if (consent && !consent.checked) valid = false;

        if (!valid) {
          status.className = "service-form__status";
          status.textContent = "Проверьте обязательные поля и согласие на обработку данных.";
          return;
        }

        status.className = "service-form__status is-success";
        status.textContent = "Спасибо! Заявка принята. В WordPress здесь будет Contact Form 7.";
      });

      return modal;
    }

    function setOpen(open) {
      ensureModal();
      modal.classList.toggle("is-open", open);
      modal.setAttribute("aria-hidden", String(!open));
      body.classList.toggle("is-locked", open);

      if (open) {
        setMenu(false);
        window.setTimeout(function () {
          var input = modal.querySelector("input[name='your-name']");
          if (input) input.focus();
        }, 20);
      } else if (lastTrigger) {
        lastTrigger.focus();
      }
    }

    triggers.forEach(function (trigger) {
      trigger.addEventListener("click", function (event) {
        var href = trigger.getAttribute("href") || "";
        if (href === "#appointment" && localAppointment) return;
        event.preventDefault();
        lastTrigger = trigger;
        setOpen(true);
      });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && modal && modal.classList.contains("is-open")) setOpen(false);
    });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initServiceModal);
  else initServiceModal();


  /* Back to top */
  function initBackToTop() {
    if (document.querySelector('[data-back-to-top]')) return;
    var button = document.createElement('button');
    button.className = 'back-to-top';
    button.type = 'button';
    button.setAttribute('aria-label', 'Наверх');
    button.setAttribute('data-back-to-top', '');
    button.innerHTML = '<span aria-hidden="true">↑</span>';
    document.body.appendChild(button);

    function syncBackToTop() {
      button.classList.toggle('is-visible', window.scrollY > 500);
    }

    button.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    window.addEventListener('scroll', syncBackToTop, { passive: true });
    syncBackToTop();
  }

  /* Cookie notice. Demo markup; final legal text/settings can be replaced during CMS integration. */
  function initCookieNotice() {
    if (document.querySelector('[data-cookie-notice]')) return;
    var storageKey = 'roverland-cookie-accepted';
    try {
      if (window.localStorage.getItem(storageKey) === '1') return;
    } catch (error) {}

    var notice = document.createElement('div');
    notice.className = 'cookie-notice';
    notice.setAttribute('data-cookie-notice', '');
    notice.setAttribute('role', 'region');
    notice.setAttribute('aria-label', 'Уведомление об использовании cookie');
    notice.innerHTML = '<div class="cookie-notice__text"><strong>Мы используем cookie</strong><span>Они помогают корректной работе сайта и улучшению сервиса. Подробнее — в <a href="privacy.html">политике конфиденциальности</a>.</span></div><button class="button button--primary cookie-notice__accept" type="button" data-cookie-accept>Хорошо</button>';
    document.body.appendChild(notice);

    var accept = notice.querySelector('[data-cookie-accept]');
    if (accept) accept.addEventListener('click', function () {
      try { window.localStorage.setItem(storageKey, '1'); } catch (error) {}
      notice.classList.add('is-hiding');
      window.setTimeout(function () { notice.remove(); }, 220);
    });
  }

  initBackToTop();
  initCookieNotice();

})();
