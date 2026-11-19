/**
 * wp-admin: the Hub's own field boxes (inc/admin/): tabs, claimed-only fields, list rows, image and gallery pickers
 * (WordPress media library), the offers/classes tick lists and the business picker. jQuery is WordPress's own.
 */
(function ($) {
  "use strict";

  /* Tabs */
  $(document).on("click", "[data-hub-tab]", function () {
    var box = $(this).closest("[data-hub-box]");
    var tab = $(this).data("hub-tab");
    box.find("[data-hub-tab]").removeClass("is-active").attr("aria-selected", "false");
    $(this).addClass("is-active").attr("aria-selected", "true");
    box.find("[data-hub-panel]").attr("hidden", true).filter('[data-hub-panel="' + tab + '"]').removeAttr("hidden");
  });

  /* Claimed-only fields follow the "Claimed by the business" switch */
  function syncClaimed(box) {
    var on = box.find('input[type=checkbox][name="hub[biz_claimed]"]').is(":checked");
    box.attr("data-claimed", on ? "1" : "0");
  }
  $(document).on("change", 'input[name="hub[biz_claimed]"]', function () {
    syncClaimed($(this).closest("[data-hub-box]"));
  });

  /* List fields: add and remove rows */
  $(document).on("click", "[data-hub-list-add]", function () {
    var list = $(this).closest("[data-hub-list]");
    var tbody = list.find("tbody");
    var i = Date.now() % 100000;   // any unused index: rows are re-numbered by PHP on save
    tbody.append(list.find("[data-hub-list-template]").html().replace(/__i__/g, i));
    tbody.find("tr:last input:first").trigger("focus");
  });
  $(document).on("click", ".hub-list__remove", function () {
    $(this).closest("tr").remove();
  });

  /* One image */
  $(document).on("click", "[data-hub-image-pick]", function (e) {
    e.preventDefault();
    var wrap = $(this).closest("[data-hub-image]");
    var frame = wp.media({ title: "Choose an image", library: { type: "image" }, button: { text: "Use this image" }, multiple: false });
    frame.on("select", function () {
      var a = frame.state().get("selection").first().toJSON();
      var url = (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url);
      wrap.find("input[type=hidden]").val(a.id);
      wrap.find(".hub-image__preview").html('<img src="' + url + '" alt="">');
      wrap.find("[data-hub-image-pick]").text("Change image");
      wrap.find("[data-hub-image-remove]").removeAttr("hidden");
    });
    frame.open();
  });
  $(document).on("click", "[data-hub-image-remove]", function () {
    var wrap = $(this).closest("[data-hub-image]");
    wrap.find("input[type=hidden]").val("");
    wrap.find(".hub-image__preview").empty();
    wrap.find("[data-hub-image-pick]").text("Choose image");
    $(this).attr("hidden", true);
  });

  /* Gallery: add, remove, drag to reorder */
  function galleryItem(name, id, url) {
    return '<li><img src="' + url + '" alt=""><input type="hidden" name="' + name + '" value="' + id + '">' +
      '<button type="button" class="hub-gallery__remove" aria-label="Remove photo">&times;</button></li>';
  }
  $(document).on("click", "[data-hub-gallery-add]", function (e) {
    e.preventDefault();
    var wrap = $(this).closest("[data-hub-gallery]");
    var frame = wp.media({ title: "Add photos", library: { type: "image" }, button: { text: "Add to photos" }, multiple: "add" });
    frame.on("select", function () {
      frame.state().get("selection").each(function (m) {
        var a = m.toJSON();
        if (wrap.find('input[value="' + a.id + '"]').length) return;
        wrap.find(".hub-gallery__list").append(galleryItem(wrap.data("name"), a.id, a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url));
      });
    });
    frame.open();
  });
  $(document).on("click", ".hub-gallery__remove", function () {
    $(this).closest("li").remove();
  });

  /* Offers / classes tick lists: filter by name */
  $(document).on("input", "[data-hub-links-search]", function () {
    var q = $(this).val().toLowerCase();
    $(this).siblings(".hub-links").find("li").each(function () {
      $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
    });
  });

  /* Business picker on offers and classes */
  var timer;
  $(document).on("input", "[data-hub-pick-search]", function () {
    var input = $(this);
    var pick = input.closest("[data-hub-pick]");
    var list = pick.find("[data-hub-pick-results]");
    var q = input.val().trim();
    clearTimeout(timer);
    if (q.length < 2) { list.attr("hidden", true).empty(); return; }
    timer = setTimeout(function () {
      $.getJSON(hubAdmin.ajax, { action: "hub_business_search", q: q, _ajax_nonce: hubAdmin.nonce }, function (res) {
        list.empty();
        var rows = res && res.success ? res.data : [];
        if (!rows.length) list.append('<li class="hub-pick__none">No business with that name</li>');
        rows.forEach(function (b) {
          $('<li><button type="button" class="button-link"></button></li>').find("button")
            .text(b.name + (b.area ? " · " + b.area : "")).attr("data-id", b.id).attr("data-name", b.name).end().appendTo(list);
        });
        list.removeAttr("hidden");
      });
    }, 250);
  });
  $(document).on("click", "[data-hub-pick-results] button", function () {
    var pick = $(this).closest("[data-hub-pick]");
    if (pick.is("[data-hub-multi]")) {   // several businesses: add to the list (once, up to data-max)
      var list = pick.find("[data-hub-picked]");
      var id = String($(this).data("id"));
      var max = parseInt(pick.data("max"), 10) || 0;
      if (!list.find('input[value="' + id + '"]').length && (!max || list.children().length < max)) {
        $('<li><input type="hidden"><span></span> <button type="button" class="button-link hub-picked__remove" aria-label="Remove">&times;</button></li>')
          .find("input").attr("name", pick.data("hub-multi")).val(id).end()
          .find("span").text($(this).data("name")).end()
          .appendTo(list);
      }
      pick.find("[data-hub-pick-results]").attr("hidden", true).empty();
      pick.find("[data-hub-pick-search]").val("");
      return;
    }
    pick.find("[data-hub-pick-id]").val($(this).data("id"));
    pick.find("[data-hub-pick-name]").text($(this).data("name"));
    pick.find("[data-hub-pick-edit]").attr("href", "post.php?post=" + $(this).data("id") + "&action=edit");
    pick.find("[data-hub-pick-current]").removeAttr("hidden");
    pick.find("[data-hub-pick-results]").attr("hidden", true).empty();
    pick.find("[data-hub-pick-search]").val("");
  });
  $(document).on("click", "[data-hub-pick-clear]", function () {
    var pick = $(this).closest("[data-hub-pick]");
    pick.find("[data-hub-pick-id]").val("");
    pick.find("[data-hub-pick-current]").attr("hidden", true);
  });

  $(document).on("click", ".hub-picked__remove", function () {
    $(this).closest("li").remove();
  });

  $(function () {
    $("[data-hub-box]").each(function () { syncClaimed($(this)); });
    $(".hub-gallery__list").sortable({ items: "li", tolerance: "pointer" });
    $("[data-hub-picked]").sortable({ items: "li", axis: "y" });
  });
})(jQuery);
