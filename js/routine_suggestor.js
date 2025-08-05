// Courses from PHP must be injected before this script runs
let allCourses = window.allCourses || [];
let selectedCourses = [];

function renderDropdown(filtered) {
    const dropdown = document.getElementById('dropdown');
    dropdown.innerHTML = '';
    if (filtered.length === 0) {
        dropdown.style.display = 'none';
        return;
    }
    filtered.forEach(course => {
        const item = document.createElement('div');
        item.className = 'shadcn-dropdown-item';
        item.textContent = `${course.course_code} - ${course.course_title}`;
        item.onclick = () => {
            if (!selectedCourses.includes(course.course_code)) {
                selectedCourses.push(course.course_code);
                renderChips();
            }
            document.getElementById('courseSearch').value = '';
            dropdown.style.display = 'none';
        };
        dropdown.appendChild(item);
    });
    dropdown.style.display = 'block';
}

function renderChips() {
    const chips = document.getElementById('selectedChips');
    chips.innerHTML = '';
    // Remove previous hidden inputs
    document.querySelectorAll('input[name="selected_courses[]"]').forEach(e => e.remove());
    selectedCourses.forEach(code => {
        const course = allCourses.find(c => c.course_code === code);
        const chip = document.createElement('div');
        chip.className = 'shadcn-chip';
        chip.textContent = course ? `${course.course_code} - ${course.course_title}` : code;
        const remove = document.createElement('span');
        remove.className = 'shadcn-chip-remove';
        remove.innerHTML = '&times;';
        remove.onclick = () => {
            selectedCourses = selectedCourses.filter(c => c !== code);
            renderChips();
        };
        chip.appendChild(remove);
        chips.appendChild(chip);

        // Add hidden input for each selected course
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'selected_courses[]';
        hidden.value = code;
        document.getElementById('routineForm').appendChild(hidden);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('courseSearch').addEventListener('input', function() {
        const val = this.value.toLowerCase();
        const filtered = allCourses.filter(c =>
            c.course_code.toLowerCase().includes(val) ||
            c.course_title.toLowerCase().includes(val)
        ).filter(c => !selectedCourses.includes(c.course_code));
        renderDropdown(filtered);
    });
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#dropdown') && !e.target.closest('#courseSearch')) {
            document.getElementById('dropdown').style.display = 'none';
        }
    });
    renderChips();
});

// Export for possible future use
window.routineSuggestor = { renderDropdown, renderChips, selectedCourses }; 