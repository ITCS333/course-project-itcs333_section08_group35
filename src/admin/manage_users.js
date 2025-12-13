/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by manipulating the 'students' array
     and re-rendering the table.
*/

// --- Global Data Store ---
// This array will be populated with data fetched from 'students.json'.
let students = [];

// --- Element Selections ---
const studentTableBody = document.querySelector('#student-table tbody');
const addStudentForm = document.querySelector('#add-student-form');
const changePasswordForm = document.querySelector('#password-form');
const searchInput = document.querySelector('#search-input');
const tableHeaders = document.querySelectorAll('#student-table thead th');


// --- Functions ---

/**
 * TODO: Implement the createStudentRow function.
 * This function should take a student object {name, id, email} and return a <tr> element.
 * The <tr> should contain:
 * 1. A <td> for the student's name.
 * 2. A <td> for the student's ID.
 * 3. A <td> for the student's email.
 * 4. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and a data-id attribute set to the student's ID.
 * - A "Delete" button with class "delete-btn" and a data-id attribute set to the student's ID.
 */
function createStudentRow(student) {
  // ... your implementation here ...
  const tr = document.createElement('tr');
  
  const nameTd = document.createElement('td');
  nameTd.textContent = student.name;
  
  const idTd = document.createElement('td');
  idTd.textContent = student.id;
  
  const emailTd = document.createElement('td');
  emailTd.textContent = student.email;
  
  const actionsTd = document.createElement('td');
  
  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.className = 'edit-btn';
  editBtn.setAttribute('data-id', student.id);
  
  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'delete-btn';
  deleteBtn.setAttribute('data-id', student.id);
  
  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);
  
  tr.appendChild(nameTd);
  tr.appendChild(idTd);
  tr.appendChild(emailTd);
  tr.appendChild(actionsTd);
  
  return tr;
}

/**
 * TODO: Implement the renderTable function.
 * This function takes an array of student objects.
 * It should:
 * 1. Clear the current content of the `studentTableBody`.
 * 2. Loop through the provided array of students.
 * 3. For each student, call `createStudentRow` and append the returned <tr> to `studentTableBody`.
 */
function renderTable(studentArray) {
  // ... your implementation here ...
  studentTableBody.innerHTML = '';
  
  studentArray.forEach(student => {
    const row = createStudentRow(student);
    studentTableBody.appendChild(row);
  });

}

/**
 * TODO: Implement the handleChangePassword function.
 * This function will be called when the "Update Password" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "current-password", "new-password", and "confirm-password" inputs.
 * 3. Perform validation:
 * - If "new-password" and "confirm-password" do not match, show an alert: "Passwords do not match."
 * - If "new-password" is less than 8 characters, show an alert: "Password must be at least 8 characters."
 * 4. If validation passes, show an alert: "Password updated successfully!"
 * 5. Clear all three password input fields.
 */
function handleChangePassword(event) {
  // ... your implementation here ...
  event.preventDefault();
  
  const currentPassword = document.getElementById('current-password').value;
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  
  if (newPassword !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }
  if (newPassword.length < 8) {
    alert('Password must be at least 8 characters.');
    return;
  }
  

  alert('Password updated successfully!');
  
  document.getElementById('current-password').value = '';
  document.getElementById('new-password').value = '';
  document.getElementById('confirm-password').value = '';
}

/**
 * TODO: Implement the handleAddStudent function.
 * This function will be called when the "Add Student" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "student-name", "student-id", and "student-email".
 * 3. Perform validation:
 * - If any of the three fields are empty, show an alert: "Please fill out all required fields."
 * - (Optional) Check if a student with the same ID already exists in the 'students' array.
 * 4. If validation passes:
 * - Create a new student object: { name, id, email }.
 * - Add the new student object to the global 'students' array.
 * - Call `renderTable(students)` to update the view.
 * 5. Clear the "student-name", "student-id", "student-email", and "default-password" input fields.
 */
function handleAddStudent(event) {
  // ... your implementation here ...
  event.preventDefault();
  
  const name = document.getElementById('student-name').value.trim();
  const id = document.getElementById('student-id').value.trim();
  const email = document.getElementById('student-email').value.trim();
  
  if (!name || !id || !email) {
    alert('Please fill out all required fields.');
    return;
  }
  
  const exists = students.some(student => student.id === id);
  if (exists) {
    alert('A student with this ID already exists.');
    return;
  }
  const newStudent = {
    name: name,
    id: id,
    email: email
  };
  
  students.push(newStudent);
  
  renderTable(students);
  
  document.getElementById('student-name').value = '';
  document.getElementById('student-id').value = '';
  document.getElementById('student-email').value = '';
  document.getElementById('default-password').value = '';
  
  alert('added successfully');
}

/**
 * TODO: Implement the handleTableClick function.
 * This function will be an event listener on the `studentTableBody` (event delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it is a "delete-btn":
 * - Get the `data-id` attribute from the button.
 * - Update the global 'students' array by filtering out the student with the matching ID.
 * - Call `renderTable(students)` to update the view.
 * 3. (Optional) Check for "edit-btn" and implement edit logic.
 */
