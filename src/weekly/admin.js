/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.
*/

// --- Global Data Store ---
// This will hold the weekly data loaded from the JSON file.
let weeks = [];
// Track which week we are currently editing (null if adding new)
let editingId = null;

// --- Element Selections ---
const weekForm = document.querySelector('#week-form');
// Fixed: Selecting the tbody inside the weeks-table
const weeksTableBody = document.querySelector('#weeks-table tbody');
const submitBtn = document.querySelector('#add-week');

// --- Functions ---

/**
 * Creates the HTML row for a specific week.
 * @param {Object} week - The week object {id, title, description, ...}
 * @returns {HTMLElement} - The <tr> element ready to be appended.
 */
function createWeekRow(week) {
  // Create the row element
  const tr = document.createElement('tr');

  // 1. Create Title Cell
  const titleTd = document.createElement('td');
  titleTd.textContent = week.title;
  tr.appendChild(titleTd);

  // 2. Create Description Cell
  const descTd = document.createElement('td');
  descTd.textContent = week.description;
  tr.appendChild(descTd);

  // 3. Create Actions Cell
  const actionsTd = document.createElement('td');

  // Create Edit Button
  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.classList.add('btn-edit'); // Matching class from HTML comments
  editBtn.dataset.id = week.id;
  actionsTd.appendChild(editBtn);

  // Add spacing or a text node between buttons if desired
  actionsTd.appendChild(document.createTextNode(' '));

  // Create Delete Button
  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('btn-delete'); // Matching class from HTML comments
  deleteBtn.dataset.id = week.id;
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

  return tr;
}

/**
 * Clears the table and re-renders all rows based on the global `weeks` array.
 */
function renderTable() {
  // 1. Clear the current table body
  weeksTableBody.innerHTML = '';

  // 2. Loop through the data and create rows
  weeks.forEach(week => {
    const row = createWeekRow(week);
    weeksTableBody.appendChild(row);
  });
}

/**
 * Handles the form submission to add or update a week.
 */
function handleAddWeek(event) {
  // 1. Prevent default form submission (page reload)
  event.preventDefault();

  // 2. Get values from form inputs
  const titleInput = document.querySelector('#week-title');
  const dateInput = document.querySelector('#week-start-date');
  const descInput = document.querySelector('#week-description');
  const linksInput = document.querySelector('#week-links');

  // 3. Process links (split by newline)
  const linksArray = linksInput.value.split('\n').filter(link => link.trim() !== '');

  if (editingId) {
    // UPDATE MODE
    const index = weeks.findIndex(w => String(w.id) === String(editingId));
    if (index !== -1) {
      weeks[index] = {
        ...weeks[index],
        title: titleInput.value,
        date: dateInput.value,
        description: descInput.value,
        links: linksArray
      };
    }
    // Reset state
    editingId = null;
    submitBtn.textContent = "Add Week";
  } else {
    // ADD MODE
    // 4. Create new week object
    const newWeek = {
      id: `week_${Date.now()}`,
      title: titleInput.value,
      date: dateInput.value,
      description: descInput.value,
      links: linksArray
    };
    // 5. Add to global array
    weeks.push(newWeek);
  }

  // 6. Refresh the table
  renderTable();

  // 7. Reset the form
  weekForm.reset();
}

/**
 * Handles clicks within the table body (Event Delegation).
 * Specifically looks for Delete and Edit buttons.
 */
function handleTableClick(event) {
  const target = event.target;
  const id = target.dataset.id;

  // 1. Check if the clicked element is a delete button
  if (target.classList.contains('btn-delete')) {
    // Confirm deletion
    if(confirm("Are you sure you want to delete this week?")) {
        // Filter out the week with the matching ID
        weeks = weeks.filter(week => String(week.id) !== id);
        renderTable();
    }
  }

  // 2. Check if the clicked element is an edit button
  if (target.classList.contains('btn-edit')) {
    const weekToEdit = weeks.find(week => String(week.id) === id);
    if (weekToEdit) {
      // Set Global editing state
      editingId = id;
      
      // Populate Form
      document.querySelector('#week-title').value = weekToEdit.title;
      document.querySelector('#week-start-date').value = weekToEdit.date || '';
      document.querySelector('#week-description').value = weekToEdit.description;
      document.querySelector('#week-links').value = weekToEdit.links ? weekToEdit.links.join('\n') : '';
      
      // Change Button Text
      submitBtn.textContent = "Update Week";
      
      // Scroll to form for better UX
      weekForm.scrollIntoView({ behavior: 'smooth' });
    }
  }
}

/**
 * Main initialization function.
 * Fetches data and sets up event listeners.
 */
async function loadAndInitialize() {
  try {
    // 1. Fetch data from api
    const response = await fetch('api/index.php?resource=weeks');
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    // 2. Parse JSON and store in global variable
    weeks = await response.json();

    // 3. Populate table
    renderTable();

  } catch (error) {
    console.error("Failed to load weekly data:", error);
    // Optional: Show an error message on the page
    weeksTableBody.innerHTML = '<tr><td colspan="3">Error loading data. Showing local state.</td></tr>';
  } finally {
    // 4. Add Event Listeners (Moved here to ensure they attach regardless of fetch success)
    weekForm.addEventListener('submit', handleAddWeek);
    weeksTableBody.addEventListener('click', handleTableClick);
  }
}

// --- Initial Page Load ---
loadAndInitialize();