// --- Global Data Store ---
// This will hold the assignments loaded from the JSON file.
let assignments = [];
// Track if we are currently editing an assignment (null = create mode, ID = edit mode)
let editingId = null; 

// --- Element Selections ---
const assignmentForm = document.querySelector("#assignment-form");
const assignmentTbody = document.querySelector("#assignments-tbody");
// Fix: Added safety check. If assignmentForm is null, this won't crash the script.
const submitBtn = assignmentForm ? assignmentForm.querySelector("button[type='submit']") : null; 

// --- Functions ---
// Fix: Renamed from createAssignmentRow to createAssignmentArticle to match TASK4201 requirements
function createAssignmentArticle(assignment) {
  const tr = document.createElement("tr");
  // Calculate file count for display
  const fileCount = Array.isArray(assignment.files) ? assignment.files.length : 0;
  
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
  if (!assignmentTbody) return; // Safety check
  assignmentTbody.innerHTML = "";
  assignments.forEach(assignment => {
    // Fix: Updated function call to match the renamed function
    const row = createAssignmentArticle(assignment);
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
  // Fix: Added safety check in case "file" input is missing
  const fileInput = formElements["file"];
  const fileLines = fileInput ? fileInput.value
    .split('\n')
    .map(line => line.trim())
    .filter(line => line.length > 0) : [];

  const assignmentData = {
    title: titleValue,
    description: descriptionValue,
    due_date: dueDateValue,
    files: fileLines
  };

  try {
    let url = 'api/index.php?resource=assignments';
    let method = 'POST';

    // Check if we are in Edit Mode
    if (editingId) {
        method = 'PUT';
        assignmentData.id = editingId; // Add ID to body for PUT request
    }

    const response = await fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(assignmentData)
    });

    const result = await response.json();

    if (response.ok) {
      if (editingId) {
        // Update local array
        const index = assignments.findIndex(a => a.id == editingId);
        if (index !== -1) {
             // Merge updates
             assignments[index] = { ...assignments[index], ...assignmentData };
        }
        // Reset Edit Mode
        editingId = null;
        if(submitBtn) submitBtn.textContent = "Add Assignment";
        alert("Assignment updated successfully!");
      } else {
        // Push the assignment returned by the API (with real ID)
        assignments.push(result.data);
      }
      
      renderTable();
      assignmentForm.reset();
    } else {
      alert(result.message || 'Failed to save assignment');
    }

  } catch (err) {
    console.error('Error saving assignment:', err);
    alert('Error saving assignment. See console for details.');
  }
}

async function handleTableClick(event) {
  const target = event.target;
  const id = target.getAttribute("data-id");

  // --- DELETE BUTTON ---
  if (target.classList.contains("delete-btn")) {
    if (!confirm("Are you sure you want to delete this assignment !")) return;

    try {
      const response = await fetch(`api/index.php?resource=assignments&id=${id}`, {
        method: 'DELETE'
      });
      const result = await response.json();
      if (response.ok) {
        assignments = assignments.filter(a => a.id != id);
        renderTable();
        // If we deleted the item being edited, reset the form
        if (editingId == id) {
            editingId = null;
            assignmentForm.reset();
            if(submitBtn) submitBtn.textContent = "Add Assignment";
        }
      } else {
        alert(result.message || 'Failed to delete assignment');
      }
    } catch (err) {
      console.error('Error deleting assignment:', err);
      alert('Error deleting assignment. See console for details.');
    }
  }

  // --- EDIT BUTTON ---
  if (target.classList.contains("edit-btn")) {
    const assignment = assignments.find(a => a.id == id);
    if (assignment) {
        // Set global edit ID
        editingId = id;
        
        // Populate form fields
        const formElements = assignmentForm.elements;
        if(formElements["title"]) formElements["title"].value = assignment.title;
        if(formElements["description"]) formElements["description"].value = assignment.description;
        if(formElements["due-date"]) formElements["due-date"].value = assignment.due_date;
        
        // Join files array back into string for textarea
        if(formElements["file"] && Array.isArray(assignment.files)) {
            formElements["file"].value = assignment.files.join('\n');
        }

        // Change Submit button text
        if(submitBtn) submitBtn.textContent = "Update Assignment";

        // Scroll to form (optional UX improvement)
        assignmentForm.scrollIntoView({ behavior: 'smooth' });
    }
  }

} 

// --- Main Async Function ---
async function loadAndInitialize() {
  try {
    const response = await fetch('api/index.php?resource=assignments');
      
    const result = await response.json();
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    // Handle case where API returns wrapped data or direct array
    assignments = Array.isArray(result) ? result : (result.data || []);
    
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