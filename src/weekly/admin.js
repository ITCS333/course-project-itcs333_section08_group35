/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.
*/

// --- Global Data Store ---
// This will hold the weekly data loaded from the JSON file.
let weeks = [];

// --- Element Selections ---
const weekForm = document.querySelector('#week-form');
const weeksTableBody = document.querySelector('#weeks-tbody');

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
  // (Optional) Add edit logic listener here or in global handler
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
 * Handles the form submission to add a new week.
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

  // 4. Create new week object
  // Note: We generate a pseudo-unique ID using Date.now()
  const newWeek = {
    id: `week_${Date.now()}`,
    title: titleInput.value,
    date: dateInput.value,
    description: descInput.value,
    links: linksArray
  };

  // 5. Add to global array
  weeks.push(newWeek);

  // 6. Refresh the table
  renderTable();

  // 7. Reset the form
  weekForm.reset();
}

/**
 * Handles clicks within the table body (Event Delegation).
 * Specifically looks for the Delete button.
 */
function handleTableClick(event) {
  // 1. Check if the clicked element is a delete button
  if (event.target.classList.contains('btn-delete')) {
    // 2. Get the ID from the data attribute
    const idToDelete = event.target.dataset.id;
    
    // 3. Confirm deletion (optional, but good UX)
    if(confirm("Are you sure you want to delete this week?")) {
        // 4. Filter out the week with the matching ID
        weeks = weeks.filter(week => String(week.id) !== idToDelete);
    
        // 5. Refresh the table
        renderTable();
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

    // 4. Add Event Listeners
    weekForm.addEventListener('submit', handleAddWeek);
    weeksTableBody.addEventListener('click', handleTableClick);

  } catch (error) {
    console.error("Failed to load weekly data:", error);
    // Optional: Show an error message on the page
    weeksTableBody.innerHTML = '<tr><td colspan="3">Error loading data. Please try again later.</td></tr>';
  }
}

// --- Initial Page Load ---
loadAndInitialize();