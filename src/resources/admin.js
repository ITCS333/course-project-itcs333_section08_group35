let resources = [];

const resourceForm = document.querySelector('#resource-form');
const resourcesTableBody = document.querySelector('#resources-tbody');

function createResourceRow(resource) {
    const tr = document.createElement('tr');

    const titleTd = document.createElement('td');
    titleTd.textContent = resource.title;
    tr.appendChild(titleTd);

    const descTd = document.createElement('td');
    descTd.textContent = resource.description;
    tr.appendChild(descTd);

    const actionsTd = document.createElement('td');

    const editBtn = document.createElement('button');
    editBtn.textContent = 'Modify';
    editBtn.classList.add('edit-btn');
    editBtn.setAttribute('data-id', resource.id);
    actionsTd.appendChild(editBtn);

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Remove';
    deleteBtn.classList.add('delete-btn');
    deleteBtn.setAttribute('data-id', resource.id);
    actionsTd.appendChild(deleteBtn);

    tr.appendChild(actionsTd);
    return tr;
}

function renderTable() {
    resourcesTableBody.innerHTML = '';
    resources.forEach(resource => {
        const tr = createResourceRow(resource);
        resourcesTableBody.appendChild(tr);
    });
}

async function handleAddResource(event) {
    event.preventDefault();

    const title = document.querySelector('#resource-title').value.trim();
    const description = document.querySelector('#resource-description').value.trim();
    const link = document.querySelector('#resource-link').value.trim();

    if (!title || !link) return;

    try {
        const response = await fetch('api/index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ title, description, link })
        });

        const json = await response.json();

        if (!json.success) {
            alert('Unable to create resource: ' + (json.message || 'Operation failed'));
            return;
        }

        await loadAndInitialize();
        resourceForm.reset();
        alert('Material created successfully!');
    } catch (error) {
        console.error('Error adding resource:', error);
        alert('Could not add material. Try again later.');
    }
}

async function handleTableClick(event) {
    const target = event.target;

    if (target.classList.contains('delete-btn')) {
        const id = target.getAttribute('data-id');

        if (!confirm('Remove this resource permanently?')) {
            return;
        }

        try {
            const response = await fetch(`api/index.php?id=${id}`, {
                method: 'DELETE'
            });

            const json = await response.json();

            if (!json.success) {
                alert('Cannot remove resource: ' + (json.message || 'Operation failed'));
                return;
            }

            await loadAndInitialize();
            alert('Material removed successfully!');
        } catch (error) {
            console.error('Error deleting resource:', error);
            alert('Unable to remove material. Try again.');
        }
    }
}

async function loadAndInitialize() {
    try {
        const response = await fetch('api/index.php');
        const json = await response.json();

        if (!json.success) {
            throw new Error(json.message || 'Could not fetch resources');
        }

        resources = json.data || [];
        renderTable();

        if (!resourceForm.dataset.listenerAdded) {
            resourceForm.addEventListener('submit', handleAddResource);
            resourceForm.dataset.listenerAdded = 'true';
        }
        if (!resourcesTableBody.dataset.listenerAdded) {
            resourcesTableBody.addEventListener('click', handleTableClick);
            resourcesTableBody.dataset.listenerAdded = 'true';
        }
    } catch (error) {
        console.error('Error loading resources:', error);
        alert('Cannot load materials. Refresh the page.');
    }
}

loadAndInitialize();
