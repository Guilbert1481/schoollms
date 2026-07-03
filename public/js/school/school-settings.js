(function () {
  const cfg = window.__schoolSettings || {};
  const academicYearsBaseUrl = cfg.academicYearsBaseUrl; 
  const termsBaseUrl = cfg.termsBaseUrl || cfg.academicYearsBaseUrl;

  function byId(id) {
    return document.getElementById(id);
  }

  function showModal(id) {
    const el = byId(id);
    if (!el) return;
    el.classList.remove("hidden");
    el.classList.add("flex");
    el.setAttribute("aria-hidden", "false");
  }

  function hideModal(id) {
    const el = byId(id);
    if (!el) return;
    el.classList.add("hidden");
    el.classList.remove("flex");
    el.setAttribute("aria-hidden", "true");
  }

  // -----------------------
  // Academic Year modals
  // -----------------------
  window.openCreateAYModal = function () {
    showModal("createAYModal");
  };

  window.closeCreateAYModal = function () {
    hideModal("createAYModal");
  };

  window.openEditAYModal = function (id, name, startDate, endDate) {
    // Fill values
    const nameEl = byId("edit_ay_name");
    const startEl = byId("edit_ay_start_date");
    const endEl = byId("edit_ay_end_date");

    if (nameEl) nameEl.value = name || "";
    if (startEl) startEl.value = startDate || "";
    if (endEl) endEl.value = endDate || "";

    // Set correct PUT action: /academic-years/{id}
    const form = byId("editAYForm");
    if (form) {
      if (!academicYearsBaseUrl) {
        console.error("academicYearsBaseUrl is not defined in window.__schoolSettings");
      } else {
        form.action = `${academicYearsBaseUrl}/${id}`;
      }
    }

    showModal("editAYModal");
  };

  window.closeEditAYModal = function () {
    hideModal("editAYModal");
  };

  // -----------------------
  // Term modals
  // -----------------------
  window.openCreateTermModal = function (academicYearId, ayName, ayStart, ayEnd) {
    // Set subtitle (optional)
    const sub = byId("createTermSub");
    if (sub) {
      sub.textContent = `${ayName} (${ayStart} → ${ayEnd})`;
    }

    // Set form action: /academic-years/{academicYearId}/terms
    const form = byId("createTermForm");
    if (form) {
      if (!termsBaseUrl) {
        console.error("termsBaseUrl is not defined in window.__schoolSettings");
      } else {
        form.action = `${termsBaseUrl}/${academicYearId}/terms`;
      }
      form.reset();
    }

    // Clear values + reset enrollment type
    const enrollmentEl = byId("create_enrollment_type");
    const termSel = byId("create_term_term");
    const startEl = byId("create_term_start");
    const endEl = byId("create_term_end");
    const titleEl = byId("create_title");
    if (enrollmentEl) enrollmentEl.value = "";
    if (termSel) termSel.value = "";
    if (startEl) startEl.value = "";
    if (endEl) endEl.value = "";
    if (titleEl) titleEl.value = "";
    if (typeof toggleTitleField === "function") toggleTitleField("create");

    showModal("createTermModal");
  };

  window.closeCreateTermModal = function () {
    hideModal("createTermModal");
  };

  window.openEditTermModal = function (academicYearId, termId, termType, startDate, endDate, enrollmentType, title, educationNodeId) {
    const sub = byId("editTermSub");
    if (sub) {
      sub.textContent = `Academic Year ID: ${academicYearId}`;
    }

    const enrollmentEl = byId("edit_enrollment_type");
    const termSel = byId("edit_term_term");
    const startEl = byId("edit_term_start");
    const endEl = byId("edit_term_end");
    const titleEl = byId("edit_title");
    const levelEl = byId("edit_education_node_id");

    if (enrollmentEl) enrollmentEl.value = enrollmentType || "regular";
    if (termSel) termSel.value = termType || "";
    if (startEl) startEl.value = startDate || "";
    if (endEl) endEl.value = endDate || "";
    if (titleEl) titleEl.value = title || "";
    if (levelEl) levelEl.value = (educationNodeId !== null && educationNodeId !== undefined && educationNodeId !== "") ? String(educationNodeId) : "";
    if (typeof toggleTitleField === "function") toggleTitleField("edit");

    // PUT /academic-years/{academicYearId}/terms/{id}
    const form = byId("editTermForm");
    if (form) {
      if (!termsBaseUrl) {
        console.error("termsBaseUrl is not defined in window.__schoolSettings");
      } else {
        form.action = `${termsBaseUrl}/${academicYearId}/terms/${termId}`;
      }
    }

    showModal("editTermModal");
  };

  window.closeEditTermModal = function () {
    hideModal("editTermModal");
  };
})();


document.addEventListener('DOMContentLoaded', function () {

    function wireFilter(prefix) {
        const enrollmentTypeSelect = document.getElementById(prefix + '_enrollment_type');
        const termSelect = document.getElementById(prefix + '_term_term');
        if (!enrollmentTypeSelect || !termSelect) return;

        const filterTerms = () => {
            const selectedType = (enrollmentTypeSelect.value || '').toLowerCase();
            const options = termSelect.querySelectorAll('option');
            const currentVal = termSelect.value;

            options.forEach(option => {
                if (!option.dataset.type) return;
                option.hidden = option.dataset.type.toLowerCase() !== selectedType;
            });

            // Clear selection only if the current value is now hidden.
            const stillVisible = Array.from(options).some(o => o.value === currentVal && !o.hidden);
            if (!stillVisible) termSelect.value = '';
        };

        enrollmentTypeSelect.addEventListener('change', filterTerms);
        // Run once on load so existing edit-modal values are filtered correctly.
        filterTerms();
    }

    wireFilter('create');
    wireFilter('edit');
});