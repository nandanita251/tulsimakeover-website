// Select elements
const menuToggle = document.getElementById('menu-toggle');
const navMenu = document.getElementById('nav-menu');
const dropdowns = document.querySelectorAll('.dropdown');

// Function to toggle navigation menu
menuToggle.addEventListener('click', () => {
    navMenu.classList.toggle('active');
    menuToggle.classList.toggle('open');
});

// Add functionality for dropdowns
dropdowns.forEach(dropdown => {
    const dropdownLink = dropdown.querySelector('li > a');
    const dropdownContent = dropdown.querySelector('.dropdown-content');

    dropdownLink.addEventListener('click', (e) => {
        e.preventDefault(); // Prevent default link behavior
        dropdownContent.classList.toggle('show');
    });
});

// Close dropdown if clicked outside
document.addEventListener('click', (e) => {
    dropdowns.forEach(dropdown => {
        const dropdownContent = dropdown.querySelector('.dropdown-content');
        if (!dropdown.contains(e.target)) {
            dropdownContent.classList.remove('show');
        }
    });
});

// Close nav menu if clicked outside in mobile view
document.addEventListener('click', (e) => {
    if (!menuToggle.contains(e.target) && !navMenu.contains(e.target) && navMenu.classList.contains('active')) {
        navMenu.classList.remove('active');
        menuToggle.classList.remove('open');
    }
});

// Add smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();

        const targetId = this.getAttribute('href').substring(1);
        const targetElement = document.getElementById(targetId);

        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop,
                behavior: 'smooth'
            });
        }
    });
});
