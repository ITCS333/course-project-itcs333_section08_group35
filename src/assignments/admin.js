/*
  Requirement: Make the "Manage Assignments" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="assignments-tbody"` to the <tbody> element
     so you can select it.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the assignments loaded from the JSON file.
let assignments = [];

// --- Element Selections ---
// TODO: Select the assignment form ('#assignment-form').
const assignmentForm = document.querySelector("#assignment-form");

// TODO: Select the assignments table body ('#assignments-tbody').
const assignmentTbody = document.querySelector("#assignment-tbody");

// --- Functions ---

/**
 * TODO: Implement the createAssignmentRow function.
 * It takes one assignment object {id, title, dueDate}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `dueDate`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createAssignmentRow(assignment) {
  // ... your implementation here ...
  const tr = document.createElement("tr");
  tr.innerHTML = `
    <td>${assignment.title}</td>
    <td>${assignment.dueDate}</td>
    <td>
      <button class="edit-btn" data-id="${assignment.id}">Edit</button>
      <button class="delete-btn" data-id="${assignment.id}">Delete</button>
    </td>
  `;
  return tr;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `assignmentsTableBody`.
 * 2. Loop through the global `assignments` array.
 * 3. For each assignment, call `createAssignmentRow()`, and
 * append the resulting <tr> to `assignmentsTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
  assignmentTbody.innerHTML = "";
  assignments.forEach(assignment => {
    const row = createAssignmentRow(assignment);
    assignmentTbody.appendChild(row);
  });
}

/**
 * TODO: Implement the handleAddAssignment function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, due date, and files inputs.
 * 3. Create a new assignment object with a unique ID (e.g., `id: \`asg_${Date.now()}\``).
 * 4. Add this new assignment object to the global `assignments` array (in-memory only).
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
function handleAddAssignment(event) {
  // ... your implementation here ...
  event.preventDefault();
  const formElements = assignmentForm.elements;
  const titleValue = formElements["title"] ? formElements["title"].value : "";
  const descriptionValue = formElements["description"] ? formElements["description"].value : "";
  const dueDateValue = formElements["due-date"] ? formElements["due-date"].value : ""; 
  const fileValue = formElements["file"] && formElements["file"].files.length > 0 ? formElements["file"].files[0].name : "";

  const newAssignment = {
    id: `asg_${Date.now()}`,
    title: titleValue,
    description: descriptionValue,
    dueDate: dueDateValue,
    file: fileValue
  };

  assignments.push(newAssignment);
  renderTable();
  assignmentForm.reset();
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `assignmentsTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `assignments` array by filtering out the assignment
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {
  // ... your implementation here ...
  if (event.target.classList.contains("delete-btn")) {
    const deleteAssignmentId = event.target.getAttribute("data-id");
    if (!confirm("Are you sure you want to delete this assignment !")) {
      return;
    }
    assignments = assignments.filter(assignment => assignment.id !== deleteAssignmentId);
    renderTable();
  }
  //handle edit button functionality
  /** 
  if (event.target.classList.contains("edit-btn")) {
    const editAssignmentId = event.target.getAttribute("data-id");
    console.log("Edit button clicked for ID:", editAssignmentId);
    if (!confirm("Are you sure you want to edit this assignment !")) {
      return;
    }
  }*/
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response and store the result in the global `assignments` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `assignmentForm` (calls `handleAddAssignment`).
 * 5. Add the 'click' event listener to `assignmentsTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
  async function loadAndInitialize() {
    try {
      const response = await fetch('assignments.json');
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      assignments = await response.json();
      renderTable();
      if (assignmentForm){
        assignmentForm.addEventListener('submit', handleAddAssignment);
      }
      
      if (assignmentTbody) {
      assignmentTbody.addEventListener('click', handleTableClick);
    }

    } catch (error) {
      console.error('Error loading assignments:', error);
    }
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
