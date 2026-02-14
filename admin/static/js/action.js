const sidebar = document.getElementById('sidebar');
const hamburger = document.getElementById('hamburger');
const closeBtn = document.getElementById('close-btn');
const mainContent = document.getElementById('main-content');

// Open sidebar
hamburger.addEventListener('click', () => {
    sidebar.classList.add('active');
});

// Close sidebar
closeBtn.addEventListener('click', () => {
    sidebar.classList.remove('active');
});