function handleTableClick(event) {
  
const target = event.target;
  

  if (target.classList.contains('delete-btn')) {
    const studentId = target.getAttribute('data-id');
    
    
    if (confirm('are you sure you want to delete?')) {
      
      students = students.filter(student => student.id !== studentId);
      
      
      renderTable(students);
      
      alert('Student deleted successfully!');
    }
  }
  
  
  if (target.classList.contains('edit-btn')) {
    const studentId = target.getAttribute('data-id');
    const student = students.find(s => s.id === studentId);
    
    if (student) {
      document.getElementById('student-name').value = student.name;
      document.getElementById('student-id').value = student.id;
      document.getElementById('student-email').value = student.email;
      
      students = students.filter(s => s.id !== studentId);
      renderTable(students);
      
      alert('Edit the details then click (Add Student) to save changes');
    }
  }
}

/**
 * TODO: Implement the handleSearch function.
 * This function will be called on the "input" event of the `searchInput`.
 * It should:
 * 1. Get the search term from `searchInput.value` and convert it to lowercase.
 * 2. If the search term is empty, call `renderTable(students)` to show all students.
 * 3. If the search term is not empty:
 * - Filter the global 'students' array to find students whose name (lowercase)
 * includes the search term.
 * - Call `renderTable` with the *filtered array*.
 */
function handleSearch(event) {
  // ... your implementation here ...
  const searchTerm = searchInput.value.toLowerCase().trim();
  
  if (!searchTerm) {
    renderTable(students);
    return;
  }
  
  const filteredStudents = students.filter(student => {
    return (student.name.toLowerCase().includes(searchTerm) ||
           student.id.toLowerCase().includes(searchTerm) ||
           student.email.toLowerCase().includes(searchTerm));
  });
  
  renderTable(filteredStudents);
}

/**
 * TODO: Implement the handleSort function.
 * This function will be called when any `th` in the `thead` is clicked.
 * It should:
 * 1. Identify which column was clicked (e.g., `event.currentTarget.cellIndex`).
 * 2. Determine the property to sort by ('name', 'id', 'email') based on the index.
 * 3. Determine the sort direction. Use a data-attribute (e.g., `data-sort-dir="asc"`) on the `th`
 * to track the current direction. Toggle between "asc" and "desc".
 * 4. Sort the global 'students' array *in place* using `array.sort()`.
 * - For 'name' and 'email', use `localeCompare` for string comparison.
 * - For 'id', compare the values as numbers.
 * 5. Respect the sort direction (ascending or descending).
 * 6. After sorting, call `renderTable(students)` to update the view.
 */
function handleSort(event) {
  // ... your implementation here ...
  const th = event.currentTarget;
  const columnIndex = th.cellIndex;
  
  let sortProperty;
  if (columnIndex === 0)
    sortProperty = 'name';
  else if (columnIndex === 1)
    sortProperty = 'id';
  else if (columnIndex === 2)
    sortProperty = 'email';
  else 
    return;
  
  const currentDirection = th.getAttribute('data-sort-dir') || 'asc';
  const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
  
  th.setAttribute('data-sort-dir', newDirection);
  
  students.sort((a, b) => {
    let aValue = a[sortProperty];
    let bValue = b[sortProperty];
    
    if (sortProperty === 'name' || sortProperty === 'email') {
      if (newDirection === 'asc') {
        return aValue.localeCompare(bValue);
      } else {
        return bValue.localeCompare(aValue);
      }
    }
    
    if (sortProperty === 'id') {
      if (newDirection === 'asc') {
        return aValue.localeCompare(bValue);
      } else {
        return bValue.localeCompare(aValue);
      }
    }
  });
  
  renderTable(students);
}

/**
 * TODO: Implement the loadStudentsAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use the `fetch()` API to get data from 'students.json'.
 * 2. Check if the response is 'ok'. If not, log an error.
 * 3. Parse the JSON response (e.g., `await response.json()`).
 * 4. Assign the resulting array to the global 'students' variable.
 * 5. Call `renderTable(students)` to populate the table for the first time.
 * 6. After data is loaded, set up all the event listeners:
 * - "submit" on `changePasswordForm` -> `handleChangePassword`
 * - "submit" on `addStudentForm` -> `handleAddStudent`
 * - "click" on `studentTableBody` -> `handleTableClick`
 * - "input" on `searchInput` -> `handleSearch`
 * - "click" on each header in `tableHeaders` -> `handleSort`
 */
async function loadStudentsAndInitialize() {
  // ... your implementation here ...
  
    const response = await fetch('api/students.json');
  try{  
    if (!response.ok) {
      throw new Error('Failed to load students data');
    }
    
    const data = await response.json();
    students = data;
    
    renderTable(students);
    
    if (changePasswordForm) {
      changePasswordForm.addEventListener('submit', handleChangePassword);
    }
    
    if (addStudentForm) {
      addStudentForm.addEventListener('submit', handleAddStudent);
    }
    
    if (studentTableBody) {
      studentTableBody.addEventListener('click', handleTableClick);
    }
    
    if (searchInput) {
      searchInput.addEventListener('input', handleSearch);
    }
    
    if (tableHeaders) {
      tableHeaders.forEach(th => {
        if (th.cellIndex < 3) {
          th.addEventListener('click', handleSort);
        }
      });
    }
    
    console.log('Admin portal initialized successfully!');
    
  }
  catch (error) {
    console.error('Error loading students:', error);
    alert('Failed to load student data. Please check the console for details.');
  }

}
// --- Initial Page Load ---
// Call the main async function to start the application.
loadStudentsAndInitialize();
