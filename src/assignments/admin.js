// --- Global Data Store ---
// This will hold the assignments loaded from the JSON file.
let assignments = [];

// --- Element Selections ---
const assignmentForm = document.querySelector("#assignment-form");

const assignmentTbody = document.querySelector("#assignments-tbody");

// --- Functions ---
function createAssignmentRow(assignment) {
  const tr = document.createElement("tr");
  tr.innerHTML = `
    <td>${assignment.title}</td>
    <td>${assignment.due_date}</td>
    <td>
      <button class="edit-btn" data-id="${assignment.id}">Edit</button>
      <button class="delete-btn" data-id="${assignment.id}">Delete</button>
    </td>
  `;
  return tr;
}

function renderTable() {
  assignmentTbody.innerHTML = "";
  assignments.forEach(assignment => {
    const row = createAssignmentRow(assignment);
    assignmentTbody.appendChild(row);
  });
}

async function handleAddAssignment(event) {
  event.preventDefault();
  const formElements = assignmentForm.elements;
  const titleValue = formElements["title"] ? formElements["title"].value : "";
  const descriptionValue = formElements["description"] ? formElements["description"].value : "";
  const dueDateValue = formElements["due-date"] ? formElements["due-date"].value : ""; 

  // Split files into array if multiple lines
  const fileLines = formElements["file"].value
    .split('\n')
    .map(line => line.trim())
    .filter(line => line.length > 0);

  const newAssignment = {
    title: titleValue,
    description: descriptionValue,
    due_date: dueDateValue,
    files: fileLines
  };

  try {
    const response = await fetch('api/index.php?resource=assignments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(newAssignment)
    });

    const result = await response.json();

    if (response.ok) {
      // Push the assignment returned by the API (with real ID)
      assignments.push(result.data);
      renderTable();
      assignmentForm.reset();
    } else {
      alert(result.message || 'Failed to add assignment');
    }

  } catch (err) {
    console.error('Error adding assignment:', err);
    alert('Error adding assignment. See console for details.');
  }
}

async function handleTableClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const deleteAssignmentId = event.target.getAttribute("data-id");
    if (!confirm("Are you sure you want to delete this assignment !")) return;

    try {
      const response = await fetch(`api/index.php?resource=assignments&id=${deleteAssignmentId}`, {
        method: 'DELETE'
      });
      const result = await response.json();
      if (response.ok) {
        assignments = assignments.filter(a => a.id != deleteAssignmentId);
        renderTable();
      } else {
        alert(result.message || 'Failed to delete assignment');
      }
    } catch (err) {
      console.error('Error deleting assignment:', err);
      alert('Error deleting assignment. See console for details.');
    }
  }
} // <-- properly closed

// --- Main Async Function ---
async function loadAndInitialize() {
  try {
    const response = await fetch('api/index.php?resource=assignments');
      
    const result = await response.json();
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    assignments = result;
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

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
