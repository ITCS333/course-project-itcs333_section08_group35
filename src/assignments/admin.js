// --- Global Data Store ---
// This will hold the assignments loaded from the JSON file.
let assignments = [];

// --- Element Selections ---
const assignmentForm = document.querySelector("#assignment-form");

const assignmentTbody = document.querySelector("#assignment-tbody");

// --- Functions ---
function createAssignmentRow(assignment) {
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

function renderTable() {
  assignmentTbody.innerHTML = "";
  assignments.forEach(assignment => {
    const row = createAssignmentRow(assignment);
    assignmentTbody.appendChild(row);
  });
}

function handleAddAssignment(event) {
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

function handleTableClick(event) {
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
// --- Main Async Function ---
  async function loadAndInitialize() {
    try {
      const response = await fetch('api/assignments.json');
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


// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
