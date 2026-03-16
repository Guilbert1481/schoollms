document.addEventListener('DOMContentLoaded', () => {

  const form = document.getElementById('question-metadataForm');
  if (!form) return;

  const subject = document.getElementById('tpl-subject');
  const topic   = document.getElementById('tpl-topic');
  const lesson  = document.getElementById('tpl-lesson');
  const comp    = document.getElementById('tpl-competency');
  const btn     = form.querySelector('button[type="submit"]');
  const csrf    = document.querySelector('meta[name="csrf-token"]')?.content;

  function setOptions(select, items, placeholder = 'Select') {
    if (!select) return;
    select.innerHTML = `<option value="">${placeholder}</option>`;
    if (!items || !items.length) {
      select.disabled = true;
      return;
    }
    items.forEach(i => {
      const opt = document.createElement('option');
      opt.value = i.id;
      opt.textContent = i.name;
      select.appendChild(opt);
    });
    select.disabled = false;
  }

  async function fetchJson(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw res;
    return res.json();
  }

  subject?.addEventListener('change', async () => {
    setOptions(topic, []);
    setOptions(lesson, []);
    setOptions(comp, []);
    if (!subject.value) return;
    setOptions(topic, await fetchJson(`/api/subjects/${subject.value}/topics`));
  });

  topic?.addEventListener('change', async () => {
    setOptions(lesson, []);
    setOptions(comp, []);
    if (!topic.value) return;
    setOptions(lesson, await fetchJson(`/api/topics/${topic.value}/lessons`));
  });

  lesson?.addEventListener('change', async () => {
    setOptions(comp, []);
    if (!lesson.value) return;
    setOptions(comp, await fetchJson(`/api/lessons/${lesson.value}/competencies`));
  });

  /* =========================
     SUBMIT METADATA
     ========================= */
  /* =========================
   SUBMIT METADATA
   ========================= */
form.addEventListener('submit', async (e) => {
    e.preventDefault();

    btn.disabled = true;
    btn.textContent = 'Saving...';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json' // Tells Laravel to send JSON back
            },
            body: new FormData(form)
        });

        const data = await response.json();

        if (response.ok && data.redirect) {
            // Success: Move to the builder
            window.location.href = data.redirect;
        } else {
            // Fail: Show validation errors
            alert(data.message || 'Check your required fields');
        }

    } catch (err) {
        console.error(err);
        alert('Error saving metadata');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Proceed';
    }
});

});
