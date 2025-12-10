let topics = [];

const newTopicForm = document.querySelector('#new-topic-form');

const topicListContainer = document.querySelector('#topic-list-container');

function createTopicArticle(topic) {
  const article = document.createElement('article');
  
  const h3 = document.createElement('h3');
  const a = document.createElement('a');
  a.href = `topic.html?id=${topic.id}`;
  a.textContent = topic.subject;
  h3.appendChild(a);
  
  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${topic.author} on ${topic.date}`;
  
  const actionsDiv = document.createElement('div');
  actionsDiv.className = 'actions';
  
  const editBtn = document.createElement('a');
  editBtn.href = '#';
  editBtn.textContent = 'Edit';
  
  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'delete-btn';
  deleteBtn.setAttribute('data-id', topic.id);
  
  actionsDiv.appendChild(editBtn);
  actionsDiv.appendChild(deleteBtn);
  
  article.appendChild(h3);
  article.appendChild(footer);
  article.appendChild(actionsDiv);
  
  return article;
}

function renderTopics() {
  topicListContainer.innerHTML = '';
  
  topics.forEach(topic => {
    const article = createTopicArticle(topic);
    topicListContainer.appendChild(article);
  });
}

function handleCreateTopic(event) {
  event.preventDefault();
  
  const subject = document.querySelector('#topic-subject').value;
  const message = document.querySelector('#topic-message').value;
  
  const newTopic = {
    id: `topic_${Date.now()}`,
    subject: subject,
    message: message,
    author: 'Student',
    date: new Date().toISOString().split('T')[0]
  };
  
  topics.push(newTopic);
  
  renderTopics();
  
  newTopicForm.reset();
}

function handleTopicListClick(event) {
  if (event.target.classList.contains('delete-btn')) {
    const id = event.target.getAttribute('data-id');
    
    topics = topics.filter(topic => topic.id !== id);
    
    renderTopics();
  }
}

async function loadAndInitialize() {
  const response = await fetch('topics.json');
  const data = await response.json();
  topics = data;
  
  renderTopics();
  
  newTopicForm.addEventListener('submit', handleCreateTopic);
  
  topicListContainer.addEventListener('click', handleTopicListClick);
}

loadAndInitialize();