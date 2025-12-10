let currentTopicId = null;
let currentReplies = [];

const topicSubject = document.querySelector('#topic-subject');
const opMessage = document.querySelector('#op-message');
const opFooter = document.querySelector('#op-footer');
const replyListContainer = document.querySelector('#reply-list-container');
const replyForm = document.querySelector('#reply-form');
const newReplyText = document.querySelector('#new-reply');

function getTopicIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

function renderOriginalPost(topic) {
  topicSubject.textContent = topic.subject;
  opMessage.textContent = topic.message;
  opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;
}

function createReplyArticle(reply) {
  const article = document.createElement('article');
  
  const p = document.createElement('p');
  p.textContent = reply.text;
  
  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${reply.author} on ${reply.date}`;
  
  const actionsDiv = document.createElement('div');
  actionsDiv.className = 'actions';
  
  const editBtn = document.createElement('a');
  editBtn.href = '#';
  editBtn.textContent = 'Edit';
  
  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'delete-reply-btn';
  deleteBtn.setAttribute('data-id', reply.id);
  
  actionsDiv.appendChild(editBtn);
  actionsDiv.appendChild(deleteBtn);
  
  article.appendChild(p);
  article.appendChild(footer);
  article.appendChild(actionsDiv);
  
  return article;
}

function renderReplies() {
  replyListContainer.innerHTML = '';
  
  currentReplies.forEach(reply => {
    const article = createReplyArticle(reply);
    replyListContainer.appendChild(article);
  });
}

function handleAddReply(event) {
  event.preventDefault();
  
  const text = newReplyText.value;
  
  if (!text) {
    return;
  }
  
  const newReply = {
    id: `reply_${Date.now()}`,
    author: 'Student',
    date: new Date().toISOString().split('T')[0],
    text: text
  };
  
  currentReplies.push(newReply);
  
  renderReplies();
  
  newReplyText.value = '';
}

function handleReplyListClick(event) {
  if (event.target.classList.contains('delete-reply-btn')) {
    const id = event.target.getAttribute('data-id');
    
    currentReplies = currentReplies.filter(reply => reply.id !== id);
    
    renderReplies();
  }
}

async function initializePage() {
  currentTopicId = getTopicIdFromURL();
  
  if (!currentTopicId) {
    topicSubject.textContent = 'Topic not found.';
    return;
  }
  
  const [topicsResponse, repliesResponse] = await Promise.all([
    fetch('topics.json'),
    fetch('replies.json')
  ]);
  
  const topics = await topicsResponse.json();
  const repliesData = await repliesResponse.json();
  
  const topic = topics.find(t => t.id === currentTopicId);
  
  currentReplies = repliesData[currentTopicId] || [];
  
  if (topic) {
    renderOriginalPost(topic);
    renderReplies();
    
    replyForm.addEventListener('submit', handleAddReply);
    replyListContainer.addEventListener('click', handleReplyListClick);
  } else {
    topicSubject.textContent = 'Topic not found.';
  }
}

initializePage();